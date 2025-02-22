<?php
class Rednumber_Marketing_CRM_Frontend_Perfex_Eform{
	private static $add_on ="perfex"; 
	private static $form ="eform"; 
	private $datas_submits= array(); 
	function __construct(){
		add_action('ipt_fsqm_hook_save_success', array($this,'submit_form'));
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function sync($form_id){
		esc_html_e("Will update soon!","crm-marketing");
	}
	function submit_form($form ){
		$form_id = $form->form_id;
		$submission = array();
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$submission = Rednumber_Marketing_CRM_Backend_Eform::cover_entry_to_data($form);
			$this->add_submit($datas,$submission);
		}
	}
	function add_submit( $datas, $form_data){ 
		$datas = $datas[0];
		$api = new Rednumber_Marketing_CRM_Perfex_API();
		foreach( $datas as $key=> $data ){
			if( isset($data["enable"]) && $data["enable"] == "on"){  
				$submits =  $this->get_submits($data,$form_data,$key);
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
		return $response;
	}
	function get_submits($datas, $form_data, $type=null){
		$submits = array();
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form_data,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Perfex::cover_data_to_api($submits_new,$type,$form_data,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Perfex_Eform;