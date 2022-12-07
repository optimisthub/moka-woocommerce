<?php
/*
 * Plugin Name: Moka Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/optimisthub/moka-woocommerce
 * Description: Moka Payment gateway for woocommerce
 * Version: 3.5.6
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

	$tableNames = [
		$wpdb->prefix . 'moka_transactions',
		$wpdb->prefix . 'moka_transactions_hash',
		$wpdb->prefix . 'moka_subscriptions',
	];
	
	$charsetCollate = $wpdb->get_charset_collate();

	$createTableQuery = [
		"CREATE TABLE $tableNames[0] (
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
		) $charsetCollate;",

		"CREATE TABLE $tableNames[1] (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`id_hash` text,
			`id_order` int DEFAULT NULL,
			`order_details` text,
			`optimist_id` text,
			`created_at` timestamp NULL DEFAULT NULL,
			PRIMARY KEY (`id`)
		) $charsetCollate;",

		"CREATE TABLE $tableNames[2] (
			`id` int unsigned NOT NULL AUTO_INCREMENT,
			`order_id` text,
			`order_amount` decimal(10,2) DEFAULT '0.00',
			`order_details` text,
			`subscription_status` int DEFAULT '0', 
			`subscription_period` text,
			`subscription_next_try` text,
			`user_id` int DEFAULT NULL,
			`optimist_id` text,
			`created_at` timestamp NULL DEFAULT NULL,
			`updated_at` timestamp NULL DEFAULT NULL,
			PRIMARY KEY (`id`)
		) $charsetCollate;",

	];

	require( ABSPATH . 'wp-admin/includes/upgrade.php' );

	foreach($createTableQuery as $perQuery )
	{  
		dbDelta( $perQuery ); 
	} 

	add_option( 'moka_transactions', $mokaVersion );
}

register_activation_hook(__FILE__, 'mokaPaySqlTables');

add_action( 'plugins_loaded', 'loadOptimisthubMokaTranslations' ); 

