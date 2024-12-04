<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: TGXCAPITAL
Description: Insercao de Vat pra clientes
Version: 1.0.0
*/
    

define('VAT_DETAILS_MODULE_NAME', 'tgxcapital');
//define('VAT_DETAILS_MODULE_UPLOAD_FOLDER', module_dir_path(BSSI_MAIL_MODULE_NAME, 'uploads'));

$CI = &get_instance();



/**
 * Register the activation 
 */
register_activation_hook(VAT_DETAILS_MODULE_NAME, 'tgxcapital_activation_hook');

/**
 * The activation function
 */
function tgxcapital_activation_hook()
{
  
    
    require(__DIR__ . '/install.php');
}
/**
 * Register new menu item in admin sidebar menu
 */
//if (staff_can('view', VAT_DETAILS_MODULE_NAME)) {

/*

$CI->app_menu->add_sidebar_menu_item('clients', [
            'name'     => _l('Rede linear'),
            'position' => 3,
            'icon'     => 'fa fa-list',
            'href'     => admin_url('clients?group=28'),
            'icon'     => 'fa fa-users',
        ]);
 * 
 */
        $CI->app_tabs->add_customer_profile_tab('linear', [
            'name'     => 'Rede linear',
            'href'     => admin_url('tgxcapital/Vat_admin_view'),
             'view'     => 'tgxcapital/linear',
            'icon'     => 'fa fa-list',
            'position' => 2,
        ]);
        
         $CI->app_tabs->add_customer_profile_tab('binario', [
            'name'     => 'Rede Binaria',
            'href'     => admin_url('tgxcapital/binario'),
             'view'     => 'tgxcapital/binario',
            'icon'     => 'fa fa-users',
            'position' => 2,
        ]);
         
         //si_custom_theme/si_custom_theme_settings
       
        
//        $CI->app_tabs->add_customer_profile_tab('entity_details', [
//            'name'     => 'Tags',
//            'view'     => '#',
//            'href'     => '#',
//            'icon'     => 'fa fa-users',
//            'position' => 3,
//        ]);
//         $CI->app_tabs->add_customer_profile_tab('onboard', [
//            'name'     => 'Onboard',
//            'view'     => 'admin/clients/groups/onboard',
//            'href'     => admin_url('vat_details/Entity_admin_view'),
//            'icon'     => 'fa fa-users',
//            'position' => 0,
//        ]);
        
        
      
//        $CI->app_menu->add_sidebar_menu_item('entity_details', [
//            'name'     => _l('Task Default'),
//            'position' => 3,
//            'icon'     => 'fa fa-list',
//            'href'     => admin_url('vat_details/VatDetails'),
//            'icon'     => 'fa fa-cogs',
//        ]);
    
//}

