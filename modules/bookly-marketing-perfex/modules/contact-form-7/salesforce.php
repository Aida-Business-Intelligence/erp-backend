<?php
class Rednumber_Marketing_CRM_Frontend_Salesforce_Contact_From_7{
	private static $add_on ="salesforce"; 
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
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
			if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){
					$datas = $datas[0];
					$contact = $datas["contact"];
					
					$api = new Rednumber_Marketing_CRM_Salesforce_API();
					if( isset($contact["enable"]) && $contact["enable"] == "on"){
						$submits =  $this->get_submits($contact,$form,"contact");
						$response = $api->add_contact($submits);
						$response = json_decode($response,true);
						var_dump($submits);
						var_dump($response);
						die();
					}
					
			}
		}
	}
	function get_submits($datas, $form, $type=null){
		$plugins = array("plugin"=>"contact_form_7","datas"=>$datas,"form"=>$form,"type"=>$type);
		$submits = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		switch( $type ){
			case "contact":
				return $submits;
			default:
				return $submits;
				break;
		}
	}
}
new Rednumber_Marketing_CRM_Frontend_Salesforce_Contact_From_7;