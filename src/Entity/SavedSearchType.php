<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Provides an entity type for configuring how searches can be saved.
 *
 * @ConfigEntityType(
 *   id = "search_api_saved_search_type",
 *   label = @Translation("Saved search type"),
 *   label_collection = @Translation("Saved search type"),
 *   label_singular = @Translation("saved search type"),
 *   label_plural = @Translation("saved search types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count saved search type",
 *     plural = "@count saved search types",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\search_api_saved_searches\Entity\SavedSearchTypeStorage",
 *     "list_builder" = "Drupal\search_api_saved_searches\SavedSearchTypeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\search_api_saved_searches\Form\SavedSearchTypeForm",
 *       "edit" = "Drupal\search_api_saved_searches\Form\SavedSearchTypeForm",
 *       "delete" = "Drupal\search_api_saved_searches\Form\SavedSearchTypeDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer search_api_saved_searches",
 *   config_prefix = "type",
 *   bundle_of = "search_api_saved_search",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id" = "id",
 *     "label" = "label",
 *     "default",
 *     "options",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/search-api-saved-searches/type/{search_api_saved_search_type}/edit",
 *     "add-form" = "/admin/config/search/search-api-saved-searches/add-type",
 *     "edit-form" = "/admin/config/search/search-api-saved-searches/type/{search_api_saved_search_type}/edit",
 *     "delete-form" = "/admin/config/search/search-api-saved-searches/type/{search_api_saved_search_type}/delete",
 *     "collection" = "/admin/config/search/search-api-saved-searches",
 *   }
 * )
 */
class SavedSearchType extends ConfigEntityBundleBase implements SavedSearchTypeInterface {

  /**
   * The type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The type label.
   *
   * @var string
   */
  protected $label;

  /**
   * Whether this is the default type.
   *
   * @todo Is this still needed? Pretty sure it's not.
   *
   * @var bool
   */
  protected $default = FALSE;

  /**
   * The settings for this type.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update && !$this->isSyncing()) {
      try {
        EntityFormDisplay::create([
          'status' => TRUE,
          'id' => 'search_api_saved_search.default.create',
          'targetEntityType' => 'search_api_saved_search',
          'bundle' => 'default',
          'mode' => 'create',
          'content' => [
            'label' => [
              'type' => 'string_textfield',
              'weight' => 0,
              'region' => 'content',
              'settings' => [
                'size' => 60,
                'placeholder' => '',
              ],
              'third_party_settings' => [],
            ],
            'mail' => [
              'type' => 'email_default',
              'weight' => 2,
              'region' => 'content',
              'settings' => [
                'size' => 60,
                'placeholder' => 'user@example.com',
              ],
              'third_party_settings' => [],
            ],
            'notify_interval' => [
              'type' => 'number',
              'weight' => 1,
              'region' => 'content',
              'settings' => [
                'placeholder' => '',
              ],
              'third_party_settings' => [],
            ],
          ],
          'hidden' => [
            'created' => TRUE,
            'langcode' => TRUE,
            'last_executed' => TRUE,
            'last_queued' => TRUE,
            'uid' => TRUE,
          ],
        ])->save();
      }
      catch (EntityStorageException $e) {
        $vars = ['%label' => $this->label()];
        watchdog_exception('search_api_saved_searches', $e, '%type while trying to configure the "Create" form display for the new saved search type %label: @message in %function (line %line of %file).', $vars);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($key, $default = NULL) {
    // @todo Some of the options (mail texts) need to be translatable. Is this
    //   the place to implement that (partly)?
    $keys = explode('.', $key);
    $value = NestedArray::getValue($this->options, $keys, $exists);
    return $exists ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveQuery(QueryHelperInterface $query_helper = NULL) {
    if (!$query_helper) {
      $query_helper = \Drupal::service('search_api.query_helper');
    }
    foreach ($query_helper->getAllResults() as $result) {
      // @todo There will later be some configuration for picking which queries
      //   should be matched by a type (based on display/search ID, I guess).
      return $result->getQuery();
    }
    return NULL;
  }

}
