<?php
/*
 * Plugin Name: Moka Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/optimisthub/moka-woocommerce
 * Description: Moka Payment gateway for woocommerce
 * Version: 3.8.5
 * Author: Optimist Hub
 * Author URI: https://optimisthub.com/?utm_source=moka-woocommerce&utm_campaign=moka-woocommerce&utm_content=plugins
 * Domain Path: /languages/ 
 * Text Domain: moka-woocommerce
 */

if ( !defined('ABSPATH') ) {
    exit;
}

define( 'OPTIMISTHUB_MOKA_PAY_VERSION', '3.8.5' );
define( 'OPTIMISTHUB_MOKA_FILE', __FILE__ );
define( 'OPTIMISTHUB_MOKA_BASENAME', plugin_basename( OPTIMISTHUB_MOKA_FILE ) );
define( 'OPTIMISTHUB_MOKA_DIR', plugin_dir_path( OPTIMISTHUB_MOKA_FILE ) );
define( 'OPTIMISTHUB_MOKA_URL', plugin_dir_url( OPTIMISTHUB_MOKA_FILE ) );
define( 'OPTIMISTHUB_MOKA_UPDATE', 'https://moka.wooxup.com/' );
define( 'OPTIMISTHUB_MOKA_DOMAIN', 'moka-woocommerce' );

/**
 * Auto load Optimisthub Moka
 *
 * @return void
 */
function loadOptimisthubMoka() 
{
	require __DIR__ . '/vendor/autoload.php';    
    $path = dirname( plugin_basename( OPTIMISTHUB_MOKA_FILE ) ) . '/languages';
    load_plugin_textdomain( OPTIMISTHUB_MOKA_DOMAIN, false, $path );
}

/**
 * Generate Moka Transaction Table For Transaction Logs
 *
 * @return void
 */
function mokaPaySqlTables() 
{
	global $wpdb;

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

	if( !function_exists( 'dbDelta' ))
	{
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}

	foreach($createTableQuery as $perQuery )
	{  
		dbDelta( $perQuery ); 
	} 

	update_option( 'moka_transactions', OPTIMISTHUB_MOKA_PAY_VERSION );
}

register_activation_hook(__FILE__, 'mokaPaySqlTables');

add_action( 'plugins_loaded', 'loadOptimisthubMoka' ); 
