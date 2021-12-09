<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OptimisthubUpdateChecker 
{
    private $endpoint = 'https://moka.wooxup.com/check';
    private $platform = 'wordpress';
    private $currentVersion = '0.1';

    public function check() {
        $response = wp_remote_post( $this->endpoint,
            [
                'method'      => 'POST',
                'timeout'     => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => [],
                'body'        => 
                [
                    'platform'  =>  $this->platform,
                    'version'   =>  $this->currentVersion,
                ],
                'cookies'     => [],
            ]
        );    
        
        return $response;
    }

}