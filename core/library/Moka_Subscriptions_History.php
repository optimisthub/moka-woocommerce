<?php
    if (!class_exists('WP_List_Table')) {
        require_once(ABSPATH . 'wp-admin/includes/screen.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }


/**
 * Get Subscriptions Data from Subscriptions History Table with WP_List_Table
 * @since 3.0
 * @copyright 2022 Optimisthub
 * @author Fatih Toprak 
 */
class Optimisthub_Moka_Subscriptions_History_List_Tabley extends WP_List_Table
{

    public $tableName = 'moka_subscriptions';

    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );

        $perPage = 15;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'order_id'              => 'WooCommerce ID',
            'user_id'               => __('User ID', 'moka-woocommerce'),
            'order_amount'          => __('Amount', 'moka-woocommerce'),
            'order_details'         => __('Details', 'moka-woocommerce'),
            'subscription_period'   => __('Period', 'moka-woocommerce'),
            'subscription_status'   => __('Status', 'moka-woocommerce'),
            'created_at'            => __('Created At', 'moka-woocommerce'), 
            'actions'               => __('Actions', 'moka-woocommerce'),
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return [];
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return [
            'order_id' => ['order_id', false],
            'user_id' => ['user_id', false],
            'order_amount' => ['order_amount', true],
            'subscription_period' => ['subscription_period', true],
            'subscription_status' => ['subscription_status', true],
            'created_at' => ['created_at', true],
        ];
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        global  $wpdb;
        $table  = $wpdb->prefix . $this->tableName;
        $data   =  $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" , ARRAY_A );     
        $data   = self::formatTableDataResults($data);
        return $data;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'order_id':
            case 'user_id':
            case 'order_amount':
            case 'order_details':
            case 'subscription_period':
            case 'subscription_status':
            case 'created_at': 
            case 'actions':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'order_id';
        $order = 'desc';

        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'];
        }


        $result = strcmp( $a[$orderby], $b[$orderby] );

        if($order === 'asc')
        {
            return $result;
        }

        return -$result;
    }

    private function formatTableDataResults($data)
    {
        $return = [];

        if($data)
        {
            foreach($data as $key => $perRow)
            {

                $userId         = data_get($perRow, 'user_id', 0);
                $optimistId     = data_get($perRow, 'optimist_id', 0);
                $orderId        = data_get($perRow, 'order_id', 0);
                $userData       = get_user_by( 'id', $userId ); 
                $orderDetails   = json_decode( data_get($perRow, 'order_details'), true );
                $currency       = data_get($orderDetails, 'Currency', 0);
                $status         = data_get($perRow, 'subscription_status', null);

                ray($userId);

                $return[] = 
                [
                    'order_id'      => '<a href="'.esc_url(get_admin_url().'post.php?post='.$orderId).'&action=edit">'.$orderId.'</a><br><span style="font-size:11px">'.$optimistId.'</span>',
                    'user_id'       => '<a href="'.esc_url(get_admin_url().'users.php?s='.$userData->user_nicename).'">'.$userData->user_nicename.'</a>',
                    'order_amount'  => esc_html(data_get($perRow, 'order_amount',0.0) . ' ' .$currency),
                    'order_details' => 
                    data_get($orderDetails, 'CustomerDetails.FirstName').' '.data_get($orderDetails, 'CustomerDetails.LastName').'<br>'.
                    '<a href="tel:'.data_get($orderDetails, 'CustomerDetails.GsmNumber').'">'.data_get($orderDetails, 'CustomerDetails.GsmNumber').'</a>'.'<hr>'.
                        data_get($orderDetails, 'CardNumber').'<br>',
                    'subscription_period' => data_get($perRow, 'subscription_period', null),
                    'subscription_status' => $status == 0 ? '<mark class="active_subs">Aktif</mark>' : '<mark class="passive_subs">Pasif</mark>',
                    'created_at' => date('d.m.Y.H:i:s', strtotime(data_get($perRow, 'created_at', null))),
                    'actions' => 
                        ($status == 0) ? '
                        <span class="subscription-payManually">Ödeme</span>
                        <span class="subscription-cancelManually">İptal</span>
                        ' : '
                        <span class="subscription-noActions">Düzenlenemez</span>'
                ];
            }
        }

        return $return;

    }
}