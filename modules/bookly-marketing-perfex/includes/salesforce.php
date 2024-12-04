<?php
class Rednumber_Marketing_CRM_Salesforce{
	private static $add_on ="salesforce";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
		add_action("crm_marketing_remove_options_".self::$add_on,array($this,"remove_options"));
	}
	function add_on($add_ons){
		if(!array_key_exists(self::$add_on, $add_ons)) { 
			$add_ons[self::$add_on] = array(
				"lable"      =>esc_html__("Pipedrive CRM","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pipedrive.png",
 				"des"        => esc_html__(" The plugin allows register the user to your mailing list after form is submitted.","crm-marketing"));
		}
		return $add_ons;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Pipedrive Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function form_detail(){
		if(isset($_GET["type"])) {	
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			if( $options["api"] == ""){
				?>
				<div><h1><?php esc_html_e("Please set API KEY: ","crm-marketing") ?> <a href="<?php echo esc_url(admin_url("admin.php?page=crm-marketing-config&tab=pipedrive")) ?>"><?php esc_html_e("API KEY","crm-marketing") ?></a></h1></div>
				<?php
			}else{ 
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php
				wp_nonce_field("crm_marketing_".self::$add_on);
				$type = sanitize_text_field($_GET["type"]);
				$form_id = sanitize_text_field($_GET["id"]);
				$inner_tab = sanitize_text_field($_GET["inner_tab"]);
				if($inner_tab == ""){
					$inner_tab = ".crm-marketing-tab-content-inner-person";
				}
				$datas = Rednumber_Marketing_CRM_Database::get_datas($type,self::$add_on,$form_id);
				if( !$datas ){
					$datas = array();
				}
		        $properties = array();
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form",$list_fields,$form_id);
		         $api = new Rednumber_Marketing_CRM_Salesforce_API(true);
		         $logics = array();
				?>
			<input type="hidden" name="action" value="crm_marketing_<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="add_on" class ="crm_marketing_type_add_on" value="<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="type" class="crm_marketing_type" value="<?php echo esc_attr($type) ?>">
			<input type="hidden" name="form_id" class ="crm_marketing_form_id" value="<?php echo esc_attr($form_id) ?>">
			<input type="hidden" name="inner_tab" class="crm_marketing_inner_tab" value="<?php echo esc_attr($inner_tab) ?>">
			<textarea class="crm-marketing-list-fields hidden"><?php echo json_encode($list_fields) ?></textarea>
			<textarea class="crm-marketing-logic hidden"><?php echo json_encode($logics) ?></textarea>
			<div class="crm-marketing-content">
				<div class="crm-marketing-header-content">
					<h3><?php esc_html_e("Pipedrive CRM Connect","crm-marketing") ?></h3>
				</div>
				<?php 
				 ?>
				<div class="crm-marketing-container-content">
					<?php 
					$forms = array();
					$tabs = $api->get_data("attrs_tabs");
					//Contact/Persons
					foreach ( $tabs as $tab ) {
						$html_datas = array();
						$forms[ $tab["name"] ]["title"] = esc_html($tab["label"]);
						$html_datas["contact[enable]"] = array("value"=>$datas[0]["contact"]["enable"],"label"=>"Enable ".$tab["label"],"type"=>"checkbox");
						$forms[ $tab["name"] ]["value"] = $html_datas;
					}
					?>
					<ul class="crm-marketing-tab-main">
						<?php 
						foreach( $forms as $k => $v) {
							?>
							<li class="<?php if($inner_tab == ".crm-marketing-tab-content-inner-".$k){ echo esc_attr("active");} ?>" data-id=".crm-marketing-tab-content-inner-<?php echo esc_attr($k) ?>"><?php echo esc_html( $v["title"]  ) ?></li>
							<?php
						}
						?>
					</ul>
					<div class="crm-marketing-tab-content">
					    <?php 
						foreach( $forms as $k => $v) {
							if($inner_tab == ".crm-marketing-tab-content-inner-".$k){ 
								$class_hidden ="acvive";
							}else{
								$class_hidden ="hidden";
							}
							?>
							<div class="crm-marketing-tab-content-inner crm-marketing-tab-content-inner-<?php echo esc_attr($k) ?> <?php echo esc_attr($class_hidden) ?>">
								<?php Rednumber_Marketing_CRM_backend::render_html_form($v["value"]); ?>
							</div>
							<?php
						}
						?>
					</div>
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
		$options = get_option("crm_marketing_".self::$add_on,array("method"=>"api"));
		 $api = new Rednumber_Marketing_CRM_Salesforce_API();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field("crm_marketing_settings_".self::$add_on); ?>
		    <input type="hidden" name="action" value="crm_marketing_settings_<?php echo esc_attr(self::$add_on) ?>">
		    <table class="form-table">
		    	<?php 
		    		$token = get_option("_".self::$add_on."_crm_token");
		    		if($token["instance_url"]){
		    			?>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Account","crm-marketing") ?></th>
				        <td><strong><?php echo esc_url($token["instance_url"]) ?></strong></td>
			        </tr>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Remove access","crm-marketing") ?></th>
				        <td><a data-add_on="<?php echo esc_attr(self::$add_on) ?>" class="button button-default crm-marketing-remove-options" href="#"><?php esc_html_e("Remove access","crm-marketing") ?></a></td>
			        </tr>
		    			<?php
		    		}else{
		    	?>
		        <tr valign="top">
			        <th scope="row"><?php esc_html_e("Authorization_code","crm-marketing") ?></th>
			        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" /></td>
		        </tr>
		        <tr>
			        <th scope="row"><?php esc_html_e("Get authorization code","crm-marketing") ?></th>
			        <td><a target="_blank" class="button button-default" href="<?php echo esc_url($api->get_authorization()) ?>"><?php esc_html_e("Get authorization code","crm-marketing") ?></a><td>
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
			if( isset($hubspots["api"]) && $hubspots["api"] != "" ){
				$api = new Rednumber_Marketing_CRM_Salesforce_API();
				$token = $api->get_token($hubspots["api"]);
			}
			update_option("crm_marketing_".self::$add_on,$hubspots);
			$url = admin_url( 'admin.php' )."?page=crm-marketing-config&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
		}
	}
	function remove_options(){
		delete_option("crm_marketing_".self::$add_on);
		delete_option("_".self::$add_on."_crm_token");
	}
	function register_detail_settings(){
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'crm_marketing_'.self::$add_on ) ) {
		    die('Security check'); 
		} else {
			$inner_tab = sanitize_text_field($_POST["inner_tab"]);
			$add_on = sanitize_text_field($_POST["add_on"]);
			$form_id = sanitize_text_field($_POST["form_id"]);
			$type = sanitize_text_field($_POST["type"]);
			$referer = sanitize_textarea_field($_POST["_wp_http_referer"]);
			$contact = map_deep( $_POST["contact"], 'sanitize_text_field' );
			$contact=array_map('stripslashes_deep', $contact);
			$datas = array();
			$datas[] = array("contact"=>$contact);
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on."&inner_tab=".$inner_tab;
			wp_redirect( $url );
			exit;
		}
	} 
}
new Rednumber_Marketing_CRM_Salesforce;