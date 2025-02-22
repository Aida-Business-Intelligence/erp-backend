<?php
//version 1.2
class Rednumber_Marketing_CRM_Frontend_PDF_Form_Widget{ 
	public $attachments_array = [];
	function __construct(){	
		add_filter("crm_marketing_pdf_default_elementor",array($this,"crm_marketing_pdf_default"));
		//add_action('elementor_pro/forms/process', array($this,'send_data'),11, 2);
	}
	function crm_marketing_pdf_default($name){
		return "elementor-form";
	}
	function send_data($record, $ajax_handler){
		 $raw_fields = $record->get( 'fields' );
		 $form_data = array();
	    foreach ( $raw_fields as $id => $field ) {
	        $form_data[ '[field id="'.$id.'"]' ] = $field['value'];
	    }
		$post_id = $record->get_form_settings( 'edit_post_id' );
		$form_post_id = $record->get_form_settings( 'id' );
		$form_id = $form_post_id ."-".$post_id;	
		$datas = Rednumber_Marketing_CRM_Database::get_datas("elementor","pdf",$form_id);

	}
}
new Rednumber_Marketing_CRM_Frontend_PDF_Form_Widget;