<?php

add_action( 'plugins_loaded', 'initOptimisthubGatewayClass' );

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
            $this->testmode = 'yes' === $this->get_option( 'testmode' );
            $this->installment = 'yes' === $this->get_option( 'installment' );
            $this->enable_3d = 'yes' === $this->get_option( 'enable_3d' );
            $this->company_code = $this->get_option( 'company_code' );
            $this->company_name = $this->get_option( 'company_name' );
            $this->api_username = $this->get_option( 'api_username' );
            $this->api_password = $this->get_option( 'api_password' );
            $this->order_prefix = $this->get_option( 'order_prefix' );
            $this->order_status = $this->get_option( 'order_status' );
            $this->subscriptions = $this->get_option( 'subscriptions' );
            $this->isSubscriptionsEnabled = 'yes' == $this->subscriptions;
            
            $this->optimisthubMoka = new MokaPayment();
            $this->maxInstallment = range(1,12);
            $this->userInformation = self::getUserInformationData();

            $this->assets = $this->assetDir();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] ); 
            add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] ); 
            add_filter( 'woocommerce_credit_card_form_fields' , [$this,'payment_form_fields'] , 10, 2 ); 
            add_action( 'admin_head', [$this, 'admin_css']);   
            add_action( 'woocommerce_receipt_'.$this->id, [$this, 'receipt_page']);  
            
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
                'subscriptions' => [
                    'title'       => __( 'Subscription', 'moka-woocommerce' ) .' -  '. __( 'Enable/Disable', 'moka-woocommerce' ),
                    'label'       => __('Enable subscription ?', 'moka-woocommerce' ),
                    'type'        => 'checkbox',
                    'description' => __('It allows you to sell products via subscription method on your site.' , 'moka-woocommerce'),
                    'default'     => 'no',
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
                wp_register_style( 'moka-pay-admin',  plugins_url( 'moka-woocommerce-master/assets/moka-admin.css' ) , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
                wp_enqueue_style ( 'moka-pay-admin' );
            } 

            wp_enqueue_script( 'moka-pay-corejs', plugins_url( 'moka-woocommerce-master/assets/moka-admin.js' ), false, OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_localize_script( 'moka-pay-corejs', 'moka_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
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
                        <img src="<?php echo plugins_url( 'moka-woocommerce-master/assets/img/mokapos.png' ); ?>" alt="">
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

            if ( get_query_var('order-pay') ) {
                $order = wc_get_order(get_query_var('order-pay'));
                $total = $order->get_total();  
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
                            id="'.$payment_id.'-current-order-total" 
                            class="current-order-total" 
                            type="hidden"  
                            value="'.$total.'"
                            name="' .$payment_id. '-current-order-total" 
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

            echo '<div id="ajaxify-installment-table" class="installment-table"></div>'; 
            if($this->isSubscriptionsEnabled)
            {
                echo '<div class="mokapay-save-card-info-message">
                <p>'.__('Your card information that you have added during the payment will be kept by Moka with the assurance of Moka. Your next subscription payment will be taken with this card.', 'moka-woocommerce').'</p>
                <img src="'.$this->assets.'/img/Logo-mokapos.svg" />
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
            wp_enqueue_script( 'moka-pay-corejs', $this->assets .  'moka.js' , false, OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_register_style( 'moka-pay-card_css', $this->assets. 'moka.css' , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
            wp_enqueue_style ( 'moka-pay-card_css' );
            wp_localize_script( 'moka-pay-corejs', 'moka_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
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
                wc_add_notice(  __( "<strong>Card holder</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }

            if( empty(data_get($postedData, $this->id.'-card-number'))) 
            {
                wc_add_notice(  __( "<strong>Card Number</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }

            if( empty(data_get($postedData, $this->id.'-card-expiry') )) 
            {
                wc_add_notice(  __( "<strong>Card Expiry</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }
            if( empty(data_get($postedData, $this->id.'-card-cvc'))) 
            {
                wc_add_notice(  __( "<strong>Card CVC</strong> is required.", 'moka-woocommerce' ), 'error' );
                return false;
            }

            if($this->installment)
            {
                if( empty(data_get($postedData, $this->id.'-installment'))) 
                {
                    wc_add_notice(  __( "<strong>Installment</strong> is required.", 'moka-woocommerce' ), 'error' );
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
            $hasSubscription    = $this->isOrderHasSubscriptionProduct($orderItems);
 
            if($order->get_total() < $currentTotal)
            {
                self::saveComissionDecision( [
                    'orderId' => $orderId,
                    'orderTotal' => $order->get_total(),
                    'currentOrderTotal' => $currentTotal,

                ]); 

                $order->add_order_note( 
                    sprintf( 
                        __( 'Sipariş Tutarında, taksitli alışveriş talep edildiğinden dolayı güncelleme yapıldı. %s', 'moka-woocommerce' ), 
                        $currentTotal. ' '.$currency. ' ['.$installmentNumber.' Taksit]'
                    ), 
                    false,
                    true
                );  
            } 

            if($this->isSubscriptionsEnabled && $hasSubscription)
            { 
 
                $userId     = get_current_user_id(); 
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

                self::saveSubscription(
                    [
                        'order_id'      => $orderId,
                        'order_amount'  => $currentTotal,
                        'order_details' => $this->formatOrderDetailsForLog($saveSubsRecord), 
                        'subscription_status'  => '0',
                        'subscription_period'  => '0',
                        'subscription_next_try'  => '0',
                        'user_id'  => $userId,
                        'optimist_id'   => data_get($orderDetails,'OtherTrxCode'), 
                        'created_at'    => date('Y-m-d H:i:s'),      
                    ]
                );
            } 
  
            dd('1');
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
                    'created_at'    => date('Y-m-d H:i:s'),      
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
                'created_at'    => date('Y-m-d H:i:s'), 
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
                $recordParams['result_message'] = 'Kart Bilgileri Başarılı Bir Şekilde Doğrulandı.';
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
                'created_at'    => date('Y-m-d H:i:s'), 
            ];

            $order = new WC_order($orderId);
            $isCompleted = self::validatePayment(data_get($fetchData, 'id_hash'));

            if($isCompleted)
            {
                $total = data_get($orderDetails,'Amount');
                $currency = data_get($orderDetails,'Currency'); 

                $order->update_status('processing', __('Payment is processing via Moka Pay.', 'moka-woocommerce'));
                $order->add_order_note( __('Hey, the order is paid by Moka Pay!','moka-woocommerce').'<br> Tutar : '.$total.' '.$currency , false,false );
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
                
                wp_redirect($redirectUrl);
                exit;

            } else {

                $order = new WC_order($orderId);
                $order->update_status('pending', __('Waiting for user payment.', 'moka-woocommerce'));

                if(isset($_POST) && data_get($_POST, 'resultCode') && data_get($_POST, 'hashValue'))
                { 
                    wc_add_notice('Ödemeniz tahsil edilemedi. Lütfen yeniden deneyiniz.', 'notice' );
                    echo '<div class="woocommerce-notices-wrapper"><ul class="woocommerce-error" role="alert"><li class="">'.self::errorMessagesWithErrorCodes(data_get($_POST, 'resultCode')).' : <a class="moka-continue-checkout" href="'.wc_get_checkout_url().'">'.get_the_title(wc_get_page_id('checkout')).'</a></li></ul></div>'; 
                    $recordParams['result_message'] = self::errorMessagesWithErrorCodes(data_get($_POST, 'resultCode'));
                    self::saveRecord($recordParams);  

                } else {
                    wc_add_notice('Ödemeniz tahsil edilemedi. Lütfen yeniden deneyiniz.', 'notice' );
                    $recordParams['result_message'] = __('Waiting for user payment.', 'moka-woocommerce');
                    self::saveRecord($recordParams);  
                }
            }
            
        }
            
        public function webhook() 
        {
        }

        /**
         * Check isOption Has on Db.
         *
         * @param [type] $name
         * @param boolean $site_wide
         * @return void
         */
        private function option_exists($name, $site_wide=false){
            global $wpdb; 
            return $wpdb->query("SELECT * FROM ". ($site_wide ? $wpdb->base_prefix : $wpdb->prefix). "options WHERE option_name ='$name' LIMIT 1");
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
 
            $orderIdTrx = $orderId;
            $orderId    = $orderId.'-'.time();
            $expriyDate = self::formatExperyDate(data_get($postData, $this->id.'-card-expiry'));
            $rates      = data_get($postData, $this->id.'-installment-rates'); 
            $rates      = urldecode($rates);
            $rates      = json_decode($rates);
            
            $selectedInstallment    = data_get($postData, $this->id.'-installment');
            $currentComission       = data_get($rates, $selectedInstallment.'.value'); 
            $getAmount = $order->get_total();
            $customerId = $order->get_user_id();

            $orderData = [
                'CardHolderFullName'    => (string) data_get($postData, $this->id.'-name-oncard'),
                'CardNumber'            => (string) self::formatCartNumber(data_get($postData, $this->id.'-card-number')),
                'ExpMonth'              => (string) data_get($expriyDate,'month' ),
                'ExpYear'               => (string) self::formatExpiryDate(data_get($expriyDate,'year' )),
                'CvcNumber'             => (string) data_get($postData, $this->id.'-card-cvc'),
                'Amount'                => (string) self::calculateComissionRate($getAmount,$currentComission),
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
                    'CustomerCode' => (string) $this->company_code.'-OPT-'.$customerId,
                    'FirstName' => (string) $order->get_billing_first_name(),
                    'LastName' => (string) $order->get_billing_last_name(),
                    'Gender' => '',
                    'BirthDate' => '',
                    'GsmNumber' => (string) $order->get_billing_phone(),
                    'Email' => (string) $order->get_billing_email(),
                    'Address' => (string) (string) $order->get_billing_address_1(). ' ' .$order->get_billing_address_2(). ' ' .$order->get_billing_city(),
                    'CardHolderFullName'    => (string) data_get($postData, $this->id.'-name-oncard'),
                    'ExpMonth'              => (string) data_get($expriyDate,'month' ),
                    'ExpYear'               => (string) self::formatExpiryDate(data_get($expriyDate,'year' )),
                    'CardNumber'            => (string) self::formatCartNumber(data_get($postData, $this->id.'-card-number')),
                    'CardName'              => (string) $order->get_billing_first_name(). '\'s saved card',
                    'MokaStores'            => [
                        'orderData'             => $order,
                        'customerId'            => $customerId,
                    ]
                ]

                // TODO : Basket Product Details
                //'BasketProduct'         => self::formatBaksetProducts($order), 
            ]; 
             
            return $orderData;

        }

        /**
         * User IP Adress
         *
         * @return void
         */
        private function getUserIp()
        {
            if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                    $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
            $client  = @$_SERVER['HTTP_CLIENT_IP'];
            $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
            $remote  = $_SERVER['REMOTE_ADDR'];

            if(filter_var($client, FILTER_VALIDATE_IP))
            {
                $ip = $client;
            }
            elseif(filter_var($forward, FILTER_VALIDATE_IP))
            {
                $ip = $forward;
            }
            else
            {
                $ip = $remote;
            }

            return $ip;
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
            $total = ( ( ($total*$percent)/100) + $total); 
            return number_format($total,2,'.', '');
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
            
            $orderFee = new stdClass();
            $orderFee->id = $this->id.'-installment-fee';
            $orderFee->name = __('Installment Fee', 'moka-woocommerce');
            $orderFee->amount = $installmentFee;
            $orderFee->taxable = false;
            $orderFee->tax = 0;
            $orderFee->tax_data = array();
            $orderFee->tax_class = '';
            
            $order->add_fee($orderFee);
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
                    $errorOutput = "Hatalı hash bilgisi";
                    break;
                case "Limit is insufficient":
                    $errorOutput = "Kart limitiniz yetersiz.";
                    break;
                case "PaymentDealer.RequiredFields.AmountRequired":
                    $errorOutput = "Tutar Göndermek Zorunludur.";
                    break;
                case "PaymentDealer.RequiredFields.ExpMonthRequired":
                    $errorOutput = "Son Kullanım Tarihi Gönderme Zorunludur.";
                    break;
                case "PaymentDealer.CheckPaymentDealerAuthentication.InvalidAccount":
                    $errorOutput = "Böyle bir bayi bulunamadı";
                    break;
                case "PaymentDealer.CheckPaymentDealerAuthentication.VirtualPosNotFound":
                    $errorOutput = "Bu bayi için sanal pos tanımı yapılmamış";
                    break;
                case "PaymentDealer.CheckDealerPaymentLimits.DailyDealerLimitExceeded":
                    $errorOutput = "Bayi için tanımlı günlük limitlerden herhangi biri aşıldı";
                    break;
                case "PaymentDealer.CheckDealerPaymentLimits.DailyCardLimitExceeded":
                    $errorOutput = "Gün içinde bu kart kullanılarak daha fazla işlem yapılamaz";
                case "PaymentDealer.CheckCardInfo.InvalidCardInfo":
                    $errorOutput = "Kart bilgilerinde hata var lütfen doğru bilgileri işleyiniz";
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InstallmentNotAvailableForForeignCurrencyTransaction":
                    $errorOutput = "Yabancı para ile taksit yapılamaz";
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForDealer":
                    $errorOutput = "Bu taksit sayısı bu bayi için yapılamaz";
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.InvalidInstallmentNumber":
                    $errorOutput = "Taksit sayısı 2 ile 9 arasıdır";
                    break;
                case "PaymentDealer.DoDirectPayment3dRequest.ThisInstallmentNumberNotAvailableForVirtualPos":
                    $errorOutput = "Sanal Pos bu taksit sayısına izin vermiyor";
                    break;
                default:
                    $errorOutput = "Beklenmeyen bir hata oluştu";
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
            $codes =  [
                '000' => 'Genel Hata',
                '001' => 'Kart Sahibi Onayı Alınamadı',
                '002' => 'Kartınızın limiti yetersiz.',
                '003' => 'Kredi Kartı Numarası Geçerli Formatta Değil',
                '004' => 'Genel Red',
                '005' => 'Kart Sahibine Açık Olmayan İşlem',
                '006' => 'Kartın Son Kullanma Tarihi Hatali',
                '007' => 'Geçersiz İşlem',
                '008' => 'Bankaya Bağlanılamadı',
                '009' => 'Tanımsız Hata Kodu',
                '010' => 'Banka SSL Hatası',
                '011' => 'Manual Onay İçin Bankayı Arayınız',
                '012' => 'Kart Bilgileri Hatalı - Kart No veya CVV2',
                '013' => 'Visa MC Dışındaki Kartlar 3D Secure Desteklemiyor',
                '014' => 'Geçersiz Hesap Numarası',
                '015' => 'Geçersiz CVV',
                '016' => 'Onay Mekanizması Mevcut Değil',
                '017' => 'Sistem Hatası',
                '018' => 'Çalıntı Kart',
                '019' => 'Kayıp Kart',
                '020' => 'Kısıtlı Kart',
                '021' => 'Zaman Aşımı',
                '022' => 'Geçersiz İşyeri',
                '023' => 'Sahte Onay',
                '024' => '3D Onayı Alındı Ancak Para Karttan Çekilemedi',
                '025' => '3D Onay Alma Hatası',
                '026' => 'Kart Sahibi Banka veya Kart 3D-Secure Üyesi Değil',
                '027' => 'Kullanıcı Bu İşlemi Yapmaya Yetkili Değil',
                '028' => 'Fraud Olasılığı',
                '029' => 'Kartınız e-ticaret İşlemlerine Kapalıdır',                
            ];

            if(in_array($code, array_keys($codes))) {
                return $codes[$code];
            } else {
                return 'Beklenmeyen bir hata oluştu';
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
                if (self::option_exists($optionKey))
                {
                    delete_option($optionKey); 
                }

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
            return substr(strtoupper(hash('sha256',str_replace(['http://', 'https://'],'  ', get_bloginfo('wpurl') ))), 0, 8);
        }

        /**
         * Format Order Items
         *
         * Reference : https://stackoverflow.com/a/40715347/1137492
         * @return array
         */
        private function formatBaksetProducts( $order )
        {
            $output = [];

            if($order)
            {
                foreach ($order->get_items() as $item_id => $item ) 
                {
                    $product = $item->get_product(); 
                    $productName = $item->get_name(); 
                    $itemQuantity = $item->get_quantity();
                    $itemTotal     = $item->get_total(); 
                    $output[] = [
                        //'ProductId' => $product->get_id(),
                        'ProductCode' => $productName,
                        'UnitPrice' => $itemTotal,
                        'Quantity' => $itemQuantity,
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
         * Define plugin asset files directory
         * @since 3.0
         * @copyright 2022 Optimisthub
         * @author Fatih Toprak 
         * @return void
         */
        private function assetDir()
        {
            return str_replace('/core/library/', '/assets/' , plugin_dir_url( __FILE__ ));
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
            update_post_meta(data_get($params, 'OrderId'), '__moka_customer', json_encode($params));
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
            unset($params['CvcNumber']);
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
            unset($params['CvcNumber']);
            unset($params['ExpYear']);
            if(data_get($param, 'CardNumber'))
            {
                $param['CardNumber'] = '**** **** **** '.substr($param['CardNumber'], -4);
            } 
           
            return json_encode($param,true);
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
            $hasSubscription = null;

            if($orderItems)
            {
                foreach ( $orderItems as $itemId => $item ) 
                {
                    $productId = $item->get_product_id();
                    $product   = wc_get_product( $productId );
                    $type      = $product->get_type(); 
                    if($type === 'subscription')
                    {
                        $hasSubscription = true;
                    }
                }
            } 

            return $hasSubscription;
        }
        
    }
}
