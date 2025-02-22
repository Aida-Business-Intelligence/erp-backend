<?php 
class Rednumber_Marketing_CRM_Database {
	private static $continue = false;
	public static function save_options($type,$add_on,$form_id,$datas){
		global $wpdb;
		$table_name = $wpdb->prefix."crm_marketings";
		$datas = maybe_serialize($datas);
		$data_check = $wpdb->get_var(
			$wpdb->prepare(
			"SELECT COUNT(*) 
			FROM     $table_name
			WHERE    type = %s 
				 AND add_on = %s
				 AND form_id = %s
				",
				$type,
				$add_on,
				$form_id
				 ));
		if( $data_check > 0  ){
			//update
			$abc = $wpdb->update( 
			    $table_name, 
			    array( 
			        'datas' => $datas, 
			    ), 
			    array( 'form_id' => $form_id,
			    		'type'   => $type,
			    		'add_on' => $add_on
			    		 ),
			    array( 
			        '%s', 
			    ),
			    array( 
			    	'%s', 
			        '%s', 
			        '%s',
			    )
			);
		}else{
			//add
			$wpdb->insert( 
			   $table_name, 
			    array( 
			        'type' => $type, 
			        'form_id' => $form_id, 
			        'datas' => $datas, 
			        'add_on' => $add_on, 
			    ), 
			    array( 
			        '%s', 
			        '%s', 
			        '%s', 
			        '%s', 
			    ) 
			);
		}
	}
	public static function update_option($type,$add_on,$form_id,$datas){
		self::save_options($type,$add_on,$form_id,$datas);
	}
	public static function get_datas($type,$add_on,$form_id){
		global $wpdb;
		$table_name = $wpdb->prefix."crm_marketings";		
		$db = $wpdb->get_row( $wpdb->prepare(
			                   "SELECT *
								FROM $table_name
								WHERE type = %s
									AND add_on = %s
									AND form_id = %s",
								$type,
								$add_on,
								$form_id
		 ));
		if ( null !== $db ) {
		  	$data = $db->datas;
		  return maybe_unserialize($data);
		} else {
		  return false;
		}
	}
	// ELementor
	public static function get_submissions_id_elementor(){
		global $wpdb;
		$table_name_submissions = $wpdb->prefix."e_submissions";
		$elements =  $wpdb->get_row(
			$wpdb->prepare(
			 "SELECT id FROM $table_name_submissions ORDER BY id DESC" 
			)
		);
		$submission_id = $elements->id;
		return $submission_id;
	}
	public static function get_submissions_elementor($element_id,$type = ""){ 
		global $wpdb;
		$table_name = $wpdb->prefix."e_submissions";
		$table_name_log = $wpdb->prefix."e_submissions_actions_log";
		if( Rednumber_Marketing_CRM_Database::continue ) {
			$fivesdrafts = $wpdb->get_results(
					$wpdb->prepare(
				    "
				        SELECT submission_id
				        FROM $table_name_log
				        WHERE action_name  = '%s'
					",
						$type
					)
				);
			$posts_in = array();
			foreach( $fivesdrafts as $fivesdraft ){
				$posts_in[] = $fivesdraft->submission_id;
			}
			if( count($posts_in) > 0 ){
				$posts_in_sql = implode(",",$posts_in);
				$submissions = $wpdb->get_results(
					$wpdb->prepare(
				    "
				        SELECT form_name, id
				        FROM $table_name
				        WHERE $table_name.type = 'submission'
				        AND $table_name.status = 'new'
				        AND $table_name.element_id = '%s'
				        AND id NOT IN ( %s )
				    ",
				    	$element_id,
				    	$posts_in_sql
					)
				);
			}else{
				$submissions = $wpdb->get_results(
					$wpdb->prepare(
				    "
				        SELECT form_name, id
				        FROM $table_name
				        WHERE $table_name.type = 'submission'
				        AND $table_name.status = 'new'
				        AND $table_name.element_id = '%s'
				    ",
				    $element_id
					)
				);
			}
		}else{
			$submissions = $wpdb->get_results(
				$wpdb->prepare(
				    "
				        SELECT form_name, id
				        FROM $table_name
				        WHERE $table_name.type = 'submission'
				        AND $table_name.status = 'new'
				        AND $table_name.element_id = '%s'
				    ",
				    $element_id
				   )
				);
		}
		return $submissions;
	}
	public static function add_actions_elementor($submission_id,$type="",$status ="success"){ 
		global $wpdb;
		$table_name = $wpdb->prefix."e_submissions_actions_log";
		$current_datetime_gmt = current_time( 'mysql', true );
		$current_datetime = get_date_from_gmt( $current_datetime_gmt );
		$action = $wpdb->insert( 
			   $table_name, 
			    array( 
			        'submission_id' => $submission_id, 
			        'action_name' => $type, 
			        'action_label' => $type, 
			        'status' => $status,
			        'created_at_gmt' => $current_datetime_gmt,
					'updated_at_gmt' => $current_datetime_gmt,
					'created_at' => $current_datetime,
					'updated_at' => $current_datetime, 
			    ), 
			    array( 
			        '%d', 
			        '%s', 
			        '%s',
			        '%s',
			        '%s',
			        '%s',
			        '%s', 
			    ) 
			);
	}
	public static function get_submissions_elementor_Value($element_id){ 
		global $wpdb;
		$table_name = $wpdb->prefix."e_submissions_values";
		$fivesdrafts = $wpdb->get_results(
			$wpdb->prepare(
			    "
			        SELECT * 
			        FROM $table_name
			        WHERE submission_id = %s
			    ",
			    $element_id
			)
			);
		$datas = array();
		if ( $fivesdrafts ) { 
			foreach ( $fivesdrafts as $fivesdraft ) { 
				$datas[ $fivesdraft->key] = $fivesdraft->value;
			}
		}
		return $datas;
	}
	// Gravity Form
	public static function add_actions_gravity_forms($add_on ,$form_id,$entry_id){
		global $wpdb;
		$table_name = $wpdb->prefix."gf_entry_meta";
		$action = $wpdb->insert( 
			   $table_name, 
			    array( 
			        'form_id' => $form_id, 
			        'entry_id' => $entry_id, 
			        'meta_key' => "_add_on_crm", 
			        'meta_value' => $add_on,
			    ), 
			    array( 
			        '%d', 
			        '%d', 
			        '%s',
			        '%s'
			    ) 
			);
	}
	public static function get_submissions_gravity_forms($form_id,$add_on = ""){  
		global $wpdb;
		$datas_return = array();
		if( Rednumber_Marketing_CRM_Database::continue ) {
			$table_name = $wpdb->prefix."gf_entry";
			$search_criteria = array();
			$search_criteria['field_filters'][] = array( 'key' => '_add_on_crm', 'value' => $add_on);
			$results = GFAPI::get_entry_ids($form_id,$search_criteria);
			if( count($results) > 0 ){
				$posts_in_sql = implode(",",$results);
				$submissions = $wpdb->get_results(
				    "
				        SELECT id
				        FROM $table_name
				        WHERE form_id = $form_id
				        AND id NOT IN ( $posts_in_sql )
				    "
				);
				foreach( $submissions as $submission ){
					$datas_return[$submission->id] = GFAPI::get_entry($submission->id);
				}
			}else{
				$results = GFAPI::get_entries($form_id);
				$datas_return = $results;
			}
		}else{
			$results = GFAPI::get_entries($form_id);
			$datas_return = $results;
		}
		return $datas_return;
	}
}