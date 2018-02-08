<?php

namespace Drupal\search_api_saved_searches\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Display\DisplayPluginManager;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding and editing saved search types.
 */
class SavedSearchTypeForm extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface
   */
  protected $entity;

  /**
   * The display plugin manager.
   *
   * @var \Drupal\search_api\Display\DisplayPluginManager|null
   */
  protected $displayPluginManager;

  /**
   * The data type helper.
   *
   * @var \Drupal\search_api\Utility\DataTypeHelperInterface|null
   */
  protected $dataTypeHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);

    $form->setStringTranslation($container->get('string_translation'));
    $form->setEntityTypeManager($container->get('entity_type.manager'));
    $form->setDisplayPluginManager($container->get('plugin.manager.search_api.display'));
    $form->setDataTypeHelper($container->get('search_api.data_type_helper'));

    return $form;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * Retrieves the display plugin manager.
   *
   * @return \Drupal\search_api\Display\DisplayPluginManager
   *   The display plugin manager.
   */
  public function getDisplayPluginManager() {
    return $this->displayPluginManager ?: \Drupal::service('plugin.manager.search_api.display');
  }

  /**
   * Sets the display plugin manager.
   *
   * @param \Drupal\search_api\Display\DisplayPluginManager $display_plugin_manager
   *   The new display plugin manager.
   *
   * @return $this
   */
  public function setDisplayPluginManager(DisplayPluginManager $display_plugin_manager) {
    $this->displayPluginManager = $display_plugin_manager;
    return $this;
  }

  /**
   * Retrieves the data type helper.
   *
   * @return \Drupal\search_api\Utility\DataTypeHelperInterface
   *   The data type helper.
   */
  public function getDataTypeHelper() {
    return $this->dataTypeHelper ?: \Drupal::service('search_api.data_type_helper');
  }

  /**
   * Sets the data type helper.
   *
   * @param \Drupal\search_api\Utility\DataTypeHelperInterface $data_type_helper
   *   The new data type helper.
   *
   * @return $this
   */
  public function setDataTypeHelper(DataTypeHelperInterface $data_type_helper) {
    $this->dataTypeHelper = $data_type_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $type = $this->entity;
    $form['#tree'] = TRUE;
    if ($type->isNew()) {
      $form['#title'] = $this->t('Create saved search type');
    }
    else {
      $args = ['%type' => $type->label()];
      $form['#title'] = $this->t('Edit saved search type %type', $args);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type name'),
      '#description' => $this->t('Enter the displayed name for the saved search type.'),
      '#default_value' => $type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $type->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\search_api\Entity\Index::load',
        'source' => ['name'],
      ],
      '#disabled' => !$type->isNew(),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Disabling a saved search type will prevent the creation of new saved searches of that type and stop notifications for existing searches of that type.'),
      '#default_value' => $type->status(),
    ];

    $display_options = [];
    $displays = $this->getDisplayPluginManager()->getInstances();
    foreach ($displays as $display_id => $display) {
      $display_options[$display_id] = $display->label();
    }
    $form['options']['displays'] = [
      '#type' => 'details',
      '#title' => $this->t('Search displays'),
      '#description' => $this->t('Select for which search displays saved searches of this type can be created.'),
      '#open' => $type->isNew(),
    ];
    if (count($display_options) > 0) {
      $form['options']['displays']['default'] = [
        '#type' => 'radios',
        '#options' => [
          1 => $this->t('For all displays except the selected'),
          0 => $this->t('Only for the selected displays'),
        ],
        '#default_value' => (int) $type->getOption('displays.default', TRUE),
      ];
      $form['options']['displays']['selected'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Search displays'),
        '#options' => $display_options,
        '#default_value' => $type->getOption('displays.selected', []),
      ];
    }
    else {
      $form['options']['displays']['default'] = [
        '#type' => 'radios',
        '#options' => [
          1 => $this->t('Applies to all displays by default'),
          0 => $this->t('Applies to no displays by default'),
        ],
        '#default_value' => (int) $type->getOption('displays.default', TRUE),
      ];
      $form['options']['displays']['selected'] = [
        '#type' => 'value',
        '#value' => [],
      ];
    }

    $form['options']['misc'] = [
      '#type' => 'details',
      '#title' => $this->t('Miscellaneous'),
      '#open' => $type->isNew(),
    ];
    $form['options']['misc']['date_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Method for determining new results'),
      '#description' => $this->t('The method by which to decide which results are new. "Determine by result ID" will internally save the IDs of all results that were previously found by the user and only report results not already reported. (This might use a lot of memory for large result sets.) The other options check whether the date in the selected field is later than the date of last notification.'),
    ];
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = $this->getEntityTypeManager()
      ->getStorage('search_api_index')
      ->loadMultiple();
    $data_type_helper = $this->getDataTypeHelper();
    foreach ($indexes as $index_id => $index) {
      $fields = [];
      foreach ($index->getFields() as $key => $field) {
        // We misuse isTextType() here to check for the "Date" type instead.
        if ($data_type_helper->isTextType($field->getType(), ['date'])) {
          $fields[$key] = $this->t('Determine by @name', ['@name' => $field->getLabel()]);
        }
      }
      if ($fields) {
        $fields = [NULL => $this->t('Determine by result ID')] + $fields;
        $form['options']['misc']['date_field'][$index_id] = [
          '#type' => 'select',
          '#title' => count($indexes) === 1 ? NULL : $this->t('Searches on index %index', ['%index' => $index->label()]),
          '#options' => $fields,
          '#default_value' => $type->getOption("date_field.$index_id"),
          '#parents' => ['options', 'date_field', $index_id],
        ];
      }
      else {
        $form['options']['misc']['date_field'][$index_id] = [
          '#type' => 'value',
          '#value' => NULL,
          '#parents' => ['options', 'date_field', $index_id],
        ];
      }
    }
    $form['options']['misc']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User interface description'),
      '#description' => $this->t('Enter a text that will be displayed to users when creating a saved search. You can use HTML in this field.'),
      '#default_value' => $type->getOption('description', ''),
      '#parents' => ['options', 'description'],
    ];

    return $form;
  }

}
