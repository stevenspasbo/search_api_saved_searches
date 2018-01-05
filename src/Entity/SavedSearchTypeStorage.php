<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Provides a storage handler for saved search types.
 *
 * @todo Are these two methods even needed?
 */
class SavedSearchTypeStorage extends ConfigEntityStorage {

  /**
   *
   *
   * @return string|null
   *   The ID of the default type, or NULL if there is none.
   */
  public function getDefaultTypeId() {
    $type_ids = $this->getQuery()
      ->condition('default', TRUE)
      ->execute();
    assert(count($type_ids) <= 1);
    return $type_ids ? reset($type_ids) : NULL;
  }

  public function loadDefaultType() {

  }

}
