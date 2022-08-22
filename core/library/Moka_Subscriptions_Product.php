<?php

/**
 * Advanced Product Type
 */
class WC_Moka_Subscriptions_Product extends WC_Product_Simple {
    
    /**
     * Return the product type
     * @return string
     */
    public function get_type() {
        return 'subscription';
    }

}

new WC_Moka_Subscriptions_Product();