<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: API CRM
Description: Connect to WordPress plugins
Version: 2.0.0
Author: Rednumber
Author URI: https://codecanyon.net/user/rednumber/portfolio
*/
define('APICRM_MODULE_NAME', 'api_crm');
$CI = &get_instance();
hooks()->add_action('admin_init', 'apicrm_setup_init_menu_items');
register_language_files(APICRM_MODULE_NAME, [APICRM_MODULE_NAME]);

register_activation_hook(APICRM_MODULE_NAME, 'apicrm_activation_hook');
function apicrm_activation_hook(){
    require_once(__DIR__ . '/install.php');
}
function apicrm_setup_init_menu_items()
{
    /**
    * If the logged in user is administrator, add custom menu in Setup
    */
    if (is_admin()) {
        $CI = &get_instance();
        $CI->app_menu->add_sidebar_menu_item('api_crm-options', [
            'collapse' => true,
            'name'     => _l('api_crm'),
            'position' => 40,
            'icon'     => 'fa fa-cogs',
        ]);
        $CI->app_menu->add_sidebar_children_item('api_crm-options', [
            'slug'     => 'apicrm-add-options',
            'name'     => _l('api_crm_manager'),
            'href'     => admin_url('api_crm/manager'),
            'position' => 5,
        ]);
        
    }
}