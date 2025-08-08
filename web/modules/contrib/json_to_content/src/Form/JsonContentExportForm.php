<?php

namespace Drupal\json_to_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;

class JsonContentExportForm extends FormBase {

  public function getFormId() {
    return 'json_content_export_form';
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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export as JSON'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $content_type = $form_state->getValue('content_type');

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => $content_type, 'status' => 1]);

    $data = [];
    foreach ($nodes as $node) {
      $item = [];
      foreach ($node->getFields() as $field_name => $field) {
        // Skip internal fields
        if (strpos($field_name, 'field_') === 0 || $field_name === 'title') {
          $item[$field_name] = $field->getValue();
          // Convert field with single value to plain value
          if (count($item[$field_name]) === 1 && isset($item[$field_name][0]['value'])) {
            $item[$field_name] = $item[$field_name][0]['value'];
          }
        }
      }
      $data[] = $item;
    }

    $filename = $content_type . '_export_' . date('Ymd_His') . '.json';
    $json = json_encode($data, JSON_PRETTY_PRINT);

    $response = new Response($json);
    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
    $response->send();
    exit();
  }
}