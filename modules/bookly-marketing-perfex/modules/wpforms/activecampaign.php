<?php
class Rednumber_Marketing_CRM_Frontend_Activecampaign_WPForms{
	private static $add_on ="activecampaign"; 
	private static $form ="wpforms"; 
	function __construct(){
		add_action('wpforms_process_complete', array($this,'submit_form'),11,4);
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
	function submit_form($fields, $entry, $form_data, $entry_id ){
		$form_id = $entry["id"];
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$this->add_submit($datas,$fields,$entry,$form_data,$entry_id);
		}
	}
	function add_submit($datas, $fields, $entry, $form_data, $entry_id ){ 
		$form_id = $entry["id"];
		$datas = $datas[0];
		$contact = $datas["contact"];
		$deal = $datas["deal"];
		$task = $datas["task"];
		$list = $datas["list"];
		$note = $datas["note"];
		$api = new Rednumber_Marketing_Activecampaign_API();
		if( isset($contact["enable"]) && $contact["enable"] == "on"){
			$submits =  $this->get_submits($contact,$fields, $entry, $form_data, $entry_id,"contact");
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
			$submits =  $this->get_submits($deal,$fields, $entry, $form_data, $entry_id,"deal");
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
			$submits =  $this->get_submits($task,$fields, $entry, $form_data, $entry_id, "task");
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
			$submits =  $this->get_submits($list,$fields, $entry, $form_data, $entry_id,"list");
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
			$submits =  $this->get_submits($note,$fields, $entry, $form_data, $entry_id,"note");
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
	function get_submits($datas,  $fields, $entry, $form_data, $entry_id , $type=null){
		$submits = array();
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form_data,"type"=>$type,"add"=>$this->datas_submits,"add_ons"=>array("fields"=>$fields,"entry_id"=>$entry_id));
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Activecampaign::cover_data_to_api($submits_new,$type,$form_data,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Activecampaign_WPForms;