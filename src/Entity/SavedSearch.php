<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\search_api_saved_searches\SavedSearchInterface;

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

    // @todo If we want the notification mechanism to be configurable, this
    //   probably shouldn't be here (but in bundleFieldDefinitions(), probably).
    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('E-mail'))
      ->setDescription(t('The email address to which notifications should be sent.'))
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
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

    $fields['last_queued'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Last queued'))
      ->setDescription(t('The time that the saved search was last queued for execution.'))
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
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

    $fields['options'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Options'))
      ->setDescription(t('Further options for this saved search.'))
      ->setSetting('case_sensitive', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'hidden',
      ])
      ->setDisplayOptions('form', [
        'type' => 'hidden',
      ]);

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
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel);
    $params['user'] = $this->uid[0]->target_id;
    return $params;
  }

}
