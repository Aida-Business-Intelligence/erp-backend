<?php
class Rednumber_Marketing_CRM_Frontend_Perfex_Contact_From_7{
	private static $add_on ="perfex"; 
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
					$api = new Rednumber_Marketing_CRM_Perfex_API();
					foreach( $datas as $key=> $data ){
						if( isset($data["enable"]) && $data["enable"] == "on"){  
							$submits =  $this->get_submits($data,$form,$key);
							$response = $api->add_submit($key,$submits);
							$response = json_decode($response,true);
							if( $response["status"] ){
								$this->set_datas_submits("current_".$key,$response["id"]);
								Rednumber_Marketing_CRM_Logs::add($key.": Added ID ".$response["id"],"Send datas",self::$form,self::$add_on,$form_id);
							}else{
								Rednumber_Marketing_CRM_Logs::add($key." ERROR: ".array_shift(array_values($response["message"])),"ERROR",self::$form,self::$add_on,$form_id);
							}
						}
					}
			}
		}
	}
	function get_submits($datas, $form, $type=null){
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Perfex::cover_data_to_api($submits_new,$type,$form,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Perfex_Contact_From_7;