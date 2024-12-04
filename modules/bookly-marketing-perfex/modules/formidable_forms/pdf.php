<?php
class Rednumber_Marketing_CRM_Frontend_PDF_Formidable_Forms{
	private static $add_on ="pdf"; 
	private static $form ="formidable_forms";
	private $datas_submits= array();  
	function __construct(){
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
		add_filter("crm_marketing_pdf_default_".self::$form,array($this,"crm_marketing_pdf_default"));
			
	}
	function crm_marketing_pdf_default($name){
		return "form-id";
	}
	function sync($form_id){
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 ){ 
			esc_html_e("Feature will update soon","crm-marketing");
		}
	}
}
new Rednumber_Marketing_CRM_Frontend_PDF_Formidable_Forms;