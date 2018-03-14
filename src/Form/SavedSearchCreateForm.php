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
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save search');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    if ($return === SAVED_NEW) {
      /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
      $search = $this->entity;
      // @todo Add status field for saved searches.
      $enabled = TRUE;
      if ($enabled) {
        if ($search->get('notify_interval')->value < 0) {
          $this->messenger()->addStatus($this->t('Your saved search was successfully created.'));
        }
        else {
          $this->messenger()->addStatus($this->t('Your saved search was successfully created. You will receive notifications for new results in the future.'));
        }
      }
      else {
        $this->messenger()->addStatus($this->t('Your saved search was successfully created. You will soon receive an e-mail with a confirmation link to activate it.'));
      }
    }

    return $return;
  }

}
