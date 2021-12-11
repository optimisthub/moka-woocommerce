<?php

function isNewVersionAvaliable(){
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

function addOptimisthubMokaGateway( $gateways ) {
	$gateways[] = 'OptimistHub_Moka_Gateway'; 
	return $gateways;
}

add_filter( 'woocommerce_payment_gateways', 'addOptimisthubMokaGateway' );
add_action( 'admin_notices', 'isNewVersionAvaliable');