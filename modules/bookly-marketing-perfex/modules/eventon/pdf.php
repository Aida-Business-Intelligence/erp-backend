<?php
class Rednumber_Marketing_CRM_Frontend_PDF_Eventon{
	private static $add_on ="pdf"; 
	private static $form ="eventon";
	private $datas_submits= array();  
	function __construct(){
		add_action("booked_approved_email",array($this,"booked_approved_email"),99,5);
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
		add_filter( 'evotx_beforesend_tix_email_data', array( $this, 'add_attachment_email'), 10, 2 );	
	}
	function set_datas_submits($key,$value){
		$datas_submits= $this->datas_submits;
		$datas_submits[ $key ] = $value;
		$this->datas_submits = $datas_submits;
	}
	function sync($form_id){
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 ){ 
			esc_html_e("Feature will update soon","crm-marketing");
		}
	}
	function add_attachment_email($data, $order_id){
		$form_id = "eventon_ticket";
		$shortcode = new PDF_Eventon_Shortcode();
		$shortcode->set_order_id($order_id);
		$token_woo = $shortcode->get_datas();
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		$attachments = $data['attachments'];
		foreach( $datas as $data ){
			$name ="ticket-".$order_id;
			$name = sanitize_title($name);
			$pdf_template = $data["template"];
			$pdf_content =FDF_Create_frontend::pdf_creator_preview($pdf_template,"upload",$name,"","","",$token_woo,true);
			$path =FDF_Create_frontend::pdf_creator_preview($pdf_template,"upload",$name,$pdf_content);
			$attachments[] = $path;
		}
		$data['attachments'] = $attachments;
		return $data;
	}
}
new Rednumber_Marketing_CRM_Frontend_PDF_Eventon;