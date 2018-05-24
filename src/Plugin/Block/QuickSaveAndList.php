<?php

namespace Drupal\search_api_saved_searches\Plugin\Block;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\Select;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the "Save search" form in a block.
 *
 * @Block(
 *   id = "quick_save_and_list_searches",
 *   admin_label = @Translation("Quick save and list searches"),
 *   category = @Translation("Forms"),
 * )
 */
class QuickSaveAndList extends SaveSearch implements ContainerFactoryPluginInterface {

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface|null
   */
  protected static $entityDisplayRepository;

  /**
   * Array of existing bundles of the search_api_saved_search entity type.
   *
   * @var array
   */
  protected static $bundleInfo;

  /**
   * Nested array of view modes available, keyed by bundle ID.
   *
   * @var array
   */
  protected static $viewModeOptions = [];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $block = new static($configuration, $plugin_id, $plugin_definition);

    $block->setStringTranslation($container->get('string_translation'));
    $block->setEntityTypeManager($container->get('entity_type.manager'));
    $block->setFormBuilder($container->get('form_builder'));
    $block->setQueryHelper($container->get('search_api.query_helper'));
    $block->setEntityDisplayRepository($container->get('entity_display.repository'));
    $block->setEntityTypeBundleInfo($container->get('entity_type.bundle.info')->getBundleInfo('search_api_saved_search'));

    return $block;
  }

  /**
   * Retrieves the entity display repository service.
   *
   * @return \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity display repository.
   */
  public static function getEntityDisplayRepository() {
    return self::$entityDisplayRepository ?: \Drupal::service('entity_display.repository');
  }

  /**
   * Sets the entity display repository service.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The new entity display repository.
   */
  public static function setEntityDisplayRepository(EntityDisplayRepositoryInterface $entity_display_repository) {
    self::$entityDisplayRepository = $entity_display_repository;
  }

  /**
   * Retrieves array of information about all saved search entity bundles.
   *
   * @return array
   *   The array of bundles for the search_api_saved_search entity type.
   */
  public static function getEntityTypeBundleInfo() {
    return self::$bundleInfo ?: \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('search_api_saved_search');
  }

  /**
   * Sets array of defined bundles for the search_api_saved_search entity type.
   *
   * @param array $bundle_info
   *   Array of bundles retrieved from the 'entity_type.bundle.info' service.
   */
  public static function setEntityTypeBundleInfo(array $bundle_info) {
    self::$bundleInfo = $bundle_info;
  }

  /**
   * Retrieves the available view modes for all saved search bundles.
   *
   * @return array
   *   The array of view modes available for each saved search entity bundle.
   */
  protected static function getViewModeOptions() {
    if (empty(self::$viewModeOptions)) {
      self::setDisplayModeOptions();
    }
    return self::$viewModeOptions;
  }

  /**
   * Helper method to populate the lists of form modes and view modes.
   */
  protected static function setDisplayModeOptions() {
    // Load the list of existing saved search types.
    $bundles = self::getEntityTypeBundleInfo();
    // Populate the list of view modes, grouped by bundle/type.
    foreach ($bundles as $bundle_id => $bundle) {
      self::$viewModeOptions[$bundle_id] = self::getEntityDisplayRepository()
        ->getViewModeOptionsByBundle('search_api_saved_search', $bundle_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => 'default',
      'view_mode' => 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Add ajax behavior to the select element returned by the parent
    // ::blockForm() method.
    $form['type']['#ajax'] = [
      'callback' => self::class . '::modeOptions',
      'wrapper' => 'edit-saved-search-modes',
    ];

    // Load the saved search type for which this form has been configured.
    $selected_type = $this->configuration['type'];

    // Populate the list of view modes, grouped by bundle/type.
    $view_mode_options = self::getViewModeOptions();

    $form['modes'] = [
      '#type' => 'container',
      '#id' => 'edit-saved-search-modes',
    ];
    $form['modes']['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#description' => $this->t('Entity view mode to display in the list of existing saved searches.'),
      '#options' => $view_mode_options[$selected_type],
      '#default_value' => $this->configuration['view_mode'],
      '#required' => TRUE,
      '#empty_value' => '',
      '#process' => [
        [get_class($this), 'processModes'],
      ],
    ];

    return $form;
  }

  /**
   * Form element #process callback for the form mode and view mode fields.
   *
   * @param $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The complete form structure.
   *
   * @return array
   */
  public function processModes($element, FormStateInterface $form_state, $form) {
    $type = $form_state->getValue(['settings','type']) ?: $this->configuration['type'];

    switch ($element['#name']) {
      case 'settings[modes][view_mode]':
        $element['#options'] = self::getViewModeOptions()[$type];
        $element['#default_value'] = self::getDefaultViewMode($type, $form_state);
        break;
    }
    // Use Select::processSelect() to set the empty '- Select -' option.
    $element = Select::processSelect($element, $form_state, $form);

    return $element;
  }

  /**
   * Ajax callback to update the block configuration form.
   *
   * @param $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The portion of the form array to ajax replace the list of modes.
   */
  public static function modeOptions(&$form, FormStateInterface $form_state) {
    $type = $form_state->getValue(['settings','type']);

    $default_view_mode = self::getDefaultViewMode($type, $form_state);

    $form['settings']['modes']['#attributes']['id'] = 'edit-saved-search-modes';
    // Alter view_mode select list.
    $form['settings']['modes']['view_mode']['#default_value'] = $default_view_mode;
    return $form['settings']['modes'];
  }

  /**
   * Helper method to retrieve the default value to populate the display mode.
   *
   * @param string $saved_search_type
   *   The machine name of the saved search bundle.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $config_value
   *   Optional. The value retrieved from configuration, if set.
   *
   * @return string
   *   The machine name of the default form mode.
   */
  protected static function getDefaultViewMode($saved_search_type, FormStateInterface $form_state, $config_value = '') {
    $view_mode_options = self::getViewModeOptions()[$saved_search_type];
    $selected_view_mode = $form_state->getValue(['settings', 'modes', 'view_mode']);
    if (empty($selected_view_mode) && !empty($config_value)) {
      $selected_view_mode = $config_value;
    }
    $default_view_mode = array_key_exists($selected_view_mode, $view_mode_options) ? $selected_view_mode : '';
    return $default_view_mode;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $modes = ['form_mode', 'view_mode'];
    $this->configuration['type'] = $form_state->getValue('type');
    foreach ($modes as $mode) {
      $this->configuration[$mode] = $form_state->getValue(['modes', $mode]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $type = $this->getSavedSearchType();
    $bundle = $this->getConfiguration()['type'];
    $view_mode = $this->getConfiguration()['view_mode'];
    if (!$type || !$type->status()) {
      return [];
    }
    $query = $type->getActiveQuery($this->getQueryHelper());

    $current_user_id = \Drupal::currentUser()->id();

    $saved_search_ids = \Drupal::entityQuery('search_api_saved_search')
      ->condition('uid', $current_user_id)
      ->condition('type', $bundle)
      ->execute();

    $saved_search_entities = \Drupal::entityTypeManager()
      ->getStorage('search_api_saved_search')
      ->loadMultiple($saved_search_ids);
    $items = \Drupal::entityTypeManager()
      ->getViewBuilder('search_api_saved_search')
      ->viewMultiple($saved_search_entities, $view_mode);

    $build = [];

    $build['list'] =[
      '#type' => 'details',
      '#title' => $this->t('My Searches'),
      'list_items' => []
    ];

    if($items) {
      $build['list']['list_items'] = $items;
    }
    else {
      $build['list']['list_items'] = [
        '#markup' => t('You do not have a saved search, yet.')
      ];
    }

    $build['quick_save'] = [
      '#type' => 'details',
      '#title' => $this->t('Save New Search'),
    ];

    if (empty($query)) {
      $build['quick_save']['quick_save_form'] = [
        '#markup' => $this->t('There is no active search to save.'),
      ];
    }
    else {
      $values = [
        'type' => $type->id(),
        'query' => serialize($query),
        'mail' => \Drupal::currentUser()->getEmail(),
      ];
      $saved_search = $this->getEntityTypeManager()
        ->getStorage('search_api_saved_search')
        ->create($values);

      $form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->load('search_api_saved_search.' . $bundle . '.default');

      $form_builder = \Drupal::service('entity.form_builder');
      // @TODO: The form_display is discarded because of a bug in core, and the
      // 'default' form mode is always used. Current workaround is to just use
      // the default form display when configuring this block.
      // For more information about the core bug, see:
      // https://www.drupal.org/project/drupal/issues/2530086#comment-12250350
      $form = $form_builder->getForm($saved_search, 'create', ['form_display' => $form_display]);

      $build['quick_save']['quick_save_form'] = $form;
    }

    return $build;
  }

  /**
   * Loads the saved search type used for this block.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface|null
   *   The saved search type, or NULL if it couldn't be loaded.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getSavedSearchType() {
    if (!$this->configuration['type']) {
      return NULL;
    }
    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    $type = $this->getEntityTypeManager()
      ->getStorage('search_api_saved_search_type')
      ->load($this->configuration['type']);
    return $type;
  }

}
