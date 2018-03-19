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
   *   The search query of this saved search, or NULL if it couldn't be
   *   retrieved.
   */
  public function getQuery();

  /**
   * Retrieves the options set for this saved search.
   *
   * @return array|null
   *   The options set for this saved search, or NULL if they couldn't be
   *   retrieved.
   */
  public function getOptions();

  /**
   * Generates an access token specific to this saved search.
   *
   * This can be used for access checks independent of a user account (for
   * instance, for accessing a saved search via mail – especially for anonymous
   * users).
   *
   * @return string
   *   The access token for this search.
   */
  public function getAccessToken();

}
