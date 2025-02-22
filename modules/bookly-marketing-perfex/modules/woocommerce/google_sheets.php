<?php
class Rednumber_Marketing_CRM_Frontend_Google_Sheets_Woo{
	private static $add_on ="google_sheets"; 
	private static $form ="woocommerce"; 
	function __construct(){
		add_action( 'woocommerce_new_order', array($this,'new_order'),  1, 1  );
		add_action( 'woocommerce_thankyou', array($this,'order_completed'),  1, 1  );
		add_action( 'crm_marketing_sync_'.self::$form."_".self::$add_on,array($this,"sync"));
	}
	function new_order($order_id){
		$this->submit_form($order_id,"new_order");
	}
	function order_completed($order_id){
		$this->submit_form($order_id,"completed");
	}
	function sync($form_id){
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){ 
			$orders = wc_get_orders( array('numberposts' => -1) );
			$total_error = 0;
			$total_succsess = 0;
			foreach( $orders as $order ){ 
				$result =$this->submit_form($order->get_id(),$form_id, $order);
				if( $result != null){
					if( isset($result->spreadsheetId) && $result->spreadsheetId != ""){
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
	function submit_form($order_id ,$form_id, $order = null){
		if( $order == null ){
			$order = new WC_Order($order_id);
		}
		$result = null;
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		$datas = Rednumber_Marketing_CRM_Database::get_datas(self::$form,self::$add_on,$form_id);
		if( is_array($datas) && count($datas) > 0 && $options["api"] != "" ){
			$api = new Rednumber_Marketing_Google_Sheets_API();
			foreach( $datas as $data ){
				foreach($data as $k => $vl ){
					$$k = $vl;
				}
				$spreadsheet_id = $api->get_sheet_id($method);
				if( $spreadsheet_id != "" ){
					$rows = array_combine($map_fields["webhook"], $map_fields["form"]);
					$rows =  $this->get_submits($rows,$order_id, $order);
					$rows = array_values($rows);
					$result = $api->add_row($spreadsheet_id,$rows);
					if( isset($result->spreadsheetId) && $result->spreadsheetId != ""){
						Rednumber_Marketing_CRM_Logs::add("Added row spreadsheet id: ".$result->spreadsheetId,"Send datas" ,self::$form,self::$add_on,$form_id);
					}else{
						Rednumber_Marketing_CRM_Logs::add("Error ","Send datas",self::$form,self::$add_on,$form_id);
					}
				}
			}
		}
		return $result;
	}
	function get_submits($datas, $order_id, $order, $type=null){
		$submits = array();
		foreach( $datas as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				if( !is_array($v) ){
					$v = apply_shortcodes($v,true);
					$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($order_id,$v,$order);
					if( $value != "" ){
						$submits[$k] = $value;
					}else{
						$submits[$k] = $v;
					}
				}else{
					$new_values = array();
					foreach($v as $new_key=>$new_value){
						$new_value = apply_shortcodes($new_value,true);
						$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($order_id,$new_value,$order);
						if( $value != "" ){ 
							$new_values[$new_key] = $value;
						}else{
							$new_values[$new_key] = $new_value;
						}
					}
					$submits[$k] = $new_values;
				}
			}
		}
		return $submits;
	}
}
new Rednumber_Marketing_CRM_Frontend_Google_Sheets_Woo;