<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('OPTIMISTHUB_MOKA_PAY_VERSION', '2.9.3');

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
		add_action( 'wp_dashboard_setup', [$this, 'optimisthubDashBoardWidgetInit']);

		add_shortcode( 'moka-taksit-tablosu', [$this, 'installmentShortcode'] );
		add_shortcode( 'moka-installment-table', [$this, 'installmentShortcode'] );
	}

	/**
	 * Installment Table Shortcode suport
	 *
	 * @return void
	 */
	public function installmentShortcode()
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

	/**
	 * Add Dashboard Information Box Widget Init.
	 *
	 * @return void
	 */
	public function optimisthubDashBoardWidgetInit() 
	{
		global $wp_meta_boxes;
		wp_add_dashboard_widget('custom_help_widget', 'Moka POS by Optimist Hub', [$this,'recentNewsByOptimisthub']);
	}
 
	/**
	 * Dashboard Widget Content
	 *
	 * @return void
	 */
	public function recentNewsByOptimisthub() 
	{
		echo '<p><strong>Ücretsiz</strong> Moka POS WooCommerce Eklentisi Optimist Hub Bünyesinde geliştirilmiştir. Açık kaynak kodlu eklentiye sizler de katkıda bulunabilirsiniz. <a href="https://github.com/optimisthub/moka-woocommerce">></a></p> <p style="display:flex;justify-content:center;align-items:center"> <a href="https://optimisthub.com/?ref='. get_bloginfo("wpurl").'&source=moka-woocommerce" target="_blank"> <img style="width:220px" src="'.plugins_url( 'moka-woocommerce-master/assets/img/optimisthub.svg' ).'" alt=""> </a> </p>'; 
	}

}

new Moka_Init();



