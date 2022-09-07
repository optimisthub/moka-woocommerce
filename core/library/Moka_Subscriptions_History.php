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
        $data = [];
        $period = ['Günlük', 'Haftalık', 'Aylık'];
        $status = ['Aktif', 'İptal Edildi'];

        for ($i=0; $i < 120; $i++) { 
            # code...
            $data[] = [
                'order_id'              => rand(2992,29928872),
                'user_id'               => rand(2882,20000),
                'order_amount'          => rand(20,298).'.00'. ' ' .get_option('woocommerce_currency'),
                'order_details'         => 'Detaylar bu kısma gelecek.',
                'subscription_period'   => $period[array_rand($period)],
                'subscription_status'   => $status[array_rand($status)],
                'created_at'            => rand(1,5).'.'.date('m.Y'), 
                'actions'               => '
                    <a href="#">Ödeme Yap</a> | 
                    <a href="#">İptal Et</a>
                ',
            ];
        }

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
        $order = 'asc';

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
}