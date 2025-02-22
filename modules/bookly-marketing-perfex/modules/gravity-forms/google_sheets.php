<?php
class Rednumber_Marketing_CRM_Frontend_Google_Sheets_Gravity_Form{
	private static $add_on ="google_sheets"; 
	private static $form ="gravityforms"; 
	function __construct(){
		add_action('gform_after_submission', array($this,'submit_form'),11, 2);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function sync($form_id){
		$results = Rednumber_Marketing_CRM_Database::get_submissions_gravity_forms($form_id,self::$add_on );
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$total_succsess = 0;
		$total_error = 0;
		$total_duplicate = 0;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
			foreach($results as $result ){
				$result = $this->add_submit($datas,$result);
				if( $result ) {
						if( isset($result->spreadsheetId) && $result->spreadsheetId != ""){
							$total_succsess++;
						}else{
							$total_error++;
						}
					}else{
						$total_error++;
					}
			}
		}
		printf( esc_html__( 'Success: %s Error: %s', 'crm-marketing' ),$total_succsess ,$total_error );
	}
	function submit_form($entry, $form){
		$form_id = $entry["form_id"];
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
			$this->add_submit($datas,$entry);
		}
	}
	function add_submit( $datas, $form_data , $entry_id = null){
		$api = new Rednumber_Marketing_Google_Sheets_API();
		$form_id =$form_data["form_id"];
		$entry_id = $form_data["id"];
		$result = null;
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
					Rednumber_Marketing_CRM_Database::add_actions_gravity_forms(self::$add_on,$form_id, $entry_id);
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
					$value = str_replace(array("{","}"),"",$v);
			        $values = explode(":",$value);
			        if( count($values) > 1 ){
	        			$value = $values[1];
	        		}
					if( isset( $form_data[$value] )){
						$value =$form_data[$value] ;
					}else{
						$value = $v;
					}
					$submits[$k] = $value;
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$new_value = apply_shortcodes($new_value,true);
						$value = str_replace(array("{","}"),"",$new_value);
						$values = explode(":",$value);
						if( count($values) > 1 ){
		        			$value = $values[1];
		        		}
						if( isset( $form_data[$value] )){
							$value =$form_data[$value] ;
						}else{
							$value = $new_value;
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
new Rednumber_Marketing_CRM_Frontend_Google_Sheets_Gravity_Form;