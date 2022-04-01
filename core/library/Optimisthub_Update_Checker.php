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
        global $mokaVersion;  
        $this->plugin_slug      = 'moka-woocommerce-master';
        $this->version          = $mokaVersion;
        $this->cache_key        = 'moka_worker_update_check';
        $this->cache_allowed    = true;
        $this->endpoint         = 'https://moka.wooxup.com/check';
        $this->platform         = 'wordpress'; 
        
        add_filter( 'plugins_api', [$this, 'info' ], 20, 3 );
        add_filter( 'site_transient_update_plugins', [$this, 'update' ] );
        add_action( 'upgrader_process_complete', [$this, 'purge'], 10, 2 );
    }

    /**
     * Send Current Information to WooxUp Servers 
     *
     * @return void
     */
    public function request() 
    { 
        $remote = get_transient( $this->cache_key );
        if( false === $remote || ! $this->cache_allowed ) 
        {    
            $remote = wp_remote_post( $this->endpoint,
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
                        'version'   =>  $this->version,
                    ],
                    'cookies'     => [],
                ]
            );    

            if(
            is_wp_error( $remote )
                || 200 !== wp_remote_retrieve_response_code( $remote )
                || empty( wp_remote_retrieve_body( $remote ) )
            ) {
                return false;
            }
            
            set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );
        $remote = data_get($remote,'data');

        if( ! $remote ) 
        {
            return false;
        }

        return $remote;
    }

    /**
     * Format Plugin information
     *
     * @param [object] $res
     * @param [string] $action
     * @param [array] $args
     * @return void
     */
    public function info( $res, $action, $args ) 
    {
        if( 'plugin_information' !== $action ) {
            return false;
        }
        
        if( $this->plugin_slug !== $args->slug ) {
            return false;
        }
        
        $remote = $this->request();
        
        if( ! $remote ) {
            return false;
        }
         
        $res = new stdClass();

        $res->name          = data_get($remote, 'name');
        $res->slug          = data_get($remote, 'slug');
        $res->version       = data_get($remote, 'version');
        $res->tested        = data_get($remote, 'tested');
        $res->requires      = data_get($remote, 'requires');
        $res->author        = data_get($remote, 'author');
        $res->author_profile= data_get($remote, 'author_profile');
        $res->download_link = data_get($remote, 'download_link');
        $res->trunk         = data_get($remote, 'download_url');
        $res->requires_php  = data_get($remote, 'requires_php');
        $res->last_updated  = data_get($remote, 'last_updated');

        $res->sections = [
            'description'   => data_get($remote, 'sections.description'),
            'changelog'     => data_get($remote, 'sections.changelog')
        ];  
 

        if( ! empty( $remote->banners ) ) {
            $res->banners = [
                'low'   => data_get($remote, 'banners.low'),
                'high'  => data_get($remote, 'banners.high'),
            ]; 
        }

        return $res;
    }

    /**
     * Update Plugin
     *
     * @param [type] $transient
     * @return void
     */
    public function update( $transient ) {

        if ( empty($transient->checked ) ) {
            return $transient;
        }

        $remote = $this->request();
        
        if(
            $remote
            && version_compare( $this->version, $remote->version, '<' )
        ) {
            $res = new stdClass();
            $res->slug          = $this->plugin_slug;
            $res->plugin        = 'moka-woocommerce-master/index.php'; 
            $res->new_version   = data_get($remote, 'version');
            $res->tested        = data_get($remote, 'tested');
            $res->package       = data_get($remote, 'download_url');

            $transient->response[ $res->plugin ] = $res;

        } 

        return $transient;
    }

    public function purge()
    {
        global $options;
        if (
            $this->cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options[ 'type' ]
        ) {
            delete_transient( $this->cache_key );
        }

    }
}

new Optimisthub_Update_Checker();