<?php
class Rednumber_Marketing_CRM_Backend_Quform{ 
	private static $form ="quform"; 
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
		$datas[self::$form] = esc_html__("Quform Forms","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
		$table_name = $wpdb->prefix.'quform_forms';
		$lists_form = $wpdb->get_results( "SELECT * FROM {$table_name} WHERE active = 1");

    	foreach ( $lists_form as $form ) {
    		$config = unserialize(base64_decode($form->config));

				$datas[] = array(
                    'id'          => $form->id,
                    'title'       => $form->name,
                    'type'        => self::$form,
                    'label'       => "Quform"
                    );
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
new Rednumber_Marketing_CRM_Backend_Quform;