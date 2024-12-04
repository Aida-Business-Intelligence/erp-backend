<?php
class Rednumber_Marketing_CRM_Frontend_Hubspot_Contact_From_7{
	private static $add_on ="hubspot"; 
	private static $form ="contact_form_7";
	private $datas_submits= array();  
	function __construct(){
		add_action( 'wpcf7_before_send_mail', array($this,'save_data') );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
		add_action( 'wpcf7_mail_sent', array($this,'send_pdf') );
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){ 
			esc_html_e("Feature will update soon","crm-marketing");
		}
	}
	function send_pdf($form_tag){
		$form = WPCF7_Submission::get_instance();
		if ( $form ) {
			$form_id = $form_tag->id();
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
			if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
				$datas = $datas[0];
				$file = $datas["file"];
				//file
				if( isset($file["enable"]) && $file["enable"] == "on"){
					$api = new Rednumber_Marketing_CRM_Sendinblue_API();
					$submits =  $this->get_submits($file,$form,"file_cf7_pdf",$form_id);
					if( isset( $submits["file"] )){ 
						$response = $api->add_file($submits);
						$response = json_decode($response,true);
						if( isset($response["id"]) ){
							Rednumber_Marketing_CRM_Logs::add("File Upload PDF: Added ","Send datas",self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("File Upload PDF ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
						}
					}
					
				}
			}
		}
	}
	function save_data( $form_tag ) {
		$form = WPCF7_Submission::get_instance();
		if ( $form ) {
		    $form_datas  = $form->get_posted_data();
			$form_id = $form_tag->id();
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
			if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
					$datas = $datas[0];
					$contact = $datas["contact"];
					$deal = $datas["deal"];
					$company = $datas["company"];
					$ticket = $datas["ticket"];
					$task = $datas["task"];
					$pipeline = $datas["pipeline"];
					$file = $datas["file"];
					$api = new Rednumber_Marketing_CRM_Hubspot_API();
					if( isset($contact["enable"]) && $contact["enable"] == "on"){
						$submits =  $this->get_submits($contact,$form,"contact");
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
						$submits =  $this->get_submits($deal,$form,"deal");
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
						$submits =  $this->get_submits($company,$form,"company");
						$response = $api->add_company($submits);
						$response = json_decode($response,true);
						if( isset($response["id"]) ){
							$this->set_datas_submits("current_company_id",$response["id"]);
							Rednumber_Marketing_CRM_Logs::add("Company: Added ","Send datas",self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("Company ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
						}
					}
					if( isset($ticket["enable"]) && $ticket["enable"] == "on"){
						$submits =  $this->get_submits($ticket,$form,"ticket");
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
						$submits =  $this->get_submits($task,$form,"task");
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
						$submits =  $this->get_submits($pipeline,$form,"pipeline");
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
						$submits =  $this->get_submits($file,$form,"file");
						$response = $api->add_file($submits);
						$response = json_decode($response,true);
						if( isset($response["id"]) ){
							Rednumber_Marketing_CRM_Logs::add("File Upload: Added ","Send datas",self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("File Upload ERROR: ".$response["message"],"ERROR",self::$form,self::$add_on,$form_id);
						}
					}
			}
		}
	}
	function get_submits($datas, $form, $type=null){
		$plugins = array("plugin"=>"contact_form_7","datas"=>$datas,"form"=>$form,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Hubspot::cover_data_to_api($submits_new,$type,$form,self::$form,$datas);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Hubspot_Contact_From_7;