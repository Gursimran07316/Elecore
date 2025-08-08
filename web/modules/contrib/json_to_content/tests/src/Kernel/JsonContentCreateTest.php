<?php

namespace Drupal\Tests\json_to_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\User;

/**
 * Kernel tests for JSON content creation.
 *
 * @group json_content_builder
 */
class JsonContentCreateTest extends KernelTestBase {

  /**
   * Required modules for the test.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'json_content_builder',
  ];

  /**
   * A test user to assign as node author.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $testUser;

  /**
   * Sets up test content type, fields, and user.
   */
  protected function setUp(): void {
    parent::setUp();
  
    // Install necessary entity schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
  
    // Create a test user.
    $this->testUser = User::create([
      'name' => 'test_user',
      'mail' => 'test@example.com',
      'status' => 1,
    ]);
    $this->testUser->save();
  
    // Create a content type.
    NodeType::create([
      'type' => 'json_test',
      'name' => 'JSON Test',
    ])->save();
  
    // Add a custom field.
    FieldStorageConfig::create([
      'field_name' => 'field_description',
      'entity_type' => 'node',
      'type' => 'text_long',
    ])->save();
  
    FieldConfig::create([
      'field_name' => 'field_description',
      'entity_type' => 'node',
      'bundle' => 'json_test',
      'label' => 'Description',
    ])->save();
  }

  /**
   * Test creating multiple nodes from valid JSON.
   */
  public function testNodeCreationFromJsonArray() {
    $json = '[{"title": "Node One", "field_description": "Description for node 1"},
              {"title": "Node Two", "field_description": "Description for node 2"}]';

    $items = json_decode($json, TRUE);
    $this->assertIsArray($items);

    foreach ($items as $item) {
      $node = Node::create([
        'type' => 'json_test',
        'uid' => $this->testUser->id(), // Assign the test user as author
      ]);
      foreach ($item as $field_name => $value) {
        if ($field_name === 'title' && $node->hasField('title')) {
          $node->set('title', $value);
        } elseif ($node->hasField($field_name)) {
          $node->set($field_name, $value);
        }
      }
      $node->save();
    }

    $nids = \Drupal::entityQuery('node')
            ->accessCheck(FALSE)
            ->condition('type', 'json_test')
            ->execute();
    $this->assertCount(2, $nids);

    $nodes = Node::loadMultiple($nids);
    $titles = array_map(fn($n) => $n->label(), $nodes);
    $this->assertContains('Node One', $titles);
    $this->assertContains('Node Two', $titles);
  }

  /**
   * Test decoding invalid JSON returns null.
   */
  public function testInvalidJsonReturnsNull() {
    $invalid = '[{"title": "Bad JSON",]';
    $decoded = json_decode($invalid, TRUE);
    $this->assertNull($decoded);
  }

  /**
   * Test decoding empty JSON array.
   */
  public function testEmptyJsonReturnsEmptyArray() {
    $empty = '[]';
    $decoded = json_decode($empty, TRUE);
    $this->assertIsArray($decoded);
    $this->assertCount(0, $decoded);
  }

}