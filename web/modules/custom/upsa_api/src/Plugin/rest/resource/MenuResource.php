<?php

namespace Drupal\upsa_api\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a resource to get showcase blocks.
 *
 * @RestResource(
 *   id = "menu_resource",
 *   label = @Translation("Front menu resource"),
 *   uri_paths = {
 *     "canonical" = "/upsa-api/menu"
 *   }
 * )
 */
class MenuResource extends ResourceBase {

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
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a new MenuResource object.
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
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger, $current_user, $entity_type_manager);
    $this->fileUrlGenerator = $file_url_generator;
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
      $container->get('entity_type.manager'),
      $container->get('file_url_generator')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get() {
    // Load the menu tree based on the menu name.
    $menu_name = 'front-menu';
    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $tree = $menu_tree->load($menu_name, $parameters);

    // Transform the tree into a simple array.
    $manipulators = array(
      // Only show links that are accessible for the user.
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      // Use the default sorting of menu links.
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);

    $menu = [];
    foreach ($tree as $element) {
      if ($element->link->isEnabled()) {
        $link = $element->link;
        $menu_link_content = $link->getPluginDefinition()['metadata']['entity_id'] ?? null;
        if ($menu_link_content) {
          $entity = $this->entityTypeManager->getStorage('menu_link_content')->load($menu_link_content);
          if ($entity && $entity->hasField('field_icon')) {
            // If the field exists and has a value, retrieve the file URL.
            $icon_file_id = $entity->get('field_icon')->target_id;
            $icon_url = '';
            // Check if there's a file associated with the field_icon.
            if ($icon_file_id) {
              $file_storage = \Drupal::entityTypeManager()->getStorage('file');
              $icon_file = $file_storage->load($icon_file_id);
              if ($icon_file) {
                $icon_url = $this->fileUrlGenerator->generateAbsoluteString($icon_file->getFileUri());
              }
            }
          }
        }

        // Get the URL object from the menu link.
        $url_object = $element->link->getUrlObject();
        // Check if the URL is external or internal.
        if (!$url_object->isExternal()) {
          $url = $url_object->toString();
        } else {
          $url = $url_object->getUri();
        }
        // Get the menu link title.
        $title = $element->link->getTitle();
        // Get the menu link description.
        $description = $element->link->getDescription();
        // Get the menu link id.
        $menu_id = $element->link->getPluginId();

        $menu[] = [
          'title' => $title,
          'url' => $url,
          'description' => $description,
          'menu_id' => $menu_id,
          'icon' => $icon_url,
        ];
      }
    }

    return new ResourceResponse($menu);
  }


}
