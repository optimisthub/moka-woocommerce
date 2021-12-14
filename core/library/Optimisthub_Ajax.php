<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Optimisthub_Ajax
{
    public function __construct() {
        add_action( 'wp_ajax_optimisthub_ajax', array( $this, 'optimisthub_ajax' ) ); 
        add_action( 'wp_ajax_nopriv_optimisthub_ajax', array( $this, 'optimisthub_ajax' ) );
    }

    /**
     * Bin number validation requestvia ajax.
     *
     * @return void
     */
    public function validate_bin($params)
    {
        $postData = $params;
        $action = data_get($postData, 'action');

        if(!$action)
        {
            $error = new WP_Error( '001', 'Action Is Required' );
            return wp_send_json_error( $error );
        }

        $binNumber = data_get($postData, 'binNumber');

        $mokaPay = new MokaPayment();
        $response = $mokaPay->requestBin(['binNumber' => $binNumber]);

        if(!$response)
        {
            $error = new WP_Error( '002', 'Response Could Not Fetched.' );
            return wp_send_json_error( $error );
        }

        wp_send_json_success( [
            'binNumber' => $binNumber, 
            'time' => time(), 
            'data' => $response,
        ], 200 );

        wp_die();
    }

    /**
     * Clear Stored Installment
     *
     * @param [array] $params
     * @return void
     */
    public function clear_installment($params)
    {
        delete_option('woocommerce_mokapay-installments');
        wp_send_json_success( [
            'time' => time(), 
            'data' => ['message' => 'ok'],
        ], 200 );

        wp_die();
    }

    /**
     * General Ajax Request Callback
     */
    public function optimisthub_ajax()
    {
        $postData = $_POST;  
        $action = data_get($postData, 'action');
        $method = data_get($postData, 'method');
    
        if($method == 'validate_bin')
        {
            self::validate_bin($postData);
        }

        if($method == 'clear_installment')
        {
            self::clear_installment($postData);
        }

        wp_die();
    }

}
 
new Optimisthub_Ajax();