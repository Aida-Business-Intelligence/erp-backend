<?php
class Rednumber_Marketing_CRM_Frontend_Pipedrive_Contact_From_7{
	private static $add_on ="pipedrive"; 
	private static $form ="contact_form_7";
	private $datas_submits= array(); 
	function __construct(){
		add_action( 'wpcf7_before_send_mail', array($this,'save_data') );
		add_action( 'wpcf7_mail_sent', array($this,'send_pdf') );
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
				//Submit file
				if( isset($file["enable"]) && $file["enable"] == "on"){ 
					$api = new Rednumber_Marketing_CRM_Pipedrive_API();
					$submits =  $this->get_submits($file,$form,"file");
					if( isset( $submits["file"] )){
						$response = $api->add_file($submits);
						$response = json_decode($response,true);
						if( $response["success"] ){
							Rednumber_Marketing_CRM_Logs::add("File: Added ","Send datas",self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("File ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
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
					$lead = $datas["lead"];
					$deal = $datas["deal"];
					$preson = $datas["person"];
					$activity = $datas["activity"];
					$note = $datas["note"];
					$organization = $datas["organization"];
					$api = new Rednumber_Marketing_CRM_Pipedrive_API();
					//Submit organization
					if( isset($organization["enable"]) && $organization["enable"] == "on"){ 
						$submits =  $this->get_submits($organization,$form);
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
						$submits =  $this->get_submits($preson,$form, "preson");
						$response = $api->add_person($submits);
						$response = json_decode($response,true);
						var_dump( $submits );
						var_dump( $response );
						//var_dump( $datas );
						die();
						if( $response["success"] ){
							$this->set_datas_submits("current_person_id",$response["data"]["id"]);
							Rednumber_Marketing_CRM_Logs::add("Person: Added ","Send datas",self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("Person ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
						}
					}
					if( isset($lead["enable"]) && $lead["enable"] == "on"){
						$submits =  $this->get_submits($lead,$form,"lead");
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
						$submits =  $this->get_submits($deal,$form);
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
						$submits =  $this->get_submits($activity,$form,"activity");
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
						$submits =  $this->get_submits($note,$form);
						$response = $api->add_note($submits);
						$response = json_decode($response,true);
						if( $response["success"] ){
							Rednumber_Marketing_CRM_Logs::add("Note: Added ","Send datas",self::$form,self::$add_on,$form_id);
						}else{
							Rednumber_Marketing_CRM_Logs::add("Note ERROR: ".$response["error_info"],"ERROR",self::$form,self::$add_on,$form_id);
						}
					}
			}
		}
	}
	function get_submits($datas, $form, $type=null){
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Pipedrive::cover_data_to_api($submits_new,$type,$form,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Pipedrive_Contact_From_7;