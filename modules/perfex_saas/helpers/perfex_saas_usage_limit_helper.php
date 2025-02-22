<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Map of limitation filters and their corresponding tables.
 */
const PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP = [
    'before_invoice_added' => 'invoices',
    'before_estimate_added' => 'estimates',
    'before_create_credit_note' => 'creditnotes',
    'before_create_proposal' => 'proposals',
    'before_client_added' => 'clients',
    'before_create_contact' => 'contacts',
    'before_create_staff_member' => 'staff',
    'before_add_project' => 'projects',
    'before_add_task' => 'tasks',
    'before_ticket_created' => 'tickets', // has two option $data and $admin
    'before_lead_added' => 'leads'
];

/**
 * Register the limitation filters and their corresponding validation function.
 */
function perfex_saas_register_limitation_filters()
{
    foreach (PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP as $event => $table) {
        // Set priority to 0 as we want this to run before any other attached hooks to the filter.
        hooks()->add_filter($event, 'perfex_saas_validate_limits', 0);
    }
}

/**
 * Validate the limits for a specific event.
 * i.e check limit for invoices.
 *
 * @param mixed $data - The data passed to the filter hook.
 * @param mixed $admin - Optional. The admin data passed to the filter hook.
 * @return mixed - The filtered data.
 * @throws \Exception - When an unsupported limitation filter is encountered.
 */
function perfex_saas_validate_limits($data, $admin = null)
{
    // Get the active filter
    $filter = hooks()->current_filter();

    // Get the filter table
    $limit_name = PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP[$filter];

    // Ensure we have a table for the filter
    if (empty($limit_name)) {
        throw new \Exception("Unsupported limitation filter: $filter", 1);
    }

    // Get the tenant 
    $tenant = perfex_saas_tenant();

    // Get tenant details and get package limit
    $quota = (int)($tenant->package_invoice->metadata->limitations->{$limit_name} ?? -1);

    // Ulimited pass
    if ($quota === -1) return $data;

    // Get count for the active tenant from table and match against package
    $usage = perfex_saas_get_tenant_quota_usage($tenant, [$limit_name])[$limit_name];

    // If quota is exceeded, set flash and redirect back
    $reached_limit = $quota <= $usage;

    if ($reached_limit) {
        if (!defined('CRON')) {

            $msg =  _l('perfex_saas_quota_exhausted', $limit_name);
            set_alert('danger', $msg);

            // Handle ajax requests
            if (get_instance()->input->is_ajax_request()) {
                header('HTTP/1.0 400 Bad error');
                echo $limit_name === 'tasks' ? json_encode($msg) : $msg;
                exit;
            }

            perfex_saas_redirect_back();
        } else {
            log_message('info', _l('perfex_saas_quota_exhausted_cron', $tenant->slug, $limit_name));
        }
        exit;
    }

    return $data;
}

/**
 * Get the usage of tenant quotas.
 *
 * @param mixed $tenant - The tenant object.
 * @param string[] $limits - Optional. The list of limits to retrieve usage for. Will use global list when empty.
 * @return array - The usage list for each limit.
 */
function perfex_saas_get_tenant_quota_usage($tenant, $limits = [])
{
    $tenant_slug = $tenant->slug;
    $limits = empty($limits) ? array_values(PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP) : $limits;
    $usage_list = [];
    $db = get_instance()->db;
    foreach ($limits as $limit) {
        $table = db_prefix_perfex_saas_custom() . $limit;
        // Get count for the active tenant from table and match against package
        $usage = $db->query("SELECT COUNT(*) as total FROM $table WHERE `" . PERFEX_SAAS_TENANT_COLUMN . "` ='$tenant_slug'")->row();
        $usage_list[$limit] = (int)$usage->total;
    }
    return $usage_list;
}
