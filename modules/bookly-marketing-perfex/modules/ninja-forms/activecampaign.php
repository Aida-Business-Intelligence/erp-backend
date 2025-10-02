<?php
class Rednumber_Marketing_CRM_Frontend_Activecampaign_Ninja_Forms{
	private static $add_on ="activecampaign"; 
	private static $form ="ninjaforms"; 
	function __construct(){
		add_action('ninja_forms_after_submission', array($this,'submit_form'),11);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$total_succsess = 0;
		$total_error = 0;
		$total_duplicate = 0;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){ 
			$submissions = Ninja_Forms()->form( $form_id )->get_subs();
			if ( $submissions ) { 
				foreach ( $submissions as $submission) { 
					$form_data = $submission->get_field_values();
					$response = $this->add_submit($datas,$form_data);
					if( $response ) {
						if( $response["code"] =="duplicate_parameter" ){
							$total_duplicate++;
						}else{
							$total_succsess++;
						}
					}else{
						$total_error++;
					}
				}
				printf( esc_html__( 'Success: %s Duplicate: %s Error: %s', 'crm-marketing' ),$total_succsess, $total_duplicate ,$total_error );
			}
		}
	}
	function submit_form($form_data ){
		$form_id = $form_data["form_id"];
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$form_datas = array();
			$form_datas["form_id"] = $form_id;
			$form_datas["form_title"] = $form_data["title"];
			foreach( $form_data["fields_by_key"] as $k => $v ) { 
				$form_datas[$k] = $v["value"];
			}
			$this->add_submit($datas,$form_datas);
		}
	}
	function add_submit( $datas, $form_data){ 
		$datas = $datas[0];
		$contact = $datas["contact"];
		$deal = $datas["deal"];
		$task = $datas["task"];
		$list = $datas["list"];
		$note = $datas["note"];
		$form_id = $form_data["form_id"];
		$api = new Rednumber_Marketing_Activecampaign_API();
		if( isset($contact["enable"]) && $contact["enable"] == "on"){
			$submits =  $this->get_submits($contact,$form_data,"contact");
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
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Activecampaign_Ninja_Forms;