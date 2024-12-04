<?php
class Rednumber_Marketing_CRM_Frontend_ActivecampaignForm_Widget{
	private static $add_on ="activecampaign"; 
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
			$submissions = Rednumber_Marketing_CRM_Database::get_submissions_elementor($form_ids[0]);
			if ( $submissions ) { 
				foreach ( $submissions as $submission) { 
					$form_data = Rednumber_Marketing_CRM_Database::get_submissions_elementor_Value($submission->id);
					$form_data["form_id"] = $form_ids[0];
	   				$form_data["form_title"] = $submission->form_name;
					$response = $this->add_submit($datas,$form_data);
					if( $response ) {
						if( !$response["errors"] ){ 
							$total_succsess++;
						}else{
							$total_error++;
						}
					}else{
						$total_error++;
					}
				}
				printf( esc_html__( 'Success: %s Error: %s', 'crm-marketing' ),$total_succsess ,$total_error );
			}
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
	function add_submit($datas,$form_data){
		$datas = $datas[0];
		$contact = $datas["contact"];
		$deal = $datas["deal"];
		$task = $datas["task"];
		$list = $datas["list"];
		$note = $datas["note"];
		$api = new Rednumber_Marketing_Activecampaign_API();
		if( isset($contact["enable"]) && $contact["enable"] == "on"){
			$submits =  $this->get_submits($contact,$form_data,"contact");
			//$datas_submit
			$response = $api->add_contact($submits);
			$response = json_decode($response,true);
			if( !$response["errors"] ){
				Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit Deal
		if( isset($deal["enable"]) && $deal["enable"] == "on"){ 
			$submits =  $this->get_submits($deal,$form_data,"deal");
			$response = $api->add_deal($submits);
			$response = json_decode($response,true);
			if( $response["errors"] ){
				Rednumber_Marketing_CRM_Logs::add("Deal: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Deal ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit task
		if( isset($task["enable"]) && $task["enable"] == "on"){ 
			$submits =  $this->get_submits($task,$form_data, "task");
			$response = $api->add_task($submits);
			$response = json_decode($response,true);
			if( $response["errors"] ){
				Rednumber_Marketing_CRM_Logs::add("Taks: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Taks ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit list
		if( isset($list["enable"]) && $list["enable"] == "on"){ 
			$submits =  $this->get_submits($list,$form_data,"list");
			$response = $api->add_list($submits);
			$response = json_decode($response,true);
			if( $response["errors"] ){
				Rednumber_Marketing_CRM_Logs::add("List: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("list ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit note
		if( isset($note["enable"]) && $note["enable"] == "on"){ 
			$submits =  $this->get_submits($note,$form_data,"note");
			$response = $api->add_note($submits);
			$response = json_decode($response,true);
			if( $response["errors"] ){
				Rednumber_Marketing_CRM_Logs::add("Note: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Note ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		return $response;
	}
	function get_submits($datas, $form_data, $type=null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
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
						$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode($new_value);
						if( isset( $form_data[$value] )){
							$value =$form_data[$value] ;
						}else{
							$value = $new_value;
						}
						$new_values[] = array("field"=>$new_key,"value"=>$value);
					}
					$submits[$k] = $new_values;
				}
			}
		}
		switch( $type ){
			case "contact";
				return array("contact"=>$submits);
				break;
			case "deal";
				return array("deal"=>$submits);
				break;
			case "task";
				return array("dealTask"=>$submits);
				break;
			case "list";
				return array("lists"=>$submits);
				break;
			case "note";
				return array("note"=>$submits);
				break;
			default:
				return $submits;
				break;
		}
	}
}
new Rednumber_Marketing_CRM_Frontend_ActivecampaignForm_Widget;