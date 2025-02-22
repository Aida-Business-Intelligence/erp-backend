<?php
//v1.1
class Rednumber_Marketing_CRM_Google_Sheets{
	private static $add_on ="google_sheets";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
		add_action("crm_marketing_update_option_".self::$add_on,array($this,"update_token"));
		add_action("crm_marketing_update_meta_".self::$add_on,array($this,"update_header"));
		add_action("admin_init",array($this,"set_token"));
		add_action("crm_marketing_remove_options_".self::$add_on,array($this,"remove_options"));
	}
	function remove_options(){
		delete_option("crm_marketing_".self::$add_on);
		delete_option("google_sheets_token");
	}
	function set_token(){
		if( isset($_GET["code"])) {
			$tab = sanitize_text_field($_GET["tab"]);
			$code = sanitize_text_field($_GET["code"]);
			if( $tab == self::$add_on && $code !="" ){
				$api = new Rednumber_Marketing_Google_Sheets_API();
				$token = $api->code_to_token($code);
			}
		}
	}
	function update_token($data){
		if( isset($data["api"]) && $data["api"] != "" ){
			$api = new Rednumber_Marketing_Google_Sheets_API();
			$token = $api->code_to_token($data["api"]);
		}
	}
	function update_header($datas){
		if( count($datas) > 0 ) {
			$api = new Rednumber_Marketing_Google_Sheets_API();
			foreach( $datas as $value ){ 
				foreach($value as $key => $vl){
					$$key = $vl;
				}
				$spreadsheet_id = $api->get_sheet_id($method);
				if( $spreadsheet_id != "" ){
					$api->update_header($spreadsheet_id,$value["map_fields"]["webhook"]);
				}
			}
		}
	}
	function add_on($add_ons){
		if(!array_key_exists(self::$add_on, $add_ons)) { 
			$add_ons[self::$add_on] = array(
				"lable"      =>esc_html__("Google Sheets","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pipedrive.png",
 				"des"        => esc_html__(" The plugin allows register the user to your mailing list after form is submitted.","crm-marketing"));
		}
		return $add_ons;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Google Sheets Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function form_detail(){
		if(isset($_GET["type"])) {
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
			$options = get_option("google_sheets_token");
			if( !isset($options["access_token"])){
				?>
				<div><h1><?php esc_html_e("Enter Google Access Code: ","crm-marketing") ?> <a href="<?php echo esc_url(admin_url("admin.php?page=crm-marketing-config&tab=google_sheets")) ?>"><?php esc_html_e("API KEY","crm-marketing") ?></a></h1></div>
				<?php
			}else{ 
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
		        $logics = array();
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form",$list_fields,$form_id);
		         ?>
			<input type="hidden" name="action" value="crm_marketing_<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="add_on" class ="crm_marketing_type_add_on" value="<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="type" class="crm_marketing_type" value="<?php echo esc_attr($type) ?>">
			<input type="hidden" name="form_id" class ="crm_marketing_form_id" value="<?php echo esc_attr($form_id) ?>">
			<textarea class="crm-marketing-list-fields hidden"><?php echo json_encode($list_fields) ?></textarea>
			<textarea class="crm-marketing-logic hidden"><?php echo json_encode($logics) ?></textarea>
			<div class="crm-marketing-content">
				<div class="crm-marketing-header-content">
					<h3><?php esc_html_e("Google Sheets Connect","crm-marketing") ?></h3>
					<a data-type="<?php echo esc_attr(self::$add_on) ?>" class="crm-marketing-header-addnew button button-primary" href="#"><?php esc_html_e("Add new","crm-marketing") ?></a>
				</div>
				<?php 
				 ?>
				<div class="crm-marketing-container-content">
					<!-----------------Data repeater----------------->
					<div class="crm-marketing-container-content-data hidden">
							<table class="form-table">
								<tr valign="top">
							        <th scope="row"><?php esc_html_e("Link Google Sheet","crm-marketing") ?></th>
							        <td>
							        	<input value="" type="text" name="remove_key_method[]" class="regular-text">
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
						        <th scope="row"><?php esc_html_e("Link Google Sheet","crm-marketing") ?></th>
						        <td>
						        	<input value="<?php echo esc_url($method) ?>" type="text" name="method[]" class="regular-text">
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
					submit_button(); 
					?>
					<div class="crm-marketing-footer-action">
					<?php
					do_action("rednumber_crm_marketing_sync_".$type,$form_id);
					?>
					</div>
				</div>
			</div>
		  </form>
		<?php
		}
		}
	}
	function form_settings(){
		$options = get_option("crm_marketing_".self::$add_on,array("method"=>"app","client_id"=>"","client_secret"=>""));
		$api = new Rednumber_Marketing_Google_Sheets_API();
		$url = $api->get_authUrl();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field("crm_marketing_settings_".self::$add_on); ?>
		    <input type="hidden" name="action" value="crm_marketing_settings_<?php echo esc_attr(self::$add_on) ?>">
		    <table class="form-table">
		    	<?php 
		    	$token = get_option("google_sheets_token");
		    	if( isset($token["access_token"])){
		    	?>
		    	<tr valign="top">
				        <th scope="row"><?php esc_html_e("Status","crm-marketing") ?></th>
				        <td> <strong><?php esc_html_e("Token Success") ?></strong></td>
			    </tr>
		    	<tr valign="top">
			        <th scope="row"><?php esc_html_e("Remove access","crm-marketing") ?></th>
			        <td><a data-add_on="<?php echo esc_attr(self::$add_on) ?>" class="button button-default crm-marketing-remove-options" href="#"><?php esc_html_e("Remove access","crm-marketing") ?></a></td>
		        </tr>
		    	<?php
		    	}else {
		    		$class = "";
		    		if( $options["method"] !="app" ){
		    			$class = "hidden";
		    		}
		    	?>
		    	<tr>
			        <th scope="row"><?php esc_html_e("Use Own Google App","crm-marketing") ?></th>
			        <td class="crm-marketing-method-api_select">
			        	
			        	<input <?php checked($options["method"],"app") ?> name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[method]" type="radio" value="app"> <?php esc_html_e("Use Own Google App","crm-marketing") ?>
			        	<a href="https://add-ons.org/create-a-google-project-and-configure-sheets-api/" target="_blank"><?php esc_html_e("Get Client ID & Client Secret","crm-marketing") ?></a>
			        <td>
		        </tr>
		        <tr class="crm-marketing-method-api crm-marketing-method-api-app <?php echo esc_attr($class) ?>">
			        <th scope="row"><?php esc_html_e("Client id","crm-marketing") ?></th>
			        <td>
			        	<input value="<?php echo esc_attr($options["client_id"]) ?>" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[client_id]" type="text" class="regular-text">
			        <td>
		        </tr>
		        <tr class="crm-marketing-method-api crm-marketing-method-api-app <?php echo esc_attr($class) ?>">
			        <th scope="row"><?php esc_html_e("Client Secret","crm-marketing") ?></th>
			        <td>
			        	<input value="<?php echo esc_attr($options["client_secret"]) ?>"  name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[client_secret]" type="text" class="regular-text">
			        <td>
		        </tr>
		        <tr class="crm-marketing-method-api crm-marketing-method-api-app <?php echo esc_attr($class) ?>">
			        <th scope="row"><?php esc_html_e("Redirect URL","crm-marketing") ?></th>
			        <td>
			        	<?php
			        	$state = admin_url("admin.php?page=crm-marketing-config&tab=".self::$add_on );
			        	?>
			        	<input value="<?php echo esc_url($state) ?>" name="redirect" type="text" class="regular-text" disabled="">
			        	<p><?php esc_html_e("Please save changes before get authorization","crm-marketing") ?></p>
			        <td>
		        </tr>
		    	<tr>
			        <th scope="row"><?php esc_html_e("Get authorization code","crm-marketing") ?></th>
			        <td><a class="button button-default" href="<?php echo esc_url($url) ?>"><?php esc_html_e("Get authorization","crm-marketing") ?></a><td>
		        </tr>
		    <?php } ?>
		    </table>
		     <?php submit_button(); ?>
		</form>
		<?php
	}
	function register_settings(){
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'crm_marketing_settings_'.self::$add_on ) ) {
		    die('Security check'); 
		} else {
			$hubspots = map_deep( $_POST["crm_marketing_".self::$add_on], 'sanitize_text_field' );
			update_option("crm_marketing_".self::$add_on,$hubspots);
			do_action("crm_marketing_update_option_".self::$add_on,$hubspots);
			$url = admin_url( 'admin.php' )."?page=crm-marketing-config&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
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
			$methods = map_deep( $_POST["method"], 'sanitize_text_field' );
			$methods=array_map('stripslashes_deep', $methods);
			$datas = array();
			$new_datas = array();
			$map_fields = $_POST["map_fields"];
			$map_fields=array_map('stripslashes_deep', $map_fields);
			$map_fields = array_values($map_fields);
			$i= 0;
			foreach($methods as $method ){
				$datas[] = array("method"=>$method,"map_fields"=>$map_fields[$i]);
			}
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			do_action("crm_marketing_update_meta_".self::$add_on,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
		}
	} 
}
new Rednumber_Marketing_CRM_Google_Sheets;