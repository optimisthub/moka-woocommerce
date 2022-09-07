<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display Payment History Logs On WC-Settings Tabs
 * @since 2.6
 * @copyright 2022 Optimisthub
 * @author Fatih Toprak 
 */
class Optimisthub_Transaction_History
{

    public function __construct()
    {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'moka_transactions';
        add_action( 'woocommerce_settings_tabs', [$this,'transactionTab'] );
        add_action( 'woocommerce_settings_moka_history', [$this, 'transactionHistory'] );
    }

    /**
     * Tab Title
     *
     * @return void
     */
    public function transactionTab()
    {
        $current_tab = ( isset($_GET['tab']) && $_GET['tab'] === 'moka_history' ) ? 'nav-tab-active' : '';
        echo '<a href="admin.php?page=wc-settings&tab=moka_history" class="nav-tab '.$current_tab.'">'.__( "Moka Payment History", "moka-woocommerce" ).'</a>';
    }

    /**
     * Tab Content
     *
     * @return void
     */
    public function transactionHistory()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM $this->tableName ORDER BY id DESC LIMIT 0,1000" ); 
        return self::__renderHtml($results);
    }

    private function __renderHtml($logs)
    {
        $output = '<style>
            .per-log {background:#fff;padding:10px;margin-bottom:10px;}
            .per-log.success {background:#70ff70}
        </style>';
        $output .= '<h2>'.__( "Moka Payment History", "moka-woocommerce" ).'</h2><hr>';
        foreach ($logs as $perLog)
        {
            $class = data_get($perLog, 'result');
            $extra = $class == 0 ? ' success ' : '' ;
            
            $errorMessage = false;
            $resultCode = data_get($perLog, 'result_code');
            if($resultCode != 'Success' && !empty($resultCode))
            {
                $errorMessage = true;
            }

            $color = $errorMessage ? ' color:red ': '';

            $output .= '<div class="per-log '.$extra.'">';
                $output .= '<strong>'.__("Order Id", "moka-woocommerce" ).' : </strong>' . data_get($perLog, 'id_cart'). ' - ';
                $output .= '<strong>'.__("Customer Id", "moka-woocommerce" ).' : </strong>' . data_get($perLog, 'id_customer'). ' - ';
                $output .= '<strong>'.__("Transaction Id", "moka-woocommerce" ).' : </strong>' . data_get($perLog, 'optimist_id'). ' - ';
                $output .= '<strong>'.__("Installement", "moka-woocommerce" ).' : </strong>' . data_get($perLog, 'installment'). ' - ';
                $output .= '<strong>'.__("Amount", "moka-woocommerce" ).' : </strong>' . data_get($perLog, 'amount'). ' / '. data_get($perLog, 'amount_paid'). '<br>';
                $output .= '<strong style="'.$color.'">'.data_get($perLog, 'result_message').'</strong> - ';
                $output .= '<i>'.data_get($perLog, 'created_at').'</i>';
            $output .= '</div>';
        }
        echo $output;
    }
}

new Optimisthub_Transaction_History();