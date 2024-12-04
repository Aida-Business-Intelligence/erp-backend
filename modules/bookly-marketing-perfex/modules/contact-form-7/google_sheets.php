<?php
class Rednumber_Marketing_CRM_Frontend_Google_Sheets_Contact_From_7{
	private static $add_on ="google_sheets"; 
	private static $form ="contact_form_7"; 
	function __construct(){
		add_action( 'wpcf7_before_send_mail', array($this,'save_data') );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){ 
			esc_html_e("Feature will update soon","crm-marketing");
		}
	}
	function save_data( $form_tag ) {
		$form = WPCF7_Submission::get_instance();
		$result = null;
		if ( $form ) {
		    $form_datas  = $form->get_posted_data();
			$form_id = $form_tag->id();
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
			if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
				$api = new Rednumber_Marketing_Google_Sheets_API();
				foreach( $datas as $data ){
					foreach($data as $k => $vl ){
						$$k = $vl;
					}
					$spreadsheet_id = $api->get_sheet_id($method);
					if( $spreadsheet_id != "" ){
						$rows = array_combine($map_fields["webhook"], $map_fields["form"]);
						$rows =  $this->get_submits($rows,$form);
						$rows = array_values($rows);
						$result = $api->add_row($spreadsheet_id,$rows);
						if( isset($result->spreadsheetId) && $result->spreadsheetId != ""){
							Rednumber_Marketing_CRM_Logs::add("Added row spreadsheet id: ".$result->spreadsheetId,"Send datas" ,self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("Error ","Send datas",self::$form,self::$add_on,$form_id);
						}
					}
				}
			}
		}
		return $result;
	}
	function get_submits($datas, $form, $type=null){
		$submits = array();
		$value_ojects = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$v = apply_shortcodes($v,true);
					$value = str_replace(array("[","]"),"",$v);
					$value =$form->get_posted_data($value);
					if($value == null){
						if( strpos($v,"]") ) {
							$value = "";
						}else{
							$value = $v;
						}
					}
					$submits[$k] = $value;
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$new_value = apply_shortcodes($new_value,true);
						$value = str_replace(array("[","]"),"",$new_value);
						$value =$form->get_posted_data($value);
						if($value == null){
							if( strpos($v,"]") ) {
								$value = "";
							}else{
								$value = $v;
							}
						}
						$new_values[$new_key] = $value;
					}
					$submits[$k] = $new_values;
				}
			}
		}
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Google_Sheets_Contact_From_7;