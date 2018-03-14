<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\search_api_saved_searches\SavedSearchesException;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Drupal\user\UserInterface;

/**
 * Provides an entity type for saved searches.
 *
 * @ContentEntityType(
 *   id = "search_api_saved_search",
 *   label = @Translation("Saved search"),
 *   label_collection = @Translation("Saved searches"),
 *   label_singular = @Translation("saved search"),
 *   label_plural = @Translation("saved searches"),
 *   label_count = @PluralTranslation(
 *     singular = "@count saved search",
 *     plural = "@count saved searches"
 *   ),
 *   bundle_label = @Translation("Search type"),
 *   handlers = {
 *     "list_builder" = "Drupal\search_api_saved_searches\SavedSearchListBuilder",
 *     "access" = "Drupal\search_api_saved_searches\Entity\SavedSearchAccessControlHandler",
 *     "views_data" = "Drupal\search_api_saved_searches\SavedSearchViewsData",
 *     "form" = {
 *       "default" = "Drupal\search_api_saved_searches\Form\SavedSearchForm",
 *       "create" = "Drupal\search_api_saved_searches\Form\SavedSearchCreateForm",
 *       "edit" = "Drupal\search_api_saved_searches\Form\SavedSearchForm",
 *       "delete" = "Drupal\search_api_saved_searches\Form\SavedSearchDeleteConfirmForm",
 *     },
 *   },
 *   admin_permission = "administer search_api_saved_searches",
 *   base_table = "search_api_saved_search",
 *   data_table = "search_api_saved_search_field_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   bundle_entity_type = "search_api_saved_search_type",
 *   field_ui_base_route = "entity.search_api_saved_search_type.edit_form",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/saved-search/{search_api_saved_search}",
 *     "edit-form" = "/user/{user}/saved-searches/{search_api_saved_search}/edit",
 *     "delete-form" = "/user/{user}/saved-searches/{search_api_saved_search}/delete",
 *   },
 * )
 */
class SavedSearch extends ContentEntityBase implements SavedSearchInterface {

  /**
   * Static cache for property getters that take some computation.
   *
   * @var array
   */
  protected $cachedProperties = [];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    // Make the form display of the language configurable.
    $fields['langcode']->setDisplayConfigurable('form', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('The label for the saved search.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDescription(t('The user who owns the saved search.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\search_api_saved_searches\Entity\SavedSearch::getCurrentUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the saved search was created.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['last_executed'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Last executed'))
      ->setDescription(t('The time that the saved search was last checked for new results.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['next_execution'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next execution'))
      ->setDescription(t('The next time this saved search should be executed.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['notify_interval'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Notification interval'))
      ->setDescription(t('The interval in which you want to receive notifications of new results for this saved search.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        3600 => t('Hourly'),
        86400 => t('Daily'),
        604800 => t('Weekly'),
        -1 => t('Never'),
      ])
      ->setDefaultValue(-1)
      ->setDisplayOptions('view', [
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // @todo Is there a better data type? Should we provide one?
    $fields['query'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Search query'))
      ->setDescription(t('The saved search query.'))
      ->setSetting('case_sensitive', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ]);

    $fields['options'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Options'))
      ->setDescription(t('Further options for this saved search.'))
      ->setSetting('case_sensitive', TRUE)
      ->setDisplayOptions('view', [
        'region' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields = parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);

    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    $type = \Drupal::entityTypeManager()
      ->getStorage('search_api_saved_search_type')
      ->load($bundle);
    $fields += $type->getNotificationPluginFieldDefinitions();

    return $fields;
  }

  /**
   * Returns the default value for the "uid" base field definition.
   *
   * @return array
   *   An array with the default value.
   *
   * @see \Drupal\search_api_saved_searches\Entity\SavedSearch::baseFieldDefinitions()
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Set a default label for new saved searches. (Can't use a "default value
    // callback" for the label field because the query only gets set afterwards,
    // based on the order of field definitions.)
    if (empty($this->get('label')->value)) {
      $label = NULL;
      $query = $this->getQuery();
      if ($query && is_string($query->getOriginalKeys())) {
        $label = $query->getOriginalKeys();
      }
      $this->set('label', $label ?: t('Saved search'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $notify_interval = $this->get('notify_interval')->value;
    if ($notify_interval >= 0) {
      $last_executed = $this->get('last_executed')->value;
      $this->set('next_execution', $last_executed + $notify_interval);
    }
    else {
      $this->set('next_execution', NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // For newly inserted saved searches with "Determine by result ID" detection
    // mode, prime the list of known results.
    if (!$update) {
      try {
        $type = $this->getType();
      }
      catch (SavedSearchesException $e) {
        return;
      }
      $query = $this->getQuery();
      if (!$query) {
        return;
      }
      $index_id = $query->getIndex()->id();
      $date_field = $type->getOption("date_field.$index_id");
      if (!$date_field) {
        // Prime the "search_api_saved_searches_old_results" table with entries
        // for all current results.
        \Drupal::getContainer()
          ->get('search_api_saved_searches.new_results_check')
          ->getNewResults($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Remove any "known results" we have for the deleted searches.
    // NB: $entities is not documented to be keyed by entity ID, but since Core
    // relies on it (see \Drupal\comment\Entity\Comment::postDelete()), we
    // should be able to do the same.
    \Drupal::database()
      ->delete('search_api_saved_searches_old_results')
      ->condition('search_id', array_keys($entities), 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel);

    if ($rel !== 'canonical') {
      $params['user'] = $this->getOwnerId();
    }

    return $params;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if (!isset($this->cachedProperties[__FUNCTION__])) {
      $type = \Drupal::entityTypeManager()
        ->getStorage('search_api_saved_search_type')
        ->load($this->bundle());
      $this->cachedProperties[__FUNCTION__] = $type ?: FALSE;
    }

    if (!$this->cachedProperties[__FUNCTION__]) {
      throw new SavedSearchesException("Saved search #{$this->id()} does not have a valid type set");
    }
    return $this->cachedProperties[__FUNCTION__];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    if (!isset($this->cachedProperties[__FUNCTION__])) {
      $this->cachedProperties[__FUNCTION__] = FALSE;
      $query = $this->get('query')->value;
      if ($query) {
        $this->cachedProperties[__FUNCTION__] = unserialize($query);
      }
    }

    return $this->cachedProperties[__FUNCTION__] ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    if (!isset($this->cachedProperties[__FUNCTION__])) {
      $this->cachedProperties[__FUNCTION__] = FALSE;
      $options = $this->get('options')->value;
      if ($options) {
        $this->cachedProperties[__FUNCTION__] = unserialize($options);
      }
    }

    return $this->cachedProperties[__FUNCTION__] ?: NULL;
  }

}
