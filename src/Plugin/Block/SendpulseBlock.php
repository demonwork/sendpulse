<?php

namespace Drupal\sendpulse\Plugin\Block;

use Drupal;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'User login' block.
 *
 * @Block(
 *   id = "sendpulse_block",
 *   admin_label = @Translation("Sendpulse"),
 *   category = @Translation("Forms")
 * )
 */
class SendpulseBlock extends BlockBase
{

  public function build()
  {
    return Drupal::formBuilder()->getForm('Drupal\sendpulse\Form\SubscribeForm');
  }
}
