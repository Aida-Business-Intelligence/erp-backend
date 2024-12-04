<?php
class Rednumber_Marketing_CRM_Frontend_Google_sheets_WPForms{
	private static $add_on ="google_sheets"; 
	private static $form ="wpforms"; 
	function __construct(){
		add_action('wpforms_email_send_after', array($this,'submit_form'),11);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){ 
			$submissions = wpforms()->entry->get_entries(array( 'form_id' => $form_id ));
			foreach( $submissions as $submission ){
				$submission = (array) $submission;
				$fields = json_decode( $submission["fields"],true);
				 unset($submission["fields"]);
				 foreach( $fields as $id => $value ){
				 	$submission[ (string) $id] = $value["value"];
				 }
				 $response = $this->add_submit($datas,$submission);
			}
		}
	}
	function submit_form($form ){
		$form_id = $form->form_data["id"];
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$submission = array();
			 foreach( $form->fields as $id => $value ){
			 	$submission[ (string) $id] = $value["value"];
			 }
			 $submission["form_id"] = $form_id;
			 $submission["entry_id"] = $form->entry_id;
			$this->add_submit($datas,$submission);
		}
	}
	function add_submit( $datas, $form_data){ 
		$api = new Rednumber_Marketing_Google_Sheets_API();
		$form_id =$form_data["form_id"];
		foreach( $datas as $data ){
			foreach($data as $k => $vl ){
				$$k = $vl;
			}
			$spreadsheet_id = $api->get_sheet_id($method);
			if( $spreadsheet_id != "" ){
				$rows = array_combine($map_fields["webhook"], $map_fields["form"]);
				$rows =  $this->get_submits($rows,$form_data);
				$rows = array_values($rows);
				$result = $api->add_row($spreadsheet_id,$rows);
				if( isset($result->spreadsheetId) && $result->spreadsheetId != ""){
					Rednumber_Marketing_CRM_Logs::add("Added row spreadsheet id: ".$result->spreadsheetId,"Send datas" ,self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Error ","Send datas",self::$form,self::$add_on,$form_id);
				}
			}
		}
		return $result;
	}
	function get_submits($datas, $form_data, $type=null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$v = apply_shortcodes($v,true);
					$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode_field_id($v);
					if( isset( $form_data[$value] )){
						$value =$form_data[$value] ;
					}else{
						$value = $v;
					}
					if($value != "") {
						$submits[$k] = $value;
					}
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$new_value = apply_shortcodes($new_value,true);
						$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode_field_id($new_value);
						if( isset( $form_data[$value] )){
							$value =$form_data[$value] ;
						}else{
							$value = $new_value;
						}
						if($value != "") {
							$new_values[$new_key] = $value;
						}
					}
					if( count($new_values) > 0 ){
						$submits[$k] = $new_values;
					}
				}
			}
		}
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Google_sheets_WPForms;