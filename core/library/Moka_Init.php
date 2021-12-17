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
 * Check new version avalibiality
 *
 * @return boolean
 */
function isNewVersionAvaliable()
{
 	$update = new OptimisthubUpdateChecker();
	$versionControl = $update->check();
	$body = json_decode( data_get($versionControl, 'body') );
 
	if(data_get($versionControl, 'response.code') &&  data_get($versionControl, 'response.code') == 200)
	{
		if(data_get($body,'data.older_versions'))
		{
			echo '<div class="notice notice-error is-dismissible"> <p><strong>Moka PAY Security Alert : </strong>'.data_get($body, 'data.message').'.<br><a href="'.current(data_get($body, 'data.older_versions')).'" target="_blank">Download Latest Version</a></p> </div>';
		}

	}
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
add_action( 'admin_notices', 'isNewVersionAvaliable');

function generateSessionHandler()
{
    if (!session_id())
        session_start();
}
add_action("init", "generateSessionHandler", 1);