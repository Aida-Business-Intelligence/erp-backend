<?php
class PDF_Eventon_Shortcodes {
	public $order = null;
	private $order_id = false;
	public function set_order_id($id =""){
		$this->order_id = $id ;
		$this->add_shortcode();
	}
	function get_datas(){
		$billings = array(
				"event_order_starus"=>"Order Status",
				"event_order_shipping_method"=>"Shipping method",
				"event_order_payment_method"=>"Payment method",
				"event_order_currency"=>"Order currency",
				"event_order_subtotal"=>"Subtotal",
				"event_order_price_total"=>"Total",
				"event_order_total_tax"=>"Total Tax",
				"event_order_discount_total"=>"Discount total",
				"event_order_total_quantity"=>"Total Qty",
				"event_billing_first_name"=>"Billing first Name",
				"event_billing_last_name"=>"Billing last Name",
				"event_billing_city"=>"Billing city",
				"event_billing_company"=>"Billing company",
				"event_billing_address_1"=>"Billing address 1",
				"event_billing_address_2"=>"Billing address 2",
				"event_billing_state"=>"Billing state",
				"event_billing_postcode"=>"Billing postcode",
				"event_billing_phone"=>"Billing Phone",
				"event_billing_email"=>"Billing email",
				"event_billing_country"=>"Billing country",
				"event_number"=>"Billing country",
				"event_name"=>"Billing country",
				"event_details"=>"Billing country",
				"event_location"=>"Billing country",
				"event_date"=>"Billing country",
				"event_date_to"=>"Billing country",
				"event_time"=>"Billing country",
				"event_time_to"=>"Billing country",

			);
		$datas = array();
		foreach( $billings as $k => $v ){
			$datas[ "[".$k."]"] = do_shortcode("[".$k."]");
		}
		return $datas;
	}
	function add_shortcode(){
		add_shortcode( 'event_number', array($this,'shortcode_main' ));
		add_shortcode( 'event_name', array($this,'shortcode_main' ));
		add_shortcode( 'event_details', array($this,'shortcode_main' ));
		add_shortcode( 'event_location', array($this,'shortcode_main' ));
		add_shortcode( 'event_date', array($this,'shortcode_main' ));
		add_shortcode( 'event_date_to', array($this,'shortcode_main' ));
		add_shortcode( 'event_time', array($this,'shortcode_main' ));
		add_shortcode( 'event_time_to', array($this,'shortcode_main' ));
		add_shortcode( 'event_payment_method', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_first_name', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_last_name', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_city', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_company', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_address_1', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_address_2', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_state', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_postcode', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_phone', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_email', array($this,'shortcode_main' ));
		add_shortcode( 'event_billing_country', array($this,'shortcode_main' ));

		add_shortcode( 'event_order_starus', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_shipping_method', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_payment_method', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_currency', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_subtotal', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_price_total', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_total_tax', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_discount_total', array($this,'shortcode_main' ));
		add_shortcode( 'event_order_total_quantity', array($this,'shortcode_main' ));
	}
	function shortcode_main($atts, $content, $tag) {
		if ( empty($this->order_id) || !class_exists('WC_Order') ) {
			return false;
		}
		$order = $this->order;
		if( !isset( $order) ){
			$order = new WC_Order($this->order_id);
			$this->order = $order;
		}
		$event_id =  $order->get_meta("_event_id");
		$TA = new EVOTX_Attendees();
		$tickets = $TA->_get_tickets_for_order($order->get_id(), 'event');
		$ticket_id = 0;
		$ticket_number = "";
		$ticket_status = "";
		$ticket_values = "";
		foreach( $tickets as $key => $ticket ){
			$ticket_id = $key;
			foreach( $ticket as $k => $v  ){
				$ticket_number = $k;
				$ticket_values = $v;
				break;
			}
			break;
		}
		$time = get_post_meta( $ticket_id, 'evcal_srow', true );
		$time_end = get_post_meta( $ticket_id, 'evcal_erow', true );
		$time_zone = get_post_meta( $ticket_id, '_evo_tz', true );
		$wp_date_format = get_option("date_format");
		$wp_time_format = get_option("time_format");
		$is_all_day = get_post_meta( $ticket_id, 'evcal_allday', true );
		switch( $tag ){	
			case 'event_number':
				return $ticket_number;
				break;
			case 'event_name':
				return get_the_title( $ticket_id );
				break;
			case 'event_details':
				return get_post_field('post_content', $ticket_id);
				break;
			case 'event_subtitle':
				return get_post_meta( $ticket_id, 'evcal_subtitle', true );
				break;
			case 'event_location':
				$event_tax_term = wp_get_post_terms($ticket_id, "event_location");
				if ( $event_tax_term && ! is_wp_error( $event_tax_term ) ){	
					return $event_tax_term[0]->name;
				 }else{
				 	return "";
				 }
				break;
			case 'event_date':
				return date($wp_date_format,$time);
				break;
			case 'event_date_to':
				return date($wp_date_format,$time_end); 
				break;
			case 'event_time':
				if( $is_all_day == "no" ){
					return date($wp_time_format,$time);
				}else{
					return evo_lang_get('evcal_lang_allday','All Day');
				}
				break;
			case 'event_time_to':
				if( $is_all_day == "no" ){
					return date($wp_time_format,$time_end);
				}else{
					return evo_lang_get('evcal_lang_allday','All Day');
				}
				break;
			case 'event_order_date':
				return $order->get_date_created()->date_i18n(wc_date_format());
				break;
			case 'event_payment_method':
				return $ticket_values["payment_method"];
				break;
			case 'event_payment_status':
				return $ticket_values["oS"];
				break;
			case 'event_billing_first_name':
				return $order->get_billing_first_name();
				break;
			case 'event_billing_last_name':
				return $order->get_billing_last_name();
				break;
			case 'event_billing_city':
				return $order->get_billing_city();
				break;
			case 'event_billing_company':
				return $order->get_billing_company();
				break;
			case 'event_billing_address_1':
				return $order->get_billing_address_1();
				break;
			case 'event_billing_address_2':
				return $order->get_billing_address_2();
				break;
			case 'event_billing_state':
				return $order->get_billing_state();
				break;
			case 'event_billing_postcode':
				return $order->get_billing_postcode();
				break;
			case 'event_billing_phone':
				return $order->get_billing_phone();
				break;
			case 'event_billing_email':
				return $order->get_billing_email();
				break;
			case 'event_billing_country':
				return $order->get_billing_country();
				break;
			case 'event_order_starus':
				return $order->get_status();
				break;
			case 'event_order_shipping_method':
				return $order->get_shipping_method();
				break;
			case 'event_order_payment_method':
				return $order->get_payment_method_title();
				break;
			case 'event_order_currency':
				return $order->get_currency();
				break;
			case 'event_order_subtotal':
				return $order->get_subtotal();
				break;
			case 'event_order_price_total':
				return  $order->get_total();
				break;
			case 'event_order_total_tax':
				return $order->get_total_tax();
				break;
			case 'event_order_discount_total':
				return $order->get_total_discount();
				break;
			case 'event_order_total_quantity':
				$total_quantity = 0;
				foreach ( $order->get_items() as $item_id => $item ) {
			       $quantity = $item->get_quantity();
			       $total_quantity += $quantity;
			    }
				return $total_quantity;
				break;
		}
	}
}