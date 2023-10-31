<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Optimisthub_Ajax
{
    public function __construct() 
    {
        $this->mokaPayRequest = new MokaPayment();
        $this->mokaOptions  = get_option('woocommerce_mokapay_settings');
        $this->installments = get_option('woocommerce_mokapay-installments') ? get_option('woocommerce_mokapay-installments') : self::generateDynamicInstallmentData();
        $this->installment_total = data_get($this->mokaOptions, 'show_installment_total', 'yes') === 'yes';
        $this->enableInstallment = data_get($this->mokaOptions, 'installment', 'yes') === 'yes';
        $this->limitInstallment =  data_get($this->mokaOptions, 'limitInstallment', 12);
        $this->limitInstallmentByProduct = data_get($this->mokaOptions, 'limitInstallmentByProduct', 'no') === 'yes';
 
        add_action( 'wp_ajax_optimisthub_ajax', array( $this, 'optimisthub_ajax' ) ); 
        add_action( 'wp_ajax_nopriv_optimisthub_ajax', array( $this, 'optimisthub_ajax' ) );
    }

    /**
     * Bin number validation requestvia ajax.
     *
     * @return void
     */
    public function validate_bin($postData)
    {
        $result = [
            'time' => time(),
            'status' => false,
            'message' => false,
            'html' => false,
            'error' => false,
        ];
 
        $action = data_get($postData, 'action', false);

        if( !$action ) {
            $result['message'] = 'action is required';
            $result['error'] = true;
        }

        $binNumber = substr( data_get($postData, 'binNumber'), 0 , 6 );

        if( strlen($binNumber)  != 6 ){
            $result['message'] = 'binNumber is required';
            $result['error'] = true;
        }

        if( !$result['error'] ){
            $avaliableInstallment = null;
            $mokaPay = new MokaPayment();
            $response = $mokaPay->requestBin(['binNumber' => $binNumber]);

            if( $response['status'] ) {            
                $bankCode = data_get($response['body'], 'BankCode');
                $bankGroup = data_get($response['body'], 'GroupName');

                $installments = self::fetchInstallment();
                
                if($bankGroup && $installments) { 
                    $bankCode = mb_strtolower($bankCode); 
                    $bankGroup = mb_strtolower($bankGroup); 
                    foreach($installments as $perInstallment) {
                        if($perInstallment['groupName'] == $bankGroup) {
                            $avaliableInstallment = $perInstallment;
                        }
                    } 
                }    
                
                $result = array_merge($result,
                    [
                        'status' => true,
                        'message' => __( 'Success', 'moka-woocommerce' ),
                        'cardInformation' => $response['body'],
                        'installments' => $avaliableInstallment,
                        'html' => self::renderedHtml($response['body'], [
                            'card'          => $response['body'], 
                            'installments'  => $avaliableInstallment, 
                            'state'         => data_get($postData, 'state'),
                            'bankCode'      => $bankCode,
                            'bankGroup'     => $bankGroup,
                        ]),
                    ]
                );  
            } else {
                $result['message'] = $response['code'] ?? '002' . $response['error'] ?? 'response could not fetched.';
                $result['html'] = self::renderedHtml($response['body'], []);
            }
        }
        wp_send_json( $result );
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
        wp_send_json( [
            'status' => true,
            'time' => time(), 
            'message' => __( 'Success', 'moka-woocommerce' ),
        ] );
    }
    
    /**
     * Dealer informations check
     *
     * @param [array] $params
     * @return void
     */
    public function moka_test($params)
    {
        $test_cards = $this->mokaPayRequest->get_test_cards(true);

        $result = [
            'time' => time(),
            'status' => false,
            'commissioncheck' => false,
            'bincheck' => false,
            'remote' => false,
            'message' => false,
        ];

        if( $test_cards ){
            $result['remote'] = true;
            if(
                data_get($this->mokaOptions, 'company_code') && 
                data_get($this->mokaOptions, 'api_username') && 
                data_get($this->mokaOptions, 'api_password') 
            ){
                $commissioncheck = $this->mokaPayRequest->getInstallments();
                $result['commissiondata'] = $commissioncheck;
                $commissioncheck = data_get($commissioncheck, 'CommissionList');
                if( $commissioncheck ){
                    $result['commissioncheck'] = true;
                }

                $mokaPay = new MokaPayment();
                $binNumber = substr($test_cards[array_rand($test_cards)], 0, 6);
                $bincheck = $mokaPay->requestBin(['binNumber' => $binNumber ]);
                $result['bindata'] = $bincheck;
                if( $bincheck['status'] ){
                    $result['bincheck'] = true;
                }

                $result['status'] = true;
            }else{
                $result['message'] = __( 'The test function can be performed after saving the merchant information.', 'moka-woocommerce' );
            }
        }

        wp_send_json( $result );
    }

    /**
     * General Ajax Request Callback
     */
    public function optimisthub_ajax()
    {
        $postData = $_POST;  
        $action = data_get($postData, 'action');
        $method = data_get($postData, 'method');
    
        if( $method == 'validate_bin' ) {
            self::validate_bin($postData);
        }

        if( $method == 'clear_installment' ) {
            self::clear_installment($postData);
        }

        if( $method == 'cancel_subscription' ) {
            self::cancel_subscription($postData);
        }

        if( $method == 'moka_test' ) {
            self::moka_test($postData);
        }

        if( $method == 'debug_download' ) {
            self::debug_download();
        }
        
        if( $method == 'debug_clear' ) {
            self::debug_clear();
        }
        
        if( $method == 'update_check' ) {
            self::update_check();
        }

        wp_die();
    }

    public function cancel_subscription( $params )
    {
        global $wpdb; 

        $result = [
            'time' => time(),
            'status' => false,
            'message' => false,
            'error' => false,
        ];

        $orderId = data_get($params, 'orderId', false);
        $orderId = intval($orderId) > 0 ? intval($orderId) : false;

        if( !$orderId ) {
            $result['message'] = __( 'Order identifier not found', 'moka-woocommerce' );
            $result['error'] = true;
        }

        if( !wc_get_order($orderId) ){
            $result['message'] = __( 'Order not found', 'moka-woocommerce' );
            $result['error'] = true;
        }

        $records = $wpdb->get_row( 
            $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'moka_subscriptions WHERE order_id = %d AND subscription_status = 0', $orderId ) 
        );

        if( !$records ){
            $result['message'] = __( 'Subscription not found', 'moka-woocommerce' );
            $result['error'] = true;
        }

        if( $records && !$result['error'] ) {
            $date = current_datetime()->format('Y-m-d H:i:s');
 
            $updateStatus = $wpdb->query(
                $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'moka_subscriptions SET subscription_status = %d WHERE
                order_id = %d', 1, $orderId ),
            );   

            if( $updateStatus ) {
                $wpdb->query( 
                    $wpdb->prepare( 'UPDATE ' . $wpdb->prefix . 'moka_subscriptions SET updated_at = %s WHERE order_id = %d', $date, $orderId ) 
                );
                
                $result['status'] = true;
                $result['message'] = __( 'Subscription has been successfully canceled. Please wait.', 'moka-woocommerce'
                );
            } else {
                $result['message'] = __( 'An unexpected error occurred', 'moka-woocommerce' );
            }
        }

        wp_send_json($result);
    }

    /**
     * Fetch installments from database
     *
     * @return void
     */
    private function fetchInstallment()
    { 
        $isInstallmentsActive = data_get($this->mokaOptions, 'installment', 'yes') === 'yes';
        if( $isInstallmentsActive )
        {
            return self::calculateMaxInstallment($this->installments);
        } 
    }

    /**
     * Render html installment template
     *
     * @param [array] $params
     * @return void
     */
    private function renderedHtml( $response, $params )
    {

        global $woocommerce;

        $orderTotal = false;
        $orderState = data_get($params, 'state');
        if($orderState == 'cart'){
            $orderTotal = data_get($woocommerce, 'cart.total');
        }elseif($orderState == 'order'){
            if(get_query_var('order-pay')){
                $order = wc_get_order(get_query_var('order-pay'));
                if($order){
                    $orderTotal = $order->get_total();
                }else{
                    $orderTotal = data_get($woocommerce, 'cart.total');
                }
            }else{
                $orderTotal = data_get($woocommerce, 'cart.total');
            }
        }

        $installmentRates = data_get($params, 'installments.rates');
        $maxInstallment = data_get($params, 'card.MaxInstallmentNumber');
        $bankCode = data_get($params, 'bankCode');
        $bankGroup = data_get($params, 'bankGroup');

        if( !isset($installmentRates[1]) ){
            $installmentRates[1] = [
                'active' => 1,
                'value' => 0,
            ];
        }


        $formHtml = ''; 
        $formHtml.='<input type="hidden" name="mokapay-order-state" value="'.$orderState.'">';
        $formHtml.='<input type="hidden" name="mokapay-order-bankCode" value="'.$bankCode.'">';
        $formHtml.='<input type="hidden" name="mokapay-order-bankGroup" value="'.$bankGroup.'">';

        if( !$orderTotal || !$response || !$this->enableInstallment ) {
            $formHtml.='<input type="hidden" name="mokapay-installment" value="1">';
            return $formHtml;
        }

        if($installmentRates) {
            $formHtml .='<fieldset style="padding-bottom:30px"><p class="form-row form-row-wide">';
                #$formHtml .= '<img class="aligncenter" src="'.data_get($params, 'card.CardTemplate').'" />';
                $formHtml .= '<label>'.__( "Installment Shopping", 'moka-woocommerce' ).'</label>';

                $formHtml .='
                 <style>
                    .custom-checkboxes {display:flex;}
                    .w-w-50 {width:49%;float:left;margin-right:1%;}
                    p.form-row.w-w-50 {
                        width: 100% !important;
                        display: block !important;
                        margin: 0 !important;
                    }
                    p.form-row.w-w-50 input {
                        width: unset !important;
                    }
                    
                    p.form-row.w-w-50 label {
                        line-height: 1 !important;
                    }
                    .installment-table .moka-total {
                        font-size:80%;
                        color:#333;
                    }
                 </style>
                ';

                foreach(range(1, $maxInstallment) as $kk => $perInstallmentKey) {
                    if($installmentRates[$perInstallmentKey]['active'] == 1)
                    {
                        $optionValue = $perInstallmentKey == 1 ? __( "Cash In Advence", 'moka-woocommerce' ) : $perInstallmentKey. ' '. __( "Installment", 'moka-woocommerce' );
                        $checked = $perInstallmentKey == 1 ? ' checked="checked" ' : '';

                        $installment_price = self::calculateComissionRate($orderTotal, $installmentRates[$perInstallmentKey]['value'], $perInstallmentKey);
                        
                        $installment_total = $this->installment_total ? ' <span class="moka-total"> = ' . self::moka_price( self::calculateComissionRateTotal($orderTotal, $installmentRates[$perInstallmentKey]['value'], $perInstallmentKey) ) . '</span>' : '';

                        if($perInstallmentKey == 1) {
                            $formHtml .=' <p class="form-row w-w-50">
                                <input '.$checked.' id="installment-pick' . $kk . '" type="radio" class="input-radio w-w-50" name="mokapay-installment" value="' . $perInstallmentKey . '">
                                <label for="installment-pick' . $kk . '"> ' . $optionValue . ' = ' .self::moka_price($installment_price) . '</label>
                            </p>';  
                        }else{
                            $formHtml .=' <p class="form-row w-w-50">
                                <input '.$checked.' id="installment-pick' . $kk . '" type="radio" class="input-radio w-w-50" name="mokapay-installment" value="' . $perInstallmentKey . '">
                                <label for="installment-pick' . $kk . '"> ' . $optionValue . ' x ' . self::moka_price($installment_price) . $installment_total . '</label>
                            </p>';                        
                        }

                        $formHtml .=' ';
                    }
                }
            $formHtml .= '</p></fieldset>';
        }

        if(!$installmentRates)
        {
            $formHtml.='<input type="hidden" name="mokapay-installment" value="1">';
            return $formHtml;
        } 
        return $formHtml;
    }

    /**
     * Calculate comisssion rates for installment information
     *
     * @param [int] $total
     * @param [int] $percent
     * @param [int] $installment
     * @return void
     */
    private function calculateComissionRate( $total, $percent, $installment )
    {
        $realPercent = floatval( floatval($total) * floatval($percent) / 100 );
        $totalPrice = floatval($total) + $realPercent; 
        
        return self::moka_number_format( ($totalPrice/intval($installment)) );
    }
    
    private function calculateComissionRateTotal( $total, $percent, $installment )
    {
        $realPercent = floatval( floatval($total) * floatval($percent) / 100 );
        $totalPrice = floatval($total) + $realPercent; 
        
        return self::moka_number_format( $totalPrice );
    }

    /**
     * Set dynamic installment table from live server
     *
     * @return void
     */
    private function generateDynamicInstallmentData()
    {
        $list = $this->mokaPayRequest->getInstallments();
        $list = data_get($list, 'CommissionList');

        if(!$list)
        {
            return false;
        }
        return $this->mokaPayRequest->formatInstallmentResponse($list);
    }
    
    private function moka_price($price)
    {   
        $price = preg_replace('/\s+/', '', $price);
        $price = self::moka_number_format($price);
        $price = $price . get_woocommerce_currency_symbol();        
        return $price;
    }

    private function moka_number_format($price, $decimal = 2){
        $_price = floatval($price);
        $_price = number_format( $_price, ($decimal + 1), '.', '');
        $_price = substr($_price, 0, -1);
        return $_price;
    }

    private function calculateMaxInstallment($installments){
        global $woocommerce;

        $_limitInstallment = intval($this->limitInstallment);

        if( $this->limitInstallmentByProduct ) {
            $cart_contents = $woocommerce->cart->get_cart();
            if($cart_contents && !empty($cart_contents)){
                foreach($cart_contents as $cart_content){
                    $product_limitInstallment = get_post_meta($cart_content['product_id'] , '_limitInstallment', true);
                    if(
                        $product_limitInstallment &&
                        intval($product_limitInstallment)>0 &&
                        $_limitInstallment > intval($product_limitInstallment)
                    ){
                        $_limitInstallment = intval($product_limitInstallment);
                    }
                }
            }
        }

    

        $_temp = [];
        foreach($installments as $installment_key => $installment){
            $_temp[$installment_key] = $installment;
            foreach($installment['rates'] as $rate_key => $rate){
                if($rate_key > $_limitInstallment){
                    unset($_temp[$installment_key]['rates'][$rate_key]);
                }
            }
        }
        $installments = $_temp;

        return $installments;
    }

    private function debug_download(){
        $result = [
            'time' => time(),
            'status' => false,
            'message' => __( "Cant find debug file", 'moka-woocommerce' )
        ];
        $filename = $this->mokaPayRequest->debug_file();
        if( file_exists(OPTIMISTHUB_MOKA_DIR . $filename) ){
            $result['status'] = true;
            $result['message'] = __( 'Success', 'moka-woocommerce' );
            $result['file'] = OPTIMISTHUB_MOKA_URL . $filename;
            $result['filename'] = wp_generate_uuid4().'.log';
        }
        wp_send_json($result);
    }

    private function debug_clear(){
        $result = [
            'time' => time(),
            'status' => false,
            'message' => __( "Cant find debug file", 'moka-woocommerce' )
        ];
        $filename = $this->mokaPayRequest->debug_file();
        if( file_exists(OPTIMISTHUB_MOKA_DIR . $filename) ){
            unlink(OPTIMISTHUB_MOKA_URL . $filename);
            $result['status'] = true;
            $result['message'] = __( 'Success', 'moka-woocommerce' );
        }
        wp_send_json($result);
    }
    
    private function update_check(){
        $result = [
            'time' => time(),
            'status' => true,
            'message' => __( 'Success', 'moka-woocommerce' )
        ];
        wp_update_plugins();
        wp_send_json($result);
    }

}
 
new Optimisthub_Ajax();