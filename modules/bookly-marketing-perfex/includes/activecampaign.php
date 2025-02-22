<?php
class Rednumber_Marketing_CRM_Activecampaign{
	private static $add_on ="activecampaign";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
	}
	function add_on($add_ons){
		if(!array_key_exists(self::$add_on, $add_ons)) { 
			$add_ons[self::$add_on] = array(
				"lable"      =>esc_html__("Activecampaign CRM","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pipedrive.png",
 				"des"        => esc_html__(" The plugin allows register the user to your mailing list after form is submitted.","crm-marketing"));
		}
		return $add_ons;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Activecampaign Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
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
					$inner_tab = ".crm-marketing-tab-content-inner-contact";
				}
				$datas = Rednumber_Marketing_CRM_Database::get_datas($type,self::$add_on,$form_id);
				if( !$datas ){
					$datas = array();
				}
		        $properties = array();
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
		         $logics = array();
		         $api = new Rednumber_Marketing_Activecampaign_API(true);
		        $forms = array();
				$html_datas = array();
				$forms["contact"]["title"] = esc_html__("Contact","crm-marketing");
				$html_datas["contact[enable]"] = array("value"=>$datas[0]["contact"]["enable"],"label"=>"Enable contact","type"=>"checkbox");
				$attrs_contact = $api->get_data("attrs_contact");
				foreach( $attrs_contact as $contact ){ 
					$new_data = $contact;
					$new_data["value"]= $datas[0]["contact"][$contact["name"]];
					$html_datas["contact[".$contact["name"]."]"] = $new_data;
				}
				$forms["contact"]["value"] = $html_datas;
				//deal
				$html_datas = array();
				$forms["deal"]["title"] = esc_html__("Deal","crm-marketing");
				$html_datas["contact[enable]"] = array("value"=>$datas[0]["deal"]["enable"],"label"=>"Enable Deal","type"=>"checkbox");
				$attrs_deal = $api->get_data("attrs_deal");
				foreach( $attrs_deal as $deal ){ 
					$new_data = $deal;
					$new_data["value"]= $datas[0]["deal"][$deal["name"]];
					$html_datas["deal[".$deal["name"]."]"] = $new_data;
				}
				$forms["deal"]["value"] = $html_datas;
				//task
				$html_datas = array();
				$forms["task"]["title"] = esc_html__("Task","crm-marketing");
				$html_datas["contact[enable]"] = array("value"=>$datas[0]["task"]["enable"],"label"=>"Enable Deal","type"=>"checkbox");
				$attrs_task = $api->get_data("attrs_task");
				foreach( $attrs_task as $task ){ 
					$new_data = $task;
					$new_data["value"]= $datas[0]["task"][$task["name"]];
					$html_datas["task[".$task["name"]."]"] = $new_data;
				}
				$forms["task"]["value"] = $html_datas;
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
					<h3><?php esc_html_e("Activecampaign CRM Connect","crm-marketing") ?></h3>
				</div>
				<?php 
				 ?>
				<div class="crm-marketing-container-content">
					<ul class="crm-marketing-tab-main">
						<?php 
						foreach( $forms as $k => $v) {
							?>
							<li class="<?php if($inner_tab == ".crm-marketing-tab-content-inner-".$k){ echo esc_attr("active");} ?>" data-id=".crm-marketing-tab-content-inner-<?php echo esc_attr($k) ?>"><?php echo esc_html( $v["title"]  ) ?></li>
							<?php
						}
						?>
						<li data-id=".crm-marketing-tab-content-inner-list"><?php esc_html_e("list") ?></li>
						<li data-id=".crm-marketing-tab-content-inner-note"><?php esc_html_e("Note") ?></li>
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
						<div class="crm-marketing-tab-content-inner crm-marketing-tab-content-inner-list hidden">
							<table class="form-table">
								<tr valign="top" >
						        	<th scope="row"><?php esc_html_e("Enable","crm-marketing") ?></th>
							        <td>
							        	<input <?php if( isset($datas[0]["list"]["enable"])){ echo esc_attr("checked");} ?>  type="checkbox" name="list[enable]"> <?php esc_html_e("Enable list","crm-marketing") ?>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Name *","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("list[name]",$datas[0]["list"]["name"]); ?>
							        	<p><?php esc_html_e("Name of the list to create") ?></p>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("String id *","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("list[stringid]",$datas[0]["list"]["stringid"]); ?>
							        	<p><?php esc_html_e("URL-safe list name. Example: 'list-name-sample'","crm-marketing") ?></p>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Sender url *","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("list[sender_url]",$datas[0]["list"]["sender_url"]); ?>
							        	<p><?php esc_html_e("The website URL this list is for.","crm-marketing") ?></p>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Sender reminder *","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("list[sender_reminder]",$datas[0]["list"]["sender_reminder"]); ?>
							        	<p><?php esc_html_e("A reminder for your contacts as to why they are on this list and you are messaging them.","crm-marketing") ?></p>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Carbon Copy","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("list[carboncopy]",$datas[0]["list"]["carboncopy"]); ?>
							        	<p><?php esc_html_e("Comma-separated list of email addresses to send a copy of all mailings to upon send","crm-marketing") ?></p>
							        </td>
					        	</tr>
					        </table>
						</div>
						<div class="crm-marketing-tab-content-inner crm-marketing-tab-content-inner-note hidden">
							<table class="form-table">
								<tr valign="top" >
						        	<th scope="row"><?php esc_html_e("Enable","crm-marketing") ?></th>
							        <td>
							        	<input <?php if( isset($datas[0]["note"]["enable"])){ echo esc_attr("checked");} ?>  type="checkbox" name="note[enable]"> <?php esc_html_e("Enable note","crm-marketing") ?>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Note Content*","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("note[note]",$datas[0]["note"]["note"]); ?>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Rel type *","crm-marketing") ?></th>
							        <td>
							        	<?php 
							        	$list_types = array("Activity"=>"Activity","Deal"=>"Deal","DealTask"=>"DealTask","Subscriber"=>"Subscriber","CustomerAccount"=>"CustomerAccount");
							        	Rednumber_Marketing_CRM_backend::add_select_seletor("note[reltype]",$list_types,$datas[0]["note"]["reltype"],false); ?>
							        </td>
					        	</tr>
					        	<tr valign="top">
						        	<th scope="row"><?php esc_html_e("Rel id *","crm-marketing") ?></th>
							        <td>
							        	<?php Rednumber_Marketing_CRM_backend::add_number_seletor("note[relid]",$datas[0]["note"]["relid"]); ?>
							        </td>
					        	</tr>
					        </table>
						</div>
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
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>"","url"=>""));
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field("crm_marketing_settings_".self::$add_on); ?>
		    <input type="hidden" name="action" value="crm_marketing_settings_<?php echo esc_attr(self::$add_on) ?>">
		    <table class="form-table">
		    	<?php 
		    	if($options["api"] !=""){
		    		?>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Account","crm-marketing") ?></th>
				        <td> <strong><?php echo esc_attr( $options["url"]) ?></strong></td>
			        </tr>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Remove access","crm-marketing") ?></th>
				        <td><a data-add_on="<?php echo esc_attr(self::$add_on) ?>" class="button button-default crm-marketing-remove-options" href="#"><?php esc_html_e("Remove access","crm-marketing") ?></a></td>
			        </tr>
		    		<?php
		    	}else{
		    	?>
		    	<tr valign="top">
			        <th scope="row"><?php esc_html_e("API URL","crm-marketing") ?></th>
			        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[url]" value="<?php echo esc_attr( $options["url"]); ?>" />
			        	<p><?php esc_html_e("Get your API URL ","crm-marketing") ?> <a target="_blank" href="https://contactform7.add-ons.org/contact-form-7-activecampaign-crm-integration/"><?php esc_html_e("Get API URL","crm-marketing") ?></a></p>
			        </td>
			     </tr>
		        <tr valign="top">
			        <th scope="row"><?php esc_html_e("Personal API token","crm-marketing") ?></th>
			        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" />
			        	<p><?php esc_html_e("Get your API key from your settings ","crm-marketing") ?> <a target="_blank" href="https://contactform7.add-ons.org/contact-form-7-activecampaign-crm-integration/"><?php esc_html_e("Get API Key","crm-marketing") ?></a></p>
			        </td>
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
			$url = admin_url( 'admin.php' )."?page=crm-marketing-config&tab=".self::$add_on;
			wp_redirect( $url );
			exit;
		}
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
			$deal = map_deep( $_POST["deal"], 'sanitize_text_field' );
			$deal=array_map('stripslashes_deep', $deal);
			$task = map_deep( $_POST["task"], 'sanitize_text_field' );
			$task=array_map('stripslashes_deep', $task);
			$list = map_deep( $_POST["list"], 'sanitize_text_field' );
			$list=array_map('stripslashes_deep', $list);
			$note = map_deep( $_POST["note"], 'sanitize_text_field' );
			$note=array_map('stripslashes_deep', $note);
			$datas = array();
			$datas[] = array("contact"=>$contact,"deal"=>$deal,"task"=>$task,"list"=>$list,'note'=>$note);
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on."&inner_tab=".$inner_tab;
			wp_redirect( $url );
			exit;
		}
	}
	public static function cover_data_to_api($submits_new, $type ="",$form_data = "",$form_type = ""){
		$submits = array();
		switch( $type ){
			case "contact";
				return array("contact"=>$submits);
				break;
			case "deal";
				return array("deal"=>$submits);
				break;
			case "task";
				return array("dealTask"=>$submits);
				break;
			case "list";
				return array("lists"=>$submits);
				break;
			case "note";
				return array("note"=>$submits);
				break;
			default:
				return $submits;
				break;
		}
	} 
}
new Rednumber_Marketing_CRM_Activecampaign;