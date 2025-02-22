<?php
class Rednumber_Marketing_CRM_Backend_Contact_Form_7{ 
	private static $form ="contact_form_7"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Contact Form 7","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
    	$the_query = new WP_Query(array("post_type"=>"wpcf7_contact_form","posts_per_page"=>-1));
    	if( $the_query->have_posts() ){
	    	while ( $the_query->have_posts() ) : $the_query->the_post();
	        $datas[] = array(
                    'id'          => get_the_ID(),
                    'title'       => get_the_title(),
                    'type'        => self::$form,
                    'label'       => "Contact Form 7"
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
		$ContactForm = WPCF7_ContactForm::get_instance( $form_id );
		$tags = $ContactForm->scan_form_tags();
		foreach ($tags as $tag_inner):
		    if ($tag_inner['type'] == 'group' || $tag_inner['name'] == '') continue;
		    $shortcode["[".$tag_inner['name']."]"] = $tag_inner['name'];
		endforeach;   
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Contact_Form_7;