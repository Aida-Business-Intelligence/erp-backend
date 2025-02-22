<?php
class Rednumber_Marketing_CRM_Webhooks {
	private static $add_on ="webhooks";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
	}
	function add_settings($lists){
		$lists["webhooks"] = array("label"=>esc_html__("Webhooks Configuration","crm-marketing"),"detail"=>array($this,"form_detail"));
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
					<h3><?php esc_html_e("Webhooks Connect","crm-marketing") ?></h3>
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
						        <tr valign="top">
							        <th scope="row"><?php esc_html_e("Method","crm-marketing") ?></th>
							        <td>
							        	<select name="remove_key_method[]" >
							        		<option value="post"><?php esc_html_e("POST","crm-marketing") ?></option>
							        		<option value="get"><?php esc_html_e("GET","crm-marketing") ?></option>
							        	</select>
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
					         <tr valign="top">
							        <th scope="row"><?php esc_html_e("Method","crm-marketing") ?></th>
							        <td>
							        	<select name="method[]" >
							        		<option value="post"><?php esc_html_e("POST","crm-marketing") ?></option>
							        		<option <?php selected($method,"get") ?> value="get"><?php esc_html_e("GET","crm-marketing") ?></option>
							        	</select>
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
	function register_detail_settings(){
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'crm_marketing_'.self::$add_on ) ) {
		    die('Security check'); 
		} else {
			$add_on = sanitize_text_field($_POST["add_on"]);
			$form_id = sanitize_text_field($_POST["form_id"]);
			$type = sanitize_text_field($_POST["type"]);
			$referer = sanitize_textarea_field($_POST["_wp_http_referer"]);
			$urls = map_deep( $_POST["url"], 'sanitize_text_field' );
			$methods = map_deep( $_POST["method"], 'sanitize_text_field' );
			$datas = array();
			$new_datas = array();
			$map_fields = array_values( $_POST["map_fields"] );
			$i= 0;
			foreach($urls as $url ){
				$datas[] = array("url"=>$url,"method"=>$methods[$i],"map_fields"=>$map_fields[$i]);
				$i++;
			}
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
		}
	}
	public static function send_data($url,$type,$data){
		if( $type == "get" ){
			$new_url = add_query_arg($data,$url);
		}else{
			$headers = array('Content-Type' => 'application/json');
			$body = wp_json_encode( $data );
			wp_remote_post($url,array('timeout'=>45,"headers"=>$headers,"body"=>$body));
		}
	}	   
}
new Rednumber_Marketing_CRM_Webhooks;