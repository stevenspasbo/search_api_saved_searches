<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\search_api\Plugin\ConfigurablePluginBase;
use Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification;

/**
 * Defines a base class for notification plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. These definition arrays may be altered through
 * hook_search_api_saved_searches_notification_info_alter(). The definition
 * includes the following keys:
 * - id: The unique, system-wide identifier of the notification plugin.
 * - label: The human-readable name of the notification plugin, translated.
 * - description: A human-readable description for the notification plugin,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiSavedSearchesNotification(
 *   id = "my_notification",
 *   label = @Translation("My notification"),
 *   description = @Translation("This is my notification plugin."),
 * )
 * @endcode
 *
 * @see \Drupal\search_api_saved_searches\Annotation\SearchApiSavedSearchesNotification
 * @see \Drupal\search_api_saved_searches\Notification\DataTypePluginManager
 * @see \Drupal\search_api_saved_searches\Notification\NotificationInterface
 * @see plugin_api
 */
abstract class NotificationPluginBase extends ConfigurablePluginBase implements NotificationInterface {

}
