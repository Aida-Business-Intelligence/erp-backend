<?php
//version 1.2
use Elementor\Plugin;
class Rednumber_Marketing_CRM_Backend_Form_Widget{ 
	private static $form ="elementor"; 
	function __construct(){
		add_filter('crm_marketing_data_table', array($this,'add_datas'));
		add_filter('crm_marketing_map_fields_form_'.self::$form, array($this,'add_map_fields'),10,2);
		add_filter('crm_marketing_list_add_ons',array($this,"add_add_on"));
		add_action('rednumber_crm_marketing_sync_'.self::$form,array($this,"add_sync"));
	}
	function add_sync(){
		?>
		<p><?php esc_html_e("Please save changes before SYNC","crm-marketing"); ?></p>
		<a href="#" class="button button-primary crm_marketing_sync"><?php esc_html_e("SYNC Submissions","crm-marketing") ?></a>
		<?php
	}
	function add_add_on($datas){
		$datas[self::$form] = esc_html__("Elementor Form Widget","crm-marketing");
		return $datas;
	}
	function add_datas($datas){
		global $wpdb;
		$lists_form = $wpdb->get_results( "SELECT $wpdb->postmeta.meta_value, $wpdb->posts.ID, $wpdb->posts.post_title FROM $wpdb->postmeta INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id  WHERE $wpdb->postmeta.meta_key = '_elementor_data'  AND $wpdb->posts.post_status = 'publish'");
	 	$i =1 ;
	 	foreach ( $lists_form as $form ) {
	 		$post_id = $form->ID;
	 		$widgets = [
			   'form',
			];
	 		$document = Plugin::$instance->documents->get( $post_id );
			if ( ! $document ) {
				continue;
			}
			$data = $document->get_elements_data();
			if ( empty( $data ) ) {
				continue;
			}
			$data_form = $data;
        	foreach ( $data_form as $section ){
        		foreach ( $section["elements"] as $column ){ 
					if( isset($column["elements"]) && count($column["elements"]) > 0 ){
						$datas_elements = $column["elements"];
						foreach ( $datas_elements as $widget ){ 
							if( isset($widget["widgetType"]) && isset($widget["settings"]["form_name"]) ) {
								$form_id = $widget["id"];
								$form_title = $widget["settings"]["form_name"];
								$datas[] = array(
									'id'          => $form_id . "-" .$post_id,
									'title'       => $form_title ." - ".$form_id .' ('.$form->post_title.')',
									'type'        => self::$form,
									'label'       => "Elementor Form Widget"
									);
							}
						}
					}else{
						if( isset($column["widgetType"]) && isset($column["settings"]["form_name"]) ) {
								$form_id = $column["id"];
								$form_title = $column["settings"]["form_name"];
								$datas[] = array(
									'id'          => $form_id . "-" .$post_id,
									'title'       => $form_title ." - ".$form_id .' ('.$form->post_title.')',
									'type'        => self::$form,
									'label'       => "Elementor Form Widget"
									);
							}
					}
        		}
        	}
    	}
		return $datas;
	}
	public static function get_datas_form($data_form, $form_id ){
		foreach ( $data_form as $section ){
    		foreach ( $section["elements"] as $column ){ 
    			if( isset($column["elements"]) && count($column["elements"]) > 0 ){
    				foreach ( $column["elements"] as $widget ){ 
	    				if( isset($widget["widgetType"]) && ( $widget["widgetType"] == "form" ||  $widget["widgetType"] == "global") ) {
	    					if( isset($widget["settings"]["form_name"])) {
	    						if( $form_id == $widget["id"] ){
		    						return $widget["settings"]["form_fields"];
		    					}	
	    					}
	    				}
	    			}
    			}else{
					if( isset($column["widgetType"]) && ( $column["widgetType"] == "form" ||  $column["widgetType"] == "global") ) {
	    					if( isset($column["settings"]["form_name"])) {
	    						if( $form_id == $column["id"] ){
		    						return $column["settings"]["form_fields"];
		    					}	
	    					}
	    				}
    			}
    			
    		}
    	}
	}
	public static function add_map_fields($datas, $form_id){
		$lists = self::get_form_fields($form_id);
		return array_merge($datas,$lists);
	}
	public static function get_form_fields($form_id){
		$shortcode = array();
		$post_ids = explode("-",$form_id);
		$datas = get_post_meta( $post_ids[1],'_elementor_data',true);
		$datas = json_decode($datas,true);
		$tags = self::get_datas_form($datas,$post_ids[0]);
		$shortcode["[form_id]"] = "Form ID";
		$shortcode["[form_title]"] = "Form Title";
		foreach ($tags as $tag_inner):
		    if( isset($tag_inner["_id"])  ) {
		    	if(isset($tag_inner["field_label"]) ){
		    		$label = $tag_inner["field_label"];
		    	}else{
		    		$label = $tag_inner["custom_id"];
		    	}
		    	   $shortcode['[field id="'.$tag_inner["custom_id"].'"]'] = $label;
		    }
		endforeach;  
        return $shortcode;
	}
	public static function get_id_shortcode( $shortcode ) {
	    $attributes = [];
	    if (preg_match_all('/\w+\=\".*?\"/', $shortcode, $key_value_pairs)) {
	        foreach($key_value_pairs[0] as $kvp) {
	            $kvp = str_replace('"', '', $kvp);
	            $pair = explode('=', $kvp);
	            $attributes[$pair[0]] = $pair[1];
	        }
	    }
	    if( isset($attributes["id"])){
	    	return $attributes["id"];
	    }else{
	    	return str_replace(array("[","]"),"",$shortcode);
	    }
	}
	public static function get_id_shortcode_field_id( $shortcode ) {
	    $attributes = [];
	    if (preg_match_all('/\w+\=\".*?\"/', $shortcode, $key_value_pairs)) {
	        foreach($key_value_pairs[0] as $kvp) {
	            $kvp = str_replace('"', '', $kvp);
	            $pair = explode('=', $kvp);
	            $attributes[$pair[0]] = $pair[1];
	        }
	    }
	    if( isset($attributes["field_id"])){
	    	return $attributes["field_id"];
	    }else{
	    	return str_replace(array("{","}"),"",$shortcode);
	    }
	}
}
new Rednumber_Marketing_CRM_Backend_Form_Widget;