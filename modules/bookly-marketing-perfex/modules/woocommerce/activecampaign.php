<?php
class Rednumber_Marketing_CRM_Frontend_Activecampaign_Woo{
	private static $add_on ="activecampaign"; 
	private static $form ="woocommerce"; 
	function __construct(){
		add_action( 'woocommerce_new_order', array($this,'new_order'),  1, 1  );
		add_action( 'woocommerce_thankyou', array($this,'order_completed'),  1, 1  );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
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
					if( !$response["errors"] ){
						$total_succsess++;
					}else{
						$total_error++;
					} 
				}else{
					$total_error++;
				}
			}
			echo $total_succsess . " successes " .$total_error . " error";
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
			$contact = $datas["contact"];
			$deal = $datas["deal"];
			$task = $datas["task"];
			$list = $datas["list"];
			$note = $datas["note"];
			$api = new Rednumber_Marketing_Activecampaign_API();
			if( isset($contact["enable"]) && $contact["enable"] == "on"){
				$submits =  $this->get_submits($contact,$order_id, $order,"contact");
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
				$submits =  $this->get_submits($deal,$order_id, $order,"deal");
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
				$submits =  $this->get_submits($task,$order_id, $order, "task");
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
				$submits =  $this->get_submits($list,$order_id, $order,"list");
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
				$submits =  $this->get_submits($note,$order_id, $order,"note");
				$response = $api->add_note($submits);
				$response = json_decode($response,true);
				if( $response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Note: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Note ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
		}
	}
	function get_submits($datas, $order_id, $order, $type=null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($order_id,$v,$order);
					if( $value != "" ){
						$submits[$k] = $value;
					}else{
						$submits[$k] = $v;
					}
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($order_id,$new_value,$order);
						if( $value != "" ){ 
							$new_values[] = array("field"=>$new_key,"value"=>$value);
						}
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
new Rednumber_Marketing_CRM_Frontend_Activecampaign_Woo;