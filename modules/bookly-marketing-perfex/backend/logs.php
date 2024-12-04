<?php
class Rednumber_Marketing_CRM_Logs{
	public static function add($text,$action ="",$type="",$add_on="",$form_id=""){
		global $wpdb;
		$table_name = $wpdb->prefix."crm_logs";
		$wpdb->insert( 
			   $table_name, 
			    array( 
			        'type' => $type, 
			        'form_id' => $form_id, 
			        'datas' => $text, 
			        'add_on' => $add_on, 
			        'action' => $action
			    ), 
			    array( 
			        '%s', 
			        '%s', 
			        '%s', 
			        '%s', 
			        '%s', 
			    ) 
			);
	}
	public static function get($type="",$addon="",$form_id=""){
		global $wpdb;
		$table_name = $wpdb->prefix."crm_logs";
		$where = array();
		if( $type != "" ){
			$where["type"] = $type;
		}
		if( $addon != "" ){
			$where["addon"] = $addon;
		}
		if( $form_id != "" ){
			$where["form_id"] = $form_id;
		}
		$sql_where ="";
		if( count($where) > 0 ){
			$sql_where = "WHERE";
			$i=0;
			foreach( $where as $k => $v ){
				if( $i == 0 ){
					$sql_where .=" ".$k." = '".$v."'";
				}else{
					$sql_where .=" AND ".$k." = '".$v."'";
				}
				$i++;
			}
		}
		$fivesdrafts = $wpdb->get_results(
			    "
			        SELECT * 
			        FROM $table_name
			        {$sql_where}
			        ORDER BY id DESC
			        LIMIT 50
			    "
			);
		return $fivesdrafts;
	}
	public static function remove_all(){
		global $wpdb;
		$table_name = $wpdb->prefix."crm_logs";
		$wpdb->query( 
		    $wpdb->prepare( "DELETE FROM $table_name")
		);
	}
}