<?php
class Rednumber_Marketing_CRM_Frontend_Pipedrive_Woo{
	private static $add_on ="pipedrive"; 
	private static $form ="woocommerce";
	private $datas_submits= array();  
	function __construct(){
		add_action( 'woocommerce_new_order', array($this,'new_order'),  1, 1  );
		add_action( 'woocommerce_thankyou', array($this,'order_completed'),  1, 1  );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function new_order($order_id){
		$this->submit_form($order_id,"new_order");
	}
	function order_completed($order_id){
		$this->submit_form($order_id,"completed");
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){ 
			$orders = wc_get_orders( array('numberposts' => -1) );
			$total_error = 0;
			$total_succsess = 0;
			foreach( $orders as $order ){ 
				$response =$this->submit_form($order->get_id(),$form_id, $order);
				if( $response != null){
					if( isset($response["message"]) ){ 
						$total_error++;
					}else{
						$total_succsess++;
					} 
				}
			}
			printf( esc_html__( 'Success: %s Error: %s', 'crm-marketing' ),$total_succsess ,$total_error );
		}
	}
	function submit_form($order_id ,$form_id, $order = null){
		if( $order == null ){
			$order = new WC_Order($order_id);
		}
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$datas = $datas[0];
			$lead = $datas["lead"];
			$deal = $datas["deal"];
			$preson = $datas["person"];
			$activity = $datas["activity"];
			$note = $datas["note"];
			$organization = $datas["organization"];
			$file = $datas["file"];
			$response = false;
			$api = new Rednumber_Marketing_CRM_Pipedrive_API();
			//Submit organization
			if( isset($organization["enable"]) && $organization["enable"] == "on"){ 
				$submits =  $this->get_submits($organization,$order_id, $order);
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
				$submits =  $this->get_submits($preson,$order_id, $order, "preson");
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
				$submits =  $this->get_submits($lead,$order_id, $order ,"lead");
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
				$submits =  $this->get_submits($deal,$order_id, $order);
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
				$submits =  $this->get_submits($activity,$order_id, $order,"activity");
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
				$submits =  $this->get_submits($note,$order_id, $order);
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
			$submits =  $this->get_submits($file,$order_id,$order,"file");
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
	}
	function get_submits($datas, $order_id, $order, $type=null){
		$submits = array();
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"order_id"=>$order_id,"order"=>$order,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Pipedrive::cover_data_to_api($submits_new,$type,"",self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Pipedrive_Woo;