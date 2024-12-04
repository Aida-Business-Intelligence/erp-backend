<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Define global constants
defined('PERFEX_SAAS_MODULE_NAME') or define('PERFEX_SAAS_MODULE_NAME', 'perfex_saas');
defined('PERFEX_SAAS_TENANT_COLUMN') or define('PERFEX_SAAS_TENANT_COLUMN', 'perfex_saas_tenant_id');
defined('PERFEX_SAAS_ROUTE_ID') or define('PERFEX_SAAS_ROUTE_ID', 'ps'); // @TODO: load this from settings
defined('PERFEX_SAAS_FILTER_TAG') or define('PERFEX_SAAS_FILTER_TAG', 'psaas');
defined('APP_DB_DRIVER') or define('APP_DB_DRIVER', 'mysqli');

// Tenant recognition modes
defined('PERFEX_SAAS_TENANT_MODE_PATH') or define('PERFEX_SAAS_TENANT_MODE_PATH', 'path');
defined('PERFEX_SAAS_TENANT_MODE_DOMAIN') or define('PERFEX_SAAS_TENANT_MODE_DOMAIN', 'custom_domain');
defined('PERFEX_SAAS_TENANT_MODE_SUBDOMAIN') or define('PERFEX_SAAS_TENANT_MODE_SUBDOMAIN', 'subdomain');

/** @var string[] List of options field that will should not be controlled by tenants i.e security fields */
defined('PERFEX_SAAS_ENFORCED_SHARED_FIELDS') or define('PERFEX_SAAS_ENFORCED_SHARED_FIELDS', ['allowed_files']);
