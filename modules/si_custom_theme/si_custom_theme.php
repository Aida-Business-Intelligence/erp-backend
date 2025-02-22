<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Dynamic Customized Theme
Description: Advanced Theme that will provide predefined templates and easy customization as per your choice. 
Author: Sejal Infotech
Author URI: http://www.sejalinfotech.com
Version: 1.0.2
Requires at least: 2.3.*
*/

define('SI_CUSTOM_THEME_MODULE_NAME', 'si_custom_theme');
define('SI_CUSTOM_THEME_VALIDATION_URL','http://www.sejalinfotech.com/perfex_validation/index.php');
define('SI_CUSTOM_THEME_KEY','c2lfY3VzdG9tX3RoZW1l');
define('SI_CUSTOM_THEME_UPLOAD_PATH', APP_MODULES_PATH . SI_CUSTOM_THEME_MODULE_NAME.'/uploads/');

$CI = &get_instance();
hooks()->add_action('app_admin_head', 'si_custom_theme_hook_admin_head');
hooks()->add_action('app_customers_head', 'si_custom_theme_hook_app_customers_head');
hooks()->add_action('app_external_form_head', 'si_custom_theme_hook_load_partial');
hooks()->add_action('app_admin_authentication_head', 'si_custom_theme_hook_load_partial');
hooks()->add_filter('module_'.SI_CUSTOM_THEME_MODULE_NAME.'_action_links', 'module_si_custom_theme_action_links');
hooks()->add_action('admin_init', 'si_custom_theme_hook_admin_init');
hooks()->add_action('settings_tab_footer','si_custom_theme_hook_settings_tab_footer');#for perfex low version V2.4 
hooks()->add_action('settings_group_end','si_custom_theme_hook_settings_tab_footer');#for perfex high version V2.8.4

/**
* Load the module helper
*/
$CI->load->helper(SI_CUSTOM_THEME_MODULE_NAME . '/si_custom_theme');

/**
* Load the module model
*/
$CI->load->model(SI_CUSTOM_THEME_MODULE_NAME . '/si_custom_theme_model');

/**
* Register activation module hook
*/
register_activation_hook(SI_CUSTOM_THEME_MODULE_NAME, 'si_custom_theme_activation_hook');
function si_custom_theme_activation_hook()
{
	$CI = &get_instance();
	require_once(__DIR__ . '/install.php');
}
/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(SI_CUSTOM_THEME_MODULE_NAME, [SI_CUSTOM_THEME_MODULE_NAME]);

function module_si_custom_theme_action_links($actions)
{
   
	if(get_option(SI_CUSTOM_THEME_MODULE_NAME.'_activated') && get_option(SI_CUSTOM_THEME_MODULE_NAME.'_activation_code')!='')
		$actions[] = '<a href="' . admin_url('si_custom_theme') . '">' . _l('settings') . '</a>';
	else
           	$actions[] = '<a href="' . admin_url('settings?group=si_custom_theme_settings') . '">' . _l('si_ct_settings_validate') . '</a>';	
	//$actions[] = '<a href="' . admin_url('si_custom_theme') . '">' . _l('settings') . '</a>';

	return $actions;
}
function si_custom_theme_hook_settings_tab_footer($tab)
{
	if($tab['slug']=='si_custom_theme_settings' && !get_option(SI_CUSTOM_THEME_MODULE_NAME.'_activated')){
		echo '<script src="'.module_dir_url('si_custom_theme','assets/js/si_custom_theme_settings_footer.js').'"></script>';
	}
}
/**
* Admin side styles will be set
* @return null
*/
function si_custom_theme_hook_admin_head()
{
	si_custom_theme_render(['general', 'texts', 'tabs', 'buttons', 'admin', 'modals', 'tags'],get_option(SI_CUSTOM_THEME_MODULE_NAME.'_default_theme'));
	si_ct_bg_image_render();
	if(get_option(SI_CUSTOM_THEME_MODULE_NAME.'_default_theme')!=1)
		echo '<link href="'.module_dir_url('si_custom_theme','assets/css/si_custom_theme_style.css').'" rel="stylesheet" />';
	si_custom_theme_custom_css('si_custom_theme_custom_admin_area');	
}
/**
* Clients side theme style
* @return null
*/
function si_custom_theme_hook_app_customers_head()
{
	$theme_id = get_option(SI_CUSTOM_THEME_MODULE_NAME.'_default_clients_theme');
	si_custom_theme_render(['general', 'texts', 'tabs', 'buttons', 'customers', 'modals'],$theme_id);
	si_ct_bg_image_render();
	if(get_option(SI_CUSTOM_THEME_MODULE_NAME.'_default_clients_theme')!=1)
		echo '<link href="'.module_dir_url('si_custom_theme','assets/css/si_custom_theme_style_client.css').'" rel="stylesheet" />';
	si_custom_theme_custom_css('si_custom_theme_custom_clients_area');
}
/**
* Custom CSS
* @param  string $main_area clients or admin area options
* @return null
*/
function si_custom_theme_custom_css($main_area)
{
	$clients_or_admin_area             = get_option($main_area);
	$custom_css_admin_and_clients_area = get_option('si_custom_theme_custom_clients_and_admin_area');
	if (!empty($clients_or_admin_area) || !empty($custom_css_admin_and_clients_area)) {
		echo '<style id="si_custom_theme_custom_css">' . PHP_EOL;
		if (!empty($clients_or_admin_area)) {
			$clients_or_admin_area = clear_textarea_breaks($clients_or_admin_area);
			echo ($clients_or_admin_area) . PHP_EOL;
		}
		if (!empty($custom_css_admin_and_clients_area)) {
			$custom_css_admin_and_clients_area = clear_textarea_breaks($custom_css_admin_and_clients_area);
			echo ($custom_css_admin_and_clients_area) . PHP_EOL;
		}
		echo '</style>' . PHP_EOL;
	}
}
/**
* General and buttons styles when no login
* @return null
*/
function si_custom_theme_hook_load_partial()
{
	si_custom_theme_render(['general', 'texts', 'buttons']);
	si_ct_bg_image_render();
}
/**
* Init module menu items in setup
* @return null
*/
function si_custom_theme_hook_admin_init()
{
	#Add customer permissions
	$capabilities = [];
	$capabilities['capabilities'] = [
		'view'   => _l('permission_view'),
	];
	register_staff_capabilities('si_custom_theme', $capabilities, _l('si_custom_theme'));
	$CI = &get_instance();
	if(get_option(SI_CUSTOM_THEME_MODULE_NAME.'_activated') && get_option(SI_CUSTOM_THEME_MODULE_NAME.'_activation_code')!=''){
		if (is_admin() || has_permission('si_custom_theme', '', 'view')) {
			$CI->app_menu->add_setup_menu_item('si-custom-theme', [
				'href'     => admin_url('si_custom_theme'),
				'name'     => _l('si_custom_theme'),
				'position' => 60,
			]);
		}
	}
	/**  Add Tab In Settings Tab of Setup **/
	if (is_admin() || has_permission('settings', '', 'view')) {
		$CI->app_tabs->add_settings_tab('si_custom_theme_settings', [
			'name'     => _l('si_ct_settings'),
			'view'     => 'si_custom_theme/si_custom_theme_settings',
			'position' => 60,
		]);
	}
}