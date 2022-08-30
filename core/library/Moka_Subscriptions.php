<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
class MokaSubscription
{

    public $mokaOptions;
    public $isSubscriptionsEnabled;
    public $productType = 'subscription';

    public function __construct()
    {
        $this->mokaOptions = get_option('woocommerce_mokapay_settings');
        $this->isSubscriptionsEnabled = 'yes' == data_get($this->mokaOptions, 'subscriptions');
        
        register_activation_hook( __FILE__,[$this, 'addProductTypeTaxonomy' ] );
        
        add_action( 'woocommerce_loaded',[$this, 'registerSubscriptionProductType' ] );
        add_filter( 'product_type_selector',[$this, 'addProductTyepSelectorOnBackend' ] );
        add_action( 'woocommerce_product_options_general_product_data', [$this, 'displaySubscriptionProductMetas']);
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'registerSubscriptionProductTab' ), 0 );
        add_action( 'woocommerce_product_data_panels', array( $this, 'registerSubscriptionProductTabContent' ) );
        add_action( 'woocommerce_process_product_meta_subscription', array( $this, 'saveSubscriptionSettings' ) );
        add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'changeAddToCartText'], 20, 2 ); 
        add_action( 'woocommerce_single_product_summary', [$this, 'addToCartButtonProductSummary'], 20 );



        
        add_action( 'woocommerce_new_product', [$this,'syncOnProductSave'], 10, 1 );
        add_action( 'woocommerce_update_product', [$this,'syncOnProductSave'], 10, 1 );
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
            update_post_meta($productId,'_regular_price', $price);
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

    public function registerSubscriptionProductType()
    {
        require_once __DIR__.'/Moka_Subscriptions_Product.php';
    }

    public function addProductTyepSelectorOnBackend($types)
    {
        $types[$this->productType] = __( 'Abonelikler', 'moka-woocommerce' );
        return $types;
    }

    public function addProductTypeTaxonomy()
    {
        // If there is no advanced product type taxonomy, add it.
        if ( ! get_term_by( 'slug', 'advanced', 'product_type' ) ) {
            wp_insert_term( 'advanced', 'product_type' );
        }
    }
 
    public function displaySubscriptionProductMetas()
    {
        echo '<div class="options_group show_if_'.$this->productType.' clear"></div>';
    }
 
    public function registerSubscriptionProductTab($tabs)
    {
        $tabs['general']['class'] = 'hide_if_grouped hide_if_'.$this->productType;
        $tabData[$this->productType] = [
            'label'    => __( 'Abonelik Özellikleri', 'moka-woocommerce' ),
            'target' => $this->productType.'_type_product_options',
            'class'  => 'show_if_'.$this->productType,
        ]; 
                
        $tabs = array_merge($tabData, $tabs);
        return $tabs;
    }

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
                        'label'       => __( 'Abonelik Periyodu', 'moka-woocommerce' ),
                        'value'       => $product_object->get_meta( '_period_per', true ),
                        'options'     => [
                            'her' => 'Her',
                            'her_2' => 'Her 2.', 
                            'her_3' => 'Her 3.', 
                            'her_4' => 'Her 4.', 
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
                        'options'     => ['gun' => 'Gün','hafta' => 'Hafta', 'ay' => 'Ay'],
                        'default'     => '',
                        'placeholder' => __( 'Period', 'moka-woocommerce' ),
                    ]
                );
                woocommerce_wp_text_input(
                    array(
                        'id'          => '_subscription_price',
                        'label'       => __( 'Abonelik Fiyatı', 'moka-woocommerce' ),
                        'value'       => $product_object->get_meta( '_subscription_price', true ),
                        'default'     => '',
                        'placeholder' => __( 'Abonelik Fiyatı', 'moka-woocommerce' ),
                        'data_type' => 'price',
                    )
                );
            ?>
          </div>
        </div>
        <?php
    }

    public function saveSubscriptionSettings($postId)
    {
        $price = isset( $_POST['_subscription_price'] ) ? sanitize_text_field( $_POST['_subscription_price'] ) : '';
        update_post_meta( $postId, '_subscription_price', $price );
        $_period_per = isset( $_POST['_period_per'] ) ? sanitize_text_field( $_POST['_period_per'] ) : '';
        update_post_meta( $postId, '_period_per', $_period_per );
        $_period_in = isset( $_POST['_period_in'] ) ? sanitize_text_field( $_POST['_period_in'] ) : '';
        update_post_meta( $postId, '_period_in', $_period_in );
    }

    public function changeAddToCartText($buttonText, $product)
    {
        $type = $product->get_type(); 
        
        if($type == $this->productType)
        { 
            $buttonText = __("Subscribe", "moka-woocommerce");
        }

        return $buttonText;
    }

    public function addToCartButtonProductSummary()
    {
        global $product;

        if ( $this->productType == $product->get_type() ) {
            do_action( 'woocommerce_before_add_to_cart_button' ); ?>
   
            <p class="cart">
                <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" rel="nofollow" class="single_add_to_cart_button button alt">
                    <?php echo __("Subscribe", "moka-woocommerce"); ?>
                </a>
            </p>

            <?php do_action( 'woocommerce_after_add_to_cart_button' );
        }
    }

}

new MokaSubscription();