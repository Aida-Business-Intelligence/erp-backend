<?php
class Rednumber_Marketing_CRM_Bitrix24 {
	private static $add_on ="bitrix24";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
	}
	function add_on($add_ons){
		if(!array_key_exists(self::$add_on, $add_ons)) { 
			$add_ons[self::$add_on] = array(
				"lable"      =>esc_html__("Bitrix24 CRM","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-bitrix24.png",
 				"des"        => esc_html__(" The plugin allows register the user to your mailing list after form is submitted.","crm-marketing"));
		}
		return $add_ons;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Bitrix24 Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function form_detail(){
		if(isset($_GET["type"])) {
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			if( $options["api"] == ""){
				?>
				<div><h1><?php esc_html_e("Please set API KEY: ","crm-marketing") ?> <a href="<?php echo esc_url(admin_url("admin.php?page=crm-marketing-config&tab=bitrix24")) ?>"><?php esc_html_e("API KEY","crm-marketing") ?></a></h1></div>
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
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
		         $api = new Rednumber_Marketing_CRM_Sendinblue_API();
		         $ids_list_contact = $api->get_all_lists_contact();
		         $properties = $api->get_all_attributes_contact();
				?>
			<input type="hidden" name="action" value="crm_marketing_<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="add_on" class ="crm_marketing_type_add_on" value="<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="type" class="crm_marketing_type" value="<?php echo esc_attr($type) ?>">
			<input type="hidden" name="form_id" class ="crm_marketing_form_id" value="<?php echo esc_attr($form_id) ?>">
			<div class="crm-marketing-content">
				<div class="crm-marketing-header-content">
					<h3><?php esc_html_e("Sendinblue CRM Connect","crm-marketing") ?></h3>
					<a data-type="<?php echo esc_attr(self::$add_on) ?>" class="crm-marketing-header-addnew button button-primary" href="#"><?php esc_html_e("Add new","crm-marketing") ?></a>
				</div>
				<div class="crm-marketing-container-content">
					<!-----------------Data repeater----------------->
					<div class="crm-marketing-container-content-data hidden">
							<table class="form-table">
								<tr valign="top">
							        <th scope="row"><?php esc_html_e("Import Leads","crm-marketing") ?></th>
							        <td>
							        	<select name="remove_key_method[]" >
							        		<option value="contacts"><?php esc_html_e("Contacts","crm-marketing") ?></option>
							        		<option value="companies"><?php esc_html_e("Companies","crm-marketing") ?></option>
							        		<option value="deals"><?php esc_html_e("Deals","crm-marketing") ?></option>
							        		<option value="tasks"><?php esc_html_e("Tasks","crm-marketing") ?></option>
							        		<option value="Notes"><?php esc_html_e("Notes","crm-marketing") ?></option>
							        	</select>
							        </td>
						        </tr>
						        <tr valign="top">
							        <th scope="row"><?php esc_html_e("The lists to add the contact","crm-marketing") ?></th>
							        <td>
							        	<select name="remove_key_list_contact_ids[]">
							        	<?php
							        		$i=0;
							        		foreach( $ids_list_contact as $list ){
							        			?>
							        			<option <?php selected($i,0) ?>  value="<?php echo esc_attr($list->id) ?>"> <?php echo esc_attr($list->name) ?> </option>
							        			<?php
							        			$i++;
							        		}
							        	 ?>
							        	 </select>
							        </td>
						        </tr>
						        <?php
						         Rednumber_Marketing_CRM_backend::map_fields(null,$form_id,"",$properties,$list_fields,"select","select");
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
						        <th scope="row"><?php esc_html_e("Import Leads","crm-marketing") ?></th>
						        <td>
						        	<select name="method[]" >
						        		<option <?php selected($method,'contacts') ?> value="contacts"><?php esc_html_e("Contacts","crm-marketing") ?></option>
						        		<option <?php selected($method,'companies') ?> value="companies"><?php esc_html_e("Companies","crm-marketing") ?></option>
						        		<option <?php selected($method,'deals') ?> value="deals"><?php esc_html_e("Deals","crm-marketing") ?></option>
						        		<option <?php selected($method,'tasks') ?> value="tasks"><?php esc_html_e("Tasks","crm-marketing") ?></option>
						        		<option <?php selected($method,'notes') ?> value="notes"><?php esc_html_e("Notes","crm-marketing") ?></option>
						        	</select>
						        </td>
					        </tr>
					        <tr valign="top">
							        <th scope="row"><?php esc_html_e("The lists to add the contact","crm-marketing") ?></th>
							        <td>
							        	<select name="list_contact_ids[]">
							        	<?php
							        		foreach( $ids_list_contact as $list ){
							        			?>
							        			<option <?php selected($list_contact_ids,$list->id) ?>  value="<?php echo esc_attr($list->id) ?>"> <?php echo esc_attr($list->name) ?> </option>
							        			<?php
							        		}
							        	 ?>
							        	 </select>
							        </td>
						        </tr>
					        <?php
					         Rednumber_Marketing_CRM_backend::map_fields($datas,$form_id,$i,$properties,$list_fields,"select","select");
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
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field("crm_marketing_settings_".self::$add_on); ?>
		    <input type="hidden" name="action" value="crm_marketing_settings_<?php echo esc_attr(self::$add_on) ?>">
		    <table class="form-table">
		        <tr valign="top">
		        <th scope="row"><?php esc_html_e("API KEY","crm-marketing") ?></th>
		        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" />
		        	<p><?php esc_html_e("Get your API key from your settings ","crm-marketing") ?> <a target="_blank" href="https://account.sendinblue.com/advanced/api"><?php esc_html_e("(SMTP & API)","crm-marketing") ?></a></p>
		        </td>
		        </tr>
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
			$list_contact_ids = map_deep($_POST["list_contact_ids"],'sanitize_text_field');
			$list_contact_ids=array_map('stripslashes_deep', $list_contact_ids);
			$datas = array();
			$new_datas = array();
			$map_fields = $_POST["map_fields"];
			$map_fields=array_map('stripslashes_deep', $map_fields);
			$map_fields = array_values($map_fields);
			$i= 0;
			foreach($methods as $method ){
				$datas[] = array("method"=>$method,"list_contact_ids"=>$list_contact_ids[$i],"map_fields"=>$map_fields[$i]);
				$i++;
			}
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
		}
	} 
}
new Rednumber_Marketing_CRM_Bitrix24;