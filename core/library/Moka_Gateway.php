<?php

use Carbon\Carbon;

/**
 * Gateway Class
 *
 * @return void
 */
function initOptimisthubGatewayClass() 
{ 
	
    if(!class_exists('WC_Payment_Gateway'))
    {
        return;
    }
    
    class OptimistHub_Moka_Gateway extends WC_Payment_Gateway {

        public function __construct() 
        {  
  
            $this->id = 'mokapay';  
            $this->icon = ''; // TODO : Moka Icon
            $this->has_fields = true; 
            $this->method_title = 'Moka by Isbank';
            $this->method_description = __('Moka by Isbank WooCommerce Gateway','moka-woocommerce');
            $this->supports = [
                'products', 
                'subscriptions',
                'subscription_cancellation', 
                'subscription_suspension', 
                'subscription_reactivation',
                'subscription_amount_changes',
                'subscription_date_changes',
                'subscription_payment_method_change',
                'subscription_payment_method_change_customer',
                'subscription_payment_method_change_admin', 
                'tokenization',
            ];
    
            $this->init_form_fields();  
            $this->init_settings();
    
            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );
            $this->enabled = $this->get_option( 'enabled' );
            $this->testmode = 'yes' === $this->get_option( 'testmode', 'no' );
            $this->installment = 'yes' === $this->get_option( 'installment', 'yes' );
            $this->enable_3d = 'yes' === $this->get_option( 'enable_3d', 'yes' );
            $this->show_installment_total = 'yes' === $this->get_option( 'show_installment_total' );
            $this->company_code = $this->get_option( 'company_code' );
            $this->company_name = $this->get_option( 'company_name' );
            $this->api_username = $this->get_option( 'api_username' );
            $this->api_password = $this->get_option( 'api_password' );
            $this->order_prefix = $this->get_option( 'order_prefix' );
            $this->order_status = $this->get_option( 'order_status' );
            $this->subscriptions = $this->get_option( 'subscriptions' );
            $this->installment_message = 'yes' === $this->get_option( 'installment_message', 'yes' );
            $this->installment_tab_enable = 'yes' === $this->get_option( 'installment_tab_enable', 'yes' );
            $this->installment_tab_position =  $this->get_option( 'installment_tab_position', 20 );
            $this->isSubscriptionsEnabled = 'yes' === $this->subscriptions; 
            $this->optimisthubMoka = new MokaPayment();
            $this->limitInstallment =  $this->get_option( 'limitInstallment', 12 );
            $this->limitInstallmentByProduct = 'yes' === $this->get_option( 'limitInstallmentByProduct', 'no' );
            $this->debugMode = 'yes' === $this->get_option( 'debugMode', 'no' );
            $this->maxInstallment = range(1,12);
            $this->userInformation = self::getUserInformationData();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] ); 
            add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] ); 
            add_filter( 'woocommerce_credit_card_form_fields' , [$this,'payment_form_fields'] , 10, 2 ); 
            add_action( 'admin_head', [$this, 'admin_css']);   
            add_action( 'woocommerce_receipt_'.$this->id, [$this, 'receipt_page']); 
            add_filter( 'woocommerce_generate_mokahr_html', [$this, 'mokahr_html']);

            self::__saveRates();
            
        }

        public function mokahr_html(){
            return '<tr valign="top"><td colspan="2"><hr /></td></tr>';
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
                'mokahr1' => [
                    'type' => 'mokahr',
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
                'mokahr2' => [
                    'type' => 'mokahr',
                ],
                'testmode' => [
                    'title'       => 'Test '.__( 'Enable/Disable', 'moka-woocommerce' ),
                    'label'       => __('Enable Test Mode?', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __('Place the payment gateway in test mode using test API keys.', 'moka-woocommerce' ),
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ],
                'mokahr3' => [
                    'type' => 'mokahr',
                ],
                'subscriptions' => [
                    'title'       => __( 'Subscription', 'moka-woocommerce' ) .' -  '. __( 'Enable/Disable', 'moka-woocommerce' ),
                    'label'       => __('Enable subscription ?', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __('It allows you to sell products via subscription method on your site.' , 'moka-woocommerce'),
                    'default'     => 'no',
                    'desc_tip'    => true,
                ],
                'mokahr4' => [
                    'type' => 'mokahr',
                ],
                'enable_3d' => [
                    'title'       => __( 'Enable 3D', 'moka-woocommerce' ),
                    'label'       => __( 'Enable 3d Payment?', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'yes'
                ],
                'mokahr5' => [
                    'type' => 'mokahr',
                ],
                'installment' => [
                    'title'       => __( 'Installement', 'moka-woocommerce' ),
                    'label'       => __('Enable/Disable Installement ?', 'moka-woocommerce' ),
                    'type'        => 'checkbox', 
                    'default'     => 'yes',
                ],
                'limitInstallment' => [
                    'title' => __( 'Limit Installement', 'moka-woocommerce' ),
                    'label' => __( 'Limit Installement', 'moka-woocommerce' ),
                    'type' => 'select',
                    'options' => [
                        1 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 1 ),
                        2 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 2 ),
                        3 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 2 ),
                        4 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 4 ),
                        5 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 5 ),
                        6 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 6 ),
                        7 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 7 ),
                        8 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 8 ),
                        9 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 9 ),
                        10 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 10 ),
                        11 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 11 ),
                        12 => sprintf( __( '%s Installement', 'moka-woocommerce' ), 12 ),
                    ],
                    'description' => '',
                    'default' => '12'
                ],
                'limitInstallmentByProduct' => [
                    'title' => __( 'Limit Installement By Product', 'moka-woocommerce' ),
                    'label' => __( 'Enable/Disable Installement by Product ?', 'moka-woocommerce' ),
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no'
                ],
                'installment_message' => [
                    'title'       => __( 'Show Installment Message under the price_html ?', 'moka-woocommerce' ) .' -  '. __( 'Enable/Disable', 'moka-woocommerce' ),
                    'label'       => __('Show Installment Message under the price_html ?', 'moka-woocommerce' ),
                    'type'        => 'checkbox', 
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ],
                'show_installment_total' => [
                    'title'       => __( 'Show Installment Total Amount', 'moka-woocommerce' ),
                    'label'       => __( 'Show Installment Total Amount', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ],
                'installment_tab_enable' => [
                    'title'       => __( 'Installment table display on product pages', 'moka-woocommerce' ) .' -  '. __( 'Enable/Disable', 'moka-woocommerce' ),
                    'label'       => __('If you want the installment table to be displayed as a product tab on the product pages, turn it on.', 'moka-woocommerce' ),
                    'type'        => 'checkbox', 
                    'default'     => 'yes',
                    'desc_tip'    => true,
                ],
                'installment_tab_position' => [
                    'title'       => __( 'Position of Installment Options Tab among other tabs', 'moka-woocommerce' ),
                    'type'        => 'number', 
                    'default'     => 20,
                    'label'       => __('The default value is 20. You can change the tab position according to the features provided by your theme or plugins. Example (like 40,60,90.)', 'moka-woocommerce' ),
                    'desc_tip'    => true,
                ],
                'mokahr6' => [
                    'type' => 'mokahr',
                ],
                'order_prefix' => [
                    'title'       => __( 'Order Prefix', 'moka-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'This field provides convenience for the separation of orders during reporting for the Moka POS module used in more than one site. (Optional)', 'moka-woocommerce' ),
                    'default'     => self::generateDefaultOrderPrefix(),
                ],
                'order_status' => [
                    'title'       => __( 'Order Status', 'moka-woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'You can choose what the status of the order will be when your payments are successfully completed.', 'moka-woocommerce' ),
                    'options'     => self::getOrderStatuses(),
                    'default'     => 'wc-completed',
                ],
                'mokahr7' => [
                    'type' => 'mokahr',
                ],
                'debugMode' => [
                    'title'       => __( 'Enable/Disable Debug Mode', 'moka-woocommerce' ),
                    'label'       => __( 'Enable/Disable Debug Mode', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
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
                wp_register_style( 'moka-pay-admin',  OPTIMISTHUB_MOKA_URL.'assets/moka-admin.css' , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
                wp_enqueue_style ( 'moka-pay-admin' );
            } 

            if($pagenow == 'admin.php' && isset($_GET['page']) && $_GET['page'] == 'subscription')
            {
                wp_register_style( 'moka-pay-admin',  OPTIMISTHUB_MOKA_URL.'assets/moka-admin.css' , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
                wp_enqueue_style ( 'moka-pay-admin' );
            } 

            wp_enqueue_script( 'moka-pay-corejs', OPTIMISTHUB_MOKA_URL.'assets/moka-admin.js', false, OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_localize_script( 'moka-pay-corejs', 'moka_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'subscription_confirm' => __( 'If you agree, your subscription will be cancelled and the payment will not be renewed. However; you will be able to continue to use your subscription until the membership expiration date.', 'moka-woocommerce' ),
                'success_redirection' => __( 'Your transaction has been completed successfully. Within 2 seconds the page will be refreshed', 'moka-woocommerce' ),
                'update_comission' => __( 'When you do this, all of the instalment data you have entered is deleted and the current ones from Moka Pay servers are overwritten. The process cannot be reversed. To continue, please enter confirmation in the field below and continue the process. Otherwise, your transaction will not continue.', 'moka-woocommerce' ),
                'version' => OPTIMISTHUB_MOKA_PAY_VERSION,
                'installment_test' => __( 'Installment Rate Test', 'moka-woocommerce' ),
                'bin_test' => __( 'Bank Identification Test', 'moka-woocommerce' ),
                'remote_test' => __( 'Remote Connection Test', 'moka-woocommerce' ),
                'success' => __( 'Success', 'moka-woocommerce' ),
                'failed' => __( 'Failed', 'moka-woocommerce' ),
                'download_debug' => __( 'Download debug file', 'moka-woocommerce' ),
                'clear_debug' => __( 'Clear debug file', 'moka-woocommerce' ),
                'debug_notfound' => __( 'Cant find debug file', 'moka-woocommerce' ),
            ] );
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
        <img src="<?php echo OPTIMISTHUB_MOKA_URL.'assets/img/mokapos.png'; ?>" />
        <h2><?php _e('Moka Pos Settings','moka-woocommerce'); ?></h2>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <div class="moka-admin-test-details">
            <button type="button" class="moka-admin-savesettings">
                <?php _e('Save Settings','moka-woocommerce'); ?>
            </button>
            <button type="button" class="moka-admin-dotest">
                <?php _e('Test Informations','moka-woocommerce'); ?>
            </button>
        </div>
        <div class="moka-admin-test-results"></div>
    </div>
    <div class="right">
        <div class="optimist">
            <?php include __DIR__ .'/static/Optimisthub.php' ?>
        </div>
    </div>
</div>
<div class="moka-admin-interface">
    <?php  
        $this->optimisthubMoka->generateInstallmentsTableHtml(
        [
            'maxInstallment' => $this->maxInstallment,
            'paymentGatewayId' => $this->id
        ]);
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
      
            global $woocommerce;
            $referer = is_wc_endpoint_url( 'order-pay' ) ? 'order-pay' : 'checkout'; 
            $total = data_get($woocommerce, 'cart.total'); 
            $state = 'cart';

            if ( get_query_var('order-pay') ) {
                $order = wc_get_order(get_query_var('order-pay'));
                $total = $order->get_total();  
                $state = 'order';
            } 

            $cc_fields = [
                'current-step-of-payment' => '
                    <p class="form-row form-row-wide">
                        <input 
                            id="'.$payment_id.'-current-step-of-payment" 
                            class="current-step-of-payment" 
                            type="hidden"  
                            value="'.$referer.'"
                            name="' .$payment_id. '-current-step-of-payment" 
                        />
                        <input 
                            id="'.$payment_id.'-current-order-state" 
                            class="current-order-state" 
                            type="hidden"  
                            value="'.$state.'"
                            name="' .$payment_id. '-current-order-state" 
                        />
                    </p>',
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
                    $this->description .= __( 'TEST MODE ENABLED. In test mode, you can use the card numbers listed in
                    <a href="https://developer.moka.com/">documentation</a>', 'moka-woocommerce' );
                    $this->description  = trim( $this->description );
                } 
                echo wpautop( wp_kses_post( $this->description ) );
            } 
             
            do_action( 'woocommerce_credit_card_form_start', $this->id );
            
            $cc_form           = new WC_Payment_Gateway_CC();
            $cc_form->id       = $this->id;
            $cc_form->supports = $this->supports; 
            $cc_form->form();

            echo '<div id="ajaxify-installment-table" class="installment-table"></div>'; 
            if($this->isSubscriptionsEnabled)
            {
                echo '<div class="mokapay-save-card-info-message">
                <p>'.__('Your card information that you have added during the payment will be kept by Moka with the assurance of Moka. Your next subscription payment will be taken with this card.', 'moka-woocommerce').'</p>
                <img src="'.OPTIMISTHUB_MOKA_URL.'assets/img/logo-mavi-moka.svg" alt="Moka POS" style="height:40px" />
                </div>';
            }
            do_action( 'woocommerce_credit_card_form_end', $this->id );  
        }
 
        /**
         * Payment Scripts
         *
         * @return void
         */
        public function payment_scripts() 
        { 
            wp_enqueue_script( 'moka-pay-corejs', OPTIMISTHUB_MOKA_URL . 'assets/moka.js' , false, OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_localize_script( 'moka-pay-corejs', 'moka_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'subscription_confirm' => __( 'If you agree, your subscription will be cancelled and the payment will not be renewed. However; you will be able to continue to use your subscription until the membership expiration date.', 'moka-woocommerce' ),
                'success_redirection' => __( 'Your transaction has been completed successfully. Within 2 seconds the page will be refreshed', 'moka-woocommerce' ),
                'update_comission' => __( 'When you do this, all of the instalment data you have entered is deleted and the current ones from Moka Pay servers are overwritten. The process cannot be reversed. To continue, please enter confirmation in the field below and continue the process. Otherwise, your transaction will not continue.', 'moka-woocommerce' ),
                'version' => OPTIMISTHUB_MOKA_PAY_VERSION,
                'installment_test' => __( 'Installment Rate Test', 'moka-woocommerce' ),
                'bin_test' => __( 'Bank Identification Test', 'moka-woocommerce' ),
                'remote_test' => __( 'Remote Connection Test', 'moka-woocommerce' ),
                'success' => __( 'Success', 'moka-woocommerce' ),
                'failed' => __( 'Failed', 'moka-woocommerce' ),
                'download_debug' => __( 'Download debug file', 'moka-woocommerce' ),
                'clear_debug' => __( 'Clear debug file', 'moka-woocommerce' ),
                'debug_notfound' => __( 'Cant find debug file', 'moka-woocommerce' ),
            ] );
        }
            
        /**
         * Validate form fields
         *
         * @return void
         */
        public function validate_fields() 
        {

            $postedData = $_POST; 

            if( empty(data_get($postedData, $this->id.'-name-oncard') )) 
            {
                wc_add_notice(  __( '<strong>Card holder</strong> is required.', 'moka-woocommerce' ), 'error' );
                return false;
            }

            if( empty(data_get($postedData, $this->id.'-card-number'))) 
            {
                wc_add_notice(  __( '<strong>Card Number</strong> is required.', 'moka-woocommerce' ), 'error' );
                return false;
            }

            if( empty(data_get($postedData, $this->id.'-card-expiry') )) 
            {
                wc_add_notice(  __( '<strong>Card Expiry</strong> is required.', 'moka-woocommerce' ), 'error' );
                return false;
            }
            if( empty(data_get($postedData, $this->id.'-card-cvc'))) 
            {
                wc_add_notice(  __( '<strong>Card CVC</strong> is required.', 'moka-woocommerce' ), 'error' );
                return false;
            }

            if($this->installment)
            {
                if( empty(data_get($postedData, $this->id.'-installment'))) 
                {
                    wc_add_notice(  __( '<strong>Installment</strong> is required.', 'moka-woocommerce' ), 'error' );
                    return false;
                }
            }

            return true;
        }
        
        /**
         * Process payment
         *
         * Desc : Error complex : https://hotexamples.com/examples/-/WC_order/add_order_note/php-wc_order-add_order_note-method-examples.html
         * @param [type] $order_id
         * @return void
         */
        public function process_payment( $orderId ) 
        { 

            $order              = new WC_order($orderId); 
            $orderDetails       = self::formatOrder($orderId); 
            $currentTotal       = data_get($orderDetails, 'Amount');
            $installmentNumber  = data_get($orderDetails, 'InstallmentNumber');
            $currency           = $order->get_currency();

            $orderItems         = $order->get_items();
 
            if($order->get_total() < $currentTotal)
            {
                self::saveComissionDecision( [
                    'orderId' => $orderId,
                    'orderTotal' => $order->get_total(),
                    'currentOrderTotal' => $currentTotal,

                ]); 

                $order->add_order_note( 
                    sprintf( 
                        __( 'The order amount has been updated due to the request for shopping in installments. %s', 'moka-woocommerce' ), 
                        $currentTotal. ' '.$currency. ' ['.$installmentNumber.' '.__( 'Installment', 'moka-woocommerce' ).']'
                    ), 
                    false,
                    true
                );  
            } 

            $payOrder           = $this->optimisthubMoka->initializePayment($orderDetails);
            $callbackUrl        = data_get($payOrder, 'Data.Url');
            $callbackHash       = data_get($payOrder, 'Data.CodeForHash');
            $callbackResult     = data_get($payOrder, 'ResultCode');
            $callbackMessage    = data_get($payOrder, 'ResultMessage');
            $callbackException  = data_get($payOrder, 'Exception');

            $orderDetails['orderId']   = $orderId;
            $orderDetails['userInfo']  = $this->userInformation;
        
            self::saveHash(
                [
                    'id_hash'       => $callbackHash,
                    'id_order'      => $orderId,
                    'order_details' => $this->formatOrderDetailsForLog($orderDetails), 
                    'optimist_id'   => data_get($orderDetails,'OtherTrxCode'), 
                    'created_at'    => date_i18n('Y-m-d H:i:s'),      
                ]
            );

            $recordParams = 
            [
                'id_cart'       => data_get($orderDetails,'orderId'),
                'id_customer'   => data_get($orderDetails,'userInfo.ID'),
                'optimist_id'   => data_get($orderDetails,'OtherTrxCode'),
                'amount'        => data_get($orderDetails,'Amount'),
                'amount_paid'   => 0,
                'installment'   => data_get($orderDetails,'InstallmentNumber'),
                'result_code'   => $callbackResult,
                'result_message'=> self::mokaPosErrorMessages($callbackResult),
                'result'        => 1, // 1 False 0 True
                'created_at'    => date_i18n('Y-m-d H:i:s'), 
            ]; 

            ## Display Error on Checkout
            if($callbackResult != 'Success')
            {
                wc_add_notice(self::mokaPosErrorMessages($callbackResult), 'error' );
                self::saveRecord($recordParams);
            }

            ## Redirect to Reciepent Scenario when Successfully Validated Card Information
            if($callbackResult == 'Success')
            {   
                $recordParams['result_message'] = __( 'Card details have been successfully verified.', 'moka-woocommerce' );
                self::saveRecord($recordParams);
                return [
                    'result' => 'success',
                    'redirect' => $callbackUrl,
                ];
            }
        }

        public function receipt_page( $orderId )
        {
            global $woocommerce; 
      
            $fetchData = self::getHash(['orderId' => $orderId]);
            $orderDetails = json_decode( data_get($fetchData, 'order_details'), true );

            $recordParams = 
            [
                'id_cart'       => data_get($fetchData, 'id_order'),
                'id_customer'   => data_get($orderDetails,'userInfo.ID'),
                'optimist_id'   => data_get($orderDetails,'OtherTrxCode'),
                'amount'        => data_get($orderDetails,'Amount'),
                'amount_paid'   => 0,
                'installment'   => data_get($orderDetails,'InstallmentNumber'),
                'result_code'   => data_get($_POST, 'resultMessage'),
                'result_message'=> self::mokaPosErrorMessages(data_get($_POST, 'resultCode')),
                'result'        => 1, // 1 False 0 True
                'created_at'    => date_i18n('Y-m-d H:i:s'), 
            ];

            $order = new WC_order($orderId);
            $isCompleted = self::validatePayment(data_get($fetchData, 'id_hash'));

            if($isCompleted)
            { 

                $total = data_get($orderDetails,'Amount');
                $currency = data_get($orderDetails,'Currency'); 

                $order->update_status('processing', __('Payment is processing via Moka Pay.', 'moka-woocommerce'));
                $order->add_order_note( __('Hey, the order is paid by Moka Pay!','moka-woocommerce').'<br> '.__( 'Total.', 'moka-woocommerce' ).' : '.$total.' '.$currency , false,false );
                $order->payment_complete();

                ## User Role Changer Support
                self::userRoleChangerSupport($orderId);
                ## User Role Changer Support

			    $order->reduce_order_stock();
                
                $woocommerce->cart->empty_cart();

                ### Set Completed 
                $order->update_status($this->order_status, __('Payment Completed', 'moka-woocommerce'). ' Moka Payment Id : '.data_get($orderDetails,'OtherTrxCode'));
                ### Set Completed 
                
                $recordParams['amount_paid'] = data_get($orderDetails,'Amount');
                $recordParams['result'] = 0;
                $recordParams['result_message'] = __('Hey, the order is paid by Moka Pay!','moka-woocommerce').'<br> Tutar : '.$total.' '.$currency;
                self::saveRecord($recordParams);

                $checkoutOrderUrl = $order->get_checkout_order_received_url();
                $redirectUrl = add_query_arg([
                    'msg' => 'Thank You', 'type' => 'woocommerce-message'
                ], $checkoutOrderUrl); 

                // Subscription product completed successfully
                $orderItems = $order->get_items();
                $hasSubscription = $this->isOrderHasSubscriptionProduct($orderItems);
                 
                if($this->isSubscriptionsEnabled && $hasSubscription)
                { 
                    $userId     = $this->getOrderCustomerId($orderId);
                    $customer   = $this->optimisthubMoka->addCustomerWithCard($orderDetails);  
 
                    $cardToken  = data_get($customer, 'CardList.0.CardToken');
                    $savedCard  = data_get($customer, 'CardList.0');
                    $orderDetails['CardToken'] = $cardToken;
    
                    $tokenParams = $savedCard;
                    $tokenParams['CardToken']   = $cardToken;
                    $tokenParams['OrderId']     = $orderId; 
                    $customer['OrderId']        = $orderId; 
                    
                    $token = $this->fetchCardToken($tokenParams);

                    $this->setCustomerDataToOrderMeta($customer);
                    $orderDetails['CardToken'] = $token; 

                    $saveSubsRecord = $this->formatSubsRecord($orderDetails);
                    $subscriptionPeriod = $this->getSubscriptionProductPeriod($orderItems); 
                    
                    self::saveSubscription(
                        [
                            'order_id'      => $orderId,
                            'order_amount'  => $total,
                            'order_details' => $this->formatOrderDetailsForLog($saveSubsRecord), 
                            'subscription_status'   => '0',
                            'subscription_period'   => data_get($subscriptionPeriod, 'period_string'),
                            'subscription_next_try' => data_get($subscriptionPeriod, 'next_try'),
                            'try_count'     => 0,
                            'user_id'       => $userId,
                            'optimist_id'   => data_get($orderDetails,'OtherTrxCode'), 
                            'created_at'    => current_datetime()->format('Y-m-d H:i:s'),
                        ]
                    );

                    global $wpdb;
                    $hashTable = 'moka_transactions_hash';
                    $wpdb->query(
                            $wpdb->prepare( "UPDATE $wpdb->prefix$hashTable SET order_details = %s WHERE id_order = %d", $this->formatOrderDetailsForLog($saveSubsRecord), $orderId ),
                    );  
                } 
                // Subscription product completed successfully
                
                wp_redirect($redirectUrl);
                exit;

            } else {

                $order = new WC_order($orderId);
                $order->update_status('pending', __('Waiting for user payment.', 'moka-woocommerce'));

                if(isset($_POST) && data_get($_POST, 'resultCode') && data_get($_POST, 'hashValue'))
                { 
                    wc_add_notice( __( 'Your payment could not be collected. Please try again.', 'moka-woocommerce' ), 'notice' );
                    echo '<div class="woocommerce-notices-wrapper"><ul class="woocommerce-error" role="alert"><li class="">'.self::errorMessagesWithErrorCodes(data_get($_POST, 'resultCode')).' : <a class="button" href="'.wc_get_checkout_url().'">'.get_the_title(wc_get_page_id('checkout')).'</a></li></ul></div>'; 
                    $recordParams['result_message'] = self::errorMessagesWithErrorCodes(data_get($_POST, 'resultCode'));
                    self::saveRecord($recordParams);  

                } else {
                    wc_add_notice( __( 'Your payment could not be collected. Please try again.', 'moka-woocommerce' ), 'notice' );
                    $recordParams['result_message'] = __('Waiting for user payment.', 'moka-woocommerce');
                    self::saveRecord($recordParams);  
                }
            }
            
        }
            
        public function webhook() 
        {
        }

        /**
         * Set Cookies
         *
         * @param [array] $params
         * @return void
         */
        private function setcookieSameSite( $params )
        {
            // TODO :: Will be deprecated in next version.
            if (PHP_VERSION_ID < 70300) {
                setcookie(data_get($params, 'name'), data_get($params,'value'), data_get($params, 'expire'), data_get($params, 'path')." samesite=None", data_get($params,'domain'), data_get($params, 'secure'), data_get($params,'httponly'));
            } else {
                setcookie(data_get($params, 'name'), data_get($params,'value'), [
                    'expires' => data_get($params, 'expire'),
                    'path' => data_get($params, 'path'),
                    'domain' => data_get($params,'domain'),
                    'samesite' => 'None',
                    'secure' => data_get($params, 'secure'),
                    'httponly' => data_get($params,'httponly'),
                ]);

            }
        }

        /**
         * Fromat Order Data
         *
         * WC_Order : https://woocommerce.github.io/code-reference/classes/WC-Order.html
         * @param [type] $orderId
         * @return void
         */
        private function formatOrder( $orderId )
        {

            global $woocommerce; 
            $postData = $_POST;
 
            $order = self::fetchOrder($orderId);  
            $getAmount = $order->get_total();
            $customerId = $order->get_user_id();

            $orderItems = $order->get_items();
            $hasSubscription = $this->isOrderHasSubscriptionProduct($orderItems);
 
            $orderIdTrx = $orderId;
            $orderId    = $orderId.'-'.time();
            $expriyDate = self::formatExperyDate(data_get($postData, $this->id.'-card-expiry'));

            $rates = self::prepare_installment( data_get($postData, $this->id.'-order-bankCode'), data_get($postData, $this->id.'-order-bankGroup') );
            
            $selectedInstallment = data_get($postData, $this->id.'-installment');
            $selectedInstallment = self::calculateMaxInstallment($orderItems, $selectedInstallment);

            $currentComission = data_get($rates, $selectedInstallment.'.value'); 

            $orderData = [
                'rates'                 => $rates,
                'CardHolderFullName'    => (string) data_get($postData, $this->id.'-name-oncard'),
                'CardNumber'            => (string) self::formatCartNumber(data_get($postData, $this->id.'-card-number')),
                'ExpMonth'              => (string) data_get($expriyDate,'month' ),
                'ExpYear'               => (string) self::formatExpiryDate(data_get($expriyDate,'year' )),
                'CvcNumber'             => (string) data_get($postData, $this->id.'-card-cvc'),
                'Amount'                => (string) self::calculateComissionRate($getAmount, $currentComission),
                'Currency'              => (string) $order->get_currency() == 'TRY' ? 'TL' : $order->get_currency() ,
                'InstallmentNumber'     => (int) $selectedInstallment,
                'ClientIP'              => (string) self::getUserIp(),
                'RedirectUrl'           => (string) self::checkoutPaymentUrl($orderIdTrx),
                'OtherTrxCode'          => (string) $this->order_prefix.'-OPT-'.$orderId,
                'Software'              => (string) strtoupper('OPT-WpWoo-'.get_bloginfo('version').'-'.WC_VERSION),
                'ReturnHash'            => (int) 1,
                'SubMerchantName'       => "",
                'BuyerInformation'      => 
                [
                    "BuyerFullName"     => (string) $order->get_billing_first_name(). ' '.$order->get_billing_last_name(),
                    "BuyerGsmNumber"    => (string) $order->get_billing_phone(),
                    "BuyerEmail"        => (string) $order->get_billing_email(),
                    "BuyerAddress"      => (string) $order->get_billing_address_1(). ' ' .$order->get_billing_address_2(). ' ' .$order->get_billing_city(),
                ],
                'CustomerDetails'       => [
                    'CustomerCode'          => (string) $this->company_code.'-OPT-'.$customerId,
                    'FirstName'             => (string) $order->get_billing_first_name(),
                    'LastName'              => (string) $order->get_billing_last_name(),
                    'Gender'                => '',
                    'BirthDate'             => '',
                    'GsmNumber'             => (string) $order->get_billing_phone(),
                    'Email'                 => (string) $order->get_billing_email(),
                    'Address'               => (string) (string) $order->get_billing_address_1(). ' ' .$order->get_billing_address_2(). ' ' .$order->get_billing_city(),
                    'CardHolderFullName'    => (string) data_get($postData, $this->id.'-name-oncard'),
                    'ExpMonth'              => (string) data_get($expriyDate,'month' ),
                    'ExpYear'               => (string) self::formatExpiryDate(data_get($expriyDate,'year' )),
                    'CardNumber'            => (string) self::formatCartNumber(data_get($postData, $this->id.'-card-number')),
                    'CardName'              => (string) $order->get_billing_first_name(). '\'s saved card',
                    'MokaStores'            => [
                        'orderData'     => $order,
                        'customerId'    => $customerId,
                    ]
                ], 
                //'BasketProduct'         => self::formatBaksetProducts($orderItems), 
            ]; 

            if($hasSubscription)
            {
                $orderData['Description'] = 'RecurringPayment-'.$orderId;
            }
             
            return $orderData;

        }

        /**
         * User IP Adress
         *
         * @return void
         */
        private function getUserIp()
        {
            if ( isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
            $remote  = $_SERVER['REMOTE_ADDR'];

            if( isset($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) )
            {
                $remote = $_SERVER['HTTP_CLIENT_IP'];
            }
            elseif( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) )
            {
                $remote = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            return $remote;
        }

        /**
         * Format Cart number
         *
         * @param [type] $string
         * @return void
         */
        private function formatCartNumber($string)
        {
            return preg_replace('/\s+/', '',strip_tags(trim(ltrim(rtrim($string)))));
        }

        /**
         * Format Credit Cart Expiyr
         *
         * @param [type] $string
         * @return void
         */
        private function formatExperyDate($string)
        {
            $date = explode('/', $string);
            return [
                'month' => ltrim(rtrim(trim(current($date)))),
                'year' => ltrim(rtrim(trim(last($date)))),
            ]; 
        }

        /**
         * Calculate comisssion rates for installment information
         *
         * @param [int] $total
         * @param [int] $percent
         * @param [int] $installment
         * @return void
         */
        private function calculateComissionRate( $total, $percent )
        { 
            if($total && $percent){
                $realPercent = floatval( floatval($total) * floatval($percent) / 100 );
                $total = floatval($total) + $realPercent;
            }
            return self::moka_number_format($total);
        }

        /**
         * Fetch Order by ID
         *
         * @param [type] $orderId
         * @return void
         */
        private function fetchOrder($orderId)
        { 
            return wc_get_order( $orderId );
        }
        
        /**
         * Installment Fees
         *
         * @param [type] $params
         * @return void
         */
        private function saveComissionDecision( $params )
        {
            $installmentFee = data_get($params, 'currentOrderTotal') - data_get($params, 'orderTotal'); 
            
            $order = self::fetchOrder(data_get($params, 'orderId'));
                     
            $orderFee = new \WC_Order_Item_Fee();
            $orderFee->set_props(
                [
                    'name' => __('Installment Fee', 'moka-woocommerce'),
                    'tax_class' => '',
                    'total' => $installmentFee,
                    'total_tax' => 0,
                    'taxes' => [],
                    'order_id' => $order->get_id(),
                ]
            );
            $orderFee->save();

            $order->add_item( $orderFee );
            $order->calculate_totals(true);
            $order->save();
        }
    
        /**
         * Checkout Payment url
         *
         * @param [type] $orderId
         * @return void
         */
        private function checkoutPaymentUrl($orderId)
        {
            $order = self::fetchOrder($orderId);

            return version_compare(WOOCOMMERCE_VERSION, '2.1.0', '>=') ? $order->get_checkout_payment_url(true) : get_permalink(get_option('woocommerce_pay_page_id')); 
        }
        
        /**
         * Validte payment With Stored Hash
         *
         * @return void
         */
        private function validatePayment($hash)
        {
            $postData       = $_POST;
            $hashValue      = data_get($postData, 'hashValue');
            $hashSession    = hash("sha256", $hash."T");

            if ($hashValue == $hashSession) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Service Error Translation
         *
         * @param [type] $string
         * @return void
         */
        private function mokaPosErrorMessages($string)
        {
            $errorOutput = '';
            switch ($string) {
                case "PaymentDealer.CheckPaymentDealerAuthentication.InvalidRequest":
                    $errorOutput = __( 'Invalid request detected. Try Again.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.CheckPaymentDealerAuthentication.InvalidAccount":
                    $errorOutput = __( 'No dealer found.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.CheckPaymentDealerAuthentication.VirtualPosNotFound":
                    $errorOutput = __( 'Virtual pos is not defined for the dealer.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.CheckDealerPaymentLimits.DailyDealerLimitExceeded":
                    $errorOutput = __( 'Any of the daily limits defined for the dealer have been exceeded.',
                'moka-woocommerce' );
                    break;
                case "PaymentDealer.CheckCardInfo.InvalidCardInfo":
                    $errorOutput = __( 'There is an error in the card details, please check.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.CheckDealerPaymentLimits.DailyCardLimitExceeded":
                    $errorOutput = __( 'No further transactions can be made as the daily limit of the card has been
                exceeded.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidRequest":
                    $errorOutput = __( 'Invalid request detected. Try Again.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.RedirectUrlRequired":
                    $errorOutput = __( 'Redirect Url Required.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidCurrencyCode":
                    $errorOutput = __( 'Invalid Currency Code.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidInstallmentNumber":
                    $errorOutput = __( 'The number of installments is invalid.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InstallmentNotAvailableForForeignCurrencyTransaction":
                    $errorOutput = __( 'No installments in foreign currency.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.ForeignCurrencyNotAvailableForThisDealer":
                    $errorOutput = __( 'No installments in foreign currency.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.PaymentMustBeAuthorization":
                    $errorOutput = __( 'Payment Must Be Authorization.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.AuthorizationForbiddenForThisDealer":
                    $errorOutput = __( 'Authorization Forbidden For This Dealer.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.PoolPaymentNotAvailableForDealer":
                    $errorOutput = __( 'Pool Payment Not Available For This Dealer.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.PoolPaymentRequiredForDealer":
                    $errorOutput = __( 'Pool Payment Required For This Dealer.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.TokenizationNotAvailableForDealer":
                    $errorOutput = __( 'Tokenization Not Available For This Dealer.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.CardTokenCannotUseWithSaveCard":
                    $errorOutput = __( 'Card Token Cannot Use With Save Card.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.CardTokenNotFound":
                    $errorOutput = __( 'Card Token Not Found.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.OnlyCardTokenOrCardNumber":
                    $errorOutput = __( 'Only Card Token Or Card Number.' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.ChannelPermissionNotAvailable":
                    $errorOutput = __( 'Channel Permission Not Available.' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.IpAddressNotAllowed":
                    $errorOutput = __( 'IP address is not available for this operation.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.VirtualPosNotAvailable":
                    $errorOutput = __( 'Virtual Pos Not Available.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForVirtualPos":
                    $errorOutput = __( 'The number of installments is not allowed.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForDealer":
                    $errorOutput = __( 'The number of installments is invalid.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.DealerCommissionRateNotFound":
                    $errorOutput = __( 'Dealer Commission Rate Not Found.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.DealerGroupCommissionRateNotFound":
                    $errorOutput = __( 'Dealer Group Commission Rate Not Found.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidSubMerchantName":
                    $errorOutput = __( 'Invalid Sub Merchant Name.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidUnitPrice":
                    $errorOutput = __( 'Invalid Unit Price.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidQuantityValue":
                    $errorOutput = __( 'Invalid Quantity Value.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.BasketAmountIsNotEqualPaymentAmount":
                    $errorOutput = __( 'Basket Amount Is Not Equal Payment Amount.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.BasketProductNotFoundInYourProductList":
                    $errorOutput = __( 'Basket Product Not Found In Your Product List.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.MustBeOneOfDealerProductIdOrProductCode":
                    $errorOutput = __( 'Must Be One Of Dealer Product Id Or Product Code.', 'moka-woocommerce' );
                    break;
                case "Limit is insufficient":
                    $errorOutput = __( 'Your card limit is insufficient.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.RequiredFields.AmountRequired":
                    $errorOutput = __( 'It is mandatory to send the transaction amount.', 'moka-woocommerce' );
                    break;
                case "PaymentDealer.RequiredFields.ExpMonthRequired":
                    $errorOutput = __( 'Sending the expiry date is mandatory.', 'moka-woocommerce' );
                    break;
                default:
                    $errorOutput = __( 'An unexpected error occurred.', 'moka-woocommerce' );
            }

            return $errorOutput;
        }

        /**
         * Error Codes
         *
         * @param [string] $code 
         */
        private function errorMessagesWithErrorCodes($code)
        {
            $codes = [
                '000' => __('General Error','moka-woocommerce'),
                '001' => __('Failed to obtain cardholder approval','moka-woocommerce'),
                '002' => __('Your card has insufficient limit.','moka-woocommerce'),
                '003' => __('Credit card number not in valid format','moka-woocommerce'),
                '004' => __('General rejection','moka-woocommerce'),
                '005' => __('Transaction not open to cardholder','moka-woocommerce'),
                '006' => __('Card expiry date incorrect','moka-woocommerce'),
                '007' => __('Invalid transaction','moka-woocommerce'),
                '008' => __('Failed to connect to bank','moka-woocommerce'),
                '009' => __('Undefined error','moka-woocommerce'),
                '010' => __('Bank SSL error','moka-woocommerce'),
                '011' => __('Call your bank for manual confirmation','moka-woocommerce'),
                '012' => __('Card details incorrect','moka-woocommerce'),
                '013' => __('Your card does not support 3D secure','moka-woocommerce'),
                '014' => __('Invalid account number','moka-woocommerce'),
                '015' => __('Invalid CVV','moka-woocommerce'),
                '016' => __('Approval mechanism not available','moka-woocommerce'),
                '017' => __('System error','moka-woocommerce'),
                '018' => __('Stolen card','moka-woocommerce'),
                '019' => __('Lost card','moka-woocommerce'),
                '020' => __('Restricted card','moka-woocommerce'),
                '021' => __('Timeout','moka-woocommerce'),
                '022' => __('Invalid merchant','moka-woocommerce'),
                '023' => __('Fake approval','moka-woocommerce'),
                '024' => __('3D confirmation received but money could not be withdrawn from the part','moka-woocommerce'),
                '025' => __('3D authorisation error','moka-woocommerce'),
                '026' => __('Bank or Card does not support 3D secure','moka-woocommerce'),
                '027' => __('User is not authorised to perform this operation','moka-woocommerce'),
                '028' => __('Fraud possibility','moka-woocommerce'),
                '029' => __('Your card is closed to internet purchases','moka-woocommerce'),                
                '030' => __('Bank Declined Transaction','moka-woocommerce'),
            ];

            if(in_array($code, array_keys($codes))) {
                return $codes[$code];
            } else {
                return __('An unexpected error occurred','moka-woocommerce');
            }
        }

        /**
         * Save Installment Rates to DB
         *
         * @return void
         */
        private function __saveRates()
        {
            $optionKey = 'woocommerce_mokapay-installments';

            if(data_get($_POST, $optionKey))
            {  
                return $this->optimisthubMoka->setInstallments($_POST[$optionKey]);
            }
        }

        /**
         * Fetch User Information
         *
         * @return void
         */
        private function getUserInformationData()
        {
            return version_compare(get_bloginfo('version'), '4.5', '>=') ? wp_get_current_user() : get_currentuserinfo();
        }

        /**
         * Store All History 
         *
         * @param [type] $params
         * @return void
         */
        private function saveRecord($params)
        {
            global $wpdb;
            $tableName = $wpdb->prefix . 'moka_transactions';
            return $wpdb->insert($tableName, $params);
        }

        /**
         * Save Returned Hash
         *
         * @param [type] $params
         * @return void
         */
        private function saveHash($params)
        {
            global $wpdb;
            $tableName = $wpdb->prefix . 'moka_transactions_hash';
            return $wpdb->insert($tableName, $params);            
        }

        /**
         * Save Subscriptions Data for admin panel
         *
         * @param [type] $params
         * @return void
         */
        private function saveSubscription($params)
        {
            global $wpdb;
            $tableName = $wpdb->prefix . 'moka_subscriptions';
            return $wpdb->insert($tableName, $params);            
        }

        /**
         * Fetch last hash
         *
         * @param [type] $params
         * @return void
         */
        private function getHash($params)
        {
            global $wpdb;
            $orderId = data_get($params, 'orderId');
            $tableName = $wpdb->prefix . 'moka_transactions_hash';
            return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tableName WHERE id_order = %d ORDER BY id DESC", $orderId ), ARRAY_A );       
        }

        /**
         * Generate Default Order Prefix
         *
         * @return void
         */
        private function generateDefaultOrderPrefix()
        {
            return substr( strtoupper( hash('sha256', str_replace( ['http://', 'https://'], '', get_bloginfo('wpurl') ) ) ), 0, 8 );
        }

        /**
         * Format Order Items
         *
         * @return array
         */
        private function formatBaksetProducts( $orderItems )
        {
            $output = [];
            foreach ($orderItems  as $orderItem )
            {   
                if($orderItem->get_type() == 'line_item'){
                    $orderData = $orderItem->get_data();
                    $output[] = [
                        'ProductId' => ( (isset($orderData['variation_id']) && intval($orderData['variation_id'])>0) ?
                        $orderData['variation_id'] : $orderData['product_id'] ),
                        'ProductCode' => $orderData['name'],
                        'UnitPrice' => self::moka_number_format( floatval($orderData['total']) + floatval($orderData['total_tax']) ),
                        'Quantity' => $orderData['quantity'],
                    ];
                }
            } 

            return $output;
        }

        /**
         * Format Card expriyDate
         *
         * @param [type] $str
         * @return void
         */
        private function formatExpiryDate($str)
        {
            $output = $str;
            $lenght = strlen($str);
            if($lenght>=3)
            {
                $output = substr($str,-2);
            }

            return '20'.$output;
        }

        /**
         * User Role Change Plugin Support
         *
         * @param [type] $orderId 
         */
        private function userRoleChangerSupport($orderId)
        {
            if(class_exists('DfxWooRoleChanger'))
            {  
                $roleChanger = DfxWooRoleChanger::get_instance();
                $roleChanger->role_assignment($orderId); 
            }

        }

        /**
         * Get WC order statuses
         * @since 2.9.5
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return void
         */
        private function getOrderStatuses()
        {
            return wc_get_order_statuses();
        }

        /**
         * Set or get order token.
         *
         * @param [array] $params
         * @since 3.0
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return object
         */
        private function fetchCardToken($params)
        {    
            if(metadata_exists('shop_order', data_get($params, 'orderId'), '__card_token')) {
                update_post_meta(data_get($params, 'OrderId'), '__card_token', data_get($params, 'CardToken'));
                return get_post_meta(data_get($params, 'OrderId'), '__card_token', true);
            } else {
                update_post_meta(data_get($params, 'OrderId'), '__card_token', data_get($params, 'CardToken'));
                return get_post_meta(data_get($params, 'OrderId'), '__card_token', true);
            }
        }

        /**
         * Store Dealer Information to Order.
         *
         * @param [type] $array
         * @since 3.0
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return void
         */
        private function setCustomerDataToOrderMeta($params)
        {
            update_post_meta(data_get($params, 'OrderId'), '__moka_customer', $params);
        }

        /**
         * Format Subs. Record.
         *
         * @param [array] $params
         * @since 3.0
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return array
         */
        private function formatSubsRecord($params)
        {  
            if( isset( $params['CvcNumber'] ) ) {
                unset($params['CvcNumber']);
            }
            $params['hideCardNumber'] = true;
            return $params;
        }

        /**
         * Format requests for logging.
         *
         * @param [array] $param
         * @since 3.0
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return string
         */
        private function formatOrderDetailsForLog($param)
        { 
            
            if( isset( $params['CvcNumber'] ) ) {
                unset($params['CvcNumber']);
            }
            if( isset( $params['ExpYear'] ) ) {
                unset($params['ExpYear']);
            }
            if( isset( $params['ExpMonth'] ) ) {
                unset($params['ExpMonth']);
            }
            if( isset( $params['CustomerDetails'] ) ) {
                if( isset( $params['CustomerDetails']['ExpYear'] ) ) {
                    unset($params['CustomerDetails']['ExpYear']);
                }
                if( isset( $params['CustomerDetails']['ExpYear'] ) ) {
                    unset($params['CustomerDetails']['ExpMonth']);
                }
            }

            if( data_get($param, 'hideCardNumber') ) {
                if(data_get($param, 'CardNumber')) {
                    $param['CardNumber'] = '**** **** **** '.substr($param['CardNumber'], -4);
                } 
                if(data_get($param, 'CustomerDetails.CardNumber')) {
                    $param['CustomerDetails']['CardNumber'] = '**** **** **** '.substr($param['CustomerDetails']['CardNumber'], -4);
                }
            }
           
            return json_encode($param, true);
        } 

        /**
         * Does order has subscription product
         * @param [object] $orderItems
         * @since 3.0
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return boolean
         */
        private function isOrderHasSubscriptionProduct($orderItems)
        {
            $hasSubscription = false;

            if($orderItems) {
                foreach ( $orderItems as $itemId => $item ) {
                    $productId = $item->get_product_id();
                    $product   = wc_get_product( $productId );
                    if($product->get_type() == 'subscription') {
                        $hasSubscription = true;
                    }
                }
            } 

            return $hasSubscription;
        }

        /**
         * Calculate product current / next subscription period 
         *
         * @param [array] $orderItems
         * @return array
         */
        private function getSubscriptionProductPeriod($orderItems)
        {
            $period = null;

            if($orderItems) {
                foreach ( $orderItems as $itemId => $item )  {
                    $productId = $item->get_product_id();
                    $product   = wc_get_product( $productId );
                    if($product->get_type() == 'subscription') {
                        $__data = get_post_meta($productId);
                        $__per  = data_get($__data, '_period_per.0', null);
                        $__in   = data_get($__data, '_period_in.0', null);
 
                        $currentTime = Carbon::parse(current_datetime()->format('Y-m-d H:i:s'));

                        $nextTry = $currentTime::now()->add($__per, $__in); 

                        $period = [
                            'current_time'  => Carbon::parse($currentTime)->format('Y-m-d H:i:s'),
                            'next_try'      => Carbon::parse($nextTry)->format('Y-m-d H:i:s'),
                            'period_string' => $__per.' '.$__in,
                        ];  
                    }
                }
            } 
 
            return $period;
        }

        /**
         * Fetch customer id from order
         *
         * @return void
         */
        private function getOrderCustomerId($orderId)
        {
            $order      = wc_get_order($orderId);
            $userId     = $order->get_user_id();
            return $userId;
        }

        private function moka_number_format($price, $decimal = 2){
            $_price = floatval($price);
            $_price = number_format( $_price, ($decimal + 1), '.', '');
            $_price = substr($_price, 0, -1);
            return $_price;
        }

        private function prepare_installment($bankCode, $bankGroup) {
            $installments = self::fetchInstallment();
            if($bankGroup && $installments) { 
                $bankCode = mb_strtolower($bankCode); 
                $bankGroup = mb_strtolower($bankGroup); 
                foreach($installments as $perInstallment)
                {
                    if($perInstallment['groupName'] == $bankGroup)
                    {
                        return $perInstallment['rates'];
                    }
                } 
            } 
            return false;
        }

        private function fetchInstallment()
        { 
            $mokapay_settings = get_option('woocommerce_mokapay_settings');
            $isInstallmentsActive = data_get($mokapay_settings, 'installment', 'yes') === 'yes';
            if( $isInstallmentsActive ) {   
                $installments = get_option('woocommerce_mokapay-installments') ? get_option('woocommerce_mokapay-installments') : self::generateDynamicInstallmentData();
                
                return $installments;
            } 
        }

        private function generateDynamicInstallmentData()
        {
            $list = $this->optimisthubMoka->getInstallments();
            $list = data_get($list, 'CommissionList');

            if( !$list ) {
                return false;
            }
            return $this->optimisthubMoka->formatInstallmentResponse($list);
        }

        private function calculateMaxInstallment($orderItems, $selectedInstallment){
            if( $selectedInstallment > 1 ){
                if( $this->limitInstallmentByProduct ) {
                    if( $orderItems && !empty($orderItems) ){
                        foreach($orderItems as $orderItem){
                            $orderItemData = $orderItem->get_data();
                            if(
                                $orderItem->get_type() == 'line_item' && 
                                isset($orderItemData['product_id']) && intval($orderItemData['product_id'])>0
                            ){
                                $product_limitInstallment = get_post_meta($orderItemData['product_id'], '_limitInstallment',
                                true);
                                if(
                                    $product_limitInstallment && 
                                    intval($product_limitInstallment)>0 &&
                                    $selectedInstallment > intval($product_limitInstallment)
                                ){
                                    $selectedInstallment = intval($product_limitInstallment);
                                }
                            }
                        }
                    }
                }

                if(intval($selectedInstallment) > intval($this->limitInstallment)){
                    $selectedInstallment = intval($this->limitInstallment);
                }
            }

            return $selectedInstallment;
        }
        
    }
}

initOptimisthubGatewayClass();