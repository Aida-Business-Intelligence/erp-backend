<?php
class Rednumber_Marketing_CRM_Backend_Woocommerce{ 
	private static $form ="woocommerce"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		add_action('rednumber_crm_marketing_sync_'.self::$form,array($this,"add_sync"));
	}
	function add_sync(){
		?>
		<p><?php esc_html_e("Please save changes before SYNC","crm-marketing"); ?></p>
		<a href="#" class="button button-primary crm_marketing_sync"><?php esc_html_e("SYNC Order","crm-marketing") ?></a>
		<?php
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("WooCommerece","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		$datas[] = array(
                    'id'          => "new_order",
                    'title'       => esc_html__("New order","woocommerce"),
                    'type'        => self::$form,
                    'label'       => "Woocommerce"
                    );
		$datas[] = array(
                    'id'          => "completed",
                    'title'       => esc_html__("Completed","woocommerce"),
                    'type'        => self::$form,
                    'label'       => "Woocommerce"
                    );
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode = array();
		 $lists = self::get_list_shortcode();
		 foreach ( $lists as $values ){
		 	foreach (array_values($values)[0] as $k => $v) {
		  			$shortcode["[".$k."]"] = $v;
		  		}
		 }
        return $shortcode;
	}
	public static function get_list_shortcode(){
		return array(
			array("Main Order"=>array(
				"woo_builder_order_id"=>"Order ID",
				"woo_builder_order_link"=>"Order Link",
				"woo_builder_order_date"=>"Order date",
				"woo_order_signature"=>"Signature Plugin",
			)),
			array("Billing"=>array(
				"woo_builder_billing_first_name"=>"Billing first Name",
				"woo_builder_billing_last_name"=>"Billing last Name",
				"woo_builder_billing_city"=>"Billing city",
				"woo_builder_billing_company"=>"Billing company",
				"woo_builder_billing_address_1"=>"Billing address 1",
				"woo_builder_billing_address_2"=>"Billing address 2",
				"woo_builder_billing_state"=>"Billing state",
				"woo_builder_billing_postcode"=>"Billing postcode",
				"woo_builder_billing_phone"=>"Billing Phone",
				"woo_builder_billing_email"=>"Billing email",
				"woo_builder_billing_country"=>"Billing country",
			)),
			array("Shipping"=>array(
				"woo_builder_shipping_first_name" => "Shipping first Name",
				"woo_builder_shipping_last_name" => "Shipping last Name",
				"woo_builder_shipping_city"=> "Shipping city",
				"woo_builder_shipping_company"=> "Shipping Company",
				"woo_builder_shipping_address_1"=> "Shipping address 1",
				"woo_builder_shipping_address_2"=> "Shipping address 2",
				"woo_builder_shipping_state"=> "Shipping state",
				"woo_builder_shipping_postcode"=> "Shipping postcode",
				"woo_builder_shipping_phone" => "Shipping phone",
				"woo_builder_shipping_email" => "Shipping Email",
				"woo_builder_shipping_country" => "Shipping country",
			)),
			array("Note"=>array(
				"woo_builder_customer_note" => "Customer note",
				"woo_builder_customer_provided_note" => "Customer provided note",
			)),
			array("Product"=>array(
				"woo_builder_product_title" => "Product title",
				"woo_builder_product_des" => "Product description",
			)),
			array("User"=>array(
				"woo_builder_user_email" => "User email",
				"woo_builder_user_name" => "User name",
				"woo_builder_user_id" => "User id",
			)),
			array("Order Data"=>array(
				"woo_builder_order_starus"=>"Order Status",
				"woo_builder_order_shipping_method"=>"Shipping method",
				"woo_builder_order_payment_method"=>"Payment method",
				"woo_builder_order_currency"=>"Order currency",
				"woo_builder_order_subtotal"=>"Subtotal",
				"woo_builder_order_price_total"=>"Total",
				"woo_builder_order_total_tax"=>"Total Tax",
				"woo_builder_order_discount_total"=>"Discount total",
				"woo_builder_order_total_quantity"=>"Total Qty",
			)),
		);
	}
	public static function shortcode_main($order_id, $tag , $order=null) {
		if (  !class_exists('WC_Order') ) {
			return false;
		}
		if( !$order ){
			$order = new WC_Order($order_id);
		}		
		$datas = array();
		$tag = str_replace(array("[","]"),"",$tag);
		//Order 
		$users = $order->get_user();
		switch( $tag ){
			case 'woo_order_signature':
		        return $order->get_meta('woocommerce_signature_name_data');
				break;
			case 'woo_builder_order_id':
				return $order->id;
				break;
			case 'woo_builder_order_date':
				$date_modified = $order->get_date_modified();
    			$date_paid = $order->get_date_completed();
    			$date =  empty( $date_paid ) ? $date_modified : $date_paid;
    			$date_fomat = get_option('date_format');
				return $date->date($date_fomat);
				break;
			case 'woo_builder_order_link':
				return $order->get_view_order_url();
				break;
			case 'woo_builder_order_number':
				return $order->get_order_number();
				break;
			case 'woo_builder_order_date':
				return $order->get_date_created()->date_i18n(wc_date_format());
				break;
			case 'woo_builder_billing_first_name':
				return $order->get_billing_first_name();
				break;
			case 'woo_builder_billing_last_name':
				return $order->get_billing_last_name();
				break;
			case 'woo_builder_billing_city':
				return $order->get_billing_city();
				break;
			case 'woo_builder_billing_company':
				return $order->get_billing_company();
				break;
			case 'woo_builder_billing_address_1':
				return $order->get_billing_address_1();
				break;
			case 'woo_builder_billing_address_2':
				return $order->get_billing_address_2();
				break;
			case 'woo_builder_billing_state':
				return $order->get_billing_state();
				break;
			case 'woo_builder_billing_postcode':
				return $order->get_billing_postcode();
				break;
			case 'woo_builder_billing_phone':
				return $order->get_billing_phone();
				break;
			case 'woo_builder_billing_email':
				return $order->get_billing_email();
				break;
			case 'woo_builder_billing_country':
				return $order->get_billing_country();
				break;
			case 'woo_builder_shipping_first_name':
				return $order->get_shipping_first_name();
				break;
			case 'woo_builder_shipping_last_name':
				return $order->get_shipping_last_name();
				break;
			case 'woo_builder_shipping_city':
				return $order->get_shipping_city();
				break;
			case 'woo_builder_shipping_company':
				return $order->get_shipping_company();
				break;
			case 'woo_builder_shipping_address_1':
				return $order->get_shipping_address_1();
				break;
			case 'woo_builder_shipping_address_2':
				return $order->get_shipping_address_2();
				break;
			case 'woo_builder_shipping_state':
				return $order->get_shipping_state();
				break;
			case 'woo_builder_shipping_postcode':
				return $order->get_shipping_postcode();
				break;
			case 'woo_builder_shipping_country':
				return $order->get_shipping_country();
				break;
			case 'woo_builder_customer_provided_note':
			case 'woo_builder_customer_note':
				return $order->get_customer_note();
				break;
			case 'woo_builder_user_email':
				if(isset($users->user_email)){
		            return $users->user_email;
		        } else {
		            return $order->get_billing_email();
		        }
				break;
			case 'woo_builder_user_name':
				if(isset($users->user_login) && !empty($users->user_login)){
		            return $users->user_login;
		        }else if(isset($users->user_nicename)){
		            return $users->user_nicename;
		        }else {
		            return $order->get_billing_first_name();
		        }
				break;
			case 'woo_builder_order_starus':
				return $order->get_status();
				break;
			case 'woo_builder_order_shipping_method':
				return $order->get_shipping_method();
				break;
			case 'woo_builder_order_payment_method':
				return $order->get_payment_method_title();
				break;
			case 'woo_builder_order_currency':
				return $order->get_currency();
				break;
			case 'woo_builder_order_subtotal':
				if( $order->get_subtotal() == 0 ){
					return  $order->get_total();
				}else{
					return $order->get_subtotal();
				}
				break;
			case 'woo_builder_order_price_total':
				return  $order->get_total();
				break;
			case 'woo_builder_order_total_tax':
				return $order->get_total_tax();
				break;
			case 'woo_builder_order_discount_total':
				return $order->get_total_discount();
				break;
			case 'woo_builder_order_total_quantity':
				$total_quantity = 0;
				foreach ( $order->get_items() as $item_id => $item ) {
			       $quantity = $item->get_quantity();
			       $total_quantity += $quantity;
			    }
			    if( $total_quantity == 0 ){
			    	$total_quantity = 1;
			    }
				return $total_quantity;
				break;
			case 'woo_builder_product_title':
				foreach ( $order->get_items() as $item_id => $item ) {
			       return $item->get_name();
			    }
				break;
			case 'woo_builder_product_des':
				foreach ( $order->get_items() as $item_id => $item ) {
			      	$product_id = $item['product_id'];
    				$product_instance = wc_get_product($product_id);
			       return $product_instance->get_short_description();
			    }
				break;
		}
	}
}
new Rednumber_Marketing_CRM_Backend_Woocommerce;