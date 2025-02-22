<?php
class Rednumber_Marketing_CRM_Sendinblue {
	private static $add_on ="sendinblue";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
	}
	function add_on($add_ons){
		if(!array_key_exists(self::$add_on, $add_ons)) { 
			$add_ons[self::$add_on] = array(
				"lable"      =>esc_html__("Sendinblue CRM","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-sendinblue.png",
 				"des"        => esc_html__(" The plugin allows register the user to your mailing list after form is submitted.","crm-marketing"));
		}
		return $add_ons;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Sendinblue Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function form_detail(){
		if(isset($_GET["type"])) {
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			if( $options["api"] == ""){
				?>
				<div><h1><?php esc_html_e("Please set API KEY: ","crm-marketing") ?> <a href="<?php echo esc_url(admin_url("admin.php?page=crm-marketing-config&tab=sendinblue")) ?>"><?php esc_html_e("API KEY","crm-marketing") ?></a></h1></div>
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
				$logics = array();
		        $properties = array();
		         $list_fields = array();
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".$type,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form",$list_fields);
		         $api = new Rednumber_Marketing_CRM_Sendinblue_API(true);
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
					<h3><?php esc_html_e("Sendinblue CRM Connect","crm-marketing") ?></h3>
				</div>
				<div class="crm-marketing-container-content">
					<?php
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
					$forms["deal"]["title"] = esc_html__("Deal","crm-marketing");
					$html_datas = array();
					$html_datas["deal[enable]"] = array("value"=>$datas[0]["deal"]["enable"],"label"=>"Enable deal","type"=>"checkbox");
					$attrs_deal = $api->get_data("attrs_deal");
					foreach( $attrs_deal as $deal ){ 
						$new_data = $deal;
						$new_data["value"]= $datas[0]["deal"][$deal["name"]];
						$html_datas["deal[".$deal["name"]."]"] = $new_data;
					}
					$forms["deal"]["value"] = $html_datas;
					//company
					$forms["company"]["title"] = esc_html__("Company","crm-marketing");
					$html_datas = array();
					$html_datas["company[enable]"] = array("value"=>$datas[0]["company"]["enable"],"label"=>"Enable company","type"=>"checkbox");
					$attrs_company = $api->get_data("attrs_company");
					foreach( $attrs_company as $company ){ 
						$new_data = $company;
						$new_data["value"]= $datas[0]["company"][$company["name"]];
						$html_datas["company[".$company["name"]."]"] = $new_data;
					}
					$forms["company"]["value"] = $html_datas;
					//task
					$forms["task"]["title"] = esc_html__("Task","crm-marketing");
					$html_datas = array();
					$html_datas["task[enable]"] = array("value"=>$datas[0]["task"]["enable"],"label"=>"Enable task","type"=>"checkbox");
					$attrs_task = $api->get_data("attrs_task");
					foreach( $attrs_task as $task ){ 
						$new_data = $task;
						$new_data["value"]= $datas[0]["task"][$task["name"]];
						$html_datas["task[".$task["name"]."]"] = $new_data;
					}
					$forms["task"]["value"] = $html_datas;
					//Note
					$forms["note"]["title"] = esc_html__("Note","crm-marketing");
					$html_datas = array();
					$html_datas["note[enable]"] = array("value"=>$datas[0]["note"]["enable"],"label"=>"Enable note","type"=>"checkbox");
					$attrs_note = $api->get_data("attrs_note");
					foreach( $attrs_note as $note ){ 
						$new_data = $note;
						$new_data["value"]= $datas[0]["note"][$note["name"]];
						$html_datas["note[".$note["name"]."]"] = $new_data;
					}
					$forms["note"]["value"] = $html_datas;
					//File
					$forms["file"]["title"] = esc_html__("Upload File","crm-marketing");
					$html_datas = array();
					$html_datas["file[enable]"] = array("value"=>$datas[0]["file"]["enable"],"label"=>"Enable upload file","type"=>"checkbox");
					$attrs_file = $api->get_data("attrs_file");
					foreach( $attrs_file as $file ){ 
						$new_data = $file;
						$new_data["value"]= $datas[0]["file"][$file["name"]];
						$html_datas["file[".$file["name"]."]"] = $new_data;
					}
					$forms["file"]["value"] = $html_datas;
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
				</div>
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
			$inner_tab = sanitize_text_field($_POST["inner_tab"]);
			$add_on = sanitize_text_field($_POST["add_on"]);
			$form_id = sanitize_text_field($_POST["form_id"]);
			$type = sanitize_text_field($_POST["type"]);
			$referer = sanitize_textarea_field($_POST["_wp_http_referer"]);
			$contact = map_deep( $_POST["contact"], 'sanitize_text_field' );
			$contact=array_map('stripslashes_deep', $contact);
			$deal = map_deep( $_POST["deal"], 'sanitize_text_field' );
			$deal=array_map('stripslashes_deep', $deal);
			$company = map_deep( $_POST["company"], 'sanitize_text_field' );
			$company=array_map('stripslashes_deep', $company);
			$task = map_deep( $_POST["task"], 'sanitize_text_field' );
			$task=array_map('stripslashes_deep', $task);
			$note = map_deep( $_POST["note"], 'sanitize_text_field' );
			$note= array_map('stripslashes_deep', $note);
			$file = map_deep( $_POST["file"], 'sanitize_text_field' );
			$file= array_map('stripslashes_deep', $file);
			$datas[] = array("contact"=>$contact,"deal"=>$deal,"company"=>$company,"task"=>$task,"note"=>$note,"file"=>$file);
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on."&inner_tab=".$inner_tab;
			wp_redirect( $url );
			exit;
		}
	}
	public static function cover_data_to_api($submits, $type ="",$form_data = "",$form_type = "", $form_raw=""){ 
		switch( $type ){
			case "contact":
				$list_contact_ids = array_values($submits["listIds"]);
				$list_contact_ids = implode(",",$list_contact_ids);
				$list_contact_ids = array_map('intval', explode(',', $list_contact_ids));
				$email = $submits["email"];
				unset($submits["email"]);
				unset($submits["listIds"]);
				return array("email"=>$email,"attributes"=>$submits,"listIds"=>$list_contact_ids,"updateEnabled"=>false);
			case "deal":
				$name = $submits["deal_name"];
				$amount = (int) $submits["amount"];
				$submits["amount"] = $amount;
				unset($submits["deal_name"]);
				return array("name"=>$name,"attributes"=>$submits);
			case "company":
				$name = $submits["name"];
				unset($submits["name"]);
				return array("name"=>$name,"attributes"=>$submits);
				break;
			case "task":
				if( isset($submits["done"]) ){
					$submits["done"] = true;
				}else {
					$submits["done"] = false;
				}
				if( isset($submits["contactsIds"]) ){
					$submits["contactsIds"] = array( (int) $submits["contactsIds"] );
				}
				if( isset($submits["dealsIds"]) ){
					$submits["dealsIds"] =array( $submits["dealsIds"]);
				}
				if( isset($submits["companiesIds"]) ){
					$submits["companiesIds"] = array( $submits["companiesIds"]);
				}
				if( isset($submits["duration"]) ){
					$submits["duration"] = (int) $submits["duration"];
				}
				return $submits;
			case "note":
				if( isset($submits["contactIds"]) ){
					$submits["contactIds"] = array( (int) $submits["contactIds"] );
				}
				if( isset($submits["dealIds"]) ){
					$submits["dealIds"] =array( $submits["dealIds"]);
				}
				if( isset($submits["companyIds"]) ){
					$submits["companyIds"] = array( $submits["companyIds"]);
				}
				return $submits;
				break;
			case "file":
				if( $form_type == "contact_form_7") {
					if( isset( $submits["file"] )){ 
						$name_file = str_replace(array("[","]"),"",$form_raw["file"]);
						$uploaded_files = $form_data->uploaded_files();
						foreach( $uploaded_files as $name_upload => $files ){
							if( $name_file == $name_upload ){
								$submits["file"] = $files[0];
								break;
							}
						}
					}
				}
				if( isset($submits["contactId"]) ){
					$submits["contactId"] = (int) $submits["contactId"];
				}
				break;
			case "file_cf7_pdf":
				if( isset( $submits["file"] )){  
					$name_file = str_replace(array("[","]"),"",$form_raw["file"]);
					if( $upload_pdf == "upload_pdf"){
						$name = get_post_meta($form_id,"_pdfcreator_template_name",true);
						 if($name == ""){
						 	$name = "contact-form";
						 }else{
						 	$value = str_replace(array("[","]"),"",$name);
							$value =$form_data->get_posted_data($value);
							if($value == null){
								$value = $name;
							}
						 	$name = $value;
						 }
						 $name = sanitize_title($name);
						 $name .= "-".$form_id; 
						 $upload_dir = wp_upload_dir();
						 $path_main = $upload_dir['basedir'] . '/pdfs/';  
						$submits["file"] = $path_main.$name.".pdf";
					}else{
						unset($submits["file"]);
					}
				}
				break;
			default:
				return $submits;
				break;
		}
		return $submits;
	} 
}
new Rednumber_Marketing_CRM_Sendinblue;