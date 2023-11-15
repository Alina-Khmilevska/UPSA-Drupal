<?php

namespace Drupal\upsa_api;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class UpsaApiHelper.
 */
class UpsaApiHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UpsaApiHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets blocks of a specific type and endpoint.
   *
   * @param string $block_type
   *   The block type.
   * @param string $block_endpoint
   *   The block endpoint.
   *
   * @return array
   *   An array of block entities.
   */
  public function getBlocks($block_type, $block_endpoint) {
    $query = $this->entityTypeManager->getStorage('block_content')->getQuery()
      ->condition('type', $block_type)
      ->condition('field_endpoint', $block_endpoint)
      ->accessCheck(FALSE) // Note: Only use accessCheck(FALSE) if you are sure it is safe to bypass access checks.
      ->execute();

    return $this->entityTypeManager->getStorage('block_content')->loadMultiple($query);
  }

  /**
   * Gets the file URI for an image from a media reference field on a block.
   *
   * @param \Drupal\block_content\BlockContentInterface $block
   *   The block entity containing the media reference field.
   * @param string $field_name
   *   The machine name of the media reference field.
   *
   * @return string|null
   *   The file URI or NULL if not found.
   */
  public function getImageUriFromBlock($block, $field_name) {
    $media = $block->get($field_name)->entity;
    if ($media) {
      // Load the image file from the media entity.
      $image = $media->get('field_media_image')->entity;
      if ($image) {
        $file_uri = $image->getFileUri();
        // Use the file_url_generator service to convert the URI to an absolute URL.
        return $file_uri ? \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri) : NULL;
      }
    }

    return NULL;
  }

  /**
   * Gets the URL and title from a link field on a block.
   *
   * @param \Drupal\block_content\BlockContentInterface $block
   *   The block entity containing the link field.
   * @param string $field_name
   *   The machine name of the link field.
   *
   * @return array
   *   An associative array with 'url' and 'title' keys.
   */
  public function getLinkFromBlock($block, $field_name) {
    $result = [
      'url' => NULL,
      'title' => NULL,
    ];

    $link_item = $block->get($field_name)->first();
    if ($link_item) {
      $result['url'] = $link_item->getUrl()->toString();
      $result['title'] = $block->get($field_name)->title;
    }

    return $result;
  }

}
