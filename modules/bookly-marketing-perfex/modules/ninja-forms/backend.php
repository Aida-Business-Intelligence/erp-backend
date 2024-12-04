<?php
class Rednumber_Marketing_CRM_Backend_Ninja_Forms{ 
	private static $form ="ninjaforms"; 
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
		$datas[self::$form] = esc_html__("Ninja forms","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
		$table_name = $wpdb->prefix . 'nf3_forms';
        	$templates = $wpdb->get_results( 
		    "
		        SELECT id,title 
		        FROM $table_name
		    "
		);
        if( count($templates) > 0 ) {
        	foreach ( $templates as $template ) {
				$form_id = $template->id;
				$form_title = $template->title;
				$datas[] = array(
                    'id'          => $form_id,
                    'title'       => esc_html($form_title),
                    'type'        => self::$form,
                    'label'       => "Ninja Forms"
                    );
			}
        }
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode = array();
		$shortcode["{form_id}"] = "Form ID";
		$shortcode["{form_title}"] = "Form Title";
		$fields_list = Ninja_Forms()->form( $form_id )->get_fields();
		$hidden_field_types = apply_filters( 'nf_sub_hidden_field_types', array() );
		foreach( $fields_list as $field ){
			$field_id = $field->get_id();
			$label = $field->get_setting( 'label' );
			$name = $field->get_setting( 'key' );
			if (!is_int($field_id)) continue;
			if( in_array( $field->get_setting( 'type' ), $hidden_field_types ) ) continue;
			$shortcode["{field:".$name."}"] = $label;
		}
        return $shortcode;
	}
	public static function get_all_submissions($form_id){
		global $wpdb;
	}
}
new Rednumber_Marketing_CRM_Backend_Ninja_Forms;