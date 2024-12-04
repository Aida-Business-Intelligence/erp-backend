<?php
class Rednumber_Marketing_CRM_Backend_Formidable_Forms{ 
	private static $form ="formidable_forms"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Formidable Forms","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
		$table_name = $wpdb->prefix."frm_forms";
    	$forms = $wpdb->get_results(
			"
				SELECT *
				FROM $table_name
				ORDER BY id DESC
			"
		);
    	foreach ( $forms as $form ) {
	        $datas[] = array(
                    'id'          => $form->id,
                    'title'       => $form->name,
                    'type'        => self::$form,
                    'label'       => "Formidable Forms"
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
		$table_name = $wpdb->prefix."frm_fields";
		$shortcode = array();
		$forms = $wpdb->get_results(
			$wpdb->prepare(
			"
				SELECT id,name
				FROM $table_name
				WHERE form_id = %d
			",
			$form_id
			)
		);
		foreach ($forms as $field):
			$shortcode["[".$field->id."]"] = $field->name ."[".$field->id."]";
		endforeach; 
		$shortcode["[id]"] = "Entry ID";
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Formidable_Forms;