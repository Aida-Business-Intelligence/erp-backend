<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add default setting options 
add_option('perfex_saas_enable_auto_trial', '1');
add_option('perfex_saas_autocreate_first_company', '1');
add_option('perfex_saas_reserved_slugs', 'www,app,deal,controller,master,ww3,hack');
add_option('perfex_saas_control_client_menu', '1');


// Create saas module tables
// Create packages table for managing saas packages
if (!$CI->db->table_exists(perfex_saas_table('packages'))) {
    $CI->db->query(
        "CREATE TABLE IF NOT EXISTS `" . perfex_saas_table('packages') . "` (
            `id` int NOT NULL AUTO_INCREMENT,
            `name` varchar(255) DEFAULT NULL,
            `description` tinytext,
            `slug` varchar(255) DEFAULT NULL,
            `price` decimal(10,2) DEFAULT '0.00',
            `bill_interval` varchar(255) DEFAULT NULL,
            `is_default` int NOT NULL DEFAULT '0',
            `is_private` int NOT NULL DEFAULT '0',
            `db_scheme` varchar(50) DEFAULT NULL,
            `db_pools` text,
            `status` int NOT NULL DEFAULT '1',
            `modules` text,
            `metadata` text COMMENT 'Extra data such as modules that are shown on package view list',
            `trial_period` int DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`)
          ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";"
    );
}

// Create companines table for managing instances created
if (!$CI->db->table_exists(perfex_saas_table('companies'))) {
    $CI->db->query(
        "CREATE TABLE IF NOT EXISTS `" . perfex_saas_table('companies') . "` (
            `id` int NOT NULL AUTO_INCREMENT,
            `clientid` int NOT NULL,
            `slug` varchar(30) NOT NULL,
            `name` varchar(100) NOT NULL,
            `status` enum('active','inactive','disabled','banned','pending') NOT NULL DEFAULT 'pending',
            `dsn` text,
            `custom_domain` VARCHAR(255) DEFAULT NULL,
            `metadata` text COMMENT 'Extra data',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`)
          ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";"
    );
}

// Add package relation column to invoices
if (!$CI->db->field_exists(perfex_saas_column('packageid'), db_prefix() . 'invoices')) {

    $CI->db->query(
        "ALTER TABLE `" . db_prefix() . "invoices` ADD `" . perfex_saas_column('packageid') . "` INT NULL DEFAULT NULL AFTER `subscription_id`;"
    );
}




// Add email templates
$deployed_template = [
    'type' => 'client',
    'slug' => 'company-instance-deployed',
    'name' => 'Deployed CRM instance',
    'subject' => 'Company CRM Instance created successfully',
    'message' => 'Dear {contact_firstname},<br/><br/>
    I am writing to inform you that we have successfully deployed a CRM instance for your company <b>{instance_name}</b>. 
    <br/><br/>
    You can access your instance at <b>{instance_admin_url}</b>. Kindly log in with your current email and the respective password. 
    <br/><br/>
    Your customer can access from <b>{instance_url}</b>.<br/><br/>
    Please let us know if you have any questions or concerns. We are always happy to help.<br/><br/>
    
    Best regards,<br/>
    {email_signature}'
];

$removed_template = [
    'type' => 'client',
    'slug' => 'company-instance-removed',
    'subject' => 'Company CRM Instance removed',
    'name' => 'Removed CRM instance',
    'message' => '
    Dear {contact_firstname},<br/><br/>
    
    I am writing to inform you that your company <b>{instance_name}</b> has been removed successfully. 
    <br/><br/>
    If this is not from you or your staff, kindly reach out to us.
    <br/><br/>
    Best regards,<br/>
    {email_signature}'
];

$deployed_template_for_admin = [
    'type' => 'staff',
    'slug' => 'company-instance-deployed-for-admin',
    'name' => 'Deployed CRM instance for admin',
    'subject' => 'A CRM Instance was deployed',
    'message' => 'Dear Super Admin,<br/><br/>

    I am writing to inform you that a new instance <b>({instance_name})</b> has been created on your platform for <b>{client_company}</b>. You can check the instance at <b>{instance_url}</b>.
    <br/><br/>
    Best regards.<br/>
    {email_signature}'
];

$removed_template_for_admin = [
    'type' => 'staff',
    'slug' => 'company-instance-removed-for-admin',
    'name' => 'Removed CRM instance for admin',
    'subject' => 'Company CRM Instance removed successfully',
    'message' => 'Dear Super Admin,<br/><br/>

    I am writing to inform you that an instance has been removed from your platform for <b>{client_company}</b>. The name of the instance is <b>{instance_name}</b>.
    <br/><br/>
    Best regards.<br/>
    {email_signature}'
];

$CI->load->model('emails_model');
$templates = [$deployed_template, $deployed_template_for_admin, $removed_template, $removed_template_for_admin];
$fromname = '{companyname} | CRM';
foreach ($templates as $t) {
    //this helper check buy slug and create if not exist by slug
    create_email_template($t['subject'], $t['message'], $t['type'], $t['name'], $t['slug']);
}


// Run installer
perfex_saas_install();
