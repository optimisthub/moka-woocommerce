<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('OPTIMISTHUB_MOKA_PAY_VERSION', '3.5.2');

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
		$this->assets = $this->assetDir();

		add_action( 'wp_enqueue_scripts', [$this, 'pluginStyles'] );

		add_filter( 'woocommerce_payment_gateways', [$this, 'addOptimisthubMokaGateway'] );
		add_action( 'wp_dashboard_setup', [$this, 'optimisthubDashBoardWidgetInit']);

		add_shortcode( 'moka-taksit-tablosu', [$this, 'installmentShortcode'] );
		add_shortcode( 'moka-installment-table', [$this, 'installmentShortcode'] );

		add_filter( 'woocommerce_product_tabs', [$this, 'generateInstallmentProductTab'] );
		add_filter( 'woocommerce_get_price_html', [$this, 'renderMinInstallmentMessage'] );


	}

	public function pluginStyles()
	{
		
		wp_register_style( 'moka-pay-card_css', $this->assets. 'moka.min.css' , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
		wp_enqueue_style ( 'moka-pay-card_css' );

		wp_enqueue_script( 
			'moka-installment', 
			$this->assets . 'moka-installment.js',  
			array( 'jquery' ),  
			OPTIMISTHUB_MOKA_PAY_VERSION,  
			true  
		);

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
		$assetDir = str_replace('/core/library/', '/assets/' , plugin_dir_url( __FILE__ ));
		echo '<p><strong>Ücretsiz</strong> Moka POS WooCommerce Eklentisi Optimist Hub Bünyesinde geliştirilmiştir. Açık kaynak kodlu eklentiye sizler de katkıda bulunabilirsiniz. <a href="https://github.com/optimisthub/moka-woocommerce">></a></p> <p style="display:flex;justify-content:center;align-items:center"> <a href="https://optimisthub.com/?ref='. get_bloginfo("wpurl").'&source=moka-woocommerce" target="_blank"> <img style="width:220px" src="'.$assetDir.'/img/optimisthub.svg'.'" alt=""> </a> </p>'; 
	}

	public function generateInstallmentProductTab($tabs)
	{
		$options = get_option( 'woocommerce_mokapay_settings' );
		$isavaliable = data_get($options, 'installment_tab_enable');
		$tabPosition = data_get($options, 'installment_tab_position',20);
		
		if($isavaliable && $isavaliable=='yes')
		{
			$tabs['installment_tab'] = array(
				'title'     => __( 'Installment Options', 'moka-woocommerce' ),
				'priority'  => $tabPosition,
				'callback'  => [$this, 'generateInstallmentProductTabContent']
			);
		}

		return $tabs;
	}

	public function generateInstallmentProductTabContent()
	{
		global $product, $woocommerce;

		$installments = get_option( 'woocommerce_mokapay-installments' );

		if(!$installments)
		{
			return;
		}

		$productPrice = $product->get_price();

		if(!$productPrice)
		{
			return;
		}

		$return = '<div class="installment--table--container">';
 
		unset($installments['0-genel']);

		foreach ($installments as $perKey => $perValue) {
			
			$return .= '<div class="installment--table--column">';
			$return .= '<div class="installment--table--head"><img src="'.self::assetDir().'img/cards/banks/'.self::getCardName($perKey).'" title="'.$perKey.'" alt="'.$perKey.'" width="90" height="35"/></div>';
			$return .= '<div class="installment--table--table-head"><div>Taksit</div><div>Taksit Tutarı</div> <div>Toplam Tutar</div></div>';
				if(data_get($perValue, 'rates'))
				{
					$rates = data_get($perValue, 'rates');
					unset($rates[0]);
					unset($rates[1]);
	 
					foreach ($rates as $perRateKey => $perRateValue) 
					{
						if($perRateValue['value']>=0 && $perRateValue['active']==1)
						{
							$returnPrice = self::calculateComission($perRateKey,$perRateValue['value'],$productPrice);
							
							$return .= '<div class="perrate">
							
								<div class="perInstallment">'.$perRateKey.'</div>
								<div class="perUnitPrice">'.$returnPrice['unit_price'].' '.  get_woocommerce_currency_symbol().'</div>
								<div class="perTotal">'.$returnPrice['total_price'].' '.  get_woocommerce_currency_symbol().'</div>
								
							</div>'; 

						} else {
							$return .= '<div class="perrate"><div class="empty">-</div></div>';
						}
					}
				}
			$return .= '</div>';
		
		}

		$return .= '</div>';

		echo $return;
	}

	public function renderMinInstallmentMessage($price)
	{
		if(is_singular('product'))
		{
			global $product;
			global $woocommerce_loop;
 
			$productPrice = $product->get_price();

			$options = get_option( 'woocommerce_mokapay_settings' );
			$isavaliable = data_get($options, 'installment_message');

			if($isavaliable == 'yes' && !is_shop() && $woocommerce_loop['name'] == '')
			{
				$installments = get_option( 'woocommerce_mokapay-installments' );
				$minRate = data_get(current($installments), 'rates.12.value');
				$minRatePrice = self::calculateComission(12,$minRate,$productPrice);
		
				echo $price.' 
					<div class="min--installment--price"><span>'.$minRatePrice['unit_price']. '</span> '.get_woocommerce_currency_symbol() .' \' '.__( 'With installments starting from', 'moka-woocommerce' ).' ...</div>';
			} else {
				echo $price;
			}
		}
	}

 
    private function assetDir()
    {
        return str_replace('/core/library/', '/assets/' , plugin_dir_url( __FILE__ ));
    }

	private function getCardName($string)
	{
		$images = [
			'133-kuveyt-turk-katilim-bankasi-a-s' => 'kuveyt-turk.png',
			'101-imece-bank' => 'isbank.png',
			'4-akbank-t-a-s' => 'axess.svg',
			'78-deniz-bank-a-s' => 'bonus.svg',
			'98-finansbank-a-s' => 'cardfinans.svg',
			'004-hscb-a-s' => 'advantage.svg',
			'217-ziraat-bankasi' => 'combo.svg',
			'122-is-bankasi-a-s' => 'maximum.svg',
			'108-halk-bankasi-a-s' => 'paraf.svg',
			'174-vakiflar-bankasi-t-a-o' => 'world.svg',
			'118-ing-bank-a-s' => 'bonus.png'

		];
		return $images[$string];
	}

	private function calculateComission($installment, $percent, $price)
	{
		$realPercent 	= ($price*$percent)/100;
		$totalPrice 	= $price+($realPercent); 

		$return = [
			'percent' 		=> $percent,
			'price' 		=> $price,
			'installment' 	=> $installment,
			'real_percent' 	=> $realPercent,
			'total_price'	=> number_format($totalPrice,2),
			'unit_price'	=> number_format($totalPrice/$installment,2) 
		];
 
		return $return;
	}

}

new Moka_Init();
 