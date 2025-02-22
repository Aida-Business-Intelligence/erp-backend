<?php
class Rednumber_Marketing_CRM_Frontend_Hubspot_Gravity_Form{
	private static $add_on ="hubspot"; 
	private static $form ="gravityforms"; 
	private $datas_submits= array(); 
	function __construct(){
		add_action('gform_after_submission', array($this,'submit_form'),11, 2);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function sync($form_id){
		$results = GFAPI::get_entries($form_id);
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$total_succsess = 0;
		$total_error = 0;
		$total_duplicate = 0;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
			foreach($results as $result ){
				$response = $this->add_submit($datas,$result);
				if( isset($response["id"])  ) {
					$total_succsess++;
				}else{
					$total_error++;
				}
			}
		}
		printf( esc_html__( 'Success: %s Duplicate: %s Error: %s', 'crm-marketing' ),$total_succsess, $total_duplicate ,$total_error ); 
	}
	function submit_form($entry, $form){
		$form_id = $entry["form_id"];
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
			$this->add_submit($datas,$entry);
		}
	}
	function add_submit( $datas, $form_data){
		$datas = $datas[0];
		$contact = $datas["contact"];
		$deal = $datas["deal"];
		$company = $datas["company"];
		$ticket = $datas["ticket"];
		$task = $datas["task"];
		$pipeline = $datas["pipeline"];
		$file = $datas["file"];
		$form_id = $form_data["form_id"];
		$api = new Rednumber_Marketing_CRM_Hubspot_API();
		if( isset($contact["enable"]) && $contact["enable"] == "on"){
			$submits =  $this->get_submits($contact,$form_data,"contact");
			$response = $api->add_contact($submits);
			$response = json_decode($response,true);
			if( isset($response["id"]) ){
				$this->set_datas_submits("current_contact_id",$response["id"]);
				Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		if( isset($deal["enable"]) && $deal["enable"] == "on"){
			$submits =  $this->get_submits($deal,$form_data,"deal");
			$response = $api->add_deal($submits);
			$response = json_decode($response,true);
			if( isset($response["id"])  ){
				$this->set_datas_submits("current_deal_id",$response["id"]);
				Rednumber_Marketing_CRM_Logs::add("Deal: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Deal ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		if( isset($company["enable"]) && $company["enable"] == "on"){
			$submits =  $this->get_submits($company,$form_data,"company");
			$response = $api->add_company($submits);
			$response = json_decode($response,true);
			if( isset($response["id"])  ){
				$this->set_datas_submits("current_company_id",$response["id"]);
				Rednumber_Marketing_CRM_Logs::add("Company: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Company ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		if( isset($ticket["enable"]) && $ticket["enable"] == "on"){
			$submits =  $this->get_submits($ticket,$form_data,"ticket");
			$response = $api->add_ticket($submits);
			$response = json_decode($response,true);
			if( isset($response["id"]) ){
				$this->set_datas_submits("current_ticket_id",$response["id"]);
				Rednumber_Marketing_CRM_Logs::add("Ticket: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Ticket ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		if( isset($task["enable"]) && $task["enable"] == "on"){
			$submits =  $this->get_submits($task,$form_data,"task");
			$response = $api->add_task($submits);
			$response = json_decode($response,true);
			if( isset($response["id"]) ){
				$this->set_datas_submits("current_task_id",$response["id"]);
				Rednumber_Marketing_CRM_Logs::add("Task: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Task ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		//add_pipeline
		if( isset($pipeline["enable"]) && $pipeline["enable"] == "on"){
			$submits =  $this->get_submits($pipeline,$form_data,"pipeline");
			$new_submits = $submits;
			unset($new_submits["objectType"]);
			unset($new_submits["pipelineId"]);
			$response = $api->add_pipeline($new_submits,$submits["objectType"],$submits["pipelineId"]);
			$response = json_decode($response,true);
			if( isset($response["id"])  ){
				$this->set_datas_submits("current_pipeline_id",$response["id"]);
				Rednumber_Marketing_CRM_Logs::add("Pipeline: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Pipeline ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		if( isset($file["enable"]) && $file["enable"] == "on"){
			$submits =  $this->get_submits($file,$form_data,"file");
			$response = $api->add_file($submits);
			$response = json_decode($response,true);
			if( isset($response["id"]) ){
				Rednumber_Marketing_CRM_Logs::add("Upload: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Upload ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
			}
		}
		return $response;
	}
	function get_submits($datas, $form_data, $type=null){
		$submits = array();
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form_data,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Hubspot::cover_data_to_api($submits_new,$type,$form_data,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Hubspot_Gravity_Form;