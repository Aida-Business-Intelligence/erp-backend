<?php
class Rednumber_Marketing_CRM_Frontend_Google_sheets_Bookly{
	private static $add_on ="google_sheets"; 
	private static $form ="bookly"; 
	function __construct(){
		add_action('bookly_add_appointments', array($this,'submit_form'));
	}
	function submit_form($codes ){
		$form_id = "appointment_approval";
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$this->add_submit($datas,$codes);
		}
	}
	function add_submit( $datas, $form_data){ 
		$api = new Rednumber_Marketing_Google_Sheets_API();
		$form_id ="appointment_approval";
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
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form_data,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = $submits_new;
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Google_sheets_Bookly;