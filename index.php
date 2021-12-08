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
 
require __DIR__ . '/vendor/autoload.php';  

add_filter( 'woocommerce_payment_gateways', 'addOptimisthubMokaGateway' );
add_action( 'plugins_loaded', 'loadOptimisthubMokaTranslations' );
add_action( 'plugins_loaded', 'initOptimisthubGatewayClass' );


/**
 * Gateway Implement
 *
 * @param [type] $gateways
 * @return void
 */
function addOptimisthubMokaGateway( $gateways ) {
	$gateways[] = 'OptimistHub_Moka'; 
	return $gateways;
}

 
/**
 * Load plugin textdomain.
 */
function loadOptimisthubMokaTranslations() {
    $path = dirname( plugin_basename(__FILE__)) . '/languages';
    $result = load_plugin_textdomain( dirname( plugin_basename(__FILE__)), false, $path );
	
    if (!$result) {
        $locale = apply_filters('plugin_locale', get_locale(), dirname( plugin_basename(__FILE__)));
        die("Could not find $path/" . dirname( plugin_basename(__FILE__)) . "-$locale.mo.");
    }
}


/**
 * Gateway Class Filter
 *
 * @return void
 */
function initOptimisthubGatewayClass() 
{
	class OptimistHub_Moka extends WC_Payment_Gateway {
 
 		public function __construct() 
        { 
			$this->id = 'mokapay';  
			$this->icon = ''; // TODO : Moka Icon
			$this->has_fields = true; 
			$this->method_title = 'Moka by Isbank';
			$this->method_description = __('Moka by Isbank WooCommerce Gateway','moka-woocommerce');
			$this->supports = ['products'];
 
			$this->init_form_fields();  
			$this->init_settings();

			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->testmode = 'yes' === $this->get_option( 'testmode' );
			$this->company_code = $this->get_option( 'company_code' );
			$this->company_name = $this->get_option( 'company_name' );
			$this->api_username = $this->get_option( 'api_username' );
			$this->api_password = $this->get_option( 'api_password' );
			 

 
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] ); 
			add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
	
			
 		}
 
 		public function init_form_fields()
        {

			$this->form_fields = [
				'enabled' => [
					'title'       => __( 'Enable/Disable', 'moka-woocommerce' ),
					'label'       => __( 'Enable Moka Pay Gateway?', 'moka-woocommerce' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'yes'
				],
			 
				'title' => [
					'title'       => __( 'Title', 'moka-woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'moka-woocommerce' ),
					'default'     => __( 'Credit Card', 'moka-woocommerce' ),
					'desc_tip'    => true,
				],
				'description' => [
					'title'       => __( 'Description', 'moka-woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'This controls the description which the user sees during checkout.', 'moka-woocommerce' ),
					'default'     => __( 'Pay with your credit card via our super-cool payment gateway.', 'moka-woocommerce' ),
				],
				'testmode' => [
					'title'       => 'Test '.__( 'Enable/Disable', 'moka-woocommerce' ),
					'label'       => __('Enable Test Mode?', 'moka-woocommerce' ),
					'type'        => 'checkbox',
					'description' => __('Place the payment gateway in test mode using test API keys.', 'moka-woocommerce' ),
					'default'     => 'yes',
					'desc_tip'    => true,
				],
				'company_code' => [
					'title'       => __( 'Company Code', 'moka-woocommerce' ),
					'type'        => 'text', 
				],
				'company_name' => [
					'title'       => __( 'Company Name', 'moka-woocommerce' ),
					'type'        => 'text', 
				],
				'api_username' => [
					'title'       => __( 'Api Username', 'moka-woocommerce' ),
					'type'        => 'text', 
				],
				'api_password' => [
					'title'       => __( 'Api Password', 'moka-woocommerce' ),
					'type'        => 'text', 
				],
			];		
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
