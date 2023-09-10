<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Carbon\Carbon;

/**
 * Moka POS Subscriptions Add On
 * @since 3.0
 * @copyright 2022 Optimisthub
 * @author Fatih Toprak 
 */

class MokaSubscription
{

    public $mokaOptions;
    public $isSubscriptionsEnabled;
    public $productType = 'subscription';

    public function __construct()
    {
        $this->mokaOptions = get_option('woocommerce_mokapay_settings');
        $this->isSubscriptionsEnabled = 'yes' == data_get($this->mokaOptions, 'subscriptions');
         #$this->optimisthubMokaGateway = new OptimistHub_Moka_Gateway();
        
        register_activation_hook( __FILE__, [$this, 'addProductTypeTaxonomy' ] );
        
        // Subscription Filters and Actions
        add_action( 'woocommerce_loaded',[$this, 'registerSubscriptionProductType' ] );
        add_filter( 'product_type_selector',[$this, 'addProductTyepSelectorOnBackend' ] );
        add_action( 'woocommerce_product_options_general_product_data', [$this, 'displaySubscriptionProductMetas']);
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'registerSubscriptionProductTab' ), 0 );
        add_action( 'woocommerce_product_data_panels', array( $this, 'registerSubscriptionProductTabContent' ) );
        add_action( 'woocommerce_process_product_meta_subscription', array( $this, 'saveSubscriptionSettings' ) );
        add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'changeAddToCartText'], 20, 2 ); 
        add_action( 'woocommerce_single_product_summary', [$this, 'addToCartButtonProductSummary'], 20 );
        add_filter( 'woocommerce_order_button_text', [$this, 'changePlaceOrderTextForSubscription'] );

        // Display custom cart item meta data (in cart and checkout)
        add_filter( 'woocommerce_get_item_data', [$this,'displayCartItemCustomMetaData'], 10, 2 ); 

        add_action( 'woocommerce_new_product', [$this,'syncOnProductSave'], 10, 1 );
        add_action( 'woocommerce_update_product', [$this,'syncOnProductSave'], 10, 1 );

        // My Account Section
        add_filter( 'woocommerce_account_menu_items', [$this, 'setSubscriptionsPageLink'], 40 );
        add_action( 'init', [$this, 'addSubscriptionPermalink'] );
        add_action( 'woocommerce_account_'.$this->productType.'_endpoint',[$this, 'addSubscriptionPermalinkEndpoint'] );

        // Admin Menus
        add_action( 'admin_menu', [$this, 'addSubscriptionAdminMenuLink']);

        // Cron Jobs
        add_action( 'init', [$this, 'triggerSubscriptionPayments'] );
        add_action( 'moka_subscriptions_recurring_payments_cron_job', [$this, 'runSubscriptionPayments']);

        add_action( 'init', [$this, 'mybeAddColumnIfIsNotExists'] );


    }

    /**
     * Sync product data and schedule for subscriptions on product save.
     *
     * @param [type] $productId
     * @return void
     */
    public function syncOnProductSave($productId)
    {
        $metas      = get_post_meta($productId);
        $product    = wc_get_product( $productId );
        $type       = $product->get_type(); 
        $price      = data_get($metas, '_subscription_price.0');
 
        
        if($type == $this->productType) {
            update_post_meta($productId,'_stock_status', 'instock');
            update_post_meta($productId,'_sale_price', $price);
            update_post_meta($productId,'_price', $price);
            update_post_meta($productId,'_sold_individually', 'yes');
        }
    }

    /**
     * Get Subscription products.
     *
     * @param [type] $param
     * @return array
     */
    public function getSubscriptionProducts( $productId )
    {
    }

    /**
     * Format Subscription products.
     *
     * @param [type] $param
     * @return array
     */
    public function formatSubscriptionProductsForSchedule( $param )
    {
    }

    /**
     * addOrUpdateDealerCustomer function
     *
     * @param [int] $userId
     * @param [array] $metaData
     * @return void
     */
    public function addOrUpdateDealerCustomer($userId, $metaData)
    {
        return update_user_meta( $userId, '_moka_dealer_customer', $metaData);

    }

    /**
     * Register Subscription Product Type For WooCommerce
     *
     * @return void
     */
    public function registerSubscriptionProductType()
    {
        if($this->isSubscriptionsEnabled)
        {
            require_once __DIR__.'/Moka_Subscriptions_Product.php';
        }
    }

    /**
     * Add registered product type if subscription product type option avaliables
     *
     * @param [type] $types
     * @return void
     */
    public function addProductTyepSelectorOnBackend($types)
    {
        if($this->isSubscriptionsEnabled)
        $types[$this->productType] = __( 'Subscriptions', 'moka-woocommerce' );
        return $types;
    }

    /**
     * Add product type tax.
     *
     * @return void
     */
    public function addProductTypeTaxonomy()
    {
        // If there is no advanced product type taxonomy, add it.
        if ( ! get_term_by( 'slug', 'advanced', 'product_type' ) ) {
            wp_insert_term( 'advanced', 'product_type' );
        }
    }
    
    /**
     * Display tabs part on admin panel.
     *
     * @return void
     */
    public function displaySubscriptionProductMetas()
    {
        echo '<div class="options_group show_if_'.$this->productType.' clear"></div>';
    }
 
    /**
     * Register subscription product tab
     *
     * @param [array] $tabs
     * @return void
     */
    public function registerSubscriptionProductTab($tabs)
    {
        if($this->isSubscriptionsEnabled)
        {
            $tabs['general']['class'] = 'hide_if_grouped hide_if_'.$this->productType;
            $tabData[$this->productType] = [
                'label'    => __( 'Subscription Features', 'moka-woocommerce' ),
                'target' => $this->productType.'_type_product_options',
                'class'  => 'show_if_'.$this->productType,
            ]; 
                    
            $tabs = array_merge($tabData, $tabs);
        }
        return $tabs;
    }

    /**
     * Register subscription product tab's content.
     *
     * @param [string] $tabs
     * @return void
     */
    public function registerSubscriptionProductTabContent($tabs)
    {
        global $product_object;
        ?>
        <div id='<?php echo $this->productType; ?>_type_product_options' class='panel woocommerce_options_panel hidden'>
          <div class='options_group'>
            <?php
                woocommerce_wp_select(
                    [
                        'id'          => '_period_per',
                        'class'       => 'select short',
                        'label'       => __( 'Subscription Period', 'moka-woocommerce' ),
                        'value'       => $product_object->get_meta( '_period_per', true ),
                        'options'     => [
                            '1' => 'Her',
                            '2' => 'Her 2.', 
                            '3' => 'Her 3.', 
                            '4' => 'Her 4.', 
                        ],
                        'default'     => '',
                        'placeholder' => __( 'Period', 'moka-woocommerce' ),
                    ]
                );
                woocommerce_wp_select(
                    [
                        'id'          => '_period_in',
                        'class'       => 'select short',
                        'label'       => '',
                        'value'       => $product_object->get_meta( '_period_in', true ),
                        'options'     => [
                            'day' => __( 'Day', 'moka-woocommerce' ),
                            'week' => __( 'Week', 'moka-woocommerce' ), 
                            'month' => __( 'Month', 'moka-woocommerce' ), 
                            'year'=> __( 'Year', 'moka-woocommerce' ),
                        ],
                        'default'     => '',
                        'placeholder' => __( 'Period', 'moka-woocommerce' ),
                    ]
                );
                woocommerce_wp_text_input(
                    array(
                        'id'          => '_subscription_price',
                        'label'       => __( 'Subscription Price', 'moka-woocommerce' ),
                        'value'       => $product_object->get_meta( '_subscription_price', true ),
                        'default'     => '',
                        'placeholder' => __( 'Subscription Price', 'moka-woocommerce' ),
                        'data_type' => 'price',
                    )
                );
            ?>
          </div>
        </div>
        <?php
    }

    /**
     * Save product's subscription settings
     *
     * @param [integer] $postId
     * @return void
     */
    public function saveSubscriptionSettings($postId)
    {
        $price = isset( $_POST['_subscription_price'] ) ? sanitize_text_field( $_POST['_subscription_price'] ) : '';
        update_post_meta( $postId, '_subscription_price', $price );
        $_period_per = isset( $_POST['_period_per'] ) ? sanitize_text_field( $_POST['_period_per'] ) : '';
        update_post_meta( $postId, '_period_per', $_period_per );
        $_period_in = isset( $_POST['_period_in'] ) ? sanitize_text_field( $_POST['_period_in'] ) : '';
        update_post_meta( $postId, '_period_in', $_period_in );
    }

    /**
     * Change add to cart text for subscription product
     *
     * @param [string] $buttonText
     * @param [object] $product
     * @return void
     */
    public function changeAddToCartText($buttonText, $product)
    {
        if($this->isSubscriptionsEnabled)
        {    
            $type = $product->get_type(); 
            
            if($type == $this->productType)
            { 
                $buttonText = __("Subscribe", "moka-woocommerce");
            }
        }

        return $buttonText;
    }

    /**
     * Modifiy addToCart button part  product summary 
     *
     * @return void
     */
    public function addToCartButtonProductSummary()
    {
        if($this->isSubscriptionsEnabled)
        {
            global $product;

            if ( $this->productType == $product->get_type() ) {
                do_action( 'woocommerce_before_add_to_cart_button' ); 
                    $productId = $product->get_id(); 
                    $period = get_post_meta($productId);
                    $periodString = data_get($period, '_period_per.0'). ' ' .data_get($period,'_period_in.0');       
                ?>
    
                <p class="cart">
                    <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" rel="nofollow" class="single_add_to_cart_button button alt">
                        <?php echo __("Subscribe", "moka-woocommerce"); ?>
                    </a>
                </p>
                <p><?php echo "<br>".__('Renewal Period', 'moka-woocommerce').' : '.data_get($period, '_period_per.0'). ' ' .__(data_get($period,'_period_in.0'), 'moka-woocommerce'); ?>
                </p>
                <?php do_action( 'woocommerce_after_add_to_cart_button' );
            }
        }
    }

    /**
     * Display some information for cart item meta data.
     *
     * @param [object] $itemData
     * @param [array] $cartItem
     * @return void
     */
    public function displayCartItemCustomMetaData( $itemData, $cartItem ) 
    {
        return $itemData;
        /*
        $productId = data_get($cartItem, 'product_id');
        $period = get_post_meta($productId);
        $periodString = data_get($period, '_period_per.0'). ' ' .__(data_get($period,'_period_in.0'), 'moka-woocommerce');
        $itemData[] = [
            'key' => __('Renewal Period', 'moka-woocommerce'), 
            'value' => $periodString, 
            'display' => '',
        ];*/
        
    }  

    /**
     * Set page link to Woocommerce MyAccount Area
     *
     * @param [array] $links
     * @return array
     */
    public function setSubscriptionsPageLink($links)
    {
        if($this->isSubscriptionsEnabled)
        $links = array_slice( $links, 0, 5, true ) 
        + array( $this->productType => __( 'Subscription', 'moka-woocommerce' ) )
        + array_slice( $links, 5, NULL, true );
        return $links;
    }

    /**
     * Add Permalink Rewrite for Subscription Endpoint
     *
     * @return void
     */
    public function addSubscriptionPermalink()
    {
        if($this->isSubscriptionsEnabled)
        add_rewrite_endpoint( $this->productType, EP_PAGES );
    }

    /**
     * Render Subscription Page Content
     *
     * @return void
     */
    public function addSubscriptionPermalinkEndpoint()
    {
        if($this->isSubscriptionsEnabled)
        {

            self::registerStylesAndScripts();

            global $wpdb;
            $currentUserId  = get_current_user_id();
            $table          = 'moka_subscriptions';
            $records        = $wpdb->get_results("SELECT * FROM  $wpdb->prefix$table WHERE user_id = '$currentUserId'");
            $return         = '';
            
            if($records)
            {
                $return .= '<div id="subscription_ajax_response"></div><table class="shop_table shop_table_responsive my_account_orders">';
                $return .= '<thead><tr>
                    <th>'.__( 'Order', 'woocommerce' ).'</th>
                    <th>'.__( 'Date', 'woocommerce' ).'</th>
                    <th>'.__( 'Total', 'woocommerce' ).'</th>
                    <th>'.__( 'Status', 'woocommerce' ).'</th>
                    <th>'.__( 'Actions', 'woocommerce' ).'</th>
                </tr></thead>';
                foreach($records as $perRecord)
                {
                    $orderId                = data_get($perRecord, 'order_id');
                    $order                  = wc_get_order($orderId);
                    $subscriptionStatus     = data_get($perRecord, 'subscription_status');
                    $subscriptionDate       = data_get($perRecord, 'created_at');
                    $subscriptionNextDate   = data_get($perRecord, 'subscription_next_try');

                    $return .= '<tr>';
                        $return .= '<td class="text-center">'._x( '#', 'hash before order number', 'woocommerce' ).'<a href="'.esc_html($order->get_view_order_url()).'" target="_blank">'.esc_html($order->get_order_number()).'</a></td>';
                        $return .= '<td class="text-center">'.
                                'Başlangıç : '.esc_html( date('d.m.Y H:i', strtotime($subscriptionDate)) ).
                                '<br>'.($subscriptionStatus == 0 ? 'Sonraki Ödeme' : 'Bitiş').' : '.esc_html( date('d.m.Y H:i',
                                strtotime($subscriptionNextDate)) )
                            .'</td>';
                        $return .= '<td class="text-center">'.esc_html(data_get($perRecord, 'order_amount',0.0)).' '.esc_html($order->get_currency()).'</td>';
                        $return .= '<td class="text-center">'.($subscriptionStatus == 0 ? 'Aktif' : 'Sonlandı').'</td>';
                        $return .= '<td class="text-center">
                            '.($subscriptionStatus == 0 ? '<span data-order-id="'.esc_html($orderId).'" class="subscription-cancelManually">İptal</span>' : '<span class="subscription-noActions">Düzenlenemez</span>').'
                        </td>';
                    $return .= '</tr>';
                }
                $return .='</table>';
            } 

            if(!$records)
            {
                $return = esc_html_e( 'No order has been made yet.', 'woocommerce' );
            }

            echo $return;
        }
    }

    /**
     * Add Admin menu page.
     *
     * @return void
     */
    public function addSubscriptionAdminMenuLink()
    {
        if($this->isSubscriptionsEnabled)
        add_menu_page(
			__( 'Subscription', 'moka-woocommerce' ),
			__( 'Subscription', 'moka-woocommerce' ),
			'manage_options',
			$this->productType,
			[$this, 'addSubscriptionAdminMenuContent'],
			'dashicons-schedule',
			3
		);
    }

    /**
     * Display Admin Menu Content
     *
     * @return void
     */
    public function addSubscriptionAdminMenuContent()
    {
        if($this->isSubscriptionsEnabled)
        require_once __DIR__.'/Moka_Subscriptions_History.php';
        $subscriptionsData = new Optimisthub_Moka_Subscriptions_History_List_Tabley();
        $subscriptionsData->prepare_items();
        echo sprintf('<div class="wrap"> ');
        echo sprintf('<h1 class="wp-heading-inline">'.__( 'Subscription', 'moka-woocommerce' ).'</h1>');
        echo sprintf($subscriptionsData->display());
        echo sprintf('</div>'); 
    }


    /**
     * Change "Place Order" Button text @ WooCommerce Checkout
     * @return string 
     */
    public function changePlaceOrderTextForSubscription( $buttonText ) 
    { 
        $hasSubscription = null;

        foreach (WC()->cart->get_cart() as $itemId => $item ) 
        {
            $productId = data_get($item, 'product_id');
            $product   = wc_get_product( $productId );
            $type      = $product->get_type(); 
            if($type === 'subscription')
            {
                $hasSubscription = true;
            }
        } 

        if($hasSubscription)
        {
            return __('Subscribe', 'moka-woocommerce');
        } else {
            return $buttonText;
        }
    }

    public function triggerSubscriptionPayments()
    {
        if(!wp_next_scheduled('moka_subscriptions_recurring_payments_cron_job')) {
            wp_schedule_event(time(), 'daily', 'moka_subscriptions_recurring_payments_cron_job');
        }
    }

    public function runSubscriptionPayments()
    {
        global $wpdb;
        $table   = 'moka_subscriptions';
        $records = $wpdb->get_results("SELECT * FROM $wpdb->prefix$table WHERE subscription_status = 0 ORDER BY id DESC");
        if($records)
        {
            foreach ($records as $perKey => $perValue) {
 
                $paymentDate = data_get($perValue, 'subscription_next_try');
                $currentTime = current_datetime()->format('Y-m-d H:i:s');
                $tryCount    = (int)data_get($perValue, 'try_count'); 
                $orderId     = data_get($perValue, 'order_id');

                if(strtotime($paymentDate)<time())
                {
                    if($tryCount>=3)
                    {
                        $wpdb->update( $wpdb->prefix.$table, 
                            [
                                'try_count' => ($tryCount+1),
                                'subscription_status' => 2
                            ],    
                            ['order_id' => $orderId]
                        ); 
                        return;
                    }
    
                    if(!data_get($perValue, 'subscription_period'))
                    {
                        return;
                    }

                    $orderDetails   = json_decode(data_get($perValue, 'order_details'));
                    $payment        = new MokaPayment();
                    $otherTrxCode   = data_get($orderDetails, 'OtherTrxCode').'-'.date('His',strtotime($currentTime));
                    $requestParams  = [
                        'CardToken'             => data_get($orderDetails, 'CardToken'),
                        'Amount'                => data_get($orderDetails, 'Amount'),
                        'Currency'              => data_get($orderDetails, 'Currency') ,
                        'InstallmentNumber'     => data_get($orderDetails, 'InstallmentNumber'),
                        'ClientIP'              => data_get($orderDetails, 'ClientIP'),
                        'OtherTrxCode'          => $otherTrxCode,
                        'Software'              => strtoupper('OPT-WpWoo-'.get_bloginfo('version').'-'.WC_VERSION), 
                        'Description'           => 'RecurringPayment-'.$orderId,
                        'isSubscriptionPayment' => true,
                    ];

                    
                    $doPayment  = $payment->initializePayment($requestParams);
                    $isSuccess  = data_get($doPayment, 'Data.IsSuccessful');
  
                    if($isSuccess)
                    {
                        // Save Log  
                        $wpdb->insert($wpdb->prefix . 'moka_transactions', [
                            'id_cart'       => $orderId,
                            'id_customer'   => self::getOrderCustomerId($orderId),
                            'optimist_id'   => $otherTrxCode,
                            'amount'        => data_get($orderDetails, 'Amount'),
                            'amount_paid'   => data_get($orderDetails, 'Amount'),
                            'installment'   => data_get($orderDetails, 'InstallmentNumber'),
                            'result_code'   => data_get($doPayment, 'ResultCode'),
                            'result_message'=> 'Sistem tarafından abonelik ücreti yenilendi. - İşlem ID : '.data_get($doPayment, 'Data.VirtualPosOrderId'), 
                            'result'        => 0,
                            'created_at'    => current_datetime()->format('Y-m-d H:i:s')
                        ]);

                        // Update Subscription information
                        $subscriptionPeriod = data_get($perValue, 'subscription_period');
                        $currentTime = Carbon::parse(current_datetime()->format('Y-m-d H:i:s'));

                        $__data = explode(' ',$subscriptionPeriod);

                        $nextTry = $currentTime::now()->add($__data[0], $__data[1]); 
                 
                        $period = [
                            'current_time'  => Carbon::parse($currentTime)->format('Y-m-d H:i:s'),
                            'next_try'      => Carbon::parse($nextTry)->format('Y-m-d H:i:s'),
                            'period_string' => $subscriptionPeriod
                        ];          
                                
                        $wpdb->update( $wpdb->prefix.$table, 
                            [
                                'subscription_period'   => $period['period_string'],
                                'updated_at'            => current_datetime()->format('Y-m-d H:i:s'),
                                'subscription_next_try' => $period['next_try'],
                                'optimist_id'           => $otherTrxCode,
                                'try_count'             => ($tryCount+1)
                            ],    
                            ['order_id' => $orderId]
                        );

                    } 
                } 
            }
        }
    }

    /**
     * Register styles
     * @since 3.0
     * @copyright 2022 Optimisthub
     * @author Fatih Toprak 
     * @return void
     */
    private function registerStylesAndScripts()
    {
        wp_enqueue_script( 'moka-pay-corejs', OPTIMISTHUB_MOKA_URL . 'assets/moka.js' , false, OPTIMISTHUB_MOKA_PAY_VERSION );
        wp_register_style( 'moka-pay-card_css', OPTIMISTHUB_MOKA_URL. 'assets/moka.css' , false, OPTIMISTHUB_MOKA_PAY_VERSION );
        wp_enqueue_style ( 'moka-pay-card_css' );
        wp_localize_script( 'moka-pay-corejs', 'moka_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'subscription_confirm' => __( 'If you agree, your subscription will be cancelled and the payment will not be renewed. However; you will be able to continue to use your subscription until the membership expiration date.', 'moka-woocommerce' ),
            'success_redirection' => __( 'Your transaction has been completed successfully. Within 2 seconds the page will be refreshed', 'moka-woocommerce' ),
            'update_comission' => __( 'When you do this, all of the instalment data you have entered is deleted and the current ones from Moka Pay servers are overwritten. The process cannot be reversed. To continue, please enter confirmation in the field below and continue the process. Otherwise, your transaction will not continue.', 'moka-woocommerce' ),
            'version' => OPTIMISTHUB_MOKA_PAY_VERSION,
            'installment_test' => __( 'Installment Rate Test', 'moka-woocommerce' ),
            'bin_test' => __( 'Bank Identification Test', 'moka-woocommerce' ),
            'success' => __( 'Success', 'moka-woocommerce' ),
            'failed' => __( 'Failed', 'moka-woocommerce' ),
        ] );
    }

    /**
     * Fetch customer id from order
     *
     * @return void
     */
    private function getOrderCustomerId($orderId)
    {
        $order      = wc_get_order($orderId);
        $orderId    = $order->id;
        $userId     = $order->get_user_id();
        return $userId;
    }

    /**
     * Add Try Count
     *
     * @return void
     */
    public function mybeAddColumnIfIsNotExists()
    {
        global $wpdb;
        $table   = $wpdb->prefix.'moka_subscriptions';
        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table' AND column_name = 'try_count'"  );
        
        if(empty($row)){
            $wpdb->query("ALTER TABLE $table ADD try_count INT(1) NOT NULL DEFAULT 0 AFTER `subscription_next_try`");
        }
    }
}

new MokaSubscription();
