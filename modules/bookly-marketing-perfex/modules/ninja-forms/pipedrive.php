<?php
class Rednumber_Marketing_CRM_Frontend_Pipedrive_Ninja_Forms{
	private static $add_on ="pipedrive"; 
	private static $form ="ninjaforms"; 
	private $datas_submits= array(); 
	function __construct(){
		add_action('ninja_forms_after_submission', array($this,'submit_form'),11);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
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
		$lead = $datas["lead"];
		$deal = $datas["deal"];
		$preson = $datas["person"];
		$activity = $datas["activity"];
		$note = $datas["note"];
		$organization = $datas["organization"];
		$file = $datas["file"];
		$form_id = $form_data["form_id"];
		$response = false;
		$api = new Rednumber_Marketing_CRM_Pipedrive_API();
		//Submit organization
		if( isset($organization["enable"]) && $organization["enable"] == "on"){ 
			$submits =  $this->get_submits($organization,$form_data);
			$response = $api->add_organization($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				$this->set_datas_submits("current_org_id",$response["data"]["id"]);
				Rednumber_Marketing_CRM_Logs::add("Organization: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Organization ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit preson
		if( isset($preson["enable"]) && $preson["enable"] == "on"){ 
			$submits =  $this->get_submits($preson,$form_data, "preson");
			$response = $api->add_person($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				$this->set_datas_submits("current_person_id",$response["data"]["id"]);
				Rednumber_Marketing_CRM_Logs::add("Person: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Person ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		if( isset($lead["enable"]) && $lead["enable"] == "on"){
			$submits =  $this->get_submits($lead,$form_data,"lead");
			$response = $api->add_lead($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				$this->set_datas_submits("current_lead_id",$response["data"]["id"]);
				Rednumber_Marketing_CRM_Logs::add("Lead: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Lead ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit Deal
		if( isset($deal["enable"]) && $deal["enable"] == "on"){ 
			$submits =  $this->get_submits($deal,$form_data);
			$response = $api->add_deal($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				$this->set_datas_submits("current_deal_id",$response["data"]["id"]);
				Rednumber_Marketing_CRM_Logs::add("Deal: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Deal ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit activity
		if( isset($activity["enable"]) && $activity["enable"] == "on"){ 
			$submits =  $this->get_submits($activity,$form_data,"activity");
			$response = $api->add_activity($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				Rednumber_Marketing_CRM_Logs::add("Person: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Person ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit note
		if( isset($note["enable"]) && $note["enable"] == "on"){ 
			$submits =  $this->get_submits($note,$form_data);
			$response = $api->add_note($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				Rednumber_Marketing_CRM_Logs::add("Note: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Note ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//Submit file
		if( isset($file["enable"]) && $file["enable"] == "on"){ 
			$submits =  $this->get_submits($file,$form_data,"file");
			$response = $api->add_file($submits);
			$response = json_decode($response,true);
			if( $response["success"] ){
				Rednumber_Marketing_CRM_Logs::add("File: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("File ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		return $response;
	}
	function get_submits($datas, $form_data, $type=null){
		$submits = array();
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form_data,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Pipedrive::cover_data_to_api($submits_new,$type,$form_data,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Pipedrive_Ninja_Forms;