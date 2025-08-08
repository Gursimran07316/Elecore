<?php

namespace Drupal\Tests\json_to_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Kernel tests for JSON Content Builder logic.
 *
 * @group json_content_builder
 */
class JsonContentBuilderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'options',
    'json_content_builder',
  ];

  /**
   * Set up the environment.
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Test valid JSON parsing.
   */
  public function testValidJsonParsing() {
    $json = '{"title": "string", "body": "text"}';
    $decoded = json_decode($json, TRUE);

    $this->assertIsArray($decoded);
    $this->assertArrayHasKey('title', $decoded);
    $this->assertEquals('string', $decoded['title']);
  }

  /**
   * Test invalid JSON detection.
   */
  public function testInvalidJsonFails() {
    $json = '{"title": "string", "body": }'; // malformed
    $decoded = json_decode($json, TRUE);

    $this->assertNull($decoded);
    $this->assertNotEquals(JSON_ERROR_NONE, json_last_error());
  }

  /**
   * Test content type creation.
   */
  public function testContentTypeCreation() {
    $type = 'json_test_article';
    $this->assertNull(NodeType::load($type));

    $node_type = NodeType::create([
      'type' => $type,
      'name' => 'JSON Test Article',
    ]);
    $node_type->save();

    $this->assertNotNull(NodeType::load($type));
  }

  /**
   * Test field creation for a content type.
   */
  public function testFieldCreation() {
    $type = 'json_test_bundle';
    NodeType::create(['type' => $type, 'name' => 'JSON Bundle'])->save();

    $field_name = 'field_summary';

    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $type,
      'label' => 'Summary',
    ])->save();

    $this->assertNotNull(FieldStorageConfig::loadByName('node', $field_name));
    $this->assertNotNull(FieldConfig::loadByName('node', $type, $field_name));
  }

  /**
   * Test entity reference field creation.
   */
  public function testEntityReferenceFieldCreation() {
    $type = 'json_ref_bundle';
    NodeType::create(['type' => $type, 'name' => 'Reference Bundle'])->save();

    $field_name = 'field_ref_user';

    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'user',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $type,
      'label' => 'Author Ref',
      'settings' => [
        'handler' => 'default',
      ],
    ])->save();

    $this->assertNotNull(FieldStorageConfig::loadByName('node', $field_name));
    $this->assertNotNull(FieldConfig::loadByName('node', $type, $field_name));
  }

}