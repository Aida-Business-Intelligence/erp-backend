<?php
class Rednumber_Marketing_CRM_Perfex{
	private static $add_on ="perfex";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
		add_filter("crm_marketing_map_fields_form_".self::$add_on,array($this,"add_map_fields"));
	}
	function add_map_fields($list_fields){
		$list_fields["[current_clients]"] = "Current Organization (Customer) ID";
		$list_fields["[current_contacts]"] = "Current Person (Contact) ID";
		$list_fields["[current_staffs]"] = "Current staffs ID";
		$list_fields["[current_leads]"] = "Current Lead ID";
		$list_fields["[current_invoices]"] = "Current invoices ID";
		$list_fields["[current_tasks]"] = "Current tasks ID";	
		return $list_fields;
	}
	function add_on($add_ons){
		if(!array_key_exists(self::$add_on, $add_ons)) { 
			$add_ons[self::$add_on] = array(
				"lable"      =>esc_html__("Perfex CRM","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-perfex.png",
 				"des"        => esc_html__(" The Add-On allows you to quickly integrate your WordPress forms with PerfexCRM. Create or update contacts in your Perfex CRM, or add new sales leads after form submissions","crm-marketing"));
		}
		return $add_ons;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Perfex Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function form_detail(){
		if(isset($_GET["type"])) {	
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>"","url"=>""));
			if( $options == "" || $options == null){
				$options = array("api"=>"","url"=>"");
			}
			if( !is_array($options) || $options["api"] == ""){
				?>
				<div><h1><?php esc_html_e("Please set API KEY: ","crm-marketing") ?> <a href="<?php echo esc_url(admin_url("admin.php?page=crm-marketing-config&tab=perfex")) ?>"><?php esc_html_e("API KEY","crm-marketing") ?></a></h1></div>
				<?php
			}else{ 
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php
				wp_nonce_field("crm_marketing_".self::$add_on);
				$type = sanitize_text_field($_GET["type"]);
				$form_id = sanitize_text_field($_GET["id"]);
				if(isset($_GET["inner_tab"])){
					$inner_tab = sanitize_text_field($_GET["inner_tab"]);
				}else{
					$inner_tab = ".crm-marketing-tab-content-inner-clients";
				}
				$datas = Rednumber_Marketing_CRM_Database::get_datas($type,self::$add_on,$form_id);
				if( !$datas || !is_array($datas) ){
					$datas = array();
				}
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".self::$add_on,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form",$list_fields,$form_id);
		         $api = new Rednumber_Marketing_CRM_Perfex_API(true);
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
					<h3><?php esc_html_e("Perfex CRM Connect","crm-marketing") ?></h3>
				</div>
				<?php 
				 ?>
				<div class="crm-marketing-container-content">
					<?php 
					$forms = array();
					//Contact
					$tabs = $api->get_data("attrs");
					foreach( $tabs as $tab => $value_tab){
						$html_datas = array();
						$forms[ $tab ]["title"] = $value_tab["title"];
						if( isset($datas[0][$tab]["enable"])){
							$html_datas[$tab."[enable]"] = array("value"=>$datas[0][$tab]["enable"],"label"=>"Enable ".$tab,"type"=>"checkbox");
						}else{
							$html_datas[$tab."[enable]"] = array("value"=>"","label"=>"Enable ".$tab,"type"=>"checkbox");
						}
						
						if( $tab == "contacts" || $tab == "staffs" || $tab == "leads") {
							$html_datas[$tab."[enable_update]"] = array("value"=>$datas[0][$tab]["enable_update"],"label"=>"Create or update  ".$tab,"type"=>"checkbox");
						}
						foreach( $value_tab["datas"] as $v ){ 
							$new_data = $v;
							if( isset($datas[0][$tab][$v["name"]])){
								$new_data["value"]= $datas[0][$tab][$v["name"]];
							}else{
								$new_data["value"]= "";
							}
							$html_datas[$tab."[".$v["name"]."]"] = $new_data;
						}
						$forms[$tab]["value"] = $html_datas;
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
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>"","url"=>""));
		if( $options == "" ){
			$options = array("api"=>"","url"=>"");
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field("crm_marketing_settings_".self::$add_on); ?>
		    <input type="hidden" name="action" value="crm_marketing_settings_<?php echo esc_attr(self::$add_on) ?>">
		    <table class="form-table">
		    	<?php 
		    	if( isset($options["api"]) && $options["api"] !=""){
		    		$api = new Rednumber_Marketing_CRM_Perfex_API();
		    		$account =$api->get_account_info();
		    		$version =$api->get_version();
		    		if( $account["status"] && ( isset($version) && ( version_compare( $version["message"],"2.0" ) >= 0) ) ){
		    			$account = $account["message"];
		    		?>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Account","crm-marketing") ?></th>
				        <td> <strong><?php echo esc_attr( $options["url"]) ?></strong> - <strong><?php echo esc_attr( $account["name"]) ?></strong></td>
			        </tr>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Remove access","crm-marketing") ?></th>
				        <td><a data-add_on="<?php echo esc_attr(self::$add_on) ?>" class="button button-default crm-marketing-remove-options" href="#"><?php esc_html_e("Remove access","crm-marketing") ?></a></td>
			        </tr>
		    		<?php 
		    		}else{
		    			?>
		    			<tr valign="top">
				        <th scope="row"><?php esc_html_e("Account","crm-marketing") ?></th>
				        <?php 
				        if(  isset($version) && ( version_compare( $version["message"],"2.0" ) < 0) )  {
				        	?>
				        <td> <strong><?php esc_html_e("API need module API CRM of perfex >= 2.0, please update module perfex","crm-marketing") ?></strong></td>
				    <?php 
						}else{
				        ?>
				        <td> <strong><?php esc_html_e("Token have problems, please remove token an add new","crm-marketing") ?></strong></td>
				    <?php } ?>
			        </tr>
		    		<tr valign="top">
				        <th scope="row"><?php esc_html_e("Remove access","crm-marketing") ?></th>
				        <td><a data-add_on="<?php echo esc_attr(self::$add_on) ?>" class="button button-default crm-marketing-remove-options" href="#"><?php esc_html_e("Remove access","crm-marketing") ?></a></td>
			        </tr>
		    			<?php
		    		}
		    	}else{
		    	?>
		    	<tr valign="top">
			        <th scope="row"><?php esc_html_e("API URL","crm-marketing") ?></th>
			        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[url]" value="<?php echo esc_attr( $options["url"]); ?>" />
			        </td>
			     </tr>
		        <tr valign="top">
			        <th scope="row"><?php esc_html_e("API token","crm-marketing") ?></th>
			        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" />
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
			$datas = array();
			$datas_value = array();
			$list_tabs = Rednumber_Marketing_CRM_Perfex_API::$list_tabs;
			foreach( $list_tabs as $tab ){
				$data = map_deep( $_POST[ $tab ], 'sanitize_text_field' );
				$data=array_map('stripslashes_deep', $data);
				$datas_value[$tab]	= $data;
			}
			$datas[] = $datas_value;
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on."&inner_tab=".$inner_tab;
			wp_redirect( $url );
			exit;
		}
	}
	public static function cover_data_to_api($submits_new, $type ="",$form_data = "",$form_type = ""){
		$submits = array();
		foreach( $submits_new as $k => $v ){
			switch($k){
				case "subtotal":
				case "total":
				case "hourly_rate":
					if( !preg_match('/\\./', $v ) ){
						$v =  $v.".00";
					}	
					break;
				case "departments":
					if( !is_array($v) ){
						$v = array( $v );
					}
					break;
				default:
					break;
			}
			$submits[ $k ] = $v;
		}
		if( $type =="invoices" ){
			$item = array();
			$item[0]["description"] = $submits["description"];
			$item[0]["long_description"] = $submits["long_description"];
			$item[0]["qty"] = $submits["qty"];
			$item[0]["rate"] = $submits["rate"];
			$item[0]["order"] = $submits["order"];
			$item[0]["taxname"] = array($submits["taxname"]);
			unset( $submits["description"] );
			unset( $submits["long_description"] );
			unset( $submits["qty"] );
			unset( $submits["rate"] );
			unset( $submits["order"] );
			unset( $submits["taxname"] );
			$submits["newitems"] = $item;
		}
		return $submits;
	} 
}
new Rednumber_Marketing_CRM_Perfex;