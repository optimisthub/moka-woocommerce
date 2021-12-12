<?php

add_action( 'plugins_loaded', 'initOptimisthubGatewayClass' );

/**
 * Gateway Class
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
            $this->enable_3d = 'yes' === $this->get_option( 'enable_3d' );
            $this->company_code = $this->get_option( 'company_code' );
            $this->company_name = $this->get_option( 'company_name' );
            $this->api_username = $this->get_option( 'api_username' );
            $this->api_password = $this->get_option( 'api_password' );
            
            $this->optimisthubMoka = new MokaPayment();
            $this->maxInstallment = range(1,12);

            $this->installments = $this->get_option( 'woocommerce_mokapay-installments' );
    
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] ); 
            add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] ); 
            add_filter( 'woocommerce_credit_card_form_fields' , [$this,'payment_form_fields'] , 10, 2 ); 
            add_action( 'admin_head', [$this, 'admin_css']);     
 
            self::__saveRates();
        }
    
        /**
         * Admin Settings Panel form Fields
         *
         * @return void
         */
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
                'enable_3d' => [
                    'title'       => __( 'Enable 3D', 'moka-woocommerce' ),
                    'label'       => __( 'Enable 3d Payment?', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
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

        /**
         * Admin area css init.
         *
         * @return void
         */
        public function admin_css()
        {
            global $pagenow; 
            if($pagenow == 'admin.php' && isset($_GET['tab']) && isset($_GET['section']) && $_GET['section'] == 'mokapay')
            {
                wp_register_style( 'moka-pay-admin',  plugins_url( 'moka-woocommerce/assets/moka-admin.css' ) , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
                wp_enqueue_style ( 'moka-pay-admin' );
            } 
        }

        /**
         * Admin Options
         *
         * @return void
         */
        public function admin_options() 
        {
            ?>
                <div class="moka-admin-interface">
                    <div class="left">
                        <img src="<?php echo plugins_url( 'moka-woocommerce/assets/img/mokapos.png' ); ?>" alt="">
                        <h2><?php _e('Moka Pos Settings','moka-woocommerce'); ?></h2>

                        <table class="form-table">
                            <?php $this->generate_settings_html(); ?>
                        </table> 
                    </div>
                    <div class="right">
                        <div class="optimist">
                            <?php include __DIR__ .'/static/Optimisthub.php' ?>
                        </div>
                    </div>
                </div>
                <div class="moka-admin-interface">
                    <?php  
                        if(!$this->installments)
                        {
                            echo $this->optimisthubMoka->generateInstallmentsTableHtml(
                            [
                                'maxInstallment' => $this->maxInstallment,
                                'paymentGatewayId' => $this->id
                            ]);
                        } else {
                            echo $this->optimisthubMoka->generateDefaultInstallmentsTableHtml(
                            [
                                'maxInstallment' => $this->maxInstallment,
                                'paymentGatewayId' => $this->id
                            ]);
                        }

                   ?>
                </div>
            <?php

        }
        
        /**
         * Payment Form Fields
         *
         * @param [array] $cc_fields
         * @param [string] $payment_id
         * @return void
         */
        public function payment_form_fields($cc_fields , $payment_id)
        {
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

        /**
         * Payment Container
         *
         * @return void
         */
        public function payment_fields() 
        {
    
            if ( $this->description ) { 
                if ( $this->testmode ) {
                    $this->description .=  __( "TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href='#''>documentation</a>", 'moka-woocommerce' );
                    $this->description  = trim( $this->description );
                } 
                echo wpautop( wp_kses_post( $this->description ) ).'<br>';
            } 
             
            do_action( 'woocommerce_credit_card_form_start', $this->id );
            
            $cc_form           = new WC_Payment_Gateway_CC();
            $cc_form->id       = $this->id;
            $cc_form->supports = $this->supports; 
            $cc_form->form();
 
            do_action( 'woocommerce_credit_card_form_end', $this->id );  
        }
 
        /**
         * Payment Scripts
         *
         * @return void
         */
        public function payment_scripts() 
        { 
            wp_enqueue_script( 'moka-pay-corejs', plugins_url( 'moka-woocommerce/assets/moka.js' ), false, OPTIMISTHUB_MOKA_PAY_VERSION );
            
            wp_register_style( 'moka-pay-card_css',  plugins_url( 'moka-woocommerce/assets/moka.css' ) , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_enqueue_style ( 'moka-pay-card_css' );
        }
            
        /**
         * Validate form fields
         *
         * @return void
         */
        public function validate_fields() 
        {

            if( empty(data_get($_POST, $this->id.'-name-oncard') )) 
            {
                wc_add_notice(  __( "<strong>Card holder</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }

            if( empty(data_get($_POST, $this->id.'-card-number'))) 
            {
                wc_add_notice(  __( "<strong>Card Number</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }

            if( empty(data_get($_POST, $this->id.'-card-expiry') )) 
            {
                wc_add_notice(  __( "<strong>Card Expiry</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }
            if( empty(data_get($_POST, $this->id.'-card-cvc'))) 
            {
                wc_add_notice(  __( "<strong>Card CVC</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }

            return true;
        }
        
        /**
         * Process payment
         *
         * @param [type] $order_id
         * @return void
         */
        public function process_payment( $order_id ) 
        {
        }
            
        public function webhook() 
        {
        }

        /**
         * Save Installment Rates to DB
         *
         * @return void
         */
        private function __saveRates()
        {
            if(data_get($_POST, 'woocommerce_mokapay-installments'))
            {  
                $this->optimisthubMoka->setInstallments($_POST['woocommerce_mokapay-installments']);
            }
        }
    }
}
