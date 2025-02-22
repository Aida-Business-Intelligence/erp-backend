<?php
class Rednumber_Marketing_CRM_Frontend_Webhooks_Form_Widget{ 
	function __construct(){
		add_action('elementor_pro/forms/new_record', array($this,'send_data'),10,2);
	}
	function send_data($record, $settings){
		 $raw_fields = $record->get( 'fields' );
		 $form_data = array();
	    foreach ( $raw_fields as $id => $field ) {
	        $form_data[ $id ] = $field['value'];
	    }
		$form_id = $record->get_form_settings( 'edit_post_id' );
		$datas = Rednumber_Marketing_CRM_Database::get_datas("form_widget","webhooks",$form_id);
		if( is_array($datas) && count($datas) > 0 ){
			foreach( $datas as $data ){
				foreach($data as $k => $vl ){
					$$k = $vl;
				}
				$data_send = array();
				if( isset($map_fields) && count($map_fields) > 0 ){
					$i=0;
					var_dump($form_data);
					foreach( $map_fields["webhook"] as $key ){
						var_dump($map_fields["form"][$i] );
						if( isset( $form_data[ $map_fields["form"][$i] ] )){
							$value =  $form_data[ $map_fields["form"][$i] ];
						}else{
							$value = $map_fields["form"][$i];
						}
						$data_send[$key] = $value;
						$i++;
					}
				}
				if( count($data_send) > 0 ){
					Rednumber_Marketing_CRM_Webhooks::send_data($url,$method,$data_send);
					Rednumber_Marketing_CRM_Logs::add("Sent: ".$url,"Send datas","form_widget","Webhooks",$form_id);
				}
			}
		}
	}
}
new Rednumber_Marketing_CRM_Frontend_Webhooks_Form_Widget;