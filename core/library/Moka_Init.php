<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('OPTIMISTHUB_MOKA_PAY_VERSION', '2.3');

global $mokaVersion;
$mokaVersion = OPTIMISTHUB_MOKA_PAY_VERSION;

/**
 * Init Moka POS Confiuration and Gateway Class for WooCommerce
 * @since 2.2
 */
class Moka_Init
{
	public function __construct()
	{
		add_filter( 'woocommerce_payment_gateways', [$this, 'addOptimisthubMokaGateway'] );
		add_shortcode( 'moka-taksit-tablosu', [$this, 'installments_shortcode'] );
	}

	/**
	 * Installment Table Shortcode suport
	 *
	 * @return void
	 */
	public function installments_shortcode()
	{
		$return = '<style>#comission-rates{font-family:Arial,Helvetica,sans-serif;border-collapse:collapse;width:100%;font-size:12px}#comission-rates th{padding-top:12px;padding-bottom:12px;text-align:left;background-color:#04aa6d;color:#fff}#comission-rates .img{width:190px!important}</style>';
		$table = new MokaPayment();
		$return.=$table->generateInstallmentsTableShortcode();
		return $return;
	}

	/**
	 * Moka Gateway Init.
	 *
	 * @param [type] $gateways
	 * @return void
	 */
	public function addOptimisthubMokaGateway( $gateways ) {
		$gateways[] = 'OptimistHub_Moka_Gateway'; 
		return $gateways;
	}
}

new Moka_Init();



