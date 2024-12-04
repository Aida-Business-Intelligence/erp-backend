<?php
class Rednumber_Marketing_CRM_Zapier {
	private static $add_on ="zapier";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_init', array($this,"register_settings") );
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
	}
	function add_settings($lists){
		$lists["zapier"] = array("label"=>esc_html__("Zapier Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function form_detail(){
		if(isset($_GET["type"])) {
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php
				wp_nonce_field("crm_marketing_".self::$add_on);
				$type = sanitize_text_field($_GET["type"]);
				$form_id = sanitize_text_field($_GET["id"]);
				$datas = Rednumber_Marketing_CRM_Database::get_datas($type,self::$add_on,$form_id);
				if( !$datas ){
					$datas = array();
				}
		        $properties = array();
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
				?>
			<input type="hidden" name="action" value="crm_marketing_<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="add_on" value="<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="type" value="<?php echo esc_attr($type) ?>">
			<input type="hidden" name="form_id" value="<?php echo esc_attr($form_id) ?>">
			<div class="crm-marketing-content">
				<div class="crm-marketing-header-content">
					<h3><?php esc_html_e("Zapier Connect","crm-marketing") ?></h3>
					<a data-type="<?php echo esc_attr(self::$add_on) ?>" class="crm-marketing-header-addnew button button-primary" href="#"><?php esc_html_e("Add new","crm-marketing") ?></a>
				</div>
				<div class="crm-marketing-container-content">
					<!-----------------Data repeater----------------->
					<div class="crm-marketing-container-content-data hidden">
							<table class="form-table">
								<tr valign="top">
							        <th scope="row"><?php esc_html_e("Webhook URL","crm-marketing") ?></th>
							        <td>
							        	<input type="text" class="regular-text" name="remove_key_url[]">
							        </td>
						        </tr>
						        <?php
						         Rednumber_Marketing_CRM_backend::map_fields(null,$form_id,"",$properties,$list_fields,"text","select");
						        do_action("crm_marketing_form_webhooks_".$type,$datas,$form_id,-1); ?>
						    </table>
						    <div class="crm-marketing-content-remove-row"><span class="dashicons dashicons-no"></span></div>
					</div>
					<!-----------------Data repeater----------------->
					<?php 
					if( count($datas) > 0 ) {
						$i=0;
						foreach( $datas as $value ){ 
								foreach($value as $key => $vl){
									$$key = $vl;
								}
								?>
					<div class="crm-marketing-row-content" data-id="<?php echo esc_attr($i) ?>">
						<table class="form-table">
							<tr valign="top">
						       <th scope="row"><?php esc_html_e("Webhook URL","crm-marketing") ?></th>
						        <td>
						        	<input type="text" class="regular-text" name="url[]" value="<?php echo esc_attr($url) ?>">
						        </td>
					        </tr>
					        <?php
					         Rednumber_Marketing_CRM_backend::map_fields($datas,$form_id,$i,$properties,$list_fields,"text","select");
					        do_action("crm_marketing_form_webhooks_".$type,$datas,$form_id,$i); ?>
					    </table>
					    <div class="crm-marketing-content-remove-row"><span class="dashicons dashicons-no"></span></div>
					</div>
					<?php 
						$i++;
						} 
					} ?>
				</div>
			     <div class="crm-marketing-footer-content">
					<?php 
					Rednumber_Marketing_CRM_backend::get_wp_http_referer();
					submit_button(); ?>
				</div>
			</div>
		  </form>
		<?php
		}
	}
	function form_settings(){
		$options = get_option("crm_marketing_zapier");
		?>
		<form method="post" action="options.php">
		    <?php settings_fields( 'crm_marketing_zapier' ); ?>
		    <?php do_settings_sections( 'crm_marketing_zapier' ); ?>
		    <table class="form-table">
		        <tr valign="top">
		        	<th scope="row"><?php esc_html_e("Integration Method","crm-marketing") ?></th>
			        <td>
			        	<input type="radio" name="crm_marketing_zapier[method]" value="api" /> <?php esc_html_e("API","crm-marketing") ?> 
			        	<input type="radio" name="crm_marketing_zapier[method]" value="wtl" /> <?php esc_html_e("Web to Lead or Web to Case","crm-marketing") ?> 
			        </td>
		        </tr>
		        <tr valign="top">
		        	<th scope="row"><?php esc_html_e("Environment","crm-marketing") ?></th>
			        <td>
			        	<input type="radio" name="crm_marketing_zapier[env]" value="api" /> <?php esc_html_e("Production","crm-marketing") ?> 
			        	<input type="radio" name="crm_marketing_zapier[env]" value="wtl" /> <?php esc_html_e("Sandbox","crm-marketing") ?> 
			        </td>
		        </tr>
		        <tr valign="top">
		        <th scope="row">Some Other Option</th>
		        <td><input class="regular-text" type="text" name="crm_marketing_zapier[a]" value="<?php echo esc_attr( get_option('some_other_option') ); ?>" /></td>
		        </tr>
		    </table>
		    <?php submit_button(); ?>
		</form>
		<?php
	}
	function register_settings(){
		register_setting(
            'crm_marketing_zapier', // Option group
            'crm_marketing_zapier'
        );
	}
	function register_detail_settings(){
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'crm_marketing_'.self::$add_on ) ) {
		    die('Security check'); 
		} else {
			$add_on = sanitize_text_field($_POST["add_on"]);
			$form_id = sanitize_text_field($_POST["form_id"]);
			$type = sanitize_text_field($_POST["type"]);
			$referer = sanitize_textarea_field($_POST["_wp_http_referer"]);
			$urls = map_deep( $_POST["url"], 'sanitize_text_field' );
			$datas = array();
			$new_datas = array();
			$map_fields = array_values( $_POST["map_fields"] );
			$i= 0;
			foreach($urls as $url ){
				$datas[] = array("url"=>$url,"map_fields"=>$map_fields[$i]);
				$i++;
			}
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
		}
	}
	public static function send_data($url,$data){
		$headers = array('Content-Type' => 'application/json');
		$body = wp_json_encode( $data );
		$a = wp_remote_post($url,array('timeout'=>45,"headers"=>$headers,"body"=>$body));
	}	
}
new Rednumber_Marketing_CRM_Zapier;