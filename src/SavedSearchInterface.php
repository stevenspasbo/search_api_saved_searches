<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for saved search entities.
 */
interface SavedSearchInterface extends ContentEntityInterface, EntityOwnerInterface {

}
