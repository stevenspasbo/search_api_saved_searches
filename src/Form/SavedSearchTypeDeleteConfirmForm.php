<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * @todo
 */
class SavedSearchTypeDeleteConfirmForm extends EntityConfirmFormBase {

  /**
   * Returns the question to ask the user.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete this saved search type?');
  }

  /**
   * Returns the route to go to if the user cancels the action.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public function getCancelUrl() {
    return Url::fromRoute('', '');
  }
}
