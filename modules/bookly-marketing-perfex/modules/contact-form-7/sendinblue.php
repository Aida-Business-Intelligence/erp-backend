<?php
class Rednumber_Marketing_CRM_Frontend_Sendinblue_Contact_From_7{
	private static $add_on ="sendinblue"; 
	private static $form ="contact_form_7"; 
	function __construct(){
		add_action( 'wpcf7_before_send_mail', array($this,'save_data') );
		add_action( 'wpcf7_mail_sent', array($this,'send_pdf') );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
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
					$submits =  $this->get_submits($file,$form,"file",$form_id);
					$response = $api->add_file($submits);
					$response = json_decode($response,true);
					if( !$response["errors"] ){
						Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
					}else{
						Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
					}
				}
			}
		}
	}
	function save_data( $form_tag ) {
		$form = WPCF7_Submission::get_instance();
		if ( $form ) {
			$form_id = $form_tag->id();
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
			if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
				$api = new Rednumber_Marketing_CRM_Sendinblue_API();
				$datas = $datas[0];
				$contact = $datas["contact"];
				$deal = $datas["deal"];
				$company = $datas["company"];
				$task = $datas["task"];
				$note = $datas["note"];
				//contact
				if( isset($contact["enable"]) && $contact["enable"] == "on"){
					$submits =  $this->get_submits($contact,$form,"contact");
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
					$submits =  $this->get_submits($deal,$form,"deal");
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
					$submits =  $this->get_submits($company,$form,"company");
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
					$submits =  $this->get_submits($task,$form,"task");
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
					$submits =  $this->get_submits($note,$form,"note");
					$response = $api->add_note($submits);
					$response = json_decode($response,true);
					if( !$response["errors"] ){
						Rednumber_Marketing_CRM_Logs::add("Contact: Added ","Send datas",self::$form,self::$add_on,$form_id);
					}else{
						Rednumber_Marketing_CRM_Logs::add("Contact ERROR: ".$response["errors"][0]["title"],"ERROR",self::$form,self::$add_on,$form_id);
					}
				}
			}
		}
	}
	function get_submits($datas, $form, $type=null, $form_id = null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$v = apply_shortcodes($v,true);
					$value = str_replace(array("[","]"),"",$v);
					$value =$form->get_posted_data($value);
					if($value == null){
						$value = $v;
					}
					$submits[$k] = $value;
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$new_value = apply_shortcodes($new_value,true);
						$value = str_replace(array("[","]"),"",$new_value);
						$value =$form->get_posted_data($value);
						if($value == null){
							$value = $new_value;
						}
						$new_values[$new_key] = $new_value;
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
					$uploaded_files = $form->uploaded_files();
					foreach( $uploaded_files as $name_upload => $files ){
						if( $name_file == $name_upload ){
							$submits["file"] = $files[0];
							break;
						}
					}
					if( $name_file =="upload_pdf"){
						$name = get_post_meta($form_id,"_pdfcreator_template_name",true);
						 if($name == ""){
						 	$name = "contact-form";
						 }else{
						 	$value = str_replace(array("[","]"),"",$name);
							$value =$form->get_posted_data($value);
							if($value == null){
								$value = $name;
							}
						 	$name = $value;
						 }
						 $name = sanitize_title($name);
						 $name .= "-".$form_id; 
						 $upload_dir = wp_upload_dir();
						 $path_main = $upload_dir['basedir'] . '/pdfs/';  
						$submits["file"] = $path_main.$name.".pdf";
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
	}
}
new Rednumber_Marketing_CRM_Frontend_Sendinblue_Contact_From_7;