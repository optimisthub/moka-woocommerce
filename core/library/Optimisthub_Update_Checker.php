<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
/**
 * Update Plugin Width Self Hosted Zip File
 * @version 1.0.0
 * @author Fatih Toprak
 */
class Optimisthub_Update_Checker 
{
    public $plugin_slug;
    public $version;
    public $cache_key;
    public $cache_allowed;

    public function __construct()
    {
        $this->plugin_slug      = OPTIMISTHUB_MOKA_BASENAME;
        $this->version          = OPTIMISTHUB_MOKA_PAY_VERSION;
        $this->cache_key        = 'moka_woocommerce_update_check';
        $this->cache_allowed    = true;
        $this->endpoint         = OPTIMISTHUB_MOKA_UPDATE . 'check';
        $this->platform         = 'wordpress'; 
        
        add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
        add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );
    }

    /**
     * Send Current Information to WooxUp Servers 
     *
     * @return void
     */
    public function request() 
    { 
        $remote = get_transient( $this->cache_key );
        if( $remote && $this->cache_allowed ) {
            return $remote;
        }else{    
            $remote = wp_remote_post( $this->endpoint,
                [
                    'timeout'       => 25,
                    'body'          => 
                    [
                        'platform'  => $this->platform,
                        'version'   => $this->version,
                    ],
                ]
            );    

            if(
                is_wp_error( $remote )
                || 200 != wp_remote_retrieve_response_code( $remote )
                || empty( wp_remote_retrieve_body( $remote ) )
            ) {
                return false;
            }

            $remote = json_decode( wp_remote_retrieve_body( $remote ) );

            if( !$remote || !isset($remote->data) ) 
            {
                return false;
            }

            $remote = data_get($remote,'data');
            
            set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

            return $remote;

        }
    }

    /**
     * Format Plugin information
     *
     * @param [object] $response
     * @param [string] $action
     * @param [array] $args
     * @return void
     */
    public function info( $response, $action, $args ) 
    {
        if( 'plugin_information' !== $action && $this->plugin_slug !== $args->slug ) {
            return $response;
        }

        if ( !isset( $args->slug ) || ( isset( $args->slug ) && empty( $args->slug ) ) ) {
            return $response;
        }
        
        if ( $this->plugin_slug !== $args->slug ) {
            return $response;
        }
        
        $remote = $this->request();
        
        if( !$remote ) {
            return $response;
        }
         
        $_response = new \stdClass();

        $_response->name              = data_get($remote, 'name');
        $_response->slug              = data_get($remote, 'slug');
        $_response->version           = data_get($remote, 'version');
        $_response->tested            = data_get($remote, 'tested');
        $_response->requires          = data_get($remote, 'requires');
        $_response->author            = data_get($remote, 'author');
        $_response->author_profile    = data_get($remote, 'author_profile');
        $_response->download_link     = data_get($remote, 'download_link');
        $_response->trunk             = data_get($remote, 'download_url');
        $_response->requires_php      = data_get($remote, 'requires_php');
        $_response->last_updated      = data_get($remote, 'last_updated');

        $_response->sections = [
            'description'   => data_get($remote, 'sections.description'),
            'changelog'     => data_get($remote, 'sections.changelog')
        ];  
 
        if( !empty( $remote->banners ) ) {
            $_response->banners = [
                'low'   => data_get($remote, 'banners.low'),
                'high'  => data_get($remote, 'banners.high'),
            ]; 
        }

        return $_response;
    }

    /**
     * Update Plugin
     *
     * @param [type] $transient
     * @return void
     */
    public function update( $transient ) {

        if ( isset($transient->checked ) && empty($transient->checked ) ) {
            return $transient;
        }

        $remote = $this->request();
        
        if(
            $remote 
            && data_get($remote, 'version') 
            && version_compare( $this->version, $remote->version, '<' )
        ) {
            $_response = new stdClass();
            $_response->slug          = $this->plugin_slug;
            $_response->plugin        = OPTIMISTHUB_MOKA_BASENAME; 
            $_response->new_version   = data_get($remote, 'version');
            $_response->tested        = data_get($remote, 'tested');
            $_response->package       = data_get($remote, 'download_url');

            if( is_array($transient->response) ){
                $transient->response[ $_response->plugin ] = $_response;
            }else{
                $transient->response = [
                    $_response->plugin => $_response,
                ];
            }

        } 

        return $transient;
    }

    /**
     * Purge Stored Transients
     * 
     * @since 2.4
     * @copyright 2022 Optimisthub
     * @author Fatih Toprak 
     * @return void
     */
    public function purge()
    {
        global $options;
        if (
            $this->cache_allowed
            && isset($options['action']) && $options['action'] && 'update' == data_get($options, 'action') 
            && isset($options['type']) && $options['type'] && 'plugin' == data_get($options, 'type') 
        ) {
            delete_transient( $this->cache_key );
        }

    }
}

new Optimisthub_Update_Checker();