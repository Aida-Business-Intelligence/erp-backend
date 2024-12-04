<?php
class Rednumber_Marketing_CRM_Frontend_Sendinblue_Ninja_Forms{
	private static $add_on ="sendinblue"; 
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
		$api = new Rednumber_Marketing_CRM_Sendinblue_API();
		$datas = $datas[0];
		$contact = $datas["contact"];
		$deal = $datas["deal"];
		$company = $datas["company"];
		$task = $datas["task"];
		$note = $datas["note"];
		$file = $datas["file"];
		$form_id = $form_data["form_id"];
		//contact
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
		//deal
		if( isset($deal["enable"]) && $deal["enable"] == "on"){
			$submits =  $this->get_submits($deal,$form_data,"deal");
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
			$submits =  $this->get_submits($company,$form_data,"company");
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
			$submits =  $this->get_submits($task,$form_data,"task");
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
			$submits =  $this->get_submits($note,$form_data,"note");
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
			$submits =  $this->get_submits($file,$form_data,"file");
			$response = $api->add_file($submits);
			$response = json_decode($response,true);
			if( !$response["errors"] ){
				Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
			}else{
				Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
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
					$v = apply_shortcodes($v,true);
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
						$new_value = apply_shortcodes($new_value,true);
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
						$new_values[$new_key] = $value;
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
					$name_file = str_replace(array("[","]"),"",$datas["file"]);
					if( $name_file =="upload_pdf"){
						$upload_dir = wp_upload_dir();
						$path_main = $upload_dir['basedir'] . '/pdfs/';
						$name= "contact-form-".$form_data['form_id'];
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
new Rednumber_Marketing_CRM_Frontend_Sendinblue_Ninja_Forms;