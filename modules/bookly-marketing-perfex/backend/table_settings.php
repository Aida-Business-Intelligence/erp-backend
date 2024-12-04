<?php
// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
/**
 * Create a new table class that will extend the WP_List_Table
 */
class Rednumber_Marketing_CRM_Table extends WP_List_Table{
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
        if( isset($_GET['type-filter']) > 0 ){
            $type = sanitize_text_field( $_GET['type-filter'] );
            $new_arr = array();
            foreach($data as $vl ){
                if($vl["type"] == $type ){
                    $new_arr[] = $vl;
                }
            }
           $data = $new_arr; 
        }
        $perPage = 30;
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
            'type'          => esc_html__("Type","crm-marketing"),
            'title'       => esc_html__("Name","crm-marketing"),
            'connected'   => esc_html__("Connected","crm-marketing"), 
            'action'      => esc_html__("Action","crm-marketing"), 
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
        return array('title' => array('title', false),'id' => array('id', false));
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
        $data = apply_filters( "crm_marketing_data_table", $data );
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
    public function column_default( $item, $column_name ){
        global $wpdb;
        $table_name = $wpdb->prefix."crm_marketings";   
        $fivesdrafts = $wpdb->get_results( 
            "
                SELECT add_on
                FROM $table_name
                WHERE type = '{$item["type"]}'
                AND form_id = '{$item["id"]}'
                AND datas != 'a:0:{}'
            ",ARRAY_N
        );
        switch( $column_name ) {
            case 'type':
            case 'title':
                return  $item[$column_name]  ;
                break;
            case 'action':
                $url = admin_url("admin.php?page=crm-marketing&id=".$item["id"]."&type=".$item["type"]);
                return sprintf('<a href="%s">%s</a>',$url,esc_html__("Edit","crm-marketing"));
                break;
            case 'connected':
                if(count($fivesdrafts) > 0 ){
                    $rs="";
                    $i=0;
                    foreach ( $fivesdrafts as $fivesdraft ) {
                        if( $i== 0){
                            $rs .=$fivesdraft[0];
                        }else{
                            $rs .= ", ".$fivesdraft[0];
                        }
                        $i++;
                    }
                }else{
                   $rs = esc_html__("No connections","crm-marketing");
                }
                return $rs;
                break;
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
    function extra_tablenav( $which ) {
        $type = "";
        if( isset($_GET['type-filter']) ) {
            $type = sanitize_text_field( $_GET['type-filter'] );
        }
        $lists_add_ons = apply_filters("crm_marketing_list_add_ons",array());
        if ( $which == "top" ){
            ?>
            <input type="hidden" id="crm-marketing-url-list" value="<?php echo add_query_arg( array( 'page' => 'crm-marketing' ), admin_url( 'admin.php' ) ); ?>">
            <div class="alignleft actions bulkactions">
               <select name="type-filter" class="crm-filter-type">
                    <option value=""><?php esc_html_e("Filter by add-on","crm-marketing")  ?></option>
                    <?php foreach($lists_add_ons as $k => $v) {
                        ?>
                        <option <?php selected($type,$k) ?> value="<?php echo esc_attr($k) ?>"><?php echo esc_html($v) ?></option>
                        <?php
                    } ?>
                </select>
                <input type="submit" name="filter_action"  class="button type-query-submit" value="Filter">
            </div>
            <?php
        }
        if ( $which == "bottom" ){
             ?>
            <div class="alignleft actions bulkactions">
               <select name="type-filter" class="crm-filter-type">
                    <option value=""><?php esc_html_e("Filter by add-on","crm-marketing")  ?></option>
                    <?php foreach($lists_add_ons as $k => $v) {
                        ?>
                        <option <?php selected($type,$k) ?> value="<?php echo esc_attr($k) ?>"><?php echo esc_html($v) ?></option>
                        <?php
                    } ?>
                </select>
                <input type="submit" name="filter_action"class="button type-query-submit" value="Filter">
            </div>
            <?php
        }
    }
}