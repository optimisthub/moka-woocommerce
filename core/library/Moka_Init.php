<?php

/**
 * Installment Table Shortcode suport
 *
 * @return void
 */
function installments_shortcode()
{
	?>
		<style>
 
			#comission-rates {
				font-family: Arial, Helvetica, sans-serif;
				border-collapse: collapse;
				width: 100%;
				font-size:12px;
			}

			#comission-rates td, #comission-rates th { 
			} 
 

			#comission-rates th {
				padding-top: 12px;
				padding-bottom: 12px;
				text-align: left;
				background-color: #04AA6D;
				color: white;
			} 
			#comission-rates .img {width:190px !important;}
		</style>
	<?php
	$table = new MokaPayment();
	return $table->generateInstallmentsTableShortcode();
}

/**
 * Moka Gateway Init.
 *
 * @param [type] $gateways
 * @return void
 */
function addOptimisthubMokaGateway( $gateways ) {
	$gateways[] = 'OptimistHub_Moka_Gateway'; 
	return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'addOptimisthubMokaGateway' );
add_shortcode( 'moka-taksit-tablosu', 'installments_shortcode' );

