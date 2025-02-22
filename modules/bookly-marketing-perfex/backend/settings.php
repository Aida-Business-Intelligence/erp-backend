<?php
class Rednumber_Marketing_CRM_backend{
	function __construct(){
		add_action('admin_menu', array($this,'create_menu'));
		add_action('admin_enqueue_scripts', array($this,'add_libs'));
		add_action( 'wp_ajax_crm_marketing_remove_all_logs', array($this,"remove_all_logs") );
		add_action( 'wp_ajax_crm_marketing_remove_options', array($this,"remove_options") );
		add_action( 'wp_ajax_crm_marketing_sync', array($this,"sync") );
		add_filter("crm_marketing_map_fields_form",array($this,"shortcode_fields"));
		add_shortcode( 'crm_marketing', array($this,"shortcode_fields_process") );
	}
	function remove_options(){
		$add_on = sanitize_text_field($_POST["add_on"]);
		delete_option("crm_marketing_".$add_on);
		do_action("crm_marketing_remove_options_".$add_on);
	}
	function shortcode_fields($datas){
		$datas['[crm_marketing type="timestamp" ]'] ="Current timestamp";
		$datas['[crm_marketing type="date_time" ]'] ="Current Date and Time ( Y-m-d H:i:s )";
		$datas['[crm_marketing type="date_time_ymd" ]'] ="Current Date ( Y-m-d )";
		$datas['[crm_marketing type="date_time_ymd_1" ]'] ="Current Date ( Y/m/d )";
		$datas['[crm_marketing type="time" ]'] ="Current time ( H:i:s )";
		return $datas;
	}
	function shortcode_fields_process($atts, $content = null){
		$datas = shortcode_atts( array(
			'type' => '',
		), $atts );
		$html="";
		switch ( $datas["type"] ) {
			case "timestamp":
				 $html = time();
				break;
			case "date_time":
				 $html = date('Y-m-d H:i:s');
				break;
			case "date_time_ymd":
				 $html = date('Y-m-d');
				break;
			case "date_time_ymd_1":
				 $html = date('Y/m/d');
				break;
			case "time":
				 $html = date('H:i:s');
				break;
		}
		return $html;
	}
	function sync(){
		$type = sanitize_text_field($_POST["type"]);
		$add_on = sanitize_text_field($_POST["add_on"]);
		$id = sanitize_text_field($_POST["id"]);
		do_action("crm_marketing_sync_".$type."_".$add_on,$id);
		die();
	}
	function remove_all_logs(){
		Rednumber_Marketing_CRM_Logs::remove_all();
		die();
	}
	function create_menu(){
		add_menu_page(
	       esc_html__( 'CRM Marketing', 'crm-marketing' ),
	       esc_html__( 'CRM Marketing', 'crm-marketing' ),
	       'manage_options',
	       'crm-marketing',
	       array($this,"form_settings"),
	       'dashicons-money-alt',
	       81
	    );
	    add_submenu_page(
	    	"crm-marketing",
	    	esc_html__( 'Configuration', 'crm-marketing' ),
	    	esc_html__( 'Configuration', 'crm-marketing' ),
	    	'manage_options',
	    	'crm-marketing-config',
	    	array($this,"form_config")
	    );
	     add_submenu_page(
	    	"crm-marketing",
	    	esc_html__( 'Logs', 'crm-marketing' ),
	    	esc_html__( 'Logs', 'crm-marketing' ),
	    	'manage_options',
	    	'crm-marketing-log',
	    	array($this,"form_logs")
	    );
	}
	function add_libs(){
		wp_enqueue_style('crm-marketing', REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/css/crm-marketing.css",array(),time());
		wp_enqueue_script('crm-marketing', REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/js/crm-marketing.js",array("jquery"));
	}
	function form_logs(){
		?>
		<div>
			<h1><?php esc_html_e("List Logs","crm-marketing") ?></h1>
			<p><a href="#" class="button crm-maketing-remove-all-logs"><?php esc_html_e("Remove all logs","crm-marketing") ?></a></p>	
			<div>
				<?php
				$lists = Rednumber_Marketing_CRM_Logs::get();
				$text="";
				$i =1;
				foreach( $lists as $list ){
					$text .= $i.": ". $list->type ."-----".$list->add_on."-----".$list->datas."\n";
					$i++;
				}
				?>
				<textarea class="full_width"><?php printf("%s",$text)  ?></textarea>
			</div>
		</div>
		<?php
	}
 	function form_settings(){
 		if( isset($_GET['id']) && isset($_GET['page']) && $_GET['page']=="crm-marketing"  ) {
 			$type = sanitize_text_field($_GET['type']);
 			$id = sanitize_text_field($_GET['id']);
 			$tab ="";
 			if( isset($_GET["tab"]) ){
 				$tab = sanitize_text_field($_GET["tab"]);
 			}
			$lists_add = $this->get_lists();
			$list_orther_plugins = $lists_add;
	 		$lists_active = $this->get_lists_active();
	 		?>
	 		<div class="crm-marketing-config-container">
	 			<div class="crm-marketing-config-tag">
	 				<h3><?php esc_html_e("Settings","crm-marketing") ?></h3>
	 				<ul>
	 				<?php
	 				$args = array();
	 				foreach( $lists_active as $key => $value){ 
	 					if( isset($value["detail"])){
	 						$args[$key] = $lists_add[$key];
	 					}
	 				}
	 				foreach( $lists_add as $key => $value){ 
	 					if(!array_key_exists($key,$args)){
	 						$args[$key] = $value;
	 					}
	 				} 
	 				foreach( $args as $key => $value){
	 					if( isset($value["icon"])){
	 						$icon = $value["icon"];
	 					}else{
	 						$icon = "";
	 					}
	 					$class ="";
	 					if( array_key_exists($key,$lists_active)){
	 						$class = "enabled";
	 					}
	 					if( $tab == $key) {
	 						$class .=" active";
	 					}
	 					?>
	 						<li data-id="<?php echo esc_attr($key) ?>" class="crm-marketing-config-tag-inner m-marketing-config-tag-<?php echo esc_attr($key); ?> <?php echo esc_attr( $class )?>">
	 						<a href="#">
	 							<img src="<?php echo esc_url($icon) ?>"> <?php echo esc_html($value["lable"]) ?>
	 						</a>
	 						</li>
	 					<?php
	 				}
	 				?>
	 				</ul>
	 			</div>
	 			<div class="crm-marketing-config-content">
	 				<?php
 					$class ="";
 					if( $tab != ""){
 						$class ="hidden";
 					}
 					if( $tab == ""){
	 					$this->get_default_des();
	 				}
	 				 foreach( $lists_active as $key => $value){ 
	 					$class ="";
	 					unset($list_orther_plugins[$key]);
	 					if( $tab != $key) {
	 						$class ="hidden";
	 					}
	 					?>
	 				<div id="crm-marketing-config-tab-<?php echo esc_attr($key) ?>" class="crm-marketing-config-tab <?php echo esc_attr($class) ?>">
	 					<?php 
	 					if( isset($value["detail"])) {
	 						add_action("crm-marketing-detail-".$key,$value["detail"]);
 					 	 	do_action("crm-marketing-detail-".$key);
	 					}
	 					?>
	 				</div>
	 				<?php 
	 				} 
	 				foreach( $list_orther_plugins as $key=>$value ){
	 					?>
		 					<div id="crm-marketing-config-tab-<?php echo esc_attr($key) ?>" class="crm-marketing-config-tab hidden">
		 						<h3><?php echo esc_html($value["lable"]) ?></h3>
		 						<div class="crm-marketing-content-des">
		 							<?php 
				 						if( isset($value["des"])) {
				 							echo esc_html($value["des"]);
				 						}else{
				 							$this->get_default_des_add_on();
				 						}
				 					?>
				 					<p><?php esc_html_e("List plugins","crm-marketing") ?></p>
			 							<ul>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044079-contact-form-7-crm"><?php esc_html_e("Contact Form 7")  ?></a></li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044081-gravity-forms-crm"><?php esc_html_e("Gravity Forms")  ?></a></li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044082-wpforms-crm/"><?php esc_html_e("WPForms")  ?></a></li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044083-ninja-forms-crm"><?php esc_html_e("Ninja Forms")  ?></a><li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044085-elementor-form-crm"><?php esc_html_e("Elementor Form")  ?></a><li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044086-woocommerce-crm/"><?php esc_html_e("WooCommerce")  ?></a><li>
			 							</ul>
		 						</div>
		 				</div>
	 					<?php
	 				}
	 			?>
	 			</div>
	 			<div class="clear"></div>
	 		</div>
	 		<?php
 		}else{
 			$table = new Rednumber_Marketing_CRM_Table();
	        $table->prepare_items();
	        ?>
	            <div class="wrap">
	                <div id="icon-users" class="icon32"></div>
	                <h2><?php esc_html_e("List Forms","crm-marketing") ?></h2>
	                <?php $table->display(); ?>
	            </div>
	        <?php
 		}
 	}
 	function get_lists(){
 		$lists_add = array(
 			"pdf"     => array(
 				"lable"      =>esc_html__("PDF Creator","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pdf.png",
 				"des"        => esc_html__(" Automatically generate, email and download PDFs. A plugin is a helpful tool that helps you build and customize the PDF Templates for Forms. The plugin provides sufficient base elements and Forms elements as well as developmental tools for users to build a completed pdf. You can easily drag and drop, edit and style for transaction pdf using Layouts, insert desire contents with no coding knowledge required, and adds a PDF to the email sent out to your customers. Overall, you need to do is a couple of mouse clicks to create and experience your pdf template that will be sent to your customers.","crm-marketing"),		
 			),
 			"salesforce"     => array(
 				"lable"      =>esc_html__("Salesforce CRM","crm-marketing"),
 				"icon"       =>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-salesforce.png",
 				"des"        => esc_html__(" The plugin allows you to connect Forms and Salesforce CRM. To automatically add/update form submissions to your Salesforce CRM account, simply integrate your Forms form with Salesforce CRM Lead, Contact or Case.","crm-marketing"),
 			),
 			"zapier" => array(
 				"lable"=>esc_html__("Zapier","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-zapier.png",
 				"des"        => esc_html__("Although we’d love to build an integration for every third-party service and application available, it simply isn’t possible. Instead, we created a Zapier Add-on, enabling you to connect your forms with over 2,000 different web services and counting!","crm-marketing"),
 			),
 			"get_response" => array(
 				"lable"=>esc_html__("get response","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-getresponse.png",
 				"des"        => esc_html__("The Add-On allows you to quickly integrate all of your online forms with the GetResponse email marketing service. Collect and add subscribers to your GetResponse marketing list when a form is submitted.","crm-marketing"),
 			),
 			"webhooks" => array(
 				"lable"=>esc_html__("Webhooks","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-webhooks.png",
 				"des" => esc_html__("The Webhooks add-on helps you send form data to an external API. Easily pass information such as form submissions to the 3rd-party service of your choice.","crm-marketing"),
 			),
 			"mailchimp" => array(
 				"lable"=>esc_html__("Mailchimp","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-mailchimp.png",
 				"des" => esc_html__("The Add-On gives you an easy way to integrate all of your online forms with the Mailchimp email marketing service. Collect and add subscribers to your email marketing lists automatically when a form is submitted.","crm-marketing"),
 			),
 			"google_sheets" => array(
 				"lable"=>esc_html__("Google Sheets","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-googlesheets.png",
 				"des" => esc_html__("As the visitor provides its information on your site, upon the form submission the data will instantly be sent to Google Sheets. In this way, you can create clear and simplified data over Google Sheets.","crm-marketing"),
 			),
 			"whatsapp" => array(
 				"lable"=>esc_html__("WhatsApp","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-whatsapp.png",
 				"des" => esc_html__("The add-on connect WhatsApp help your customer is auto redirect to send the data contact form in WhatsApp after the contact sent email.","crm-marketing"),
 			),
 			"pardot" => array(
 				"lable"=>esc_html__("Pardot Integration","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pardot.png",
 				"des" => esc_html__(" To automatically add/update submissions to your Pardot account, simply integrate your form with Pardot Prospect. Also, support Salesforce SSO.","crm-marketing"),
 			),
 			"bitrix24" => array(
 				"lable"=>esc_html__("Bitrix24 CRM","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-bitrix24.png",
 				"des" => esc_html__(" After the integration, submited the form are automatically added as lead, deal, task, contact or company to the specified account in Bitrix24, together with additional data.","crm-marketing"),
 			),
 			"activecampaign" => array(
 				"lable"=>esc_html__("ActiveCampaign","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-activecampaign.png",
 				"des" => esc_html__("With the Add-On, you can quickly integrate any form on your WordPress website with ActiveCampaign’s all-in-one email marketing service.","crm-marketing"),
 			),
 			"hubspot" => array(
 				"lable"=>esc_html__("HubSpot","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-hubspot.png",
 				"des" => esc_html__("Need a CRM to manage and organize your relationships and interactions with leads and customers? Then look no further than HubSpot, an all-in-one marketing and sales platform, that will support your business on every step of its journey.","crm-marketing"),
 			),
 			"campaignmonitor" => array(
 				"lable"=>esc_html__("Campaign Monitor","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-campaignmonitor.png",
 				"des" => esc_html__("Collect and add subscribers to your Campaign Monitor email marketing list when a form is submitted","crm-marketing"),
 			),
 			"constantcontact" => array(
 				"lable"=>esc_html__("Constant Contact","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-constantcontact.png",
 				"des" => esc_html__("Grow your mailing list faster with the Add-On! Automatically send new leads to your Constant Contact mailing list, then send targeted email campaigns designed to connect, engage, and convert.","crm-marketing"),
 			),
 			"zohocrm" => array(
 				"lable"=>esc_html__("Zoho CRM","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-zohocrm.png",
 				"des" => esc_html__("The Add-On allows you to quickly integrate your WordPress forms with Zoho's CRM. Create or update contacts in your Zoho CRM, or add new sales leads after form submissions.","crm-marketing"),
 			),
 			"perfex" => array(
 				"lable"=>esc_html__("Perfex CRM","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-perfex.png",
 				"des" => esc_html__("The Add-On allows you to quickly integrate your WordPress forms with PerfexCRM. Create or update contacts in your Perfex CRM, or add new sales leads after form submissions.","crm-marketing"),
 			),
 			"pipedrive" => array(
 				"lable"=>esc_html__("Pipedrive CRM","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pipedrive.png",
 				"des" => esc_html__("This is a Pipedrive CRM integration plugin for WordPress that makes it really simple to send your Contact Form 7 forms directly to your Pipedrive CRM account. After the integration, submited the form are automatically added as lead, deal, person & organization, activity or deal & activity to the specified account in Pipedrive CRM, together with additional data.","crm-marketing"),
 			),
 			"docusign" => array(
 				"lable"=>esc_html__("docusign CRM","crm-marketing"),
 				"icon"=>REDNUMBER_MARKETING_CRM_PLUGIN_URL."backend/images/icon-pipedrive.png",
 				"des" => esc_html__("This is a docusign CRM integration plugin for WordPress that makes it really simple to send your Contact Form 7 forms directly to your docusign CRM account. After the integration, submited the form are automatically added as lead, deal, person & organization, activity or deal & activity to the specified account in docusign CRM, together with additional data.","crm-marketing"),
 			),
 		);
 		return apply_filters("crm_marketing_lists",$lists_add);
 	}
 	function get_lists_active(){
 		$lists_active = array();
 		$lists_active = apply_filters("crm_marketing_config_tag_active",$lists_active);
 		return $lists_active;
 	}
 	function form_config(){
 		$lists_add = $this->get_lists();
 		$list_orther_plugins = $lists_add;
 		$lists_active = $this->get_lists_active();
 		$tab ="";
 			if( isset($_GET["tab"]) ){
 				$tab = sanitize_text_field($_GET["tab"]);
 			}
 		?>
 		<div class="crm-marketing-config-container">
 			<div class="crm-marketing-config-tag">
 				<h3><?php esc_html_e("Configure","crm-marketing") ?></h3>
 				<ul>
 				<?php 
 				$args = array();
 				$hidden_lists = array();
 				foreach( $lists_active as $key => $value){ 
 					if( isset($value["form"])){
 						$args[$key] = $lists_add[$key];
 					}else{
 						$hidden_lists[] = $key;
 					}
 				}
 				foreach( $lists_add as $key => $value){ 
 					if(!array_key_exists($key,$args) && ! in_array($key, $hidden_lists)){
 						$args[$key] = $value;
 					}
 				}
 				foreach( $args as $key => $value){
 					if( isset($value["icon"])){
 						$icon = $value["icon"];
 					}else{
 						$icon = "";
 					}
 					$class ="";
 					if( array_key_exists($key,$lists_active)){
 						$class = "enabled";
 					}
 					if( $tab == $key) {
 						$class .=" active";
 					}
 					?>
 						<li data-id="<?php echo esc_attr($key) ?>" class="crm-marketing-config-tag-inner m-marketing-config-tag-<?php echo esc_attr($key); ?> <?php echo esc_attr( $class )?>">
 						<a href="#">
 							<img src="<?php echo esc_url($icon) ?>"> <?php echo esc_html($value["lable"]) ?>
 						</a>
 						</li>
 					<?php
 				}
 				?>
 				</ul>
 			</div>
 			<div class="crm-marketing-config-content">
 				<?php  
 				if( $tab == ""){
 					$this->get_default_des();
 				}
 				foreach( $lists_active as $key => $value){
 					unset($list_orther_plugins[$key]);
 					$class = "";
 					if( $tab != $key) {
 						$class ="hidden";
 					}
 					if( !isset($value["form"])){
 						continue;
 					}
 				 ?>
 				<div id="crm-marketing-config-tab-<?php echo esc_attr($key) ?>" class="crm-marketing-config-tab <?php echo esc_attr($class) ?>">
 					<h3><?php echo esc_html($value["label"]) ?></h3>
 					<?php 
 					 add_action("crm-marketing-form-".$key,$value["form"]);
 					 do_action("crm-marketing-form-".$key);
 					?>
 				</div>
 			<?php } 
			foreach( $list_orther_plugins as $key=>$value ){
	 					?>
		 					<div id="crm-marketing-config-tab-<?php echo esc_attr($key) ?>" class="crm-marketing-config-tab hidden">
		 						<h3><?php echo esc_html($value["lable"]) ?></h3>
		 						<div class="crm-marketing-content-des">
		 							<?php 
				 						if( isset($value["des"])) {
				 							echo esc_html($value["des"]);
				 						}else{
				 							$this->get_default_des_add_on();
				 						}
				 						?>
			 							<p><?php esc_html_e("List plugins","crm-marketing") ?></p>
			 							<ul>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044079-contact-form-7-crm"><?php esc_html_e("Contact Form 7")  ?></a></li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044081-gravity-forms-crm"><?php esc_html_e("Gravity Forms")  ?></a></li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044082-wpforms-crm/"><?php esc_html_e("WPForms")  ?></a></li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044083-ninja-forms-crm"><?php esc_html_e("Ninja Forms")  ?></a><li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044085-elementor-form-crm"><?php esc_html_e("Elementor Form")  ?></a><li>
			 								<li><a target="_blank" href="https://codecanyon.net/collections/11044086-woocommerce-crm/"><?php esc_html_e("WooCommerce")  ?></a><li>
			 							</ul>
				 							<?php
				 					?>
		 						</div>
		 				</div>
	 					<?php
	 				}
	 			?>
 			</div>
 			<div class="clear"></div>
 		</div>
 		<?php
 	}
 	public static function get_wp_http_referer(){
	    	$url = wp_unslash( $_SERVER['REQUEST_URI']);
	    	$parsed = parse_url($url);
	    	$query = $parsed['query'];
	    	parse_str($query, $params);
	    	unset($params['tab']);
	    	$params["tab"] = "zapier";
	    	$url = http_build_query($params);
	     ?>
	    <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr($url) ?>" />
	    <?php
 	}
 	function get_default_des(){
 		?>
 		<div class="crm-marketing-config-tab">
			<h3><?php esc_html_e("Select Your Marketing Integration","crm-marketing") ?></h3>
			<?php 
				esc_html_e("Select your email marketing service provider or CRM from the options on the left. If you don't see your email marketing service listed, then let us know and we'll do our best to get it added as fast as possible.","crm-marketing");
			?>
			<p><?php esc_html_e("List plugins support","crm-marketing") ?></p>
			<ul>
				<li><a target="_blank" href="https://codecanyon.net/collections/11044079-contact-form-7-crm"><?php esc_html_e("Contact Form 7")  ?></a></li>
				<li><a target="_blank" href="https://codecanyon.net/collections/11044081-gravity-forms-crm"><?php esc_html_e("Gravity Forms")  ?></a></li>
				<li><a target="_blank" href="https://codecanyon.net/collections/11044082-wpforms-crm/"><?php esc_html_e("WPForms")  ?></a></li>
				<li><a target="_blank" href="https://codecanyon.net/collections/11044083-ninja-forms-crm"><?php esc_html_e("Ninja Forms")  ?></a><li>
				<li><a target="_blank" href="https://codecanyon.net/collections/11044085-elementor-form-crm"><?php esc_html_e("Elementor Form")  ?></a><li>
				<li><a target="_blank" href="https://codecanyon.net/collections/11044086-woocommerce-crm/"><?php esc_html_e("WooCommerce")  ?></a><li>
			</ul>
		</div>
 		<?php
 	}
 	function get_default_des_add_on(){
 		?>
 		<div>
 			<?php esc_html_e("We're sorry, the addon is not available on your plan.","crm-marketing") ?>
 			<a href="https://codecanyon.net/user/rednumber/portfolio" target="_blank"><img src="https://public-assets.envato-static.com/assets/logos/envato_market-a5ace93f8482e885ae008eb481b9451d379599dfed24868e52b6b2d66f5cf633.svg"></a>
 		</div>
 		<?php
 	}
 	function list_add_ons(){
 		return array(
 			"Woocommerce"  => "https://woocommerce.com/",
 			"Bookly"  => "https://codecanyon.net/item/bookly-booking-plugin-responsive-appointment-booking-and-scheduling/7226091",
 			"Contact Form 7"=>"https://contactform7.com/",
 			"Gravity Forms"=>"https://www.gravityforms.com/",
 			"Ninja Forms"=>"https://ninjaforms.com/",
 			"WPForms" => "https://wpforms.com/"
 		);
 	}
 	public static function map_fields($datas,$form_id,$i, $properties="",$list_fields="", $webhook_field="text", $form_field = "text"){
		if( isset($datas) ){
			$lists = $datas[$i]["map_fields"];
			$lists = self::deslash($lists);
		}else{
			$lists = array();
		}
		$class_webhook_text = "";
		$class_webhook_select = "hidden";
		if($webhook_field != "text" ){
			$class_webhook_text ="hidden";
			$class_webhook_select ="";
		}
		$class_form_text = "";
		$class_form_select = "hidden";
		if($form_field != "text" ){
			$class_form_text ="hidden";
			$class_form_select ="";
		}
		?>
		<tr valign="top" class="map_fields">
	        <th scope="row"><?php esc_html_e("Map Form Fields","crm-marketing") ?></th>
	        <td>
	        	<!-----------------Data repeater----------------->
	        	<?php if( $datas == null ) { ?>
	        	<div class="data-map-field crm-data-remove">
        			<div class="map-fields-key">
	        			<input class="<?php echo esc_attr($class_webhook_text) ?>" type="text" name="remove_key_map_fields[crm_change_key][webhook][]" value="">
	        			<select class="crm-input-sync <?php echo esc_attr($class_webhook_select) ?>">
	        				<option></option>
	        				<?php foreach($properties as $k => $v ){
	        					?>
	        					<option value="<?php echo esc_attr($k) ?>"><?php echo esc_html($v) ?></option>
	        					<?php	
	        				} ?>
	        				<option value="enter_value"><?php esc_html_e("Custom Value","crm-marketing") ?></option>
	        			</select>
	        		</div>
	        		<div class="map-fields-value">
	        			<input class="<?php echo esc_attr($class_form_text) ?>" type="text" name="remove_key_map_fields[crm_change_key][form][]" value="">
	        			<select class="crm-input-sync <?php echo esc_attr($class_form_select) ?>">
	        				<option></option>
	        				<?php 
	        				foreach($list_fields as $k => $v ){
	        					?>
	        					<option value="<?php echo esc_attr($k) ?>"><?php echo esc_html($v) ?></option>
	        					<?php	
	        				} ?>
	        				<option value="enter_value"><?php esc_html_e("Custom Value","crm-marketing") ?></option>
	        			</select>
	        		</div>
	        		<div class="map-fields-action"><a class="remove" href="#"><?php esc_html_e("Remove","crm-marketing") ?></a></div>
        		</div>
        	   <?php } ?>
        		<!-----------------End Data repeater----------------->
	        	<div class="crm-martketing-map-fields">
	        		<div class="crm-martketing-map-fields-row">
		        		<div class="map-fields-value">
		        			<?php esc_html_e("Webhook key","crm-marketing") ?>
		        		</div>
		        		<div class="map-fields-key">
		        			<?php esc_html_e("Form Fields","crm-marketing") ?>
		        		</div>
		        		<div class="map-fields-action"><?php esc_html_e("Action","crm-marketing") ?></div>
	        		</div>
	        		<?php
	        			if( count($lists) > 0 ){
	        				$j=0;
	        				foreach( $lists["webhook"] as $webhook_k ){
	        					$check_enter_value = false;
	        					$attr_select = "";
	        					$attr_select_key ='selected';
	        					$class_form_text_custom = $class_form_text;
	        					$class_form_text_custom1 = $class_form_text;
	        					foreach($list_fields as $k => $v ){ 
	        						if( $k == $lists["form"][$j] ){
	        							$check_enter_value = true;
	        							break;
	        						}
	        					}
	        					if( $check_enter_value == false){
		        					$attr_select = 'selected';
		        					$class_form_text_custom = "";
		        				}
		        				if($attr_select_key != "" ){
		        					foreach($properties as $k => $v ){ 
		        						if( $k == $webhook_k ){
		        							$attr_select_key = "";
		        							break;
		        						}
		        					}
		        				}
		        				if( $attr_select_key == "selected"){
		        					$class_form_text_custom1 ="";
		        				}
	        				?>
	        				<div class="crm-martketing-map-fields-row">
				        		<div class="map-fields-key">
				        			<input class="<?php echo esc_attr($class_form_text_custom1) ?>" type="text" name="map_fields[<?php echo esc_attr($i) ?>][webhook][]" value="<?php echo esc_attr($webhook_k) ?>">
				        			<select class="crm-input-sync <?php echo esc_attr($class_webhook_select) ?>">
				        				<option></option>
				        				<?php 
				        					foreach($properties as $k => $v ){ 
					        					?>
					        					<option <?php selected($k,$webhook_k) ?> value="<?php echo esc_attr($k) ?>"><?php echo esc_html($v) ?></option>
					        					<?php	
					        				} ?>
				        				<option <?php echo esc_attr($attr_select_key) ?>  value="enter_value"><?php esc_html_e("Custom Value","crm-marketing") ?></option>
				        			</select>
				        		</div>
				        		<div class="map-fields-value">
				        			<input class="<?php echo esc_attr($class_form_text_custom) ?>" type="text" name="map_fields[<?php echo esc_attr($i) ?>][form][]" value="<?php echo esc_attr($lists["form"][$j]) ?>">
				        			<select class="crm-input-sync <?php echo esc_attr($class_form_select) ?>">
				        				<option></option>
				        				<?php 
				        				foreach($list_fields as $k => $v ){
				        					?>
				        					<option <?php selected($k,$lists["form"][$j]) ?>  value="<?php echo esc_attr($k) ?>"><?php echo esc_html($v) ?></option>
				        					<?php	
				        				} 
				        				?>
				        				<option <?php echo esc_attr($attr_select) ?>  value="enter_value"><?php esc_html_e("Custom Value","crm-marketing") ?></option>
				        			</select>
				        		</div>
				        		<div class="map-fields-action"><a class="remove" href="#"><?php esc_html_e("Remove","crm-marketing") ?></a></div>
			        		</div>
	        				<?php
	        				$j++;
	        				}
	        			}
	        		 ?>
			        <div class="crm-add-map-field-container"><a href="#" class="crm-add-map-field"><?php esc_html_e("Add Field","crm-marketing") ?></a></div>
	        	</div>
	        </td>
        </tr>
		<?php
	}
	public static function deslash($lists){
		$data_webhook = array_map('stripslashes_deep', $lists["webhook"]);
		$data_form = array_map('stripslashes_deep', $lists["form"]);
		return array("webhook"=>$data_webhook,"form"=>$data_form);
	}
	public static function render_html_form($htmls){
		?>
		<table class="form-table">
			<?php foreach($htmls as $name =>$attrs ) {
				$attr = shortcode_atts( array(
					'type' => 'text',
					'label' => '',
					'des' => '',
					'value' => '',
					'select_options' => array(),
					'required' => false,
				), $attrs );
				if( $attr["required"] ) {
					$required_text = "*";
				}else{
					$required_text = "";
				}				
				?>
			<tr valign="top" >
	        	<th scope="row">
	        		<?php 
	        			echo esc_html($attr["label"]); 
	        			echo esc_html($required_text)
	        		?>
	        		</th>
		        <td>
		        	<?php 
		        	switch ($attr["type"]) {
		        		case 'hr':
		        			?>
		        			<hr>
		        			<?php
		        			break;
		        		case 'checkbox':
		        			?>
		        			<input <?php if( $attr["value"] != "" ){ echo esc_attr("checked");} ?>  type="checkbox" name="<?php echo esc_attr($name) ?>"> <?php 
		        			break;
		        		case 'checkbox_array':
		        			?>
		        			<ul>
		        				<?php 
		        				foreach( $attr["select_options"] as $k => $v ){
		        					$value_array = array();
		        					if( is_array($attr["value"])){
		        						$value_array = $attr["value"];
		        					}
		        				?>
		        				<li><input <?php if( in_array($k,$value_array) ){ echo esc_attr("checked");} ?>  type="checkbox" name="<?php echo esc_attr($name) ?>[]" value="<?php echo esc_attr($k) ?>"> <?php echo esc_html($v) ?></li>
		        			<?php } ?>
		        			</ul>
		        			<?php 
		        			break;
		        		case 'select':
		        			Rednumber_Marketing_CRM_backend::add_select_seletor($name,$attr["select_options"],$attr["value"]);
		        			break;
		        		case 'textarea':
		        			Rednumber_Marketing_CRM_backend::add_textarea_seletor($name,$attr["value"]);
		        			break;
		        		default:
		        			Rednumber_Marketing_CRM_backend::add_number_seletor($name,$attr["value"]);
		        			break;
		        	}
		        	if( $attr["des"] != "" ){
		        		if( $attr["type"] == "checkbox" || $attr["type"] == "checkbox_array"){
		        			echo esc_html($attr["des"]);
		        		}else{
		        		?>
		        		<p class="crm-marketing-des"><?php echo esc_html($attr["des"]) ?></p>
		        		<?php
		        		}
		        	}
		        	?>
		        </td>
        	</tr>
        <?php } ?>
		</table>
		<?php
	}
	public static function add_textarea_seletor($name,$value){
		?>
		<div class="crm-marketing-merge-tags-container">
			<textarea name="<?php echo esc_attr($name) ?>" class="large-text code code-selector" ><?php echo ($value) ?></textarea>
			<span class="dashicons dashicons-shortcode crm-merge-tags"></span>
		</div>
		<?php
	}
	public static function add_number_seletor($name,$value){
		?>
		<div class="crm-marketing-merge-tags-container">
			<input value="<?php echo esc_attr($value) ?>" type="text" name="<?php echo esc_attr($name) ?>" class="regular-text code-selector" >
			<span class="dashicons dashicons-shortcode crm-merge-tags"></span>
		</div>
		<?php
	}
	public static function add_select_seletor($name,$value=array(),$selected="",$empty = true){
		?>
		<div class="crm-marketing-merge-tags-container-select">
			<select name="<?php echo esc_attr($name) ?>">
				<?php if($empty) {
					echo '<option></option>';
				}
				foreach( $value as $k => $v ){
					?>
					<option <?php selected($selected,$k) ?> value="<?php echo esc_attr($k) ?>"><?php echo esc_attr($v) ?></option>
					<?php
				}
				 ?>
			</select>
		</div>
		<?php
	}
	public static function get_datas_contact_form($plugins=array()){
		$submits = array();
		$attr = shortcode_atts( array(
					'type' => 'text',
					'datas' => array(),
					'form' => '',
					'plugin'=>'',
					'order' => '',
					'order_od' => '',
					'add' => array(),
					'add_ons'=>array()
				), $plugins );
		$form_data = $attr["form"];
		foreach( $attr["datas"] as $k => $v ){
			if( $k == "enable") {
				continue;
			}
			if( $v != ""){
				switch ($attr["plugin"] ){
					case "contact_form_7":
						//done
						if( !is_array($v) ){	
							$v = apply_shortcodes($v,true);
							$value = wpcf7_mail_replace_tags($v);
							$submits[$k] = $value;
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								$value = wpcf7_mail_replace_tags($new_value);
								$new_values[$new_key] = $value;
							}
							$submits[$k] = $new_values;
						}
						break;
					case "elementor":
						//done
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode($v);
							if( isset( $form_data[$value] )){
								$value =$form_data[$value] ;
							}else{
								$value = $v;
							}
							$submits[$k] = $value;
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								$value = Rednumber_Marketing_CRM_Backend_Form_Widget::get_id_shortcode($new_value);
								if( isset( $form_data[$value] )){
									$value =$form_data[$value] ;
								}else{
									$value = $new_value;
								}
								$new_values[$new_key] = $value;
							}
							$submits[$k] = $new_values;
						}	
						break;
					case "gravityforms":
						//done
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							$value = GFCommon::replace_variables( $v, $attr["add_ons"]["form"], $attr["add_ons"]["leads"], false, false,false,"text" );
							$submits[$k] = $value;
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								$new_values[$new_key]  = GFCommon::replace_variables( $new_value, $attr["add_ons"]["form"], $attr["add_ons"]["leads"], false, false,false,"text" );
							}
							$submits[$k] = $new_values;
						}
						break;
					case "ninjaforms":
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							$value = str_replace(array("{","}"),"",$v);
					        $values = explode(":",$value);
					        if( count($values) > 1 ){
			        			$value = $values[1];
			        		}
							if( isset( $form_data[$value] )){
								$value =$form_data[$value] ;
							}else{
								$value = $v;
							}
							$submits[$k] = $value;
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								$value = str_replace(array("{","}"),"",$new_value);
								$values = explode(":",$value);
								if( count($values) > 1 ){
				        			$value = $values[1];
				        		}
								if( isset( $form_data[$value] )){
									$value =$form_data[$value] ;
								}else{
									$value = $new_value;
								}
								$new_values[$new_key] = $value;
							}
							$submits[$k] = $new_values;
						}
						break;
					case "woocommerce":
						
						if( !is_array($v) ){
							
							$v = apply_shortcodes($v,true);
							$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($attr["order_id"],$v,$attr["order"]);
							
							if( $value != "" ){
								$submits[$k] = $value;
							}else{
								$submits[$k] = $v;
							}
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								//var_dump($new_value);
								$new_value = apply_shortcodes($new_value,true);
								$value = Rednumber_Marketing_CRM_Backend_Woocommerce::shortcode_main($attr["order_id"],$new_value,$attr["order"]);
								//var_dump($new_value);
								if( $value != "" ){ 
									$new_values[$new_key] = $value;
								}else{
									$new_values[$new_key] = $new_value;
								}
							}
							$submits[$k] = $new_values;
						}
						break;
					case "wpforms":
						//done
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							$submits[$k] =wpforms_process_smart_tags($v,$attr["datas"],$attr["add_ons"]["fields"],$attr["add_ons"]["entry_id"]);
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								$new_values[$new_key] = wpforms_process_smart_tags($new_value,$attr["datas"],$attr["add_ons"]["fields"],$attr["add_ons"]["entry_id"]);	
							}
							if( count($new_values) > 0 ){
								$submits[$k] = $new_values;
							}
						}
						break;
					case "bookly":
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							$value = $form_data->replace( $v );
							$submits[$k] = $value;
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								$value = $form_data->replace( $new_value );
								if($value != "") {
									$new_values[$new_key] = $value;
								}
							}
							if( count($new_values) > 0 ){
								$submits[$k] = $new_values;
							}
						}
						break;
					case "eform":
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							if( isset( $form_data[$v] )){
								$value =$form_data[$v] ;
							}else{
								$value = $v;
							}
							if($value != "") {
								$submits[$k] = $value;
							}
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								if( isset( $form_data[$new_value] )){
									$value =$form_data[$new_value] ;
								}else{
									$value = $new_value;
								}
								if($value != "") {
									$new_values[$new_key] = $value;
								}
							}
							if( count($new_values) > 0 ){
								$submits[$k] = $new_values;
							}
						}
						break;
					case "fluent_forms":
						$v = str_replace(array("{","}"),"",$v);
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							if( isset( $form_data[$v] )){
								$value =$form_data[$v] ;
							}else{
								$value = $v;
							}
							if($value != "") {
								$submits[$k] = $value;
							}
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								if( isset( $form_data[$new_value] )){
									$value =$form_data[$new_value] ;
								}else{
									$value = $new_value;
								}
								if($value != "") {
									$new_values[$new_key] = $value;
								}
							}
							if( count($new_values) > 0 ){
								$submits[$k] = $new_values;
							}
						}
						break;
					case "formidable_forms":
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							if( isset( $form_data[$v] )){
								$value =$form_data[$v] ;
							}else{
								$value = $v;
							}
							if($value != "") {
								$submits[$k] = $value;
							}
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								if( isset( $form_data[$new_value] )){
									$value =$form_data[$new_value] ;
								}else{
									$value = $new_value;
								}
								if($value != "") {
									$new_values[$new_key] = $value;
								}
							}
							if( count($new_values) > 0 ){
								$submits[$k] = $new_values;
							}
						}
						break;
					case "forminator":
						if( !is_array($v) ){
							$v = apply_shortcodes($v,true);
							if( isset( $form_data[$v] )){
								$value =$form_data[$v] ;
							}else{
								$value = $v;
							}
							if($value != "") {
								$submits[$k] = $value;
							}
						}else{
							$new_values = array();
							foreach($v as $new_key=>$new_value){
								$new_value = apply_shortcodes($new_value,true);
								if( isset( $form_data[$new_value] )){
									$value =$form_data[$new_value] ;
								}else{
									$value = $new_value;
								}
								if($value != "") {
									$new_values[$new_key] = $value;
								}
							}
							if( count($new_values) > 0 ){
								$submits[$k] = $new_values;
							}
						}
						break;
				}
				//maps fields current
				if( is_array($attr["add"]) && count($attr["add"])> 0 ) {
					$value = str_replace(array("[","]"),"",$v);
					if( !is_array($value)){
						if( array_key_exists( $value,$attr["add"])){  
							$submits[$k] = $attr["add"][$value];
						}
					}
					
				}
			}
		}
		return $submits;
	}
}
new Rednumber_Marketing_CRM_backend;