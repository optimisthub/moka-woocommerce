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
        $this->installment_total = 'yes' === data_get($this->mokaOptions, 'show_installment_total');
        $this->enableInstallment = 'yes' === data_get($this->mokaOptions, 'installment');
 
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
        $avaliableInstallment = null;

        $postData = $params;
 
        $action = data_get($postData, 'action');

        if(!$action)
        {
            $error = new WP_Error( '001', 'Action Is Required' );
            return wp_send_json_error( $error );
        }

        $binNumber = substr(data_get($postData, 'binNumber'),0,6);

        $mokaPay = new MokaPayment();
        $response = $mokaPay->requestBin(['binNumber' => $binNumber]);

        if( !$response )
        {
            $error = new WP_Error( '002', 'Response Could Not Fetched.' );

            $data = [
                'error_message'     => $error,
                'cardInformation'   => $response, 
                'installments'      => $avaliableInstallment,
                'renderedHtml'      => self::renderedHtml($response, []),
            ]; 
            wp_send_json_error( [
                'binNumber' => $binNumber, 
                'time'      => time(), 
                'data'      => $data,
            ] );
        }

        ## installments
        $bankCode = data_get($response, 'BankCode'); 
        $bankGroup = data_get($response, 'GroupName'); 

        $installments = self::fetchInstallment();
        
        if($bankGroup && $installments)
        { 
            $bankCode = mb_strtolower($bankCode); 
            $bankGroup = mb_strtolower($bankGroup); 
            foreach($installments as $perInstallment)
            {
                if($perInstallment['groupName'] == $bankGroup)
                {
                    $avaliableInstallment = $perInstallment;
                }
            } 
        }    
        
        ## installments
        $data = [
            'cardInformation' => $response, 
            'installments' => $avaliableInstallment,
            'renderedHtml' => self::renderedHtml($response, [
                'card'          => $response, 
                'installments'  => $avaliableInstallment, 
                'state'         => data_get($postData, 'state'),
                'bankCode'      => $bankCode,
                'bankGroup'     => $bankGroup,
            ]),
        ];  

        wp_send_json_success( [
            'binNumber' => $binNumber, 
            'time'      => time(), 
            'data'      => $data,
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
     * Dealer informations check
     *
     * @param [array] $params
     * @return void
     */
    public function admin_test($params)
    {
        $test_cards = [
            '5127541122223332',
            '4183441122223339',
            '4397481122223337',
            '5269551122223339',
        ];

        $result = [
            'commissioncheck' => false,
            'bincheck' => false,
            'message' => '',
        ];

        if(
            data_get($this->mokaOptions, 'company_code') && 
            data_get($this->mokaOptions, 'api_username') && 
            data_get($this->mokaOptions, 'api_password') 
        ){
            $commissioncheck = $this->mokaPayRequest->getInstallments();
            $commissioncheck = data_get($commissioncheck, 'CommissionList');
            if( $commissioncheck ){
                $result['commissioncheck'] = true;
                $result['commissiondata'] = $commissioncheck;
            }

            $mokaPay = new MokaPayment();
            $binNumber = substr($test_cards[array_rand($test_cards)], 0, 6);
            $bincheck = $mokaPay->requestBin(['binNumber' => $binNumber ]);
            if( $bincheck ){
                $result['bincheck'] = true;
                $result['bindata'] = $bincheck;
            }

        }else{
            $result['message'] = __( 'The test function can be performed after saving the merchant information.', 'moka-woocommerce' );
        }

        wp_send_json_success( [
            'time' => time(), 
            'data' => $result,
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

        if($method == 'cancel_subscription')
        {
            self::cancelSubscription($postData);
        }

        if($method == 'moka_admin_test')
        {
            self::admin_test($postData);
        }

        wp_die();
    }

    public function cancelSubscription( $params )
    {
        global $wpdb; 
        $orderId    = data_get($params, 'orderId');

        if(!$orderId)
        {
            wp_send_json_success( [
                'time' => time(), 
                'data' => ['error' => 'Hata : Sipariş bulunamadı.'],
            ], 200 );
        }

        $table      = 'moka_subscriptions';
        $records    = $wpdb->get_row("SELECT * FROM $wpdb->prefix$table WHERE order_id = '$orderId' AND subscription_status = 0");

        if(!$records || empty($records))
        {
            wp_send_json_success( [
                'time' => time(), 
                'data' => ['error' => 'Hata : Kayıt Bulunamadı.'],
            ], 200 );
        }

        if($records)
        {
            $date = current_datetime()->format('Y-m-d H:i:s');
 
            $updateStatus = $wpdb->query(
                $wpdb->prepare( "UPDATE $wpdb->prefix$table SET subscription_status = %s WHERE order_id = %d", '1', $orderId ),
            );   

            if(!$updateStatus)
            {
                wp_send_json_success( [
                    'time' => time(), 
                    'data' => ['error' => 'Hata : '.$wpdb->last_query ],
                ], 200 );
            }

            if($updateStatus)
            {
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->prefix$table SET updated_at = %s WHERE order_id = %d",$date, $orderId ) ); 
                wp_send_json_success( [
                    'time' => time(), 
                    'data' => ['messsage' => 'Abonelik başarılı bir şekilde iptal edildi. Lütfen bekleyiniz.'],
                ], 200 );
            }
        }
    }

    /**
     * Fetch installments from database
     *
     * @return void
     */
    private function fetchInstallment()
    { 
        $isInstallmentsActive = data_get($this->mokaOptions, 'installment');
        if($isInstallmentsActive && $isInstallmentsActive == 'yes')
        {
            return $this->installments;
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
            $order = wc_get_order(get_query_var('order-pay'));
            $orderTotal = $order->get_total();  
        }

        $installmentRates = data_get($params, 'installments.rates');
        $maxInstallment = data_get($params, 'card.MaxInstallmentNumber');
        $bankCode = data_get($params, 'bankCode');
        $bankGroup = data_get($params, 'bankGroup');

        if(!isset($installmentRates[1])){
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

        if($installmentRates)
        {
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

               # $formHtml .= '<select name="mokapay-installment" class="input-select">';
                foreach(range(1, $maxInstallment) as $kk => $perInstallmentKey)
                {
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

}
 
new Optimisthub_Ajax();