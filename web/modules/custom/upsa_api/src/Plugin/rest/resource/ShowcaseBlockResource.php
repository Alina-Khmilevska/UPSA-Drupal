<?php

namespace Drupal\upsa_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a resource to get showcase blocks.
 *
 * @RestResource(
 *   id = "showcase_block_resource",
 *   label = @Translation("Showcase block resource"),
 *   uri_paths = {
 *     "canonical" = "/upsa-api/block/showcase/{block_endpoint}"
 *   }
 * )
 */
class ShowcaseBlockResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ShowcaseBlockResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('upsa_api'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param string $block_endpoint
   *   The block endpoint identifier to filter blocks.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($block_endpoint) {
    // Check if the block_endpoint parameter is provided.
    if (!$block_endpoint) {
      return new ResourceResponse(['message' => 'No block endpoint provided'], 400);
    }
    $upsa_api_helper = \Drupal::service('upsa_api.helper');
    // Use the helper to get the blocks.
    $blocks = $upsa_api_helper->getBlocks('showcase', $block_endpoint);
    // Serialize the block entities to get their field values.
    $data = [];
    foreach ($blocks as $block) {
       // Get the image file URI.
      $image_url = $upsa_api_helper->getImageUriFromBlock($block, 'field_image');

      // Use the helper to get the link details.
      $link_details = $upsa_api_helper->getLinkFromBlock($block, 'field_link');


      // Assuming 'field_endpoint' is a text field. Adjust if it's a different field type.
      $data = [
        'id' => $block->id(),
        'title' => $block->get('field_title')->value,
        'body' => $block->get('body')->value,
        'image' => $image_url,
        'link_url' => $link_details['url'],
        'link_title' => $link_details['title'],
        'endpoint_value' => $block->get('field_endpoint')->value,
        // Add any other fields you need.
      ];
    }

    return new ResourceResponse($data);
  }


}
