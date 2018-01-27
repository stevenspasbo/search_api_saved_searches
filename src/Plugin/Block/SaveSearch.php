<?php

namespace Drupal\search_api_saved_searches\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the "Save search" form in a block.
 *
 * @Block(
 *   id = "search_api_saved_searches",
 *   deriver = "Drupal\search_api_saved_searches\Plugin\Block\SaveSearchBlockDeriver"
 * )
 */
class SaveSearch extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|null
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $block = new static($configuration, $plugin_id, $plugin_definition);

    $block->setEntityTypeManager($container->get('entity_type.manager'));
    $block->setFormBuilder($container->get('form_builder'));
    $block->setQueryHelper($container->get('search_api.query_helper'));

    return $block;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
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
   * Retrieves the form builder.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  public function getFormBuilder() {
    return $this->formBuilder ?: \Drupal::formBuilder();
  }

  /**
   * Sets the form builder.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The new form builder.
   *
   * @return $this
   */
  public function setFormBuilder(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
    return $this;
  }

  /**
   * The query helper.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface|null
   */
  protected $queryHelper;

  /**
   * Retrieves the query helper.
   *
   * @return \Drupal\search_api\Utility\QueryHelperInterface
   *   The query helper.
   */
  public function getQueryHelper() {
    return $this->queryHelper ?: \Drupal::service('search_api.query_helper');
  }

  /**
   * Sets the query helper.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The new query helper.
   *
   * @return $this
   */
  public function setQueryHelper(QueryHelperInterface $query_helper) {
    $this->queryHelper = $query_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::access($account, TRUE);

    $create_access = $this->getEntityTypeManager()
      ->getAccessControlHandler('search_api_saved_search')
      ->createAccess($this->getDerivativeId(), $account, [], TRUE);
    $access->andIf($create_access);

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo Move those checks to access()? Would mean access results can't be
    //   cached, though.
    $type = $this->getSavedSearchType();
    if (!$type) {
      return [];
    }
    $query = $type->getActiveQuery($this->getQueryHelper());
    if (!$query) {
      return [];
    }

    $values = [
      'type' => $type->id(),
      'query' => serialize($query),
      'mail' => \Drupal::currentUser()->getEmail(),
    ];
    $saved_search = $this->getEntityTypeManager()
      ->getStorage('search_api_saved_search')
      ->create($values);

    $form_object = $this->getEntityTypeManager()
      ->getFormObject('search_api_saved_search', 'create');
    $form_object->setEntity($saved_search);
    return $this->getFormBuilder()->getForm($form_object);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $type = $this->getSavedSearchType();
    if ($type) {
      $dependencies['config'][] = $type->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * Loads the saved search type used for this block.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface|null
   *   The saved search type, or NULL if it couldn't be loaded.
   */
  protected function getSavedSearchType() {
    return $this->getEntityTypeManager()
      ->getStorage('search_api_saved_search_type')
      ->load($this->getDerivativeId());
  }

}
