<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\search_api\Plugin\ConfigurablePluginInterface;

/**
 * Provides an interface for notification plugins.
 *
 * @see \Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginManager
 * @see \Drupal\search_api_saved_searches\Notification\NotificationPluginBase
 * @see plugin_api
 */
interface NotificationInterface extends ConfigurablePluginInterface {

}
