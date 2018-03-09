<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for saved search entities.
 */
interface SavedSearchInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Retrieves the type (bundle) entity for this saved search.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   *   The type entity for this saved search.
   *
   * @throws \Drupal\search_api_saved_searches\SavedSearchesException
   *   Thrown if the type is unknown.
   */
  public function getType();

  /**
   * Retrieves the search query of this saved search.
   *
   * @return \Drupal\search_api\Query\QueryInterface|null
   *   The search query of this saved search. Or NULL if it couldn't be
   *   retrieved.
   */
  public function getQuery();

}
