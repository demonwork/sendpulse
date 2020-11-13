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
    $form['#prefix'] = '<div id="sendpulse-subscribe-form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Subscribe our news.'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Enter your email to subscribe our news.'),
      '#required' => false,
      '#prefix' => '<strong>',
      '#suffix' => '</strong>',
    ];

    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Confirm process privacy data'),
      '#description' => $this->t('I agree process my private data. <a href="https://www.ya.ru">Terms</a>'),
      '#required' => false,
      '#prefix' => '<strong>',
      '#suffix' => '</strong>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Subscribe'),
      '#ajax' => [
        'wrapper' => 'sendpulse-subscribe-form-wrapper',
        'callback' => '::ajaxCallback',
      ],
      '#states' => [
        'enabled' => [
          ':input[name="confirm"]' => ['checked' => true],
        ],
      ],

    ];

    $form['#cache']['max-age'] = 0;
    $form['#attributes']['novalidate'] = 'novalidate';

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    $email = $form_state->getValue('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $form_state->setErrorByName('email', $this->t('Wrong email address.'));
    }

    $confirm = $form_state->getValue('confirm');
    if ($confirm != 1) {
      $form_state->setErrorByName('confirm', $this->t('You must agree with terms.'));
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

  public function ajaxCallback(array &$form, FormStateInterface $form_state)
  {
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $config = $this->config('sendpulse.adminsettings');
    $api_id = $config->get('sendpulse_api_id');
    $api_secret = $config->get('sendpulse_api_secret');
    $address_book = $config->get('sendpulse_address_book');
    $force_confirm = $config->get('sendpulse_force_confirm');
    $confirm_email = $config->get('sendpulse_confirm_email');

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
      $add_params = [];
      if ($force_confirm && !empty($confirm_email)) {
        $add_params = ['confirmation' => 'force', 'sender_email' => $confirm_email];
      }

      $r = $sp_client->addEmails($address_book, $data, $add_params);
      if ($r->is_error) {
        $form['exception'] = [
          '#markup' => t('Something go wrong, please contact with admin.'),
        ];
        Drupal::logger('sendpulse')->error($r->message);
        return;
      }
    } catch (Exception $exception) {
      $form['exception'] = [
        '#markup' => t('Something go wrong, please contact with admin.'),
      ];
      Drupal::logger('sendpulse')->error($exception->getMessage());
      return;
    }

//    $messenger = Drupal::messenger();
//    $messenger->addMessage('Thank you for subscribe.');

    $form['success'] = [
      '#markup' => t('Thank you for subscribe.'),
    ];
    // Redirect to home
    // $form_state->setRedirect('<front>');
  }
}
