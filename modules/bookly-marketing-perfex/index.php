<?php
/*
Plugin Name: Bookly - Perfex CRM Integration
Plugin URI: https://codecanyon.net/user/rednumber/portfolio
Description: Perfex CRM has the ability to import/capture leads from the Bookly
Text Domain: bookly-marketing-perfex
Domain Path: /languages
Author: Rednumber
Version: 2.0.1
Core Builder CRM: 1.0.6
Author URI: https://codecanyon.net/user/rednumber/portfolio
*/
define( 'REDNUMBER_MARKETING_CRM_BOOKYLY_PERFEX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if(!class_exists('Rednumber_Marketings_CRM_Init')) { 
    if(!defined('REDNUMBER_MARKETING_CRM_PLUGIN_PATH')) {
        define( 'REDNUMBER_MARKETING_CRM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    }
    if(!defined('REDNUMBER_MARKETING_CRM_PLUGIN_URL')) {
        define( 'REDNUMBER_MARKETING_CRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    }
    class Rednumber_Marketings_CRM_Init{
        function __construct(){
            register_activation_hook( __FILE__, array($this,'plugin_activation') );
            foreach (glob(REDNUMBER_MARKETING_CRM_PLUGIN_PATH."backend/*.php") as $filename){
                include $filename;
            }
        }
        function plugin_activation(){
            global $wpdb;
            include_once(ABSPATH.'wp-admin/includes/plugin.php');
            $table_name_log = $wpdb->prefix.'crm_logs';
            $table_name = $wpdb->prefix.'crm_marketings';
            if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
                $charset_collate = $wpdb->get_charset_collate();
                $sql = "CREATE TABLE $table_name (
                    id INT NOT NULL AUTO_INCREMENT,
                    type VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    form_id VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    datas TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    add_on VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                $sql_log = "CREATE TABLE $table_name_log (
                    id INT NOT NULL AUTO_INCREMENT,
                    type VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    form_id VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    datas TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    add_on VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    action VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL,
                    PRIMARY KEY  (id)
                ) $charset_collate;";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
                dbDelta( $sql_log );
            }
        }
    }
    new Rednumber_Marketings_CRM_Init;
}
if(!class_exists('Rednumber_Marketing_CRM_Backend_Bookly')) {  
    include REDNUMBER_MARKETING_CRM_BOOKYLY_PERFEX_PLUGIN_PATH."modules/bookly/backend.php"; 
}
if(!class_exists('Rednumber_Marketing_CRM_Frontend_Perfex_Bookly')) {  
   include REDNUMBER_MARKETING_CRM_BOOKYLY_PERFEX_PLUGIN_PATH."modules/bookly/perfex.php"; 
}
if(!class_exists('Rednumber_Marketing_CRM_Perfex')) { 
    include REDNUMBER_MARKETING_CRM_BOOKYLY_PERFEX_PLUGIN_PATH."includes/perfex.php"; 
    include REDNUMBER_MARKETING_CRM_BOOKYLY_PERFEX_PLUGIN_PATH."includes/perfex_api.php"; 
}
register_activation_hook( __FILE__, array("Rednumber_Marketing_CRM_Backend_Bookly","add_hook_bookly") );
if(!class_exists('Rednumber_Addons')) {  
    include REDNUMBER_MARKETING_CRM_BOOKYLY_PERFEX_PLUGIN_PATH."add-ons.php"; 
}