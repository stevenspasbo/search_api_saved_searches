<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting saved search types.
 */
class SavedSearchTypeDeleteConfirmForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete this saved search type?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    // @todo Replace with messenger service once we depend on Drupal 8.5+.
    drupal_set_message($this->t('The saved search type was successfully deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
