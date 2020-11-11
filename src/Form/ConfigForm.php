<?php


namespace Drupal\sendpulse\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sendpulse\SendpulseTokenStorage;
use Exception;
use Sendpulse\RestApi\ApiClient;

class ConfigForm extends ConfigFormBase
{

  public function __construct(ConfigFactoryInterface $config_factory)
  {
    parent::__construct($config_factory);
  }

  public function getFormId()
  {
    return 'sendpulse_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $config = $this->config('sendpulse.adminsettings');
    $api_id = $config->get('sendpulse_api_id');
    $api_secret = $config->get('sendpulse_api_secret');

    $form['sendpulse_api_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API id'),
      '#description' => $this->t('API id'),
      '#default_value' => $api_id,
    ];
    $form['sendpulse_api_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('API secret'),
      '#description' => $this->t('API secret') . ' ' . (!empty($api_secret) ? $this->t('(Secret is set.)') : ''),
      '#default_value' => $api_secret,
    ];

    if (!empty($api_id) && !empty($api_secret)) {
      $options = $this->getAddressBookList($api_id, $api_secret);
      $form['sendpulse_address_book'] = [
        '#type' => 'select',
        '#options' => $options,
        '#title' => $this->t('Address book'),
        '#description' => $this->t('Address book'),
        '#default_value' => $config->get('sendpulse_address_book'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  private function getAddressBookList($api_id, $api_secret)
  {
    $address_books = [];

    try {
      $sp_client = new ApiClient($api_id, $api_secret, new SendpulseTokenStorage('sendpulse.adminsettings'));
      $books = $sp_client->listAddressBooks();
      foreach ($books as $book) {
        $address_books[$book->id] = $book->name;
      }
    } catch (Exception $exception) {
      // TODO: сообщить админу о неполадках
    }

    return $address_books;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitForm($form, $form_state);
    $config = $this->config('sendpulse.adminsettings');
    $config->set('sendpulse_api_id', $form_state->getValue('sendpulse_api_id'));
    $secret = $form_state->getValue('sendpulse_api_secret');
    if (!empty($secret)) {
      $config->set('sendpulse_api_secret', $secret);
    }

    $config->set('sendpulse_address_book', $form_state->getValue('sendpulse_address_book'));
    $config->save();
  }

  protected function getEditableConfigNames()
  {
    return [
      'sendpulse.adminsettings',
    ];
  }
}
