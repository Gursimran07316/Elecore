<?php

namespace Drupal\Tests\json_to_content\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Kernel test for JSON export logic.
 *
 * @group json_content_builder
 */
class JsonContentExportTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'json_content_builder',
  ];

  protected $testUser;

  protected function setUp(): void {
    parent::setUp();

    // Install necessary schemas.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    // Create a test user.
    $this->testUser = User::create([
      'name' => 'export_user',
      'mail' => 'export@example.com',
      'status' => 1,
    ]);
    $this->testUser->save();

    // Create a content type.
    NodeType::create([
      'type' => 'export_test',
      'name' => 'Export Test',
    ])->save();

    // Add custom field.
    FieldStorageConfig::create([
      'field_name' => 'field_summary',
      'entity_type' => 'node',
      'type' => 'text_long',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_summary',
      'entity_type' => 'node',
      'bundle' => 'export_test',
      'label' => 'Summary',
    ])->save();
  }

  public function testExportedJsonStructureMatchesNodes() {
    // Create nodes.
    $node1 = Node::create([
      'type' => 'export_test',
      'title' => 'First Node',
      'field_summary' => 'First summary',
      'uid' => $this->testUser->id(),
      'status' => 1,
    ]);
    $node1->save();

    $node2 = Node::create([
      'type' => 'export_test',
      'title' => 'Second Node',
      'field_summary' => 'Second summary',
      'uid' => $this->testUser->id(),
      'status' => 1,
    ]);
    $node2->save();

    // Simulate export logic.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'export_test', 'status' => 1]);

    $this->assertCount(2, $nodes);

    $export = [];
    foreach ($nodes as $node) {
      $item = [];
      foreach ($node->getFields() as $field_name => $field) {
        if (strpos($field_name, 'field_') === 0 || $field_name === 'title') {
          $item[$field_name] = $field->getValue();
          if (count($item[$field_name]) === 1 && isset($item[$field_name][0]['value'])) {
            $item[$field_name] = $item[$field_name][0]['value'];
          }
        }
      }
      $export[] = $item;
    }

    // Verify structure and values.
    $this->assertEquals('First Node', $export[0]['title']);
    $this->assertEquals('First summary', $export[0]['field_summary']);
    $this->assertEquals('Second Node', $export[1]['title']);
    $this->assertEquals('Second summary', $export[1]['field_summary']);
  }

}