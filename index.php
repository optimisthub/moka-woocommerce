<?php
/*
 * Plugin Name: Moka Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/optimisthub/moka-woocommerce
 * Description: Moka Payment gateway for woocommerce
 * Version: 2.0
 * Author: Optimist Hub
 * Author URI: https://optimisthub.com?ref=mokaPayment
 * Domain Path: /languages/ 
 * Text Domain: moka-woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';    

define('OPTIMISTHUB_MOKA_PAY_VERSION', '2.0');


/**
 * Plugin translation hook
 *
 * @return void
 */
function loadOptimisthubMokaTranslations() 
{
    $path = dirname( plugin_basename(__FILE__)) . '/languages';
    $result = load_plugin_textdomain( dirname( plugin_basename(__FILE__)), false, $path );
	
    if (!$result) {
        $locale = apply_filters('plugin_locale', get_locale(), dirname( plugin_basename(__FILE__)));
        dd("Could not find $path/" . dirname( plugin_basename(__FILE__)) . "-$locale.mo.");
    }
}

global $mokaVersion;
$mokaVersion = OPTIMISTHUB_MOKA_PAY_VERSION;

/**
 * Generate Moka Transaction Table For Transaction Logs
 *
 * @return void
 */
function moka_activate() 
{
	global $wpdb;
	global $mokaVersion;

	$tableName = $wpdb->prefix . 'moka_transactions';
	
	$charsetCollate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $tableName (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`id_cart` text,
		`id_customer` int DEFAULT NULL,
		`optimist_id` text,
		`amount` decimal(10,2) DEFAULT '0.00',
		`amount_paid` decimal(10,2) DEFAULT '0.00',
		`installment` int DEFAULT '1',
		`result_code` text,
		`result_message` text,
		`result` tinyint DEFAULT '1',
		`created_at` timestamp NULL DEFAULT NULL,
		PRIMARY KEY (`id`)
	) $charsetCollate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'moka_transactions', $mokaVersion );
}

register_activation_hook(__FILE__, 'moka_activate');

add_action( 'plugins_loaded', 'loadOptimisthubMokaTranslations' ); 