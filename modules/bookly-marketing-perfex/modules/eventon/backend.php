<?php
class Rednumber_Marketing_CRM_Backend_EventON{ 
	private static $form ="eventon"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		add_action('rednumber_crm_marketing_sync_'.self::$form,array($this,"add_sync"));
	}
	function add_sync(){
		?>
		<p><?php esc_html_e("Please save changes before SYNC","crm-marketing"); ?></p>
		<a href="#" class="button button-primary crm_marketing_sync"><?php esc_html_e("SYNC Entries","crm-marketing") ?></a>
		<?php
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Contact Form 7","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		$datas[] = array(
                    'id'          => "eventon_ticket",
                    'title'       => "EventON Ticket",
                    'type'        => self::$form,
                    'label'       => "EventON"
                    );
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode1 = array();
		$shortcode = array();
		$shortcode[] = array("text"=>"Ticket Number","value"=>"[event_number]");
		$shortcode[] = array("text"=>"Event Name","value"=>"[event_name]");	
		$shortcode[] = array("text"=>"Event Details","value"=>"[event_details]");	
		$shortcode[] = array("text"=>"Location","value"=>"[event_location]");	
		$shortcode[] = array("text"=>"Date","value"=>"[event_date]");	
		$shortcode[] = array("text"=>"Date To","value"=>"[event_date_to]");	
		$shortcode[] = array("text"=>"time","value"=>"[event_time]");	
		$shortcode[] = array("text"=>"time To","value"=>"[event_time_to]");	
		$billings = array(
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
			);
		foreach( $shortcode as $v ){
			$shortcode1[$v["value"]] = $v["text"];
		}
		foreach( $billings as $k => $v ){
			$shortcode1["[".$k."]"] = $v;
		}
        return $shortcode1;
	}
}
new Rednumber_Marketing_CRM_Backend_EventON;