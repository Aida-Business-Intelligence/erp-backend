<?php
class Rednumber_Marketing_CRM_Backend_WPForms{ 
	private static $form ="wpforms"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		add_action('rednumber_crm_marketing_sync_'.self::$form,array($this,"add_sync"));
	}
	function add_sync(){
		?>
		<p><?php esc_html_e("Please save changes before SYNC","crm-marketing"); ?></p>
		<a href="#" class="button button-primary crm_marketing_sync"><?php esc_html_e("SYNC Submissions","crm-marketing") ?></a>
		<?php
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("WPForms","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
    	$the_query = new WP_Query(array("post_type"=>"wpforms","posts_per_page"=>-1));
    	if( $the_query->have_posts() ){
	    	while ( $the_query->have_posts() ) : $the_query->the_post();
	        $datas[] = array(
                    'id'          => get_the_ID(),
                    'title'       => get_the_title(),
                    'type'        => self::$form,
                    'label'       => "WPForms"
                    );
	     endwhile; 
	    	wp_reset_postdata();
    	}
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode = array();
		$form = wpforms()->form->get( absint( $form_id) );
		// If the form doesn't exists, abort.
		if ( empty( $form ) ) {
			return $shortcode;
		}
		$shortcode["{form_id}"] = "Form ID";
		$shortcode["{entry_id}"] = "Entry ID";
		// Pull and format the form data out of the form object.
		    $form_data = ! empty( $form->post_content ) ? wpforms_decode( $form->post_content ) : '';
		    // Check to see if we are showing all allowed fields, or only specific ones.
		    $form_field_ids = isset( $atts['fields'] ) && $atts['fields'] !== '' ? explode( ',', str_replace( ' ', '', $atts['fields'] ) ) : [];
		    // Setup the form fields.
		    $form_fields = array();
		    if ( empty( $form_field_ids ) ) {
		    	if( isset($form_data['fields']) ) {
		    		$form_fields = $form_data['fields'];
		    	}
		    } else {
		        $form_fields = [];
		        foreach ( $form_field_ids as $field_id ) {
		            if ( isset( $form_data['fields'][ $field_id ] ) ) {
		                $form_fields[ $field_id ] = $form_data['fields'][ $field_id ];
		            }
		        }
		    }
			 if( is_array($form_fields)) {
			 	foreach( $form_fields as $id => $datas){
				 	$shortcode['{field_id="'.$id.'"}'] = $datas["label"];
				 }
			 }
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_WPForms;