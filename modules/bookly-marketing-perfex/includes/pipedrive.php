<?php
class Rednumber_Marketing_CRM_Pipedrive{
	private static $add_on ="pipedrive";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_filter("crm_marketing_lists",array($this,"add_on"));
		add_filter("crm_marketing_map_fields_form_".self::$add_on,array($this,"add_map_fields"));
	}
	function add_map_fields($list_fields){
		$list_fields["[current_org_id]"] = "Current Organization ID";
		$list_fields["[current_person_id]"] = "Current Person ID";
		$list_fields["[current_lead_id]"] = "Current Lead ID";
		$list_fields["[current_deal_id]"] = "Current Deal ID";
		return $list_fields;
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
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".self::$add_on,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form",$list_fields,$form_id);
		         $api = new Rednumber_Marketing_CRM_Pipedrive_API(true);
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
					//Contact/Persons
					$html_datas = array();
					$forms["person"]["title"] = esc_html__("Contact/Person","crm-marketing");
					$html_datas["person[enable]"] = array("value"=>$datas[0]["person"]["enable"],"label"=>"Enable person","type"=>"checkbox");
					$attrs_person = $api->get_data("attrs_person");
					foreach( $attrs_person as $person ){ 
						$new_data = $person;
						$new_data["value"]= $datas[0]["person"][$person["name"]];
						$html_datas["person[".$person["name"]."]"] = $new_data;
					}
					$forms["person"]["value"] = $html_datas;
					//organization
					$html_datas = array();
					$forms["organization"]["title"] = esc_html__("Organization","crm-marketing");
					$html_datas["organization[enable]"] = array("value"=>$datas[0]["organization"]["enable"],"label"=>"Enable organization","type"=>"checkbox");
					$attrs_organization = $api->get_data("attrs_organization");
					foreach( $attrs_organization as $organization ){ 
						$new_data = $organization;
						$new_data["value"]= $datas[0]["organization"][$organization["name"]];
						$html_datas["organization[".$organization["name"]."]"] = $new_data;
					}
					$forms["organization"]["value"] = $html_datas;
					//lead
					$html_datas = array();
					$forms["lead"]["title"] = esc_html__("Lead","crm-marketing");
					$html_datas["lead[enable]"] = array("value"=>$datas[0]["lead"]["enable"],"label"=>"Enable lead","type"=>"checkbox");
					$attrs_lead = $api->get_data("attrs_lead");
					foreach( $attrs_lead as $lead ){ 
						$new_data = $lead;
						$new_data["value"]= $datas[0]["lead"][$lead["name"]];
						$html_datas["lead[".$lead["name"]."]"] = $new_data;
					}
					$forms["lead"]["value"] = $html_datas;
					//Deal
					$html_datas = array();
					$forms["deal"]["title"] = esc_html__("Deal","crm-marketing");
					$html_datas["deal[enable]"] = array("value"=>$datas[0]["deal"]["enable"],"label"=>"Enable deal","type"=>"checkbox");
					$attrs_deal = $api->get_data("attrs_deal");
					foreach( $attrs_deal as $deal ){ 
						$new_data = $deal;
						$new_data["value"]= $datas[0]["deal"][$deal["name"]];
						$html_datas["deal[".$deal["name"]."]"] = $new_data;
					}
					$forms["deal"]["value"] = $html_datas;
					//activity
					$html_datas = array();
					$forms["activity"]["title"] = esc_html__("Activity","crm-marketing");
					$html_datas["activity[enable]"] = array("value"=>$datas[0]["activity"]["enable"],"label"=>"Enable activity","type"=>"checkbox");
					$attrs_activity = $api->get_data("attrs_activity");
					foreach( $attrs_activity as $activity ){ 
						$new_data = $activity;
						$new_data["value"]= $datas[0]["activity"][$activity["name"]];
						$html_datas["activity[".$activity["name"]."]"] = $new_data;
					}
					$forms["activity"]["value"] = $html_datas;
					//Note
					$html_datas = array();
					$forms["note"]["title"] = esc_html__("Note","crm-marketing");
					$html_datas["note[enable]"] = array("value"=>$datas[0]["note"]["enable"],"label"=>"Enable note","type"=>"checkbox");
					$attrs_note = $api->get_data("attrs_note");
					foreach( $attrs_note as $note ){ 
						$new_data = $note;
						$new_data["value"]= $datas[0]["note"][$note["name"]];
						$html_datas["note[".$note["name"]."]"] = $new_data;
					}
					$forms["note"]["value"] = $html_datas;
					//File
					$html_datas = array();
					$forms["file"]["title"] = esc_html__("File Upload","crm-marketing");
					$html_datas["file[enable]"] = array("value"=>$datas[0]["file"]["enable"],"label"=>"Enable file","type"=>"checkbox");
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
		$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field("crm_marketing_settings_".self::$add_on); ?>
		    <input type="hidden" name="action" value="crm_marketing_settings_<?php echo esc_attr(self::$add_on) ?>">
		    <table class="form-table">
		        <tr valign="top" class="crm-marketing-method-api crm-marketing-method-api-token>">
			        <th scope="row"><?php esc_html_e("Personal API token","crm-marketing") ?></th>
			        <td><input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" />
			        	<p><?php esc_html_e("Get your API key from your settings ","crm-marketing") ?> <a target="_blank" href="https://pipedrive.readme.io/docs/how-to-find-the-api-token"><?php esc_html_e("(API)","crm-marketing") ?></a></p>
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
			$lead = map_deep( $_POST["lead"], 'sanitize_text_field' );
			$lead=array_map('stripslashes_deep', $lead);
			$deal = map_deep( $_POST["deal"], 'sanitize_text_field' );
			$deal=array_map('stripslashes_deep', $deal);
			$person = map_deep( $_POST["person"], 'sanitize_text_field' );
			$person=array_map('stripslashes_deep', $person);
			$note = map_deep( $_POST["note"], 'sanitize_text_field' );
			$note=array_map('stripslashes_deep', $note);
			$organization = map_deep( $_POST["organization"], 'sanitize_text_field' );
			$organization=array_map('stripslashes_deep', $organization);
			$activity = map_deep( $_POST["activity"], 'sanitize_text_field' );
			$activity=array_map('stripslashes_deep', $activity);
			$file = map_deep( $_POST["file"], 'sanitize_text_field' );
			$file=array_map('stripslashes_deep', $file);
			$datas = array();
			$datas[] = array("lead"=>$lead,"deal"=>$deal,"person"=>$person,"note"=>$note,"organization"=>$organization,"activity"=>$activity,"file"=>$file);
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on."&inner_tab=".$inner_tab;
			wp_redirect( $url );
			exit;
		}
	}
	public static function cover_data_to_api($submits_new, $type ="",$form_data = "",$form_type = ""){
		$value_ojects = array();
		$submits = array();
		foreach( $submits_new as $k => $v ){
			switch( $k ){ 
				case "org_id":
				case "owner_id":
				case "lead_id":
				case "pipeline_id":
				case "stage_id":
				case "probability":
				case "deal_id":
				case "person_id":
				case "user_id":
				case "probability":
					$submits[$k] = (int) $v;
						break;
				default:
					$submits[$k] =  $v;
					break;
				}	
		}
		switch($type){
			case "preson":
				if(isset($submits["phone"] )){
					$submits["phone"] = array($submits["phone"]);
				}
				if(isset($submits["email"] )){
					$submits["email"] = array($submits["email"]);
				}
				break;
			case "lead":
				if(isset($submits["amount"] )){ 
					$value_ojects["amount"] = (int) $submits["amount"];
					unset($submits["amount"]);
				}
				if(isset($submits["currency"] )){ 
					$value_ojects["currency"] = $submits["currency"];
					unset($submits["currency"]);
				}
				// code...
				break;
			case "activity":
				if(isset($submits["done"] )){ 
					$submits["done"] = 1;
				}
				break;
			case "file":
				if($form_type == "contact_form_7"){
					if( isset($submits["file"]) ){
						$name_file = str_replace(array("[","]"),"",$datas["file"]);
						if( $name_file =="upload_pdf"){
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
							$uploaded_files = $form_data->uploaded_files();
							foreach( $uploaded_files as $name_upload => $files ){
								if( $name_file == $name_upload ){
									$submits["file"] = $files[0];
									break;
								}
							}
						}
						$submits["file"] = curl_file_create($submits["file"]);
					}
				}elseif ( $form_type == "elementor" ){
					if( isset($submits["file"]) ){
						$name_file = str_replace(array("[","]"),"",$datas["file"]);
						if( $name_file =="upload_pdf"){
							$upload_dir = wp_upload_dir();
							$path_main = $upload_dir['basedir'] . '/pdfs/';
							$name= "elementor-form";
							$submits["file"] = $path_main.$name.".pdf";
						}else{
							if( is_array($submits["file"])){
								$submits["file"] = array_shift(array_values($submits["file"]));
							}
						}
						$submits["file"] = curl_file_create($submits["file"]);
					}
				}elseif( $form_type == "ninjaforms"){
					$name_file = str_replace(array("[","]"),"",$datas["file"]);
					if( $name_file =="upload_pdf"){
						$upload_dir = wp_upload_dir();
						$path_main = $upload_dir['basedir'] . '/pdfs/';
						$name= "contact-form-".$form_data['form_id'];
						$submits["file"] = $path_main.$name.".pdf";
					}else{
						if( is_array($submits["file"])){
							$submits["file"] = array_shift(array_values($submits["file"]));
						}
					}
					$submits["file"] = curl_file_create($submits["file"]);
				}
				else {
					if( is_array($submits["file"])){
						$submits["file"] = array_shift(array_values($submits["file"]));
					}
					$submits["file"] = curl_file_create($submits["file"]);
				}
				break;
			default:
				break;
		}
		if( count($value_ojects) > 0 ){
			$submits["value"]= (object) $value_ojects;
		}
		return $submits;
	} 
}
new Rednumber_Marketing_CRM_Pipedrive;