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
 *     "canonical" = "/user/{user}/saved-searches/{search_api_saved_search}/edit",
 *     "edit-form" = "/user/{user}/saved-searches/{search_api_saved_search}/edit",
 *     "delete-form" = "/user/{user}/saved-searches/{search_api_saved_search}/delete",
 *   },
 * )
 */
class SavedSearch extends ContentEntityBase implements SavedSearchInterface {

  /**
   * The search type for this saved search, or FALSE if loading it failed.
   *
   * @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface|false|null
   *
   * @see \Drupal\search_api_saved_searches\Entity\SavedSearch::getType()
   */
  protected $type;

  /**
   * The search query for this saved search, or FALSE if loading it failed.
   *
   * @var \Drupal\search_api\Query\QueryInterface|false|null
   *
   * @see \Drupal\search_api_saved_searches\Entity\SavedSearch::getQuery()
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

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
      ]);

    $fields['notify_interval'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Notification interval'))
      ->setDescription(t('The interval (in seconds) in which you want to receive notifications of new results for this saved search. Use -1 for "Never".'))
      ->addPropertyConstraints('value', [
        'Range' => [
          'min' => -1,
          'minMessage' => t('%name: The integer must be larger or equal to %min.', [
            '%name' => t('Notification interval'),
            '%min' => -1,
          ]),
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
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
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel);
    $params['user'] = $this->getOwnerId();
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
    if ($this->type === NULL) {
      $type = \Drupal::entityTypeManager()
        ->getStorage('search_api_saved_search_type')
        ->load($this->bundle());
      $this->type = $type ?: FALSE;
    }

    if (!$this->type) {
      throw new SavedSearchesException("Saved search #{$this->id()} does not have a valid type set");
    }
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery() {
    if ($this->query === NULL) {
      $this->query = FALSE;
      $query = $this->get('query')->value;
      if ($query) {
        $this->query = unserialize($query);
      }
    }

    return $this->query ?: NULL;
  }

}
