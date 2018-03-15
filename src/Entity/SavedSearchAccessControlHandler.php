<?php

namespace Drupal\search_api_saved_searches\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api_saved_searches\BundleFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides access checking for saved searches.
 */
class SavedSearchAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Permission for administering saved searches.
   */
  const ADMIN_PERMISSION = 'administer search_api_saved_searches';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $handler = new static($entity_type);

    $handler->setEntityTypeManager($container->get('entity_type.manager'));

    return $handler;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);
    if (!$access->isAllowed()
        && $entity instanceof EntityOwnerInterface) {
      $is_owner = $account->id() == $entity->getOwnerId();
      $owner_access = AccessResult::allowedIf($is_owner)
        ->addCacheableDependency($account)
        ->andIf($this->checkBundleAccess($account, $entity->bundle()));
      $access = $access->orIf($owner_access);
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $bundle = NULL) {
    $access = parent::checkCreateAccess($account, $context, $bundle);

    if (!$access->isAllowed()) {
      $access = $access->orIf($this->checkBundleAccess($account, $bundle));
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $field_name = $field_definition->getName();

    // Only admins can edit administrative fields.
    $administrative_fields = [
      'uid',
      'status',
      'created',
      'last_executed',
      'next_execution',
      'notify_interval',
    ];
    if ($operation === 'edit' && in_array($field_name, $administrative_fields, TRUE)) {
      return AccessResult::allowedIfHasPermission($account, self::ADMIN_PERMISSION);
    }

    // For serialized fields, neither viewing nor editing makes sense.
    $serialized_fields = ['query', 'options'];
    if (in_array($field_name, $serialized_fields, TRUE)) {
      return AccessResult::forbidden();
    }

    // The index ID cannot be edited, but can be viewed by admins.
    if ($field_name === 'index_id') {
      if ($operation === 'edit') {
        return AccessResult::forbidden();
      }
      return AccessResult::allowedIfHasPermission($account, self::ADMIN_PERMISSION);
    }

    // Allow for access checks on fields defined by notification plugins.
    if ($field_definition instanceof BundleFieldDefinition) {
      $plugin_id = $field_definition->getSetting('notification_plugin');
      $bundle = $field_definition->getTargetBundle();
      if ($plugin_id && $bundle) {
        /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
        $type = $this->getEntityTypeManager()
          ->getStorage('search_api_saved_search_type')
          ->load($bundle);
        if ($type && $type->isValidNotificationPlugin($plugin_id)) {
          return $type->getNotificationPlugin($plugin_id)
            ->checkFieldAccess($operation, $field_definition, $account, $items);
        }
      }
      // In doubt (that is, when some part of the previous code didn't work
      // out), only allow admin access.
      return AccessResult::allowedIfHasPermission($account, self::ADMIN_PERMISSION);
    }

    return parent::checkFieldAccess($operation, $field_definition, $account, $items);
  }

  /**
   * Checks access for using saved searches of a specific bundle.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   * @param string $bundle
   *   The bundle for which to check usage access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkBundleAccess(AccountInterface $account, $bundle) {
    $permission = "use $bundle search_api_saved_searches";
    return AccessResult::allowedIfHasPermission($account, $permission);
  }

}
