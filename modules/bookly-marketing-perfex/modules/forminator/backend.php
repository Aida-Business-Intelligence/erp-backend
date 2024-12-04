<?php
class Rednumber_Marketing_CRM_Backend_Forminator{ 
	private static $form ="forminator"; 
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
		$datas[self::$form] = esc_html__("Forminator Forms","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
    	$the_query = new WP_Query(array("post_type"=>"forminator_forms","posts_per_page"=>-1));
    	if( $the_query->have_posts() ){
	    	while ( $the_query->have_posts() ) : $the_query->the_post();
	        $datas[] = array(
                    'id'          => get_the_ID(),
                    'title'       => get_the_title(),
                    'type'        => self::$form,
                    'label'       => "Forminator Forms"
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
		$datas = get_post_meta($form_id,"forminator_form_meta",true);
		foreach( $datas["fields"] as $field ){
			$shortcode["{".$field["id"]."}"] = $field["field_label"];
		}
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Forminator;