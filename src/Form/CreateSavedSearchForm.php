<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_saved_searches\SavedSearchTypeInterface;

/**
 * Provides a form for saving a search.
 */
class CreateSavedSearchForm extends ContentEntityForm {

  /**
   * The type of search to be created.
   *
   * @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface|null
   */
  protected $savedSearchType;

  /**
   * The search query to be saved.
   *
   * @var \Drupal\search_api\Query\QueryInterface|null
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_saved_search_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SavedSearchTypeInterface $type = NULL, QueryInterface $query = NULL) {
    $this->savedSearchType = $type;
    $this->query = $query;
    $this->syncPropertiesWithFormState($form_state);
    if (!$this->savedSearchType || !$this->query) {
      return [];
    }
    $this->entity = $this->buildEntity($form, $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save search');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);

    $entity->set('type', $this->savedSearchType->id());
    $entity->set('query', $this->query);

    return $entity;
  }

  /**
   * Syncs the $savedSearchType and $query form properties with the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state with which to sync the properties.
   */
  protected function syncPropertiesWithFormState(FormStateInterface $form_state) {
    if (!$this->savedSearchType) {
      $this->savedSearchType = $form_state->get('saved_search_type');
    }
    else {
      $form_state->set('saved_search_type', $this->savedSearchType);
    }
    if (!$this->query) {
      $this->query = $form_state->get('query');
    }
    else {
      $form_state->set('query', $this->query);
    }
  }

}
