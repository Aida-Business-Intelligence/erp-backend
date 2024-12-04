<?php
class Rednumber_Marketing_CRM_Frontend_Perfex_Ninja_Forms{
	private static $add_on ="perfex"; 
	private static $form ="ninjaforms"; 
	private $datas_submits= array(); 
	function __construct(){
		add_action('ninja_forms_after_submission', array($this,'submit_form'),11);
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
						if( $response["status"] ){ 
							$total_succsess++;
						}else{
							$total_error++;
						}
					}else{
						$total_error++;
					}
				}
				printf( esc_html__( 'Success: %s Error: %s', 'crm-marketing' ),$total_succsess ,$total_error );
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
new Rednumber_Marketing_CRM_Frontend_Perfex_Ninja_Forms;