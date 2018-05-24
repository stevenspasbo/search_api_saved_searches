<?php

namespace Drupal\search_api_saved_searches;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the saved search entity type.
 *
 * @see \Drupal\search_api_saved_searches\Entity
 *
 * @ingroup saved_search_access
 */
class SavedSearchAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs a SavedSearchAccessControlHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeInterface $entity_type) {
    parent::__construct($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $savedSearch, $operation, AccountInterface $account) {
    // Check if the users have access to delete a saved search.

    if ($operation === 'delete' || $operation === 'update') {
      if (($account->isAuthenticated() && $account->hasPermission('administer search_api_saved_searches')) || $account->id() === $savedSearch->getOwnerId()) {
        return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($savedSearch);
      }
      else {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }


}
