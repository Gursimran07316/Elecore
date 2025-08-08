<?php

namespace Drupal\json_to_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

class JsonContentCreateForm extends FormBase {

  public function getFormId() {
    return 'json_content_create_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $content_types = NodeType::loadMultiple();
    $options = [];
    foreach ($content_types as $type => $info) {
      $options[$type] = $info->label();
    }

    $form['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Content Type'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['json_input'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content JSON'),
      '#description' => $this->t('Paste a JSON array of objects.'),
    ];

    $form['json_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Or upload JSON file'),
      '#description' => $this->t('Upload a JSON file. Overrides text input.'),
      '#upload_location' => 'public://json_uploads/',
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['preview'] = [
      '#type' => 'submit',
      '#value' => $this->t('Preview'),
      '#submit' => ['::previewForm'],
      '#limit_validation_errors' => [['content_type'], ['json_input'], ['json_file']],
    ];

    if ($form_state->get('preview_data')) {
      $form['preview_table'] = $form_state->get('preview_data');
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm & Create'),
      ];
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $json = trim($form_state->getValue('json_input'));
    $fid = $form_state->getValue('json_file');
  
    // Prefer uploaded file
    if (!empty($fid[0])) {
      $file = File::load($fid[0]);
      if ($file) {
        $json = file_get_contents($file->getFileUri());
      }
    }
  
    if (empty($json)) {
      $form_state->setErrorByName('json_input', $this->t('Please paste JSON or upload a JSON file.'));
      return;
    }
  
    $items = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($items)) {
      $form_state->setErrorByName('json_input', $this->t('Invalid JSON format: @msg', ['@msg' => json_last_error_msg()]));
      return;
    }
  
    if (count($items) === 0) {
      $form_state->setErrorByName('json_input', $this->t('JSON must contain at least one object.'));
      return;
    }
  
    foreach ($items as $index => $item) {
      if (!is_array($item) || count($item) === 0) {
        $form_state->setErrorByName('json_input', $this->t('Item @index in JSON is an empty object.', ['@index' => $index + 1]));
        return;
      }
    }
  }

  public function previewForm(array &$form, FormStateInterface $form_state) {
    $json = trim($form_state->getValue('json_input'));
    $fid = $form_state->getValue('json_file');

    if (!empty($fid[0])) {
      $file = File::load($fid[0]);
      if ($file) {
        $json = file_get_contents($file->getFileUri());
      }
    }

    if (empty($json)) {
      $this->messenger()->addError($this->t('Please paste JSON or upload a file.'));
      return;
    }

    $items = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($items)) {
      $this->messenger()->addError($this->t('Invalid JSON: @msg', ['@msg' => json_last_error_msg()]));
      return;
    }

    $rows = [];
    foreach ($items as $i => $item) {
      $title = $item['title'] ?? '[No title]';
      $fields = [];
      foreach ($item as $k => $v) {
        if ($k === 'title') continue;
        $fields[] = "$k: " . (is_array($v) ? json_encode($v) : $v);
      }
      $rows[] = [$i + 1, $title, implode(', ', $fields)];
    }

    $form_state->set('preview_data', [
      '#type' => 'table',
      '#header' => ['#', 'Title', 'Fields'],
      '#rows' => $rows,
      '#caption' => $this->t('Preview of content to be created'),
    ]);
    $form_state->setRebuild(TRUE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_type = $form_state->getValue('content_type');
    $json = trim($form_state->getValue('json_input'));
    $fid = $form_state->getValue('json_file');

    if (!empty($fid[0])) {
      $file = File::load($fid[0]);
      if ($file) {
        $json = file_get_contents($file->getFileUri());
      }
    }

    if (empty($json)) {
      $this->messenger()->addError($this->t('Please paste JSON or upload a file.'));
      return;
    }

    $items = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($items)) {
      $this->messenger()->addError($this->t('Invalid JSON format: @msg', ['@msg' => json_last_error_msg()]));
      return;
    }

    $created_count = 0;
    foreach ($items as $data) {
      if (!is_array($data)) continue;

      $node = Node::create(['type' => $content_type]);
      foreach ($data as $field_name => $value) {
        if ($field_name === 'title' && $node->hasField('title')) {
          $node->set('title', $value);
        } elseif ($node->hasField($field_name)) {
          $node->set($field_name, $value);
        }
      }
      $node->save();
      $created_count++;
    }

    $this->messenger()->addMessage($this->t('Created @count nodes.', ['@count' => $created_count]));
  }
}
