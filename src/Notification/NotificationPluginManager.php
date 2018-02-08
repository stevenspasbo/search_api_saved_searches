<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages notification plugins.
 *
 * @see \Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification
 * @see \Drupal\search_api_saved_searches\Notification\NotificationInterface
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginBase
 * @see plugin_api
 */
class NotificationPluginManager extends DefaultPluginManager {

  /**
   * Constructs a NotificationPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_saved_searches/notification', $namespaces, $module_handler, 'Drupal\search_api_saved_searches\Notification\NotificationInterface', 'Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification');

    $this->setCacheBackend($cache_backend, 'search_api_saved_searches_notification');
    $this->alterInfo('search_api_saved_searches_notification_info');
  }

}
