<?php
class Rednumber_Marketing_CRM_Frontend_Google_Sheets_Form_Widget{
	private static $add_on ="google_sheets"; 
	private static $form ="elementor"; 
	function __construct(){
		add_action('elementor_pro/forms/new_record', array($this,'send_data'),10,2);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function sync($form_id){
		$form_ids = explode("-", $form_id);
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$total_succsess = 0;
		$total_error = 0;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){ 
			$submissions = Rednumber_Marketing_CRM_Database::get_submissions_elementor($form_ids[0],self::$add_on);
			if ( $submissions ) { 
				foreach ( $submissions as $submission) { 
					$form_data = Rednumber_Marketing_CRM_Database::get_submissions_elementor_Value($submission->id);
					$form_data["form_id"] = $form_ids[0];
	   				$form_data["form_title"] = $submission->form_name;
					$result = $this->add_submit($datas,$form_data,$submission->id);
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
	}
	function send_data($record, $settings){
		 $raw_fields = $record->get( 'fields' );
		 $form_data = array();
		 foreach ( $raw_fields as $id => $field ) {
	        $form_data[ $id ] = $field['value'];
	    }
		$post_id = $record->get_form_settings( 'edit_post_id' );
		$form_post_id = $record->get_form_settings( 'id' );
		$form_title = $record->get_form_settings( 'form_name' );
		$form_id = $form_post_id ."-".$post_id;
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$form_data["form_id"] = $form_post_id;
	    $form_data["form_title"] = $form_title;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$this->add_submit($datas,$form_data);
		}
	}
	function add_submit($datas,$form_data, $submission_id = "" ){
		$api = new Rednumber_Marketing_Google_Sheets_API();
		$form_id =$form_data["form_id"];
		$result= null;
		foreach( $datas as $data ){
			foreach($data as $k => $vl ){
				$$k = $vl;
			}
			$spreadsheet_id = $api->get_sheet_id($method);
			if( $submission_id == "" ){
				$submission_id = Rednumber_Marketing_CRM_Database::get_submissions_id_elementor();
			}
			if( $spreadsheet_id != "" ){
				$rows = array_combine($map_fields["webhook"], $map_fields["form"]);
				$rows =  $this->get_submits($rows,$form_data);
				$rows = array_values($rows);
				$result = $api->add_row($spreadsheet_id,$rows);
				if( isset($result->spreadsheetId) && $result->spreadsheetId != ""){
					Rednumber_Marketing_CRM_Database::add_actions_elementor($submission_id,self::$add_on,"success");
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
					$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode($v);
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
						$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode($new_value);
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
new Rednumber_Marketing_CRM_Frontend_Google_Sheets_Form_Widget;