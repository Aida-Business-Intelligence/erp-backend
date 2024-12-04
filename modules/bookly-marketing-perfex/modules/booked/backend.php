<?php
class Rednumber_Marketing_CRM_Backend_Booked{ 
	private static $form ="booked"; 
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
		$datas[self::$form] = esc_html__("Booked","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		$datas[] = array(
                    'id'          => "appointment_approval",
                    'title'       => "Appointment Approval",
                    'type'        => self::$form,
                    'label'       => "Booked"
                    );
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode = array();
		$shortcode["[name]"] = "Full name of the customer";  
		$shortcode["[email]"] = "Customer's email address";  
		$shortcode["[title]"] = "The title of the appointment's time slot";  
		$shortcode["[calendar]"] = "The appointment's calendar name";  
		$shortcode["[date]"] = "Rhe appointment date";  
		$shortcode["[time]"] = "The appointment time.";  
		$shortcode["[customfields]"] = "The appointment's custom field data.";  
		$shortcode["[id]"] = "The appointment's unique identification number.";  
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Booked;