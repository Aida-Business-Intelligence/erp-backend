<?php
class Rednumber_Marketing_CRM_Frontend_PDF_Booked{
	private static $add_on ="pdf"; 
	private static $form ="booked";
	private $datas_submits= array();  
	function __construct(){
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
		add_filter("crm_marketing_pdf_default_".self::$form,array($this,"change_name_default"));
	}
	function change_name_default($text){
		return "appointment-[ID]";
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function sync($form_id){
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$token_replacements = booked_get_appointment_tokens( 15505 );
		if( is_array($datas) && count($datas) > 0 ){ 
			esc_html_e("Feature will update soon","crm-marketing");
		}
	}
	function get_submits($datas, $form, $type=null){
		$plugins = array("plugin"=>"contact_form_7","datas"=>$datas,"form"=>$form,"type"=>$type,"add"=>$this->datas_submits);
		$submits_new = Rednumber_Marketing_CRM_backend::get_datas_contact_form($plugins);
		$submits = Rednumber_Marketing_CRM_Hubspot::cover_data_to_api($submits_new,$type,$form,self::$form,$datas);
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_PDF_Booked;