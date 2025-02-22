<?php
class Rednumber_Marketing_CRM_Frontend_Sendinblue_Woo{
	private static $add_on ="sendinblue"; 
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
			$api = new Rednumber_Marketing_CRM_Sendinblue_API();
			$datas = $datas[0];
			$contact = $datas["contact"];
			$deal = $datas["deal"];
			$company = $datas["company"];
			$task = $datas["task"];
			$note = $datas["note"];
			$file = $datas["file"];
			//contact
			if( isset($contact["enable"]) && $contact["enable"] == "on"){
				$submits =  $this->get_submits($contact,$order_id,$order,"contact");
				$response = $api->add_contact($submits);
				$response = json_decode($response,true);
				if( !$response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
			//deal
			if( isset($deal["enable"]) && $deal["enable"] == "on"){
				$submits =  $this->get_submits($deal,$order_id,$order,"deal");
				$response = $api->add_deal($submits);
				$response = json_decode($response,true);
				if( !$response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
			//company
			if( isset($company["enable"]) && $company["enable"] == "on"){
				$submits =  $this->get_submits($company,$order_id,$order,"company");
				$response = $api->add_company($submits);
				$response = json_decode($response,true);
				if( !$response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
			//task
			if( isset($task["enable"]) && $task["enable"] == "on"){
				$submits =  $this->get_submits($task,$order_id,$order,"task");
				$response = $api->add_task($submits);
				$response = json_decode($response,true);
				if( !$response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
			//Note
			if( isset($note["enable"]) && $note["enable"] == "on"){
				$submits =  $this->get_submits($note,$order_id,$order,"note");
				$response = $api->add_note($submits);
				$response = json_decode($response,true);
				if( !$response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
			//file
			if( isset($file["enable"]) && $file["enable"] == "on"){
				$submits =  $this->get_submits($file,$order_id,$order,"file");
				$response = $api->add_file($submits);
				$response = json_decode($response,true);
				if( !$response["errors"] ){
					Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
				}else{
					Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
				}
			}
		}
		return $response;
	}
	function get_submits($datas, $order_id, $order, $type=null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$v = apply_shortcodes($v,true);
					$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($order_id,$v,$order);
					if( $value != "" ){
						$submits[$k] = $value;
					}else{
						$submits[$k] = $v;
					}
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$new_value = apply_shortcodes($new_value,true);
						$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($order_id,$new_value,$order);
						if( $value != "" ){ 
							$new_values[$new_key] = $value;
						}
					}
					$submits[$k] = $new_values;
				}
			}
		}
		switch( $type ){
			case "contact";
				$list_contact_ids = array_values($submits["listIds"]);
				$list_contact_ids = implode(",",$list_contact_ids);
				$list_contact_ids = array_map('intval', explode(',', $list_contact_ids));
				$email = $submits["email"];
				unset($submits["email"]);
				unset($submits["listIds"]);
				return array("email"=>$email,"attributes"=>$submits,"listIds"=>$list_contact_ids,"updateEnabled"=>false);
				break;
			case "deal";
				$name = $submits["deal_name"];
				$amount = (int) $submits["amount"];
				$submits["amount"] = $amount;
				unset($submits["deal_name"]);
				return array("name"=>$name,"attributes"=>$submits);
				break;
			case "company";
				$name = $submits["name"];
				unset($submits["name"]);
				return array("name"=>$name,"attributes"=>$submits);
				break;
			case "task";
				if( isset($submits["done"]) ){
					$submits["done"] = true;
				}else {
					$submits["done"] = false;
				}
				if( isset($submits["contactsIds"]) ){
					$submits["contactsIds"] = array( (int) $submits["contactsIds"] );
				}
				if( isset($submits["dealsIds"]) ){
					$submits["dealsIds"] =array( $submits["dealsIds"]);
				}
				if( isset($submits["companiesIds"]) ){
					$submits["companiesIds"] = array( $submits["companiesIds"]);
				}
				if( isset($submits["duration"]) ){
					$submits["duration"] = (int) $submits["duration"];
				}
				return $submits;
				break;
			case "note";
				if( isset($submits["contactIds"]) ){
					$submits["contactIds"] = array( (int) $submits["contactIds"] );
				}
				if( isset($submits["dealIds"]) ){
					$submits["dealIds"] =array( $submits["dealIds"]);
				}
				if( isset($submits["companyIds"]) ){
					$submits["companyIds"] = array( $submits["companyIds"]);
				}
				return $submits;
				break;
			case "file";
				if( isset($submits["file"]) ){
					$name_file = str_replace(array("{","}"),"",$datas["file"]);
					if( $name_file =="upload_pdf"){
						$check = get_option("woocommerce_new_order_settings");
						$lists= get_option("woocommerce_pdf");
						$templates_id= $lists["new_order"];
						$upload_dir = wp_upload_dir();
						$path_main = $upload_dir['basedir'] . '/pdfs/';
						$name = "new_order_".$order_id."---".$templates_id;
						$submits["file"] = $path_main.$name.".pdf";
					}else{
						if( is_array($submits["file"])){
							$submits["file"] = array_shift(array_values($submits["file"]));
						}
					}
				}
				if( isset($submits["contactId"]) ){
					$submits["contactId"] = (int) $submits["contactId"];
				}
				return $submits;
				break;
			default:
				return $submits;
				break;
		}
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Sendinblue_Woo;