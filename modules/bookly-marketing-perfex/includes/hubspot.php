<?php
class Rednumber_Marketing_CRM_Hubspot {
	private static $add_on ="hubspot";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
		add_action( 'admin_post_crm_marketing_settings_'.self::$add_on, array($this,"register_settings"));
		add_action("crm_marketing_remove_options_".self::$add_on,array($this,"remove_options"));
		add_action("admin_init",array($this,"set_token"));
		add_filter("crm_marketing_map_fields_form_".self::$add_on,array($this,"add_map_fields"));
	}
	function add_map_fields($list_fields){
		$list_fields["[current_contact_id]"] = "Current contact ID";
		$list_fields["[current_company_id]"] = "Current company ID";
		$list_fields["[current_ticket_id]"] = "Current ticket ID";
		$list_fields["[current_deal_id]"] = "Current Deal ID";
		$list_fields["[current_task_id]"] = "Current task ID";
		$list_fields["[current_pipeline_id]"] = "Current pipeline ID";
		return $list_fields;
	}
	function add_settings($lists){
		$lists[self::$add_on] = array("label"=>esc_html__("Hubspot Configuration","crm-marketing"),"form"=>array($this,"form_settings"),"detail"=>array($this,"form_detail"));
		return $lists;
	}
	function remove_options(){
		delete_option("crm_marketing_".self::$add_on);
		delete_option("_".self::$add_on."_crm_token");
	}
	function set_token(){
		if( isset($_GET["code"])) {
			$tab = sanitize_text_field($_GET["tab"]);
			$code = sanitize_text_field($_GET["code"]);
			if( $tab == self::$add_on && $code !="" ){
				$api = new Rednumber_Marketing_CRM_Hubspot_API();
				$token = $api->get_token($code);
				$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
				$options["api"] = $code;
				update_option("crm_marketing_".self::$add_on,$options);
			}
		}
	}
	function form_detail(){
		if(isset($_GET["type"])) {
			$type = sanitize_text_field($_GET["type"]);
			$id = sanitize_text_field($_GET["id"]);
			$options = get_option("crm_marketing_".self::$add_on,array("api"=>""));
			if( $options["api"] == ""){
				?>
				<div><h1><?php esc_html_e("Please set API KEY: ","crm-marketing") ?> <a href="<?php echo esc_url(admin_url("admin.php?page=crm-marketing-config&tab=hubspot")) ?>"><?php esc_html_e("API KEY","crm-marketing") ?></a></h1></div>
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
		         $list_fields = apply_filters("crm_marketing_map_fields_form_".self::$add_on,$list_fields,$form_id);
		         $list_fields = apply_filters("crm_marketing_map_fields_form",$list_fields,$form_id);
		         $logics = array();
				?>
			<input type="hidden" name="action" value="crm_marketing_<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="add_on" value="<?php echo esc_attr(self::$add_on) ?>">
			<input type="hidden" name="type" value="<?php echo esc_attr($type) ?>">
			<input type="hidden" name="form_id" value="<?php echo esc_attr($form_id) ?>">
			<input type="hidden" name="inner_tab" class="crm_marketing_inner_tab" value="<?php echo esc_attr($inner_tab) ?>">
			<textarea class="crm-marketing-list-fields hidden"><?php echo json_encode($list_fields) ?></textarea>
			<textarea class="crm-marketing-logic hidden"><?php echo json_encode($logics) ?></textarea>
			<div class="crm-marketing-content">
				<div class="crm-marketing-header-content">
					<h3><?php esc_html_e("HubSpot Connect","crm-marketing") ?></h3>
				</div>
				<div class="crm-marketing-container-content">
					<?php
						$api = new Rednumber_Marketing_CRM_Hubspot_API(true);
						//Contact
						$forms = array();
						$forms["contact"]["title"] = esc_html__("Contact","crm-marketing");
						$html_datas = array();
						$html_datas["contact[enable]"] = array("value"=>$datas[0]["contact"]["enable"],"label"=>"Enable contact","type"=>"checkbox");
						$attrs_contact = $api->get_data("attrs_contact");
						foreach( $attrs_contact as $contact ){ 
							$new_data = $contact;
							$new_data["value"]= $datas[0]["contact"][$contact["name"]];
							$html_datas["contact[".$contact["name"]."]"] = $new_data;
						}
						$forms["contact"]["value"] = $html_datas;
						//Deal
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
						//ticket
						$forms["ticket"]["title"] = esc_html__("Ticket","crm-marketing");
						$html_datas = array();
						$html_datas["ticket[enable]"] = array("value"=>$datas[0]["ticket"]["enable"],"label"=>"Enable ticket","type"=>"checkbox");
						$attrs_ticket = $api->get_data("attrs_ticket");
						foreach( $attrs_ticket as $ticket ){ 
							$new_data = $ticket;
							$new_data["value"]= $datas[0]["ticket"][$ticket["name"]];
							$html_datas["ticket[".$ticket["name"]."]"] = $new_data;
						}
						$forms["ticket"]["value"] = $html_datas;
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
						//Pipeline
						$forms["pipeline"]["title"] = esc_html__("Pipeline","crm-marketing");
						$html_datas = array();
						$html_datas["pipeline[enable]"] = array("value"=>$datas[0]["pipeline"]["enable"],"label"=>"Enable pipeline","type"=>"checkbox");
						$attrs_pipeline = $api->get_data("attrs_pipeline");
						foreach( $attrs_pipeline as $pipeline ){ 
							$new_data = $pipeline;
							$new_data["value"]= $datas[0]["pipeline"][$pipeline["name"]];
							$html_datas["pipeline[".$pipeline["name"]."]"] = $new_data;
						}
						$forms["pipeline"]["value"] = $html_datas;
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
						//file
						$forms["file"]["title"] = esc_html__("File Upload","crm-marketing");
						$html_datas = array();
						$html_datas["file[enable]"] = array("value"=>$datas[0]["file"]["enable"],"label"=>"Enable file upload","type"=>"checkbox");
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
					submit_button(); ?>
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
			        <th scope="row"><?php esc_html_e("Access token","crm-marketing") ?></th>
			        <td>
			        	<?php if( $options["api"] != "" ) { ?>
			        		<input class="regular-text" type="hidden" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" />
			        		<strong><?php echo substr($options["api"], 0, 20); ?>******</strong>
				        <p><a data-add_on="<?php echo esc_attr(self::$add_on) ?>" class="button button-default crm-marketing-remove-options" href="#"><?php esc_html_e("Remove access","crm-marketing") ?></a></p>
			        </tr>
                        <?php }else{
                        	?>
                        	<input class="regular-text" type="text" name="crm_marketing_<?php echo esc_attr(self::$add_on) ?>[api]" value="<?php echo esc_attr( $options["api"]); ?>" />
                        	<p><a  class="button button-default" target="_blank" href="https://add-ons.org/add-your-hubspot-access-token/"><?php esc_html_e("Get access token","crm-marketing") ?></a></p>
                        	<?php
                        } ?>
			        	</td>
		        </tr>
		    </table>
		    <?php submit_button(); ?>
		</form>
		<?php
	}
	public static function cover_data_to_api($submits_new, $type ="",$form_data = "",$form_type = "", $form_raw=""){ 
		switch( $type ){
			case "contact":
			case "deal":
			case "company":
			case "task":
			case "ticket":
			case "note":
				return array("properties"=>$submits_new);
				break;
			case "pipeline":
				$metadata = array();
				if( isset( $submits_new["ticketState"] )){
					$metadata["ticketState"] = $submits_new["ticketState"] ;
					unset($submits_new["ticketState"]);
				}
				if( isset( $submits_new["probability"] )){
					$metadata["probability"] = $submits_new["probability"] ;
					unset($submits_new["probability"]);
				}
				$submits_new["metadata"] = $metadata;
				return $submits_new;
				break;
			case "file":
				if( $form_type == "contact_form_7") {
					if( isset( $submits_new["file"] )){ 
						$name_file = str_replace(array("[","]"),"",$form_raw["file"]);
						$uploaded_files = $form_data->uploaded_files();
						foreach( $uploaded_files as $name_upload => $files ){
							if( $name_file == $name_upload ){
								$submits_new["file"] = $files[0];
								break;
							}
						}
					}
				}
				break;
			case "file_cf7_pdf":
				if( isset( $submits_new["file"] )){  
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
						$submits_new["file"] = $path_main.$name.".pdf";
					}else{
						unset($submits_new["file"]);
					}
				}
				break;
			default:
				return $submits_new;
				break;
		}
		return $submits_new;
	}
	function register_settings(){
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'crm_marketing_settings_'.self::$add_on ) ) {
		    die('Security check'); 
		} else {
			$hubspots = map_deep( $_POST["crm_marketing_".self::$add_on], 'sanitize_text_field' );
			if( isset($hubspots["api"]) && $hubspots["api"] != "" ){
				$api = new Rednumber_Marketing_CRM_Hubspot_API();
				$token = $api->get_token($hubspots["api"]);
			}
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
			$inner_tab = sanitize_text_field($_POST["inner_tab"]);
			$form_id = sanitize_text_field($_POST["form_id"]);
			$type = sanitize_text_field($_POST["type"]);
			$referer = sanitize_textarea_field($_POST["_wp_http_referer"]);
			$contact = map_deep( $_POST["contact"], 'sanitize_text_field' );
			$contact= array_map('stripslashes_deep', $contact);
			$deal = map_deep( $_POST["deal"], 'sanitize_text_field' );
			$deal= array_map('stripslashes_deep', $deal);
			$company = map_deep( $_POST["company"], 'sanitize_text_field' );
			$company= array_map('stripslashes_deep', $company);
			$ticket = map_deep( $_POST["ticket"], 'sanitize_text_field' );
			$ticket= array_map('stripslashes_deep', $ticket);
			$task = map_deep( $_POST["task"], 'sanitize_text_field' );
			$task= array_map('stripslashes_deep', $task);
			$note = map_deep( $_POST["note"], 'sanitize_text_field' );
			$note= array_map('stripslashes_deep', $note);
			$pipeline = map_deep( $_POST["pipeline"], 'sanitize_text_field' );
			$pipeline= array_map('stripslashes_deep', $pipeline);
			$file = map_deep( $_POST["file"], 'sanitize_text_field' );
			$file= array_map('stripslashes_deep', $file);
			$datas = array();
			$datas[] = array("contact"=>$contact,"deal"=>$deal,"company"=>$company,"ticket"=>$ticket,"task"=>$task,"note"=>$note,"pipeline"=>$pipeline,"file"=>$file);
			$datas = apply_filters("crm_marketing_save_form_".self::$add_on."_".$type,$datas,$form_id);
			Rednumber_Marketing_CRM_Database::update_option($type,$add_on,$form_id,$datas);
			$url = admin_url( 'admin.php' )."?page=crm-marketing&id={$form_id}&type={$type}&tab=".self::$add_on."&inner_tab=".$inner_tab;
			wp_redirect( $url );
			exit;
		}
	} 
}
new Rednumber_Marketing_CRM_Hubspot;