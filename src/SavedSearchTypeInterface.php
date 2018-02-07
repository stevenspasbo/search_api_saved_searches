<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\search_api\Utility\QueryHelperInterface;

/**
 * Provides an interface for saved search types.
 */
interface SavedSearchTypeInterface extends ConfigEntityInterface {

  /**
   * Retrieves the settings.
   *
   * @return array
   *   The settings for this type.
   */
  public function getOptions();

  /**
   * Retrieves a single, possibly nested, option.
   *
   * @param string $key
   *   The key of the option. Can contain periods (.) to access nested options.
   * @param mixed $default
   *   (optional) The value to return if the option wasn't set.
   *
   * @return mixed
   *   The value of the specified option if it exists, $default otherwise.
   */
  public function getOption($key, $default = NULL);

  /**
   * Retrieves an active search query that can be saved with this type.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface|null $query_helper
   *   (optional) The query helper service to use. Otherwise, it will be
   *   retrieved from the container.
   *
   * @return \Drupal\search_api\Query\QueryInterface|null
   *   A search query that was executed in this page request and which can be
   *   saved with this saved search type. Or NULL if no such query could be
   *   found.
   */
  public function getActiveQuery(QueryHelperInterface $query_helper = NULL);

}
