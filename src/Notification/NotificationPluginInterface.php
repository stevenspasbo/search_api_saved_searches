<?php

namespace Drupal\search_api_saved_searches\Notification;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\ConfigurablePluginInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api_saved_searches\SavedSearchInterface;

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

  /**
   * Retrieves the field definitions to add to saved searches for this plugin.
   *
   * This, together with alterSavedSearchForm(), allows the plugin to store
   *
   * The field definitions will be added to all bundles for which this
   * notification plugin is active.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of bundle field definitions, keyed by field name.
   */
  public function getFieldDefinitions();

  /**
   * Notifies the search's owner of new results.
   *
   * @param \Drupal\search_api_saved_searches\SavedSearchInterface $search
   *   The saved search for which to report new results.
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   The new results.
   */
  public function notify(SavedSearchInterface $search, ResultSetInterface $results);

}
