<?php

add_action( 'plugins_loaded', 'initOptimisthubGatewayClass' );

/**
 * Gateway Class Filter
 *
 * @return void
 */
function initOptimisthubGatewayClass() 
{ 
	
    class OptimistHub_Moka_Gateway extends WC_Payment_Gateway {
     
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
            $this->installment = 'yes' === $this->get_option( 'installment' );
            $this->company_code = $this->get_option( 'company_code' );
            $this->company_name = $this->get_option( 'company_name' );
            $this->api_username = $this->get_option( 'api_username' );
            $this->api_password = $this->get_option( 'api_password' );
    
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] ); 
            add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] ); 
            add_filter( 'woocommerce_credit_card_form_fields' , [$this,'payment_form_fields'] , 10, 2 );
    
            
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
                'installment' => [
                    'title'       => __( 'Installement', 'moka-woocommerce' ),
                    'label'       => __('Enable/Disable Installement ?', 'moka-woocommerce' ),
                    'type'        => 'checkbox', 
                    'default'     => 'yes',
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


        
        public function payment_form_fields($cc_fields , $payment_id){
 
            $cc_fields = [
                'name-on-card' => '
                    <p class="form-row form-row-wide">
                        <label for="'. $payment_id.'-card-holder">' . __('Name On Card','moka-woocommerce') . ' <span class="required">*</span></label>
                        
                        <input 
                            id="'.$payment_id.'-card-holder" 
                            class="input-text wc-credit-card-form-card-holder" 
                            type="text" 
                            autocomplete="off" 
                            placeholder="' . __('Name On Card','moka-woocommerce') . '" 
                            name="' .$payment_id. '-name-oncard" 
                        />
                    </p>',
                'card-number-field' => '
                    <p class="form-row form-row-wide">
                        <label for="'.$payment_id.'-card-number">' . __( 'Card Number', 'moka-woocommerce' ) . ' <span class="required">*</span></label>
                        
                        <input 
                            id="'.$payment_id.'-card-number" 
                            class="input-text wc-credit-card-form-card-number" 
                            type="text" 
                            maxlength="20" 
                            autocomplete="off" 
                            placeholder="•••• •••• •••• ••••" 
                            name="'.$payment_id . '-card-number"
                        />
                    </p>',
                'card-expiry-field' => '
                    <p class="form-row form-row-first">
                        <label for="'.$payment_id.'-card-expiry">' . __( 'Expiry (MM/YY)', 'woocommerce' ) . ' <span class="required">*</span></label>
                
                        <input 
                            id="'.$payment_id.'-card-expiry" 
                            class="input-text wc-credit-card-form-card-expiry" 
                            type="text" 
                            autocomplete="off" 
                            placeholder="' . __( 'MM / YY', 'woocommerce' ) . '" 
                            name="'.$payment_id.'-card-expiry"
                        />
                </p>',
                'card-cvc-field' => '
                    <p class="form-row form-row-last">
                        <label for="'.$payment_id.'-card-cvc">' . __( 'Card Code', 'moka-woocommerce' ) . ' <span class="required">*</span></label>
                        
                        <input 
                            id="'.$payment_id.'-card-cvc" 
                            class="input-text wc-credit-card-form-card-cvc" 
                            type="text" 
                            autocomplete="off" 
                            placeholder="' . __( 'CVC', 'woocommerce' ) . '" 
                            name="'.$payment_id.'-card-cvc" 
                        />
                </p>'
            ];
            return $cc_fields;
        }

    
        public function payment_fields() 
        {
    
            if ( $this->description ) { 
                if ( $this->testmode ) {
                    $this->description .=  __( "TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href='#''>documentation</a>", 'moka-woocommerce' );
                    $this->description  = trim( $this->description );
                } 
                echo wpautop( wp_kses_post( $this->description ) );
            } 
             
            do_action( 'woocommerce_credit_card_form_start', $this->id );
            
            $cc_form           = new WC_Payment_Gateway_CC();
            $cc_form->id       = $this->id;
            $cc_form->supports = $this->supports; 
            $cc_form->form();
        
            do_action( 'woocommerce_credit_card_form_end', $this->id );  
        }
 
        public function payment_scripts() 
        {  
 
            wp_enqueue_script( 'moka-pay-corejs', plugins_url( 'moka-woocommerce/assets/moka.js' ), false, OPTIMISTHUB_MOKA_PAY_VERSION );
            
            wp_register_style( 'moka-pay-card_css',  plugins_url( 'moka-woocommerce/assets/moka.css' ) , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_enqueue_style ( 'moka-pay-card_css' );
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
