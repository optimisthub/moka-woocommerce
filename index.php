<?php
/*
 * Plugin Name: Moka Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/optimisthub/moka-woocommerce
 * Description: Moka Payment gateway for woocommerce
 * Version: 1.0.0
 * Author: Optimist Hub
 * Author URI: https://optimisthub.com?ref=mokaPayment
 * Domain Path: /i18n/languages/
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/core/UpdateChecker.php';
require __DIR__ . '/core/MokaCore.php';
 

add_filter( 'woocommerce_payment_gateways', 'addOptimisthubMokaGateway' );
function addOptimisthubMokaGateway( $gateways ) {
	$gateways[] = 'OptimistHub_Moka'; 
	return $gateways;
}

add_action( 'plugins_loaded', 'initOptimisthubGatewayClass' );
function initOptimisthubGatewayClass() 
{
	class OptimistHub_Moka extends WC_Payment_Gateway {
 
 		public function __construct() 
        {
			$this->id = 'mokapay';  
			$this->icon = ''; // TODO : Moka Icon
			$this->has_fields = true; 
			$this->method_title = 'Moka by Isbank';
			$this->method_description = 'Moka by Isbank WooCommerce Gateway';
			$this->supports = ['products'];
 
			$this->init_form_fields(); 
			$this->init_settings();
 		}
 
 		public function init_form_fields()
        {
	 	}

		public function payment_fields() 
        {
		}

	 	public function payment_scripts() 
        {
	 	}
         
		public function validate_fields() 
        {
		}
        
		public function process_payment( $order_id ) 
        {
	 	}
         
		public function webhook() 
        {
	 	}
 	}
}
