<?php
class Rednumber_Marketing_CRM_Frontend_Activecampaign_Contact_From_7{
	private static $add_on ="activecampaign"; 
	private static $form ="contact_form_7"; 
	function __construct(){
		add_action( 'wpcf7_before_send_mail', array($this,'save_data') );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){ 
			esc_html_e("Feature will update soon","crm-marketing");
		}
	}
	function save_data( $form_tag ) {
		$form = WPCF7_Submission::get_instance();
		if ( $form ) {
		    $form_datas  = $form->get_posted_data();
			$form_id = $form_tag->id();
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>"","url"));
			$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
			if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
					$datas = $datas[0];
					$contact = $datas["contact"];
					$deal = $datas["deal"];
					$task = $datas["task"];
					$list = $datas["list"];
					$note = $datas["note"];
					$api = new Rednumber_Marketing_Activecampaign_API();
					if( isset($contact["enable"]) && $contact["enable"] == "on"){
						$submits =  $this->get_submits($contact,$form,"contact");
						//$datas_submit
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
						$submits =  $this->get_submits($deal,$form,"deal");
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
						$submits =  $this->get_submits($task,$form, "task");
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
						$submits =  $this->get_submits($list,$form,"list");
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
						$submits =  $this->get_submits($note,$form,"note");
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
	}
	function get_submits($datas, $form, $type=null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$value = str_replace(array("[","]"),"",$v);
					$value =$form->get_posted_data($value);
					if($value == null){
						$value = $v;
					}
					$submits[$k] = $value;
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$value = str_replace(array("[","]"),"",$new_value);
						$value =$form->get_posted_data($value);
						if($value == null){
							$value = $new_value;
						}
						$new_values[] = array("field"=>$new_key,"value"=>$value);
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
	}
}
new Rednumber_Marketing_CRM_Frontend_Activecampaign_Contact_From_7;