<?php
class Rednumber_Marketing_CRM_Backend_Eform{ 
	private static $form ="eform"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		add_action('rednumber_crm_marketing_sync_'.self::$form,array($this,"add_sync"));
	}
	public static function cover_entry_to_data($form){
		$submission = array();
		foreach( $form->data->mcq as $k=> $v ){
			if( isset($v["options"]) ){
				$submission["[M:".$k."]"] = $v["options"];
			}else{
				$submission["[M:".$k."]"] = $v["value"];
			}
		}
		foreach( $form->data->freetype as $k=> $v ){
			if( isset($v["options"]) ){
				$submission["[F:".$k."]"] = $v["options"];
			}else{
				$submission["[F:".$k."]"] = $v["value"];
			}
		}
		foreach( $form->data->pinfo as $k=> $v ){
			if( isset($v["options"]) ){
				$submission["[O:".$k."]"] = $v["options"];
			}else{
				$submission["[O:".$k."]"] = $v["value"];
			}
		}
		return $submission;
	}
	function add_sync(){
		?>
		<p><?php esc_html_e("Please save changes before SYNC","crm-marketing"); ?></p>
		<a href="#" class="button button-primary crm_marketing_sync"><?php esc_html_e("SYNC Entries","crm-marketing") ?></a>
		<?php
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Contact Form 7","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
		$table_name = $wpdb->prefix."fsq_form";
		$forms = $wpdb->get_results(
			"
				SELECT *
				FROM $table_name
			"
		);
		foreach ( $forms as $form ) {
			$datas[] = array(
                    'id'          => $form->id,
                    'title'       => $form->name,
                    'type'        => self::$form,
                    'label'       => "Eform"
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
		$shortcode = array();
		$table_name = $wpdb->prefix."fsq_form";
		$form = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = {$form_id}" );
		$datas = maybe_unserialize($form->layout);
		$pinfo = maybe_unserialize($form->pinfo);
		$freetype = maybe_unserialize($form->freetype);
		$mcq = maybe_unserialize($form->mcq);
		$tab = 0;
		foreach( $datas as $data ){
			$i = 0;
			foreach ( $data["elements"] as $field ){
				$type = "";
				$name = "";
				switch( $field["m_type"] ) {
					case "freetype":
						$type ="F";
						$name = $type."-".$field["key"]."-".$freetype[ $field["key"] ]["title"];
						break;
					case "pinfo":
						$type ="O";
						$name = $type."-".$field["key"]."-".$pinfo[ $field["key"] ]["title"];
						break;
					case "mcq":
						$type ="M";
						$name = $type."-".$field["key"]."-".$mcq[ $field["key"] ]["title"];
						break;
				}
				$shortcode["[".$type.":".$field["key"]."]"] =  $name;
			}
			$tab++;
		}
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Eform;