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
        $this->currency = get_option('woocommerce_currency');
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
            'renderedHtml' => self::renderedHtml(['card' => $response, 'installments' => $avaliableInstallment, 'total' => data_get($postData, 'total')]),
        ];  

        if(!$response)
        {
            $error = new WP_Error( '002', 'Response Could Not Fetched.' );

            $data['error_message'] = $error; 
            wp_send_json_error( [
                'binNumber' => $binNumber, 
                'time' => time(), 
                'data' => $data,
            ] );
        }

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

    /**
     * Render html installment template
     *
     * @param [array] $params
     * @return void
     */
    private function renderedHtml( $params )
    {
        $total = data_get($params, 'total');
        $maxInstallment = data_get($params, 'card.MaxInstallmentNumber');
        $installmentRates = data_get($params, 'installments.rates');
        $orderTotal = $total;

        ### Disable rate for 1 installment
        $installmentRates[1]=[
            'active' => 1,
            'value' => 0,
        ];
        ### Disable rate for 1 installment

        $formHtml = ''; 
        $formHtml.='<input type="hidden" name="mokapay-order-total" value="'.$orderTotal.'">';
        $formHtml.='<input type="hidden" name="mokapay-installment-rates" value="'.urlencode(json_encode($installmentRates)).'">';
 
        
        if(!$this->enableInstallment)
        {
            return $formHtml.='<input type="hidden" name="mokapay-installment" value="1">';
        }

        if($installmentRates)
        {
            $formHtml .='<fieldset style="padding-bottom:30px"><p class="form-row form-row-wide">';
                #$formHtml .= '<img class="aligncenter" src="'.data_get($params, 'card.CardTemplate').'" />';
                $formHtml .= '<label for="mokapay-installment">'.__( "Installment Shopping", 'moka-woocommerce' ).'</label>';
                $formHtml .= '<select name="mokapay-installment" class="input-select">';
                foreach(range(1,$maxInstallment) as $perInstallmentKey)
                {
                    if($installmentRates[$perInstallmentKey]['active'] == 1)
                    {
                        $optionValue = $perInstallmentKey == 1 ? __( "Cash In Advence", 'moka-woocommerce' ) : $perInstallmentKey. ' '. __( "Installment", 'moka-woocommerce' );
                        $formHtml.='<option value="'.$perInstallmentKey.'">'.self::calculateComissionRate($total, $installmentRates[$perInstallmentKey]['value'],$perInstallmentKey) . ' ' .$this->currency.' x '.$optionValue.'</option>';
                    }
                }
                $formHtml .= '</select>';
            $formHtml .= '</p></fieldset>';
        }

        if(!$installmentRates)
        {
            return $formHtml.='<input type="hidden" name="mokapay-installment" value="1">';
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
        $total = ( ( ($total*$percent)/100) + $total);
        return number_format(($total/$installment),2);
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

}
 
new Optimisthub_Ajax();