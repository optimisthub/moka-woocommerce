<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
class MokaPayment
{
    private $mokaOptions            = [];
    private $apiHost                = null;
    private $productionApiHost      = 'https://service.moka.com';
    private $testApiHost            = 'https://service.refmoka.com'; 

    public function __construct() 
    {
        $this->mokaOptions  = get_option('woocommerce_mokapay_settings');
        $this->apiHost      = self::apiHost($this->mokaOptions);
        self::mokaKey($this->mokaOptions);  
    }

    /**
     * Initialize Payment
     *
     * @param [type] $params
     * @return void
     */
    public function initializePayment($params) 
    {
        $method = self::payWith($this->mokaOptions);

        global $mokaKey;

        $postParams = [
            'PaymentDealerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'PaymentDealerRequest' => $params
        ]; 


        $paymentRequest = self::doRequest($method, $postParams);
        if(data_get($paymentRequest, 'response.code') && data_get($paymentRequest, 'response.code') == 200)
        {
            $responseBody = data_get($paymentRequest, 'body');
            $responseBody = json_decode($responseBody, true);
            return $responseBody;
        }
    }

    /**
     * Decide payment type
     *
     * @param [type] $params
     * @return void
     */
    public function payWith($params) 
    {
        $isEnable3D = 'yes' === data_get($params, 'enable_3d');
        return $isEnable3D ? '/PaymentDealer/DoDirectPaymentThreeD' : '/PaymentDealer/DoDirectPaymentThreeD';
    }

    /**
     * Request Bin number for card details
     * @usage : self::requestBin(['binNumber' => '529876'])
     *
     * @param [type] $params
     * @return void
     */
    public function requestBin($params) 
    {
        global $mokaKey;

        $postParams = [
            'PaymentDealerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'BankCardInformationRequest' => 
            [
                'BinNumber' => data_get($params, 'binNumber') 
            ]
        ]; 

        $response = self::doRequest('/PaymentDealer/GetBankCardInformation',$postParams);
        
        if(data_get($response, 'response.code') && data_get($response, 'response.code') == 200)
        {
            $responseBody = data_get($response, 'body');
            $responseBody = json_decode($responseBody, true);
            $responseBody = data_get($responseBody, 'Data');
            return $responseBody;
        }
        
        return $response;        
    }

    /**
     * Get Dealer Installment and any other Information For backend.
     *
     * @return void
     */
    public function getDealerInformation()
    {
        global $mokaKey;

        $postParams = [
            'DealerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'DealerRequest' => 
            [
                'DealerCode' => data_get($this->mokaOptions, 'company_code') 
            ]
        ]; 

        $response = self::doRequest('/Dealer/GetDealer',$postParams);

        if(data_get($response, 'response.code') && data_get($response, 'response.code') == 200)
        {
            $responseBody = data_get($response, 'body');
            $responseBody = json_decode($responseBody, true);
            $responseBody = data_get($responseBody, 'Data'); 
            return $responseBody;
        }
        
        return $response; 
    }

    /**
     * Fetch Installemnts From Server
     *
     * @return void
     */
    public function getInstallments() 
    {
        $avaliableInstallments = self::getDealerInformation();
        return $avaliableInstallments;
    }

    /**
     * Set Posted Installments Data to Options Table
     *
     * @param [type] $params
     * @return void
     */
    public function setInstallments($params)
    {
        update_option('woocommerce_mokapay-installments', $params); 
    }

    /**
     * Generate Installment Table For Backend
     *
     * @param [type] $params
     * @return void
     */
    public function generateInstallmentsTableHtml($params)
    {

        $storedData = get_option( 'woocommerce_mokapay-installments' );
        $avaliableInstallmentsCount = data_get($params, 'maxInstallment');
        $paymentId = data_get($params, 'paymentGatewayId');

        if(!$storedData)
        {
            $installments = self::getInstallments();
            $installments = data_get($installments, 'CommissionList');
            $installments = self::formatInstallmentResponse($installments);  
            $storedData = $installments;
        } 

        $return = '<div class="center-title"> <h2><span>Taksit Tablosu</span> <a class="js-update-comission-rates">Taksit oranlarını Moka üzerinden güncelle</a></h2> <table id="comission-rates"> <thead> <tr><td>Kart</td>';

        foreach($avaliableInstallmentsCount as $perIns)
        {
            $return.= '<td>'.$perIns.' Taksit</td>';
        }

        $return.= '</tr></thead>';
 
        foreach($storedData as $perStoredInstallmentKey => $perStoredInstallment)
        {
            $return.='<tr>';
                $imagePath =  plugins_url( 'moka-woocommerce-master/assets/img/cards/banks/' );
       
                $return.= '<tr>';
                $cardImageSlug = data_get($perStoredInstallment, 'groupName');
                $slug = sanitize_title(data_get($perStoredInstallment, 'bankName')); 
                $cardSymbol = '<img src="'.$imagePath.$cardImageSlug.'.svg"/>';

                if(!$cardImageSlug)
                {
                    $cardSymbol = data_get($perStoredInstallment, 'bankName');
                }

                $return.= '<td width="100">'.$cardSymbol.'</td>';

                $rates = data_get($perStoredInstallment, 'rates');

                $return.='<input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$slug.'][groupName]" value="'.data_get($perStoredInstallment, 'groupName').'">';
                $return.='<input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$slug.'][bankName]" value="'.data_get($perStoredInstallment, 'bankName').'">';

                for ($i=1; $i < count($rates)+1 ; $i++) { 
                    $return.='<td>';
                        
                        $isActive = data_get($rates, $i.'.active');
                 
                        if($isActive == 0)
                        {
                            $return.='<input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$slug.'][rates]['.$i.'][active]" value="1">';
                            
                            $return.='<input type="checkbox" name="woocommerce_'.$paymentId.'-installments['.$slug.'][rates]['.$i.'][active]" value="0">';
                        } else {
                            $return.='<input type="hidden" name="woocommerce_'.$paymentId.'-installments['.$slug.'][rates]['.$i.'][active]" value="0">';
                            
                            $return.='<input type="checkbox" name="woocommerce_'.$paymentId.'-installments['.$slug.'][rates]['.$i.'][active]" value="1" checked="checked">';
                        }

                        $return.='<input type="number" name="woocommerce_'.$paymentId.'-installments['.$slug.'][rates]['.$i.'][value]" step="0.01" maxlength="4" size="4"  value="'.$rates[$i]['value'].'" />'; 
                    
                    $return.='</td>';
                }
            $return.='</tr>';
        }

        $return.= '</table></div>';  
        echo $return;
    }

    /**
     * Generate Installment Table For Shortcode 
     * @return string
     */
    public function generateInstallmentsTableShortcode()
    {

        $storedData = get_option( 'woocommerce_mokapay-installments' ); 

        if(!$storedData)
        {
            return 'Lütfen Moka Pay ayarlarından, taksit seçeneğini aktif edip ayarları kaydedin.';
        }

        $return = '<div class="center"> <table id="comission-rates"> <thead> <tr><td>&nbsp;</td>';
    
        foreach(range(1,count(current($storedData)['rates'])) as $perIns)
        {
            $return.= '<td>'.$perIns.' Taksit</td>';
        }

        $return.= '</tr></thead>';
        
        foreach($storedData as $perStoredInstallmentKey => $perStoredInstallment)
        {
          
            $return.='<tr>';
                $imagePath =  plugins_url( 'moka-woocommerce-master/assets/img/cards/banks/' );
                $cardImageSlug = data_get($perStoredInstallment, 'groupName');
                $cardSymbol = '<img style="width:100px !important;max-width:unset;" src="'.$imagePath.$cardImageSlug.'.svg" />';
                if(!$cardImageSlug)
                {
                    $cardSymbol = data_get($perStoredInstallment, 'bankName');
                }
                $return.= '<tr>';
                $return.= '<td>'.$cardSymbol.'</td>';
                for ($i=1; $i < count(data_get($perStoredInstallment, 'rates'))+1 ; $i++) { 
                    $return.='<td>';
                        $return.=data_get($perStoredInstallment, 'rates')[$i]['value'] != 0 ? data_get($perStoredInstallment, 'rates')[$i]['value'].' '.get_option('woocommerce_currency') : '-'; 
                    $return.='</td>';
                }
            $return.='</tr>';
        }
 
        $return.= '</table></div>';  
        return $return;
    }

    /**
     * Generate Moka Key Hash
     *
     * @param [array] $params
     * @return void
     */
    private function mokaKey($params)
    {
        global $mokaKey;

        $dealer     = data_get($params, 'company_code');
        $username   = data_get($params, 'api_username');
        $password   = data_get($params, 'api_password');

        $output     = hash("sha256", $dealer . "MK" . $username . "PD" . $password);
        $mokaKey    = $output;
        
        return $mokaKey; 
    }

    /**
     * Select Api Host
     *
     * @param [array] $params
     * @return void
     */
    private function apiHost($params)
    {
        $isTestMode = 'yes' === data_get($params, 'testmode');
        return $isTestMode ? $this->testApiHost : $this->productionApiHost;
    }

    /**
     * Make Request to Moka
     *
     * @param [string] $method
     * @param [array] $params
     * @return void
     */
    private function doRequest($method, $params)
    {
        return wp_remote_post($this->apiHost.$method,
            [
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => [
                    'Content-Type' => 'application/json'
                ],
                'body'        => json_encode($params),
                'cookies'     => [],
            ]
        );   
    }

    /**
     * Format Default Installment Response
     *
     * @param [array] $response
     * @return void
     */
    public function formatInstallmentResponse($response)
    {
        $output = [];

        foreach ($response as $perItemKey => $perItem)
        {
            $slug = sanitize_title(data_get($perItem, 'Bank'));
       
            $output[$slug] = [
                'groupName' => strlen(data_get($perItem, 'GroupName'))>0 ? strtolower(data_get($perItem, 'GroupName',null)) : null ,
                'bankName' => data_get($perItem, 'Bank'),
                'rates' => [],
            ];

            $output[$slug]['rates']['1'] = [
                'active' => 1,
                'value'  => data_get($perItem, 'CommissionRate')
            ]; 
            foreach(range(2,12) as $i)
            { 
                $output[$slug]['rates'][$i] = [
                    'active'    => 1,
                    'value'     => data_get($perItem, 'CommissionRate'.$i),
                ]; 
            }  

            ## setAdvantage
            if(strtolower(data_get($perItem, 'GroupName')) == 'cardfinans')
            {
                $output['advantage'] = [
                    'groupName' => 'advantage',
                    'bankName' => '004 - HSCB A.Ş'
                ];
                $output['advantage']['rates']['1'] = [
                    'active' => 1,
                    'value'  => data_get($perItem, 'CommissionRate')
                ]; 
                foreach(range(2,12) as $i)
                { 
                    $output['advantage']['rates'][$i] = [
                        'active'    => 1,
                        'value'     => data_get($perItem, 'CommissionRate'.$i),
                    ]; 
                } 
            }
            ## setAdvantage

        }    
        return $output;
    }

    /**
     * Add Customer and Card to Moka 
     *
     * @param [array] $postParams
     * @return array
     */
    public function addCustomerWithCard($params)
    {
        global $mokaKey;

        $params = data_get($params, 'CustomerDetails');

        $postParams = [
            'DealerCustomerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'DealerCustomerRequest' => 
            [
                'CustomerCode'      => data_get($params, 'CustomerCode'),
                'FirstName'         => data_get($params, 'FirstName'),
                'LastName'          => data_get($params, 'LastName'),
                'Gender'            => data_get($params, 'Gender'), // 1 male 2 female
                'BirthDate'         => data_get($params, 'BirthDate'), // YYYYMMDD
                'GsmNumber'         => data_get($params, 'GsmNumber'),
                'Email'             => data_get($params, 'Email'),
                'Address'           => data_get($params, 'Address'),
                'CardHolderFullName'=> data_get($params, 'CardHolderFullName'),
                'CardNumber'        => data_get($params, 'CardNumber'),
                'ExpMonth'          => data_get($params, 'ExpMonth'), // MM
                'ExpYear'           => data_get($params, 'ExpYear'), // YYYY
                'CardName'          => data_get($params, 'CardName'), // Ex : My MasterCard, My Visa, etc
            ]
        ];  
        
        $response = self::doRequest('/DealerCustomer/AddCustomerWithCard',$postParams);
 

        if(data_get($response, 'response.code') && data_get($response, 'response.code') == 200)
        {
            $responseBody = data_get($response, 'body');
            $responseBody = json_decode($responseBody, true);
            $responseBodyResultsCode = data_get($responseBody, 'ResultCode');
            $responseBody = data_get($responseBody, 'Data');
 
            if(is_null($responseBody)) {
                $responseBody = $this->getCustomerByCustomerCode($params);
            }   

            if($responseBodyResultsCode == 'DealerCustomer.AddCustomerWithCard.CustomerCodeAlreadyUsing')
            {
                $postParams['DealerCustomerId'] = data_get($responseBody, 'DealerCustomer.DealerCustomerId');
 
                $cardCount = data_get($responseBody, 'CardListCount');
                if($cardCount==0)
                {
                    $responseBody = $this->addCard($postParams); 
                } else {
                    $postParams['CardToken'] = data_get($responseBody, 'CardList.0.CardToken');
                    $this->removeCard($postParams); 
                    $responseBody = $this->addCard($postParams);  
                }
            }

            return $responseBody;
        }

        return $response; 
    }

    /**
     * Get customer Information
     *
     * @param [type] $params
     * @return void
     */
    public function getCustomerByCustomerCode($params)
    {
        global $mokaKey;

        $postParams = [
            'DealerCustomerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'DealerCustomerRequest' => 
            [
                'CustomerCode' => data_get($params, 'CustomerCode'),
            ]
        ]; 

        $response = self::doRequest('/DealerCustomer/GetCustomer',$postParams);
        
        if(data_get($response, 'response.code') && data_get($response, 'response.code') == 200)
        {
            $responseBody = data_get($response, 'body');
            $responseBody = json_decode($responseBody, true);
            $responseBody = data_get($responseBody, 'Data');
            return $responseBody;
        }
        
        return $response; 
    }

    public function addCard($params)
    {
        global $mokaKey;

        $postParams = [
            'DealerCustomerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'DealerCustomerRequest' => 
            [
                'DealerCustomerId'  => data_get($params, 'DealerCustomerId'),
                'CardHolderFullName'=> data_get($params, 'DealerCustomerRequest.CardHolderFullName'),
                'CardNumber'        => data_get($params, 'DealerCustomerRequest.CardNumber'),
                'ExpMonth'          => data_get($params, 'DealerCustomerRequest.ExpMonth'), // MM
                'ExpYear'           => data_get($params, 'DealerCustomerRequest.ExpYear'), // YYYY
                'CardName'          => data_get($params, 'DealerCustomerRequest.CardName'), // Ex : M
            ]
        ]; 

        $response = self::doRequest('/DealerCustomer/AddCard',$postParams);
        
        if(data_get($response, 'response.code') && data_get($response, 'response.code') == 200)
        {
            $responseBody = data_get($response, 'body');
            $responseBody = json_decode($responseBody, true);
            $responseBody = data_get($responseBody, 'Data');
            return $responseBody;
        }
        
        return $response; 
    }

    public function removeCard($params)
    {
        global $mokaKey;

        $postParams = [
            'DealerCustomerAuthentication' => 
            [
                'DealerCode'=> data_get($this->mokaOptions, 'company_code'),
                'Username'  => data_get($this->mokaOptions, 'api_username'),
                'Password'  => data_get($this->mokaOptions, 'api_password'),
                'CheckKey'  => $mokaKey,
            ],
            'DealerCustomerRequest' => 
            [
                'CardToken'  => data_get($params, 'CardToken'), 
            ]
        ]; 

        $response = self::doRequest('/DealerCustomer/RemoveCard',$postParams);
        
        if(data_get($response, 'response.code') && data_get($response, 'response.code') == 200)
        {
            $responseBody = data_get($response, 'body');
            $responseBody = json_decode($responseBody, true);
            $responseBody = data_get($responseBody, 'Data');
            return $responseBody;
        }
        
        return $response; 
    }

}