<?php
use Bookly\Lib\Entities\Notification; 
class Rednumber_Marketing_CRM_Backend_Bookly{ 
	private static $form ="bookly";
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		register_activation_hook( "bookly-responsive-appointment-booking-tool/main.php", array($this,"activation_bookly") );
		do_action( 'upgrader_process_complete', array($this,"WP_Upgrader_booly"),10,2);
	}
	function WP_Upgrader_booly($upgrader_object, $options){
		if ($options['action'] == 'update' && $options['type'] == 'plugin' ) { 
			foreach($options['plugins'] as $each_plugin) { 
				if ($each_plugin == "bookly-responsive-appointment-booking-tool/main.php") {
					Rednumber_Marketing_CRM_Backend_Bookly::add_hook_bookly();
				}
			}
		}
	}
	function activation_bookly(){
		Rednumber_Marketing_CRM_Backend_Bookly::add_hook_bookly();
	}
	public static function add_hook_bookly(){
		$file = WP_PLUGIN_DIR."/bookly-responsive-appointment-booking-tool/lib/notifications/booking/BaseSender.php";
		  $text =  file_get_contents($file);
		  if ( !preg_match("/bookly_add_appointments/i", $text)) {
		    $add_action = '$attachments = new Attachments( $codes );'.PHP_EOL;
		    $add_action .= 'do_action("bookly_add_appointments",$codes); '.PHP_EOL;
		    $explode_text = explode("Notify staff and",$text);
		    $text = "";
		    $i = 0;
		    foreach( $explode_text as $value ){
		        if( $i == 0 ){
		             //first function
		            $text = str_replace('$attachments = new Attachments( $codes );',$add_action,$value);
		            $text .= "Notify staff and";
		        }else{
		            $text .= $value;
		        }
		        $i++;
		    }
		    file_put_contents($file, $text);
		  }
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Bookly","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		$datas[] = array(
                    'id'          => "appointment_approval",
                    'title'       => "New Appointment",
                    'type'        => self::$form,
                    'label'       => "Bookly"
                    );
		return $datas;
	}
	function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode = array();
		$codes = new \Bookly\Backend\Modules\Notifications\Lib\Codes( 'email' );
		$codes_array = (array) $codes;
		$i = 0;
		$codes_data = array();
		foreach( $codes_array as $key => $value ){
			if( $i == 1) {
				$codes_data = $value;
				break;
			}
			$i++;
		}
		foreach( $codes_data as $k => $values ){
			$shortcode["no_use_".rand(1000,9999)] = "---".$k."---";
			foreach( $values as $key => $value){
				$shortcode["{".$key."}"] = $value["description"]; 
			}
		}
        return $shortcode;
	}
}
new Rednumber_Marketing_CRM_Backend_Bookly;