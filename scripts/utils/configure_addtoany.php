<?php

/**
 * @file
 * Configure AddToAny module settings (social sharing buttons).
 * 
 * This script applies AddToAny module configuration programmatically.
 * It sets button styling, social links HTML, and entity display settings.
 * 
 * Usage: drush php:script scripts/utils/configure_addtoany.php
 */

try {
  $config = \Drupal::configFactory()->getEditable('addtoany.settings');

  // Set language to French
  $config->set('langcode', 'fr');

  // Set button size
  $config->set('buttons_size', 32);

  // Build social sharing HTML with proper line breaks
  $html = "<div class=\"container\">\n";
  $html .= "<ul class=\"socials\">\n";
  $html .= "<li><a href=\"\" class=\"print\" id=\"printButton\">Imprimer page</a></li>\n";
  $html .= "<li><a class=\"facebook a2a_button_facebook\" data-url=\"https://cheque-eco-energie.normandie.fr/\">Suivez-nous sur facebook</a></li>\n";
  $html .= "<li><a class=\"twitter a2a_button_twitter\">Suivez-nous sur Twitter</a></li>\n";
  $html .= "<li><a class=\"a2a_button_linkedin linkedin\" data-url=\"https://cheque-eco-energie.normandie.fr/\">Suivez-nous sur linkedin</a></li>\n";
  $html .= "<li><a href=\"\" class=\"a2a_button_viadeo viadeo\">Suivez-nous sur viadeo</a></li>\n";
  $html .= "</ul>\n";
  $html .= "</div>";

  $config->set('additional_html', $html);
  $config->set('additional_css', '');
  $config->set('additional_js', '');

  // Set universal button configuration
  $config->set('universal_button', 'default');
  $config->set('custom_universal_button', '');
  $config->set('universal_button_placement', 'before');

  // Configure entity display settings
  $config->set('entities.media', 1);
  $config->set('entities.node', 1);
  $config->set('entities.comment', 1);
  $config->set('entities.block_content', 0);
  $config->set('entities.contact_message', 0);
  $config->set('entities.file', 0);
  $config->set('entities.menu_link_content', 0);
  $config->set('entities.path_alias', 0);
  $config->set('entities.shortcut', 0);
  $config->set('entities.taxonomy_term', 0);
  $config->set('entities.user', 0);
  $config->set('entities.paragraph', 0);

  // Save configuration
  $config->save();

  echo "✅ AddToAny configured with social buttons\n";
} catch (\Exception $e) {
  echo "❌ Error configuring AddToAny: " . $e->getMessage() . "\n";
  exit(1);
}
