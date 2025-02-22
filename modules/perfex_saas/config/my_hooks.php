<?php

defined('BASEPATH') or exit('No direct script access allowed');
require_once(__DIR__ . '/../helpers/perfex_saas_middleware_helper.php');

/**
 * Detect the global tenant and define the database credential or use default db credentials.
 * We have to run this detection here as the stored DB credentials are ecnrypted and thus we need the Encryption library to decrypt.
 * Encryption library can not be in config because of race effect (db_prefix function by perfex) when loading at such early time.
 * Thus we move the segments here. 
 */
$GLOBALS['_encryption'] = load_class('Encryption');
$dsn = ['host' => '', 'user' => '', 'password' => '', 'dbname' => ''];

// Check if the its a tenant and use the tenant dsn
if (isset($GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant']))
    $dsn = (array)perfex_saas_parse_dsn($GLOBALS['_encryption']->decrypt($GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant']->dsn));

// Define database credentials
define('APP_DB_HOSTNAME', empty($dsn['host']) ? APP_DB_HOSTNAME_DEFAULT : $dsn['host']);
define('APP_DB_USERNAME', empty($dsn['user']) ? APP_DB_USERNAME_DEFAULT : $dsn['user']);
define('APP_DB_PASSWORD', empty($dsn['password']) ? APP_DB_PASSWORD_DEFAULT : $dsn['password']);
define('APP_DB_NAME', empty($dsn['dbname']) ? APP_DB_NAME_DEFAULT : $dsn['dbname']);

// Run middlewares for the tenant. i.e permission and module control.
perfex_saas_middleware();

// Early time hooks for email template. 
// Must be placed here in hooks to ensure its loaded with perfex email template loading.
hooks()->add_filter('register_merge_fields', 'perfex_saas_email_template_merge_fields');
function perfex_saas_email_template_merge_fields($fields)
{
    $fields[] =  'perfex_saas/merge_fields/perfex_saas_company_merge_fields';
    return $fields;
}
