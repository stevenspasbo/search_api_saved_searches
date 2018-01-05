<?php

namespace Drupal\search_api_saved_searches\Plugin\Block;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives separate "Save search" blocks for each saved search type.
 */
class SaveSearchBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $derivatives;

  /**
   * The saved search type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $object = new static();

    $storage = $container->get('entity_type.manager')
      ->getStorage('search_api_saved_search_type');
    $object->setStorage($storage);
    $object->setStringTranslation($container->get('string_translation'));

    return $object;
  }

  /**
   * Retrieves the saved search type storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The saved search type storage.
   */
  public function getStorage() {
    return $this->storage ?: \Drupal::entityTypeManager()
        ->getStorage('search_api_saved_search_type');
  }

  /**
   * Sets the saved search type storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The new saved search type storage.
   *
   * @return $this
   */
  public function setStorage(EntityStorageInterface $storage) {
    $this->storage = $storage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->derivatives = [];
      foreach ($this->getStorage()->loadMultiple() as $id => $type) {
        $args = ['%type' => $type->label()];
        $this->derivatives[$id] = [
          'admin_label' => $this->t('Save search (type %type)', $args),
          'category' => $this->t('Forms'),
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
