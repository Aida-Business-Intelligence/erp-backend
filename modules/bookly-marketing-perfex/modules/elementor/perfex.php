<?php
class Rednumber_Marketing_CRM_Frontend_Perfex_Form_Widget{
	private static $add_on ="perfex"; 
	private static $form ="elementor"; 
	private $datas_submits= array(); 
	function __construct(){
		add_action('elementor_pro/forms/new_record', array($this,'send_data'),10,2);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function sync($form_id){
		$form_ids = explode("-", $form_id);
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$total_succsess = 0;
		$total_error = 0;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != ""){ 
			$submissions = Rednumber_Marketing_CRM_Database::get_submissions_elementor($form_ids[0]);
			if ( $submissions ) { 
				foreach ( $submissions as $submission) { 
					$form_data = Rednumber_Marketing_CRM_Database::get_submissions_elementor_Value($submission->id);
					$form_data["form_id"] = $form_ids[0];
	   				$form_data["form_title"] = $submission->form_name;
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
	function send_data($record, $settings){
		 $raw_fields = $record->get( 'fields' );
		 $form_data = array();
		 foreach ( $raw_fields as $id => $field ) {
	        $form_data[ $id ] = $field['value'];
	    }
		$post_id = $record->get_form_settings( 'edit_post_id' );
		$form_post_id = $record->get_form_settings( 'id' );
		$form_title = $record->get_form_settings( 'form_name' );
		$form_id = $form_post_id ."-".$post_id;
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$form_data["form_id"] = $form_post_id;
	    $form_data["form_title"] = $form_title;
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$this->add_submit($datas,$form_data);
		}
	}
	function add_submit($datas,$form_data){
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
		$value_ojects = array();
		$plugins = array("plugin"=>self::$form,"datas"=>$datas,"form"=>$form_data,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Perfex::cover_data_to_api($submits_new,$type,$form_data,self::$form);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Perfex_Form_Widget;