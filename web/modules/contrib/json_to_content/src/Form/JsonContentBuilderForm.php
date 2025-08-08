<?php

namespace Drupal\json_to_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

class JsonContentBuilderForm extends FormBase {

  public function getFormId() {
    return 'json_content_builder_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['content_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content Type Machine Name'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content Type Description'),
      '#required' => FALSE,
    ];

    $form['json_input'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JSON Structure'),
      '#description' => $this->t('Paste a JSON object like {"title": "string", "body": "text"}'),
    ];

    $form['json_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Or upload JSON file'),
      '#description' => $this->t('Upload a JSON file containing field definitions. This overrides the text input.'),
      '#upload_location' => 'public://json_uploads/',
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate Content Type'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $json_input = trim($form_state->getValue('json_input'));
    $fid = $form_state->getValue('json_file');
  
    // Load file content if uploaded
    if (!empty($fid[0])) {
      $file = File::load($fid[0]);
      if ($file) {
        $json_input = file_get_contents($file->getFileUri());
      }
    }
  
    if (empty($json_input)) {
      $form_state->setErrorByName('json_input', $this->t('Please either paste JSON or upload a JSON file.'));
      return;
    }
  
    $fields = json_decode($json_input, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($fields)) {
      $form_state->setErrorByName('json_input', $this->t('Invalid JSON format: @msg', ['@msg' => json_last_error_msg()]));
      return;
    }
  
    if (empty($fields)) {
      $form_state->setErrorByName('json_input', $this->t('JSON must define at least one field.'));
      return;
    }
  
    foreach ($fields as $field_name => $definition) {
      if (!is_array($definition) && empty($definition)) {
        $form_state->setErrorByName('json_input', $this->t('Field "@name" must not be empty.', ['@name' => $field_name]));
        return;
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('content_type');
    $json = trim($form_state->getValue('json_input'));
    $fid = $form_state->getValue('json_file');

    if (!empty($fid[0])) {
      $file = File::load($fid[0]);
      if ($file) {
        $json = file_get_contents($file->getFileUri());
      }
    }

    $fields = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($fields)) {
      $this->messenger()->addError($this->t('Invalid JSON format: @msg', ['@msg' => json_last_error_msg()]));
      return;
    }

    $description = $form_state->getValue('description');

    if (!NodeType::load($type)) {
      $node_type = NodeType::create([
        'type' => $type,
        'name' => ucfirst($type),
        'description' => $description ?? '',
      ]);
      $node_type->save();
      $this->messenger()->addStatus($this->t('Content type %type created.', ['%type' => $type]));
    }

    foreach ($fields as $field_name => $field_info) {
      $machine_name = 'field_' . strtolower(preg_replace('/[^a-z0-9_]+/', '_', $field_name));

      $field_type = is_array($field_info) ? $field_info['type'] ?? 'string' : $field_info;
      $field_label = is_array($field_info) ? $field_info['label'] ?? ucfirst($field_name) : ucfirst($field_name);

      $field_storage_settings = [];
      $field_settings = [];

      if ($field_type === 'entity_reference') {
        $target_type = $field_info['target_type'] ?? NULL;

        if (!$target_type) {
          $this->messenger()->addError($this->t('Missing target_type for field %field.', ['%field' => $field_name]));
          continue;
        }

        $field_storage_settings = [
          'target_type' => $target_type,
        ];

        $field_settings = [
          'handler' => 'default',
        ];
      }

      if (!FieldStorageConfig::loadByName('node', $machine_name)) {
        FieldStorageConfig::create([
          'field_name' => $machine_name,
          'entity_type' => 'node',
          'type' => $field_type,
          'settings' => $field_storage_settings,
        ])->save();
      }

      if (!FieldConfig::loadByName('node', $type, $machine_name)) {
        FieldConfig::create([
          'field_name' => $machine_name,
          'entity_type' => 'node',
          'bundle' => $type,
          'label' => $field_label,
          'settings' => $field_settings,
        ])->save();
      }

      $this->messenger()->addMessage($this->t('Field %field (%type) added.', ['%field' => $field_label, '%type' => $field_type]));
    }
  }
}
