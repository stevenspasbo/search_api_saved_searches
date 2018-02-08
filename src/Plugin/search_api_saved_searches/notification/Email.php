<?php

namespace Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Utility\Token;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api_saved_searches\Notification\NotificationPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides e-mails as a notification mechanism.
 *
 * @SearchApiSavedSearchesNotification(
 *   id = "email",
 *   label = @Translation("E-mail"),
 *   description = @Translation("Sends new results via e-mail."),
 * )
 */
class Email extends NotificationPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token|null
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $plugin */
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $plugin->setTokenService($container->get('token'));

    return $plugin;
  }

  /**
   * Retrieves the token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token service.
   */
  public function getTokenService() {
    return $this->tokenService ?: \Drupal::service('token');
  }

  /**
   * Sets the token service.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The new token service.
   *
   * @return $this
   */
  public function setTokenService(Token $token_service) {
    $this->tokenService = $token_service;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'registered_choose_mail' => FALSE,
      'activate' => [
        'send' => TRUE,
        'title' => NULL,
        'body' => NULL,
      ],
      'notification' => [
        'title' => NULL,
        'body' => NULL,
      ],
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // @todo Is this the right place for this option?
    $form['registered_choose_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Let logged-in users also enter a different mail address'),
      '#default_value' => $this->configuration['registered_choose_mail'],
    ];

    $form['activate'] = [
      '#type' => 'details',
      '#title' => $this->t('Activation mail'),
      '#open' => !$this->configuration['activate']['title'],
    ];
    $form['activate']['send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use activation mail for anonymous users'),
      '#description' => $this->t("Will require that saved searches created by anonymous users, or by normal users with an e-mail address that isn't their own, are activated by clicking a link in an e-mail."),
      '#default_value' => $this->configuration['mail']['activate']['send'],
    ];
    $states = [
      'visible' => [
        ':input[name="activate[send]"]' => [
          'checked' => TRUE,
        ],
      ],
    ];
    $args = ['@site_name' => '[site:name]'];
    $default_title = $this->configuration['activate']['title'] ?: $this->t('Activate your saved search at @site_name', $args);
    $args['@activation_link'] = '[activation_link]';
    $default_body = $this->configuration['activate']['body']
      ?: $this->t("A saved search on @site_name with this e-mail address was created.
To activate this saved search, click the following link:

@activation_link

If you didn't create this saved search, just ignore this mail and the saved search will be deleted.

--  @site_name team", $args);
    $form['activate']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#description' => $this->t("Enter the mail's subject.") . ' ' .
        $this->t('See below for available replacements.'),
      '#default_value' => $default_title,
      '#required' => TRUE,
      '#states' => $states,
    ];
    $form['activate']['body'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Body'),
      '#description' => $this->t("Enter the mail's body.") . ' ' .
        $this->t('See below for available replacements.'),
      '#default_value' => $default_body,
      '#rows' => 12,
      '#required' => TRUE,
      '#states' => $states,
    ];

    $available_tokens = $this->getAvailableTokensList(['site', 'user']);

    $form['activate']['available_tokens'] = $available_tokens;

    return $form;
  }

  /**
   * Provides an overview of available tokens.
   *
   * @param string[] $types
   *   The token types for which to list tokens.
   *
   * @return array
   *   A form/render element for displaying the available tokens for the given
   *   types.
   */
  protected function getAvailableTokensList(array $types) {
    // Code taken from \Drupal\views\Plugin\views\PluginBase::globalTokenForm().
    $token_items = [];
    $infos = $this->getTokenService()->getInfo();
    foreach ($infos['tokens'] as $type => $tokens) {
      if (!in_array($type, $types)) {
        continue;
      }
      $item = [
        '#markup' => $type,
        'children' => [],
      ];
      foreach ($tokens as $name => $info) {
        $item['children'][$name] = "[$type:$name] - {$info['name']}: {$info['description']}";
      }

      $token_items[$type] = $item;
    }
    $available_tokens = [
      '#type' => 'details',
      '#title' => $this->t('Available token replacements'),
    ];
    $available_tokens['list'] = [
      '#theme' => 'item_list',
      '#items' => $token_items,
    ];
    return $available_tokens;
  }

}
