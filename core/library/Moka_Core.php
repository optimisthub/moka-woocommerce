<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MokaPayment
{

    private $apiEndPoint = '';
    private $apiUsername = '';
    private $apiSecret = '';
    private $installmentApiEndpoint = 'https://moka.wooxup.com/installments';

    public function __construct() {}

    public function initializePayment() {}

    public function payWith( $params ) {}

    public function requestBin( $params ) {}

    /**
     * Fetch Installemnts From Remote Server
     *
     * @return void
     */
    public function getInstallments() 
    {
        $response = wp_remote_post( $this->installmentApiEndpoint,
            [
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => [],
                'body'        => 
                [
                    'platform'  =>  'wordpress', 
                ],
                'cookies'     => [],
            ]
        ); 

        $responseBody = data_get($response, 'body');
        $responseBody = json_decode($responseBody, true);

        if(data_get($responseBody, 'data.programs'))
        {
            return data_get($responseBody, 'data.programs');
        }
    }

    /**
     * Set Posted Installments Data to Options Table
     *
     * @param [type] $params
     * @return void
     */
    public function setInstallments( $params )
    {
        update_option('woocommerce_mokapay-installments', $params); 
    }

    /**
     * Generate Installment Table For Backend
     *
     * @param [type] $params
     * @return void
     */
    public function generateInstallmentsTableHtml( $params )
    {

        $storedData = get_option( 'woocommerce_mokapay-installments' );
        $avaliableInstallmentsCount = data_get($params, 'maxInstallment');
        $paymentId = data_get($params, 'paymentGatewayId');

        $return = '<div class="center"> <h2>Taksit Tablosu</h2> <table id="comission-rates"> <thead> <tr><td>Kart</td>';

        foreach($avaliableInstallmentsCount as $perIns)
        {
            $return.= '<td>'.$perIns.' Taksit</td>';
        }

        $return.= '</tr></thead>';

        if(!$storedData)
        {
            return $this->generateDefaultInstallmentsTableHtml($params);
        }
        
        foreach($storedData as $perStoredInstallmentKey => $perStoredInstallment)
        {
            $return.='<tr>';
                $imagePath =  plugins_url( 'moka-woocommerce/assets/img/cards/banks/' );

                $return.= '<tr>';
                $return.= '<td><img src="'.$imagePath.$perStoredInstallmentKey.'.svg" /></td>';
                for ($i=1; $i < count($perStoredInstallment)+1 ; $i++) { 
                    $return.='<td>';
                        
                        $isActive = data_get($perStoredInstallment, $i.'.active');
                        if($isActive == 0)
                        {
                            $return.='<input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$perStoredInstallmentKey.']['.$i.'][active]" value="1">';
                            
                            $return.='<input type="checkbox" name="woocommerce_'.$paymentId.'-installments['.$perStoredInstallmentKey.']['.$i.'][active]" value="0">';
                        } else {
                            $return.='<input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$perStoredInstallmentKey.']['.$i.'][active]" value="0">';
                            
                            $return.='<input type="checkbox" name="woocommerce_'.$paymentId.'-installments['.$perStoredInstallmentKey.']['.$i.'][active]" value="1" checked="checked">';
                        }

                        $return.='<input type="number" name="woocommerce_'.$paymentId.'-installments['.$perStoredInstallmentKey.']['.$i.'][value]" step="0.01" maxlength="4" size="4"  value="'.$perStoredInstallment[$i]['value'].'" />'; 
                    
                    $return.='</td>';
                }
            $return.='</tr>';
        }
 


        $return.= '</table></div>';  
        return $return;
    }

    /**
     * Generate Installment Table For Shortcode 
     * @return string
     */
    public function generateInstallmentsTableShortcode()
    {

        $storedData = get_option( 'woocommerce_mokapay-installments' ); 
        $return = '<div class="center"> <table id="comission-rates"> <thead> <tr><td>&nbsp;</td>';
 
        foreach(range(1,count(current($storedData))) as $perIns)
        {
            $return.= '<td>'.$perIns.' Taksit</td>';
        }

        $return.= '</tr></thead>';

        if(!$storedData)
        {
            return '';
        }
        
        foreach($storedData as $perStoredInstallmentKey => $perStoredInstallment)
        {
            $return.='<tr>';
                $imagePath =  plugins_url( 'moka-woocommerce/assets/img/cards/banks/' );

                $return.= '<tr>';
                $return.= '<td><img style="width:100px !important;max-width:unset;" src="'.$imagePath.$perStoredInstallmentKey.'.svg" /></td>';
                for ($i=1; $i < count($perStoredInstallment)+1 ; $i++) { 
                    $return.='<td>';
                        $return.=$perStoredInstallment[$i]['value'] != 0 ? $perStoredInstallment[$i]['value'].' '.get_option('woocommerce_currency') : '-'; 
                    $return.='</td>';
                }
            $return.='</tr>';
        }
 


        $return.= '</table></div>';  
        return $return;
    }

    /**
     * Generate Default Installments
     *
     * @param [type] $params
     * @return void
     */
    public function generateDefaultInstallmentsTableHtml( $params )
    {

        $storedData = get_option( 'woocommerce_mokapay-installments' );
        $avaliableInstallmentsCount = data_get($params, 'maxInstallment');
        $paymentId = data_get($params, 'paymentGatewayId');

        $return = '<div class="center"> <h2>Taksit Tablosu</h2> <table id="comission-rates"> <thead> <tr><td>Kart</td>';

        foreach($avaliableInstallmentsCount as $perIns)
        {
            $return.= '<td>'.$perIns.' Taksit</td>';
        }

        $return.= '</tr></thead>';

        if(!$storedData)
        {   
            foreach($this->getInstallments() as $perInstallmentKey => $perInstallment)
            {
                if(data_get($perInstallment, 'installments'))
                {
                    $imagePath =  plugins_url( 'moka-woocommerce/assets/img/cards/banks/' );

                    $return.= '<tr>';
                    $return.= '<td><img src="'.$imagePath.$perInstallmentKey.'.svg" /></td>';
                    foreach($avaliableInstallmentsCount as $perIns)
                    { 
                        $return.='
                            <td>
                                <input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$perInstallmentKey.']['.$perIns.'][active]" value="0">
                                
                                <input type="checkbox" name="woocommerce_'.$paymentId.'-installments['.$perInstallmentKey.']['.$perIns.'][active]" value="1" checked="checked">
                                
                                <input type="number" name="woocommerce_'.$paymentId.'-installments['.$perInstallmentKey.']['.$perIns.'][value]" step="0.01" maxlength="4" size="4"  value="0" />
                            </td>
                        ';
                    } 
                    $return.= '</tr>';
                }               
            }
        }

        $return.= '</table></div>';  
        return $return;
    }

    private function without3dPayment( $params ) {}
    private function with3dPayment( $params ) {}
    private function getPaymentOptions() {}
    private function doRequest( $params ) {}

}