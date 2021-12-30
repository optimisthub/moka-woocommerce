<?php
/*
 * Plugin Name: Moka Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/optimisthub/moka-woocommerce
 * Description: Moka Payment gateway for woocommerce
 * Version: 2.9
 * Author: Optimist Hub
 * Author URI: https://optimisthub.com?ref=mokaPayment
 * Domain Path: /languages/ 
 * Text Domain: moka-woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';    

/**
 * Plugin translation hook
 *
 * @return void
 */
function loadOptimisthubMokaTranslations() 
{
	$reformatDirName = str_replace('-master','',dirname( plugin_basename(__FILE__)));
    $path = dirname( plugin_basename(__FILE__)) . '/languages';
    $result = load_plugin_textdomain( $reformatDirName, false, $path );
    if (!$result) {
        $locale = apply_filters('plugin_locale', get_locale(), $reformatDirName);
        dd("Could not find $path/" . $reformatDirName . "-$locale.mo.");
    }
}

/**
 * Generate Moka Transaction Table For Transaction Logs
 *
 * @return void
 */
function mokaPaySqlTables() 
{
	global $wpdb;
	global $mokaVersion;

	$tableName = $wpdb->prefix . 'moka_transactions';
	$tableNameHash = $wpdb->prefix . 'moka_transactions_hash';
	
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

	$sqlHash = "CREATE TABLE $tableNameHash (
		`id` int unsigned NOT NULL AUTO_INCREMENT,
		`id_hash` text,
		`id_order` int DEFAULT NULL,
		`order_details` text,
		`optimist_id` text,
		`created_at` timestamp NULL DEFAULT NULL,
		PRIMARY KEY (`id`)
	) $charsetCollate;";

	require( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	dbDelta( $sqlHash );

	add_option( 'moka_transactions', $mokaVersion );
}

register_activation_hook(__FILE__, 'mokaPaySqlTables');

add_action( 'plugins_loaded', 'loadOptimisthubMokaTranslations' ); 

