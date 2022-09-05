<?php
    if (!class_exists('WP_List_Table')) {
        require_once(ABSPATH . 'wp-admin/includes/screen.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }


/**
 * Create a new table class that will extend the WP_List_Table
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

        $perPage = 5;
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
            'user_id'               => 'User ID',
            'order_amount'          => 'Amount',
            'order_details'         => 'Details',
            'subscription_period'   => 'Period',
            'subscription_status'   => 'Status',
            'created_at'            => 'Created At',
            'updated_at'            => 'Created At',
            'actions'               => 'Actions',
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
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }

    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();

        $data[] = array(
                    'id'          => 1,
                    'title'       => 'The Shawshank Redemption',
                    'description' => 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.',
                    'year'        => '1994',
                    'director'    => 'Frank Darabont',
                    'rating'      => '9.3'
                    );

        $data[] = array(
                    'id'          => 2,
                    'title'       => 'The Godfather',
                    'description' => 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.',
                    'year'        => '1972',
                    'director'    => 'Francis Ford Coppola',
                    'rating'      => '9.2'
                    );

        $data[] = array(
                    'id'          => 3,
                    'title'       => 'The Godfather: Part II',
                    'description' => 'The early life and career of Vito Corleone in 1920s New York is portrayed while his son, Michael, expands and tightens his grip on his crime syndicate stretching from Lake Tahoe, Nevada to pre-revolution 1958 Cuba.',
                    'year'        => '1974',
                    'director'    => 'Francis Ford Coppola',
                    'rating'      => '9.0'
                    );

        $data[] = array(
                    'id'          => 4,
                    'title'       => 'Pulp Fiction',
                    'description' => 'The lives of two mob hit men, a boxer, a gangster\'s wife, and a pair of diner bandits intertwine in four tales of violence and redemption.',
                    'year'        => '1994',
                    'director'    => 'Quentin Tarantino',
                    'rating'      => '9.0'
                    );

        $data[] = array(
                    'id'          => 5,
                    'title'       => 'The Good, the Bad and the Ugly',
                    'description' => 'A bounty hunting scam joins two men in an uneasy alliance against a third in a race to find a fortune in gold buried in a remote cemetery.',
                    'year'        => '1966',
                    'director'    => 'Sergio Leone',
                    'rating'      => '9.0'
                    );

        $data[] = array(
                    'id'          => 6,
                    'title'       => 'The Dark Knight',
                    'description' => 'When Batman, Gordon and Harvey Dent launch an assault on the mob, they let the clown out of the box, the Joker, bent on turning Gotham on itself and bringing any heroes down to his level.',
                    'year'        => '2008',
                    'director'    => 'Christopher Nolan',
                    'rating'      => '9.0'
                    );

        $data[] = array(
                    'id'          => 7,
                    'title'       => '12 Angry Men',
                    'description' => 'A dissenting juror in a murder trial slowly manages to convince the others that the case is not as obviously clear as it seemed in court.',
                    'year'        => '1957',
                    'director'    => 'Sidney Lumet',
                    'rating'      => '8.9'
                    );

        $data[] = array(
                    'id'          => 8,
                    'title'       => 'Schindler\'s List',
                    'description' => 'In Poland during World War II, Oskar Schindler gradually becomes concerned for his Jewish workforce after witnessing their persecution by the Nazis.',
                    'year'        => '1993',
                    'director'    => 'Steven Spielberg',
                    'rating'      => '8.9'
                    );

        $data[] = array(
                    'id'          => 9,
                    'title'       => 'The Lord of the Rings: The Return of the King',
                    'description' => 'Gandalf and Aragorn lead the World of Men against Sauron\'s army to draw his gaze from Frodo and Sam as they approach Mount Doom with the One Ring.',
                    'year'        => '2003',
                    'director'    => 'Peter Jackson',
                    'rating'      => '8.9'
                    );

        $data[] = array(
                    'id'          => 10,
                    'title'       => 'Fight Club',
                    'description' => 'An insomniac office worker looking for a way to change his life crosses paths with a devil-may-care soap maker and they form an underground fight club that evolves into something much, much more...',
                    'year'        => '1999',
                    'director'    => 'David Fincher',
                    'rating'      => '8.8'
                    );

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
            case 'id':
            case 'title':
            case 'description':
            case 'year':
            case 'director':
            case 'rating':
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
        $orderby = 'title';
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