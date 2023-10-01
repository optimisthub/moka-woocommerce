<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Moka POS Confiuration and Gateway Class for WooCommerce
 * @since 2.2
 */
class Moka_Init
{
	public function __construct()
	{
		add_action( 'wp_enqueue_scripts', [$this, 'pluginStyles'] );

		add_filter( 'woocommerce_payment_gateways', [$this, 'addOptimisthubMokaGateway'] );
		add_action( 'wp_dashboard_setup', [$this, 'optimisthubDashBoardWidgetInit']);

		add_shortcode( 'moka-taksit-tablosu', [$this, 'installmentShortcode'] );
		add_shortcode( 'moka-installment-table', [$this, 'installmentShortcode'] );

		add_filter( 'woocommerce_product_tabs', [$this, 'generateInstallmentProductTab'] );
		add_filter( 'woocommerce_get_price_html', [$this, 'renderMinInstallmentMessage'] );

		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );

	}

	public function add_meta_box( $post_type ) {
        if ( $post_type == 'product' ) {
			$options = get_option( 'woocommerce_mokapay_settings' );
			$limitInstallmentByProduct = data_get($options, 'limitInstallmentByProduct', 'no') === 'yes';
			if( $limitInstallmentByProduct ){
				add_meta_box(
					'moka_installment_limit',
					__( 'Installment Limit', 'moka-woocommerce' ),
					[ $this, 'render_meta_box_content' ],
					$post_type,
					'side'
				);
			}
        }
    }

	public function save_meta_box( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return ;
		}

		if ( 'product' != $_POST['post_type'] || !current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if(isset($_POST['_limitInstallment'])){
			if(intval($_POST['_limitInstallment'])>0){
				update_post_meta( $post_id, '_limitInstallment', intval($_POST['_limitInstallment']) );
			}else{
				delete_post_meta( $post_id, '_limitInstallment' );
			}
		}
	}

	public function render_meta_box_content( $post ) {
		$limitInstallment = get_post_meta( $post->ID, '_limitInstallment', true );
	?>
<select name="_limitInstallment">
	<option value="0"><?php _e( 'Default', 'moka-woocommerce' ); ?></option>

	<?php
			foreach(range(1, 12) as $_ment){
			?>
	<option value="<?php echo $_ment; ?>" <?php selected($limitInstallment, $_ment); ?>>
		<?php printf( __( '%s Installement', 'moka-woocommerce' ), $_ment ); ?>
	</option>
	<?php
			}
			?>
</select>
<?php
	}

	public function pluginStyles()
	{
		
		wp_register_style( 'moka-pay-card_css', OPTIMISTHUB_MOKA_URL. 'assets/moka.min.css' , false,   OPTIMISTHUB_MOKA_PAY_VERSION );
		wp_enqueue_style ( 'moka-pay-card_css' );

		wp_enqueue_script( 
			'moka-installment', 
			OPTIMISTHUB_MOKA_URL . 'assets/moka-installment.js',  
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
		echo '<p><strong>Ücretsiz</strong> Moka POS WooCommerce Eklentisi Optimist Hub Bünyesinde geliştirilmiştir. Açık kaynak kodlu eklentiye sizler de katkıda bulunabilirsiniz. <a href="https://github.com/optimisthub/moka-woocommerce">></a></p> <p style="display:flex;justify-content:center;align-items:center"> <a href="https://optimisthub.com/?utm_source=moka-woocommerce&utm_campaign=moka-woocommerce&utm_content='. get_bloginfo("wpurl").'" target="_blank"> <img style="width:220px" src="'.OPTIMISTHUB_MOKA_URL.'assets/img/optimisthub.svg'.'" alt=""> </a> </p>'; 
	}

	public function generateInstallmentProductTab($tabs)
	{
		$options = get_option( 'woocommerce_mokapay_settings' );
		$isavaliable = data_get($options, 'installment_tab_enable', 'yes') === 'yes';
		$tabPosition = data_get($options, 'installment_tab_position', 20);
		
		if( $isavaliable ) {
			$tabs['installment_tab'] = [
				'title'     => __( 'Installment Options', 'moka-woocommerce' ),
				'priority'  => $tabPosition,
				'callback'  => [$this, 'generateInstallmentProductTabContent']
			];
		}

		return $tabs;
	}

	public function generateInstallmentProductTabContent()
	{
		global $product, $woocommerce;

		$installments = get_option( 'woocommerce_mokapay-installments' );

		if( !$installments ) {
			return;
		}

		$productPrice = $product->get_price();

		if( !$productPrice ) {
			return;
		}

		$mokapay_settings = get_option( 'woocommerce_mokapay_settings' );
		$maxInstallment = self::calculateMaxInstallment($mokapay_settings, $product->get_id());

		$return = [ '<div class="installment--table--container">' ];
		
		if($installments && !empty($installments)){
			if(isset($installments['0-genel'])){ unset($installments['0-genel']); }

			foreach ($installments as $perKey => $perValue) {
				
				
				$rates = data_get($perValue, 'rates');
				if($rates)
				{
					if(isset($rates[0])){ unset($rates[0]); }
					if(isset($rates[1])){ unset($rates[1]); }
					
					$_thiz_data = [];
					$_any_data = false;
					foreach ($rates as $perRateKey => $perRateValue) 
					{
						if($perRateValue['value']>=0 && $perRateValue['active']==1)
						{
							if($perRateKey <= $maxInstallment){
								$_any_data = true;
								$returnPrice = self::calculateComission($perRateKey, $perRateValue['value'], $productPrice);

								$_thiz_data[] = '<div class="perrate">

									<div class="perInstallment">'.$perRateKey.'</div>
									<div class="perUnitPrice">'.self::moka_price($returnPrice['unit_price']).'</div>
									<div class="perTotal">'.self::moka_price($returnPrice['total_price']).'</div>

								</div>';
							}
						} else {
							$_thiz_data[] = '<div class="perrate"><div class="empty">-</div></div>';
						}
					}
					if($_thiz_data  && $_any_data){
						$return[] = '<div class="installment--table--column">';
						$return[] = '<div class="installment--table--head"><img src="'.OPTIMISTHUB_MOKA_URL.'assets/img/cards/banks/'.self::getCardName($perKey).'" title="'.$perKey.'" alt="'.$perKey.'" width="90" height="35"/></div>';
						$return[] = '<div class="installment--table--table-head">
							<div>'.__( 'Installment', 'moka-woocommerce' ).'</div>
							<div>'.__( 'Installment Amount', 'moka-woocommerce' ).'</div>
							<div>'.__( 'Total Amount', 'moka-woocommerce' ).'</div>
						</div>';
						$return[] = implode('', $_thiz_data);
						$return[] = '</div>';
					}
				}
				
			}
		}

		$return[] = '</div>';

		echo implode('', $return);
	}

	public function renderMinInstallmentMessage($price)
	{
		$return = $price;

		if(is_singular('product'))
		{
			global $product;
			global $woocommerce_loop;
 
			$productPrice = $product->get_price();

			$options = get_option( 'woocommerce_mokapay_settings' );
			$isavaliable = data_get($options, 'installment_message', 'yes') === 'yes';
			$limitInstallment = data_get($options, 'limitInstallment');
			$stock = $product->get_stock_status() == 'instock' ? true : false; 

			if(
				($stock && $isavaliable && $woocommerce_loop['name'] == '') && 
				in_array($product->get_type(), ['simple','variable']) 
			)
			{
				$installments = get_option( 'woocommerce_mokapay-installments' );
				$maxInstallment = self::calculateMaxInstallment($options, $product->get_id());
				$minRate = data_get(end($installments), 'rates.'.$maxInstallment.'.value');
				$minRatePrice = self::calculateComission($limitInstallment, $minRate, $productPrice);
		
				$return .= ' 
					<div class="min--installment--price"><span>'.self::moka_price($minRatePrice['unit_price']). '</span> ' .' \' '.__( 'With installments starting from', 'moka-woocommerce' ).' ...</div>';
			} else {
				$return = $price;
			}
			return $return;
		}

		return $return;
	}

	private function getCardName($string)
	{
		$images = [
			'133-kuveyt-turk-katilim-bankasi-a-s' => 'kuveyt-turk.svg',
			'101-imece-bank' => 'isbank.svg',
			'4-akbank-t-a-s' => 'axess.svg',
			'78-deniz-bank-a-s' => 'bonus.svg',
			'98-finansbank-a-s' => 'cardfinans.svg',
			'004-hscb-a-s' => 'advantage.svg',
			'217-ziraat-bankasi' => 'combo.svg',
			'122-is-bankasi-a-s' => 'maximum.svg',
			'108-halk-bankasi-a-s' => 'paraf.svg',
			'174-vakiflar-bankasi-t-a-o' => 'world.svg',
			'118-ing-bank-a-s' => 'bonus.svg'

		];
		return $images[$string];
	}

	private function calculateComission($installment, $percent, $price)
	{
		$realPercent 	= floatval( floatval($price) * floatval($percent) /100 );
		$totalPrice 	= floatval($price) + $realPercent; 

		$return = [
			'percent' 		=> floatval($percent),
			'price' 		=> floatval($price),
			'installment' 	=> intval($installment),
			'real_percent' 	=> floatval($realPercent),
			'total_price'	=> self::moka_number_format( $totalPrice ),
			'unit_price'	=> self::moka_number_format( ($totalPrice/intval($installment)) ), 
		];
 
		return $return;
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

	private function calculateMaxInstallment($mokapay_settings, $product_id){
		$selectedInstallment = data_get($mokapay_settings, 'limitInstallment', 12);
		$limitInstallmentByProduct = data_get($mokapay_settings, 'limitInstallmentByProduct', 'no') === 'yes';
		if( $limitInstallmentByProduct ) {
			$product_limitInstallment = get_post_meta($product_id, '_limitInstallment', true);
			if(
				$product_limitInstallment && 
				intval($product_limitInstallment)>0 &&
				$selectedInstallment > intval($product_limitInstallment)
			){
				$selectedInstallment = intval($product_limitInstallment);
			}
		}

		if(intval($selectedInstallment) > intval(data_get($mokapay_settings, 'limitInstallment', 12))){
			$selectedInstallment = intval(data_get($mokapay_settings, 'limitInstallment', 12));
		}

		return $selectedInstallment;
	}

}

new Moka_Init();
 