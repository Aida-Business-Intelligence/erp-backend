<?php
class Rednumber_Marketing_CRM_Backend_Nex_Forms{ 
	private static $form ="nex_forms"; 
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
		$table_name = $wpdb->prefix."wap_nex_forms";
    	$forms = $wpdb->get_results(
			"
				SELECT *
				FROM $table_name
				ORDER BY id DESC
			"
		);
    	foreach ( $forms as $form ) {
	        $datas[] = array(
                    'id'          => $form->Id,
                    'title'       => $form->title,
                    'type'        => self::$form,
                    'label'       => "NEX Forms"
                    );
	     }
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		global $wpdb;
		$table_name = $wpdb->prefix."wap_nex_forms";
		$shortcode = array();
		$forms = $wpdb->get_row(
			$wpdb->prepare(
			"
				SELECT form_fields
				FROM $table_name
				WHERE id = %d
				ORDER BY id DESC
			",
			$form_id
			)
		);
		$tags = json_decode($forms->form_fields,true);
		foreach ($tags["fields"] as $tag_inner):
			if( isset($tag_inner["fields"])){
				$name = $tag_inner["attributes"]["name"];
				$label = $tag_inner["attributes"]["name"];
				$shortcode["{inputs.".$name."}"] = $label;
				foreach( $tag_inner["fields"] as $field ){
					if( $field["settings"]["visible"] ){
						$name = $field["attributes"]["name"];
				   		$label = $field["settings"]["label"];
				   		$shortcode["{inputs.".$name."}"] = $label;
					}
				}
			}else{
				$name = $tag_inner["attributes"]["name"];
		   		$label = $tag_inner["settings"]["label"];
		   		$shortcode["{inputs.".$name."}"] = $label;
			}
		endforeach; 
		$shortcode["{submission.id}"] = "Submission ID";  
		$shortcode["{submission.user_id}"] = "Submission User ID";  
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Nex_Forms;