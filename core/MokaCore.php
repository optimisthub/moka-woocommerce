<?php

class MokaPayment
{

    private $apiEndPoint = '';
    private $apiUsername = '';
    private $apiSecret = '';

    public function __construct() {}

    public function initializePayment() {}

    public function payWith( $params ) {}

    public function requestBin( $params ) {}

    public function installment( $params ) {}

    private function without3dPayment( $params ) {}
    private function with3dPayment( $params ) {}
    private function getPaymentOptions() {}
    private function doRequest( $params ) {}

}