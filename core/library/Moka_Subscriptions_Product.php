<?php

/**
 * Subscription Product Type
 * @desc Generates product type to WooCommerce products for subscriptions
 * @since 3.0
 * @copyright 2022 Optimisthub
 * @author Fatih Toprak 
 */


function addSubscriptionProductType () {

	class WC_Product_Subscription extends WC_Product 
    {

        public function __construct( $product ) {
            $this->product_type = 'subscription';  
            parent::__construct( $product ); 
        }

        /**
         * Add to cart url for summary.
         *
         * @return void
         */
        public function add_to_cart_url() {
            $url = $this->is_purchasable() && $this->is_in_stock() ? remove_query_arg( 'added-to-cart', add_query_arg( 'add-to-cart', $this->id ) ) : get_permalink( $this->id );
            return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
        }
    }
}

addSubscriptionProductType();
