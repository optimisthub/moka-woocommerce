<?php
/*
 * Plugin Name: Moka Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/optimisthub/moka-woocommerce
 * Description: Moka Payment gateway for woocommerce
 * Version: 1.0.0
 * Author: Optimist Hub
 * Author URI: https://optimisthub.com?ref=mokaPayment
 * Domain Path: /languages/ 
 * Text Domain: moka-woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';    

function loadOptimisthubMokaTranslations() {
    $path = dirname( plugin_basename(__FILE__)) . '/languages';
    $result = load_plugin_textdomain( dirname( plugin_basename(__FILE__)), false, $path );
	
    if (!$result) {
        $locale = apply_filters('plugin_locale', get_locale(), dirname( plugin_basename(__FILE__)));
        dd("Could not find $path/" . dirname( plugin_basename(__FILE__)) . "-$locale.mo.");
    }
}
add_action( 'plugins_loaded', 'loadOptimisthubMokaTranslations' ); 