<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for saving a search.
 */
class SavedSearchCreateForm extends ContentEntityForm {

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
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save search');
    return $actions;
  }

}
