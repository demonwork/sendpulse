<?php

namespace Drupal\sendpulse\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sendpulse\SendpulseTokenStorage;
use Exception;
use Sendpulse\RestApi\ApiClient;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SubscribeForm extends FormBase
{

  public function getFormId()
  {
    return 'sendpulse_subscribe_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Subscribe our news.'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Enter your email to subscribe our news.'),
      '#required' => TRUE,
      '#prefix' => '<strong>',
      '#suffix' => '</strong>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
    ];

    $form['#cache']['max-age'] = 0;

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Wrong email address.'));
    }

    if ($errors = $form_state->getErrors()) {
      $accessor = PropertyAccess::createPropertyAccessor();
      foreach ($errors as $field => $error) {
        if ($accessor->getValue($form, "[$field]")) {
          $accessor->setValue($form, "[$field]" . '[#prefix]', '<div class="form-group error">');
          $accessor->setValue($form, "[$field]" . '[#suffix]', '<div class="input-error-desc">' . $error . '</div></div>');
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('sendpulse.adminsettings');
    $api_id = $config->get('sendpulse_api_id');
    $api_secret = $config->get('sendpulse_api_secret');
    $address_book = $config->get('sendpulse_address_book');

    $email = $form_state->getValue('email');
    $data = [
        [
          'email' => $email,
          'variables' => [
//            'Имя' => 'Дмитрий',
//            'Phone' => '555-55-55',
          ],
        ],
    ];

    try {
      $sp_client = new ApiClient($api_id, $api_secret, new SendpulseTokenStorage('sendpulse.adminsettings'));
      $r = $sp_client->addEmails($address_book, $data);
      // TODO: проверить $r сообщить пользователю/админу о неполадках
    } catch (Exception $exception) {
      // TODO: сообщить админу о неполадках

    }

    $messenger = Drupal::messenger();
    $messenger->addMessage('Thank you for subscribe.');

    // Redirect to home
    $form_state->setRedirect('<front>');
  }
}
