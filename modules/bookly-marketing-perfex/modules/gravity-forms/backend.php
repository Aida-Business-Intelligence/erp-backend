<?php
class Rednumber_Marketing_CRM_Backend_Gravity_Forms{ 
	private static $form ="gravityforms"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		add_action('rednumber_crm_marketing_sync_'.self::$form,array($this,"add_sync"));
	}
	function add_sync(){
		?>
		<p><?php esc_html_e("Please save changes before SYNC","crm-marketing"); ?></p>
		<a href="#" class="button button-primary crm_marketing_sync"><?php esc_html_e("SYNC Entries","crm-marketing") ?></a>
		<?php
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Gravity forms","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
		$table = $wpdb->prefix."gf_form";
    	$forms = $wpdb->get_results("SELECT id, title FROM $table");
    	if( count($forms) > 0){
    		foreach ( $forms as $form ) {
    			$form_id = $form->id;
    			$form_title = $form->title;
    			$datas[] = array(
                    'id'          => $form_id,
                    'title'       => esc_html($form_title),
                    'type'        => self::$form,
                    'label'       => "Gravity Forms"
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
		$form = RGFormsModel::get_form_meta($form_id);
		if(is_array($form["fields"])){
            foreach($form["fields"] as $field){
                if(isset($field["inputs"]) && is_array($field["inputs"])){
                    foreach($field["inputs"] as $input){
                    	$lable = GFCommon::get_label($field, $input["id"]);
                    	$shortcode["{".$lable.":".$input["id"]."}"] = $lable;
                    }
                }
                else if(!rgar($field, 'displayOnly')){
                    	$fields[] =  array($field["id"], GFCommon::get_label($field));
                    	$lable = GFCommon::get_label($field);
                    	$shortcode["{".$lable.":".$field["id"]."}"] = $lable;
                }
            }
        }
        $shortcode["{form-id}}"] = "{form-id}";
        $shortcode["{entry-id}"] = "{entry-id}";
        $shortcode["{form-url}"] = "{form-url}";
        $shortcode["{form-name}"] = "{form-name}";
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Gravity_Forms;