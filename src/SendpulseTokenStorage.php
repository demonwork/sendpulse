<?php

namespace Drupal\sendpulse;

use Drupal;
use Sendpulse\RestApi\Storage\TokenStorageInterface;

class SendpulseTokenStorage implements TokenStorageInterface
{
  private $config_name;

  public function __construct($config_name)
  {
    $this->config_name = $config_name;
  }

  public function set($key, $token)
  {
    Drupal::configFactory()->getEditable($this->config_name)
      ->set($key, $token)
      ->save();
  }

  public function get($key)
  {
    return Drupal::configFactory()->get($this->config_name)->get($key);
  }
}
