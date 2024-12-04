<?php
class Rednumber_Marketing_CRM_PDF {
	private static $add_on ="pdf";
	function __construct(){
		add_filter("crm_marketing_config_tag_active",array($this,"add_settings"));
		add_action( 'admin_post_crm_marketing_'.self::$add_on, array($this,"register_detail_settings"));
	}
	function add_settings($lists){
		$lists["pdf"] = array("label"=>esc_html__("PDF Configuration","crm-marketing"),"detail"=>array($this,"form_detail"));
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
		        $orders = new WP_Query( array( 'post_type' => 'pdf_template','post_status' => 'publish','posts_per_page'=>-1 ) );
		        $templates = array();
			    if( $orders->have_posts() ):
			    	$templates[] = array(
											'label' => esc_html__("No Template",'crm-marketing'),
											'value' => '0',
										);
			         while ( $orders->have_posts() ) : $orders->the_post();
			        	$id = get_the_id();
			        	$templates[] = array(
											'label' => "(". esc_html($id) .") ". get_the_title(),
											'value' => esc_html($id),
										);
			        ?>
			        <?php
			         endwhile;
			    else:
			    	$templates[] = array(
											'label' => esc_html__("No Template",'crm-marketing'),
											'value' => '0',
										);
			    endif;
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
					<h3><?php esc_html_e("PDFs Template","crm-marketing") ?></h3>
					<a data-type="<?php echo esc_attr(self::$add_on) ?>" class="crm-marketing-header-addnew button button-primary" href="#"><?php esc_html_e("Add new","crm-marketing") ?></a>
				</div>
				<div class="crm-marketing-container-content">
					<!-----------------Data repeater----------------->
					<div class="crm-marketing-container-content-data hidden">
							<table class="form-table">
						        <tr valign="top">
							        <th scope="row"><?php esc_html_e("PDF Template","crm-marketing") ?></th>
							        <td>
							        	<select name="remove_key_pdfs[]" >
							        		<?php
							        		foreach( $templates as $vl) {
									    	?>
									    	<option value="<?php echo esc_attr($vl["value"]) ?>"><?php echo esc_html($vl["label"]) ?></option>
									    	<?php
									    } ?>
							        	</select>
							        </td>
						        </tr>
						        <tr valign="top">
							        <th scope="row"><?php esc_html_e("PDF Name","crm-marketing") ?></th>
							        <td>
							        	<?php 
							        	Rednumber_Marketing_CRM_backend::add_number_seletor("remove_key_pdfs_name[]","");
							        	$name_default = apply_filters("crm_marketing_pdf_default_".$type,"contact-[ID]");
							        	echo esc_html($name_default);
							        	?>
							        </td>
						        </tr>
						        <?php
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
							        <th scope="row"><?php esc_html_e("Custom PDF Template","crm-marketing") ?></th>
							        <td>
							        	<select name="pdfs[]" >
							        		<?php
							        		foreach( $templates as $vl) {
										    	?>
										    	<option <?php selected($template,$vl["value"]) ?> value="<?php echo esc_attr($vl["value"]) ?>"><?php echo esc_html($vl["label"]) ?></option>
										    	<?php
										    } ?>
							        	</select>
							        </td>
						        </tr>
						        <tr valign="top">
							        <th scope="row"><?php esc_html_e("Custom PDF Name","crm-marketing") ?></th>
							        <td>
							        	<?php 
							        	Rednumber_Marketing_CRM_backend::add_number_seletor("pdfs_name[]",$name);
							        	$name_default = apply_filters("crm_marketing_pdf_default_".$type,"contact-[ID]");
							        	echo esc_html($name_default);
							        	?>
							        </td>
						        </tr>
					        <?php
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
	function register_detail_settings(){
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'crm_marketing_'.self::$add_on ) ) {
		    die('Security check'); 
		} else {
			$add_on = sanitize_text_field($_POST["add_on"]);
			$form_id = sanitize_text_field($_POST["form_id"]);
			$type = sanitize_text_field($_POST["type"]);
			$referer = sanitize_textarea_field($_POST["_wp_http_referer"]);
			$urls = map_deep( $_POST["pdfs"], 'sanitize_text_field' );
			$name = map_deep( $_POST["pdfs_name"], 'sanitize_text_field' );
			$urls=array_map('stripslashes_deep', $urls);
			$name=array_map('stripslashes_deep', $name);
			$i= 0;
			foreach($urls as $url ){
				$datas[] = array("template"=>$url,"name"=>$name[$i]);
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
new Rednumber_Marketing_CRM_PDF;