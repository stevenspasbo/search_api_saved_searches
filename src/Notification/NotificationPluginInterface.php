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
interface NotificationPluginInterface extends ConfigurablePluginInterface {

  /**
   * Retrieves the saved search type.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   *   The saved search type to which this plugin is attached.
   */
  public function getSavedSearchType();

  /**
   * Sets the saved search type.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchTypeInterface $savedSearchType
   *   The new saved search type for this plugin.
   *
   * @return $this
   */
  public function setSavedSearchType($savedSearchType);

}
