<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Optimisthub_Ajax
{
    public function __construct() 
    {
        $this->mokaOptions  = get_option('woocommerce_mokapay_settings');
        $this->installments = get_option('woocommerce_mokapay-installments');
        $this->currency = get_option('woocommerce_currency');

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

        ## installments
        $bankCode = mb_strtolower(data_get($response, 'BankCode')); 
        $bankGroup = mb_strtolower(data_get($response, 'GroupName')); 
 
        $installments = self::fetchInstallment();
        $avaliableInstallment = null;
        if($bankGroup)
        { 
          
            foreach($installments as $perInstallment)
            {
                if($perInstallment['groupName'] == $bankGroup)
                {
                    $avaliableInstallment = $perInstallment;
                }
            } 
        }   
        ## installments
 
        $data= [
            'cardInformation' => $response, 
            'installments' => $avaliableInstallment,
            'renderedHtml' => self::renderedHtml(['card' => $response, 'installments' => $avaliableInstallment]),
        ];  

        wp_send_json_success( [
            'binNumber' => $binNumber, 
            'time' => time(), 
            'data' => $data,
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

    private function renderedHtml( $params )
    {
        global $woocommerce;
        $total = $woocommerce->cart->total; 
        $maxInstallment = data_get($params, 'card.MaxInstallmentNumber');
        $installmentRates = data_get($params, 'installments.rates');

        $formHtml = '';

        if($installmentRates)
        {
            $formHtml .='<fieldset style="padding-bottom:30px"><p class="form-row form-row-wide">';
                $formHtml .= '<img class="aligncenter" src="'.data_get($params, 'card.CardTemplate').'" />';
                $formHtml .= '<label for="mokapay-installment">'.__( "Installment Shopping", 'moka-woocommerce' ).'</label>';
                $formHtml .= '<select name="mokapay-installment" class="input-select">';
                foreach(range(1,$maxInstallment) as $perInstallmentKey)
                {
                    $formHtml.='<option value="'.$perInstallmentKey.'">'.self::calculateComissionRate($total, $installmentRates[$perInstallmentKey]['value'],$perInstallmentKey) . ' ' .$this->currency.' x '. $perInstallmentKey.' '. __( "Installment", 'moka-woocommerce' ) .'</option>';
                }
                $formHtml .= '</select>';
            $formHtml .= '</p></fieldset>';
        }

        return $formHtml;
    }

    private function calculateComissionRate( $total, $percent, $installment )
    {
        $total = ( ( ($total*$percent)/100) + $total);
        return number_format(($total/$installment),2);
    }

}
 
new Optimisthub_Ajax();