<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Perfex SAAS
Description: Simple comprehensive module to convert Perfex CRM to SAAS, multi-tenancy or multi-company
Version: 0.0.4
Requires at least: 3.0.*
Author: ulutfa
Author URI: https://codecanyon.net/user/ulutfa
*/

// Global common module constants
require_once('config/constants.php');

$CI = &get_instance();

/**
 * Load models
 */
$CI->load->model(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_model');
$CI->load->model(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_migration_model');

/**
 * Load the module helper
 */
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME);
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_core');
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_setup');
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_import');
$CI->load->helper(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_usage_limit');

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(PERFEX_SAAS_MODULE_NAME, [PERFEX_SAAS_MODULE_NAME, PERFEX_SAAS_MODULE_NAME . '_custom']);

/**
 * Register cron
 */
register_cron_task('perfex_saas_cron');
hooks()->add_filter('used_cron_features', function ($f) {
    $f[] = _l('perfex_saas_cron_feature_migration');
    return $f;
});

/**
 * Listen to any module activation and run the setup again.
 * This ensure new tables are prepared for saas.
 */
hooks()->add_action('module_activated', function () {
    perfex_saas_setup_master_db(true);
});

/**
 * Register activation module hook
 */
register_activation_hook(PERFEX_SAAS_MODULE_NAME, 'perfex_saas_module_activation_hook');

function perfex_saas_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Dactivation module hook
 */
register_deactivation_hook(PERFEX_SAAS_MODULE_NAME, 'perfex_saas_module_deactivation_hook');
function perfex_saas_module_deactivation_hook()
{
    perfex_saas_uninstall();
}

/**
 * Register admin footer hook - Common to both admin and instance
 * @todo Separate instance js customization from super admin
 */
hooks()->add_action('app_admin_footer', 'perfex_saas_admin_footer_hook');
function perfex_saas_admin_footer_hook()
{
    //load common admin asset
    $CI = &get_instance();
    $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/scripts');


    //load add user to package modal
    if (!perfex_saas_is_tenant() && $CI->router->fetch_class() == 'invoices')
        $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/add_user_to_package_modal');
}


/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
hooks()->add_action('admin_init', 'perfex_saas_module_init_menu_items');
function perfex_saas_module_init_menu_items()
{
    $CI = &get_instance();

    if (!perfex_saas_is_tenant() && (has_permission('perfex_saas_company', '', 'view') || has_permission('perfex_saas_package', '', 'view') || has_permission('perfex_saas_settings', '', 'view'))) {

        $CI->app_menu->add_sidebar_menu_item(PERFEX_SAAS_MODULE_NAME, [
            'name' => '<span class="tw-text-white tw-font-bold">' . _l('perfex_saas_menu_title') . '</span>',
            'icon' => 'fa fa-users tw-font-bold',
            'position' => -1000,
            'href_attributes' => [
                'class' => 'bg-primary'
            ]
        ]);

        if (has_permission('perfex_saas_packages', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_packages',
                'name' => _l('perfex_saas_packages'),
                'icon' => 'fa fa-list',
                'href' => admin_url('perfex_saas/packages'),
                'position' => 2,
            ]);
        }

        if (has_permission('perfex_saas_packages', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_invoices',
                'name' => _l('perfex_saas_invoices'),
                'icon' => 'fa-solid fa-receipt',
                'href' => admin_url('invoices') . '?' . PERFEX_SAAS_FILTER_TAG,
                'position' => 3,
            ]);
        }

        if (has_permission('perfex_saas_company', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_company',
                'name' => _l('perfex_saas_tenants'),
                'icon' => 'fa fa-university',
                'href' => admin_url('perfex_saas/companies'),
                'position' => 2,
            ]);
        }

        if (has_permission('perfex_saas_settings', '', 'view')) {
            $CI->app_menu->add_sidebar_children_item(PERFEX_SAAS_MODULE_NAME, [
                'slug' => 'perfex_saas_settings',
                'name' => _l('perfex_saas_settings'),
                'icon' => 'fa fa-cog',
                'href' => admin_url('settings?group=perfex_saas'),
                'position' => 9,
            ]);

            // SaaS tab on settings page
            $CI->app_tabs->add_settings_tab(PERFEX_SAAS_MODULE_NAME, [
                'name'     => _l('settings_group_' . PERFEX_SAAS_MODULE_NAME),
                'view'     => 'perfex_saas/settings',
                'position' => -5,
                'icon'     => 'fa fa-users',
            ]);
        }
    }

    if (perfex_saas_is_tenant()) {
        // Reserved routes
        $restricted_menus = ['modules'];
        foreach ($restricted_menus as $menu) {
            $CI->app_menu->add_setup_menu_item($menu, ['name' => '', 'href' => '', 'disabled' => true]);
        }
    }
}

/**
 * Media file folder.
 * Set max number for priority to ensure the function is more or less the last to be called
 */
hooks()->add_filter('get_media_folder', 'perfex_saas_set_media_folder_hook', PHP_INT_MAX);
function perfex_saas_set_media_folder_hook($data)
{
    $tenant_slug = perfex_saas_is_tenant() ? perfex_saas_tenant_slug() : perfex_saas_master_tenant_slug();
    if (empty($tenant_slug)) throw new \Exception("Media Error: Error Processing Request", 1);

    return $data . '/' . $tenant_slug;
}




/********SAAS CLIENTS AND SUPER ADMIN HOOKS ******/
$is_tenant = perfex_saas_is_tenant();
if (!$is_tenant) {

    // Log a selected plan id whenever we have it. I.e the copied package url
    if (!empty($_GET['ps_plan'])) {
        $CI->session->set_userdata(['ps_plan' => $CI->input->get('ps_plan', true)]);
    }

    /******* SUPER CLIENT SPECIFIC HOOKS *********/
    if (is_client_logged_in()) {
        // Will only run if its a client portal
        perfex_saas_autosubscribe();

        /**********TENANT/CLIENT Portal specific HOOKS */
        hooks()->add_action('clients_init', 'perfex_saas_clients_area_menu_items');
        function perfex_saas_clients_area_menu_items()
        {
            if (is_client_logged_in()) {
                add_theme_menu_item('companies', [
                    'name' => '<span class="tw-text-danger-600 tw-font-bold">' . _l('perfex_saas_companies') . '</span>',
                    'href' => site_url('?companies'),
                    'position' => -2,
                    'href_attributes' => [
                        'class' => 'ps-spa',
                        'data-tab' => "#companies"
                    ]
                ]);
                add_theme_menu_item('subscription', [
                    'name' => _l('perfex_saas_subscription'),
                    'href' => site_url('?subscription'),
                    'position' => -1,
                    'href_attributes' => [
                        'class' => 'ps-spa',
                        'data-tab' => "#subscription"
                    ]
                ]);
            }
        }


        hooks()->add_action('client_area_after_project_overview', 'perfex_saas_show_client_home');
        function perfex_saas_show_client_home()
        {
            include_once(__DIR__ . '/views/client/home.php');
        }

        hooks()->add_action('app_customers_footer', 'perfex_saas_client_assets');
        function perfex_saas_client_assets()
        {
            include_once(__DIR__ . '/views/client/scripts.php');
        }

        // Remove uneccessary menu item from client portal.
        // @todo Make this configurable from admin
        hooks()->add_filter('theme_menu_items', 'remove_menu_items');
        function remove_menu_items($items)
        {
            if (!perfex_saas_is_tenant() && get_option('perfex_saas_control_client_menu') == '1') {
                unset($items['projects']);
                unset($items['contracts']);
                unset($items['estimates']);
                unset($items['proposals']);
            }
            return $items;
        }
    }

    if (is_admin() || is_staff_member()) {
        /******* SUPER ADMIN PANEL SPECIFIC HOOKS *********/
        /**
         * Handle permissions
         */
        hooks()->add_action('admin_init', 'perfex_saas_permissions');
        function perfex_saas_permissions()
        {
            $capabilities = [];
            $capabilities['capabilities'] = [
                'view' => _l('perfex_saas_permission_view'),
            ];
            register_staff_capabilities('perfex_saas_dashboard', $capabilities, _l('perfex_saas_dashboard'));

            $capabilities = [];
            $capabilities['capabilities'] = [
                'view' => _l('perfex_saas_permission_view'),
                'create' => _l('perfex_saas_permission_create'),
                'edit' => _l('perfex_saas_permission_edit'),
                'delete' => _l('perfex_saas_permission_delete'),
            ];
            register_staff_capabilities('perfex_saas_companies', $capabilities, _l('perfex_saas_companies'));

            $capabilities = [];
            $capabilities['capabilities'] = [
                'view' => _l('perfex_saas_permission_view'),
                'create' => _l('perfex_saas_permission_create'),
                'edit' => _l('perfex_saas_permission_edit'),
                'delete' => _l('perfex_saas_permission_delete'),
            ];
            register_staff_capabilities('perfex_saas_packages', $capabilities, _l('perfex_saas_packages'));

            $capabilities = [];
            $capabilities['capabilities'] = [
                'view' => _l('perfex_saas_permission_view'),
                'edit' => _l('perfex_saas_permission_edit'),
            ];
            register_staff_capabilities('perfex_saas_settings', $capabilities, _l('perfex_saas_settings'));
        }

        //dashboard
        hooks()->add_action('before_start_render_dashboard_content', 'perfex_saas_dashboard_hook');
        function perfex_saas_dashboard_hook()
        {
            $CI = &get_instance();

            $data = [
                'total_packages' => $CI->db->count_all_results(perfex_saas_table('packages')),
                'total_companies' => $CI->db->count_all_results(perfex_saas_table('companies')),
                'total_subscriptions' => $CI->db->where(perfex_saas_column('packageid') . ' >', 0)->count_all_results(db_prefix() . 'invoices'),
            ];

            $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/dashboard', $data);
        }

        /** Invoice view hooks and filters */
        // Add packageid column to the datatable column and hide
        hooks()->add_filter('invoices_table_columns', 'perfex_saas_invoices_table_columns');
        function perfex_saas_invoices_table_columns($cols)
        {
            $cols[perfex_saas_column('packageid')] = ['name' => perfex_saas_column('packageid'), 'th_attrs' => ['class' => 'not_visible']];
            return $cols;
        }

        // Add packageid to selected invoice fields
        hooks()->add_filter('invoices_table_sql_columns', 'perfex_saas_invoices_table_sql_columns');
        function perfex_saas_invoices_table_sql_columns($fields)
        {
            $fields[] = perfex_saas_column('packageid');
            return $fields;
        }

        // Add package name to recurring bill on invoices list
        hooks()->add_filter('invoices_table_row_data', 'perfex_saas_invoices_table_row_data', 10, 2);
        function perfex_saas_invoices_table_row_data($row, $data)
        {
            $label = _l('perfex_saas_invoice_recurring_indicator');
            $col = perfex_saas_column('packageid');
            if (!empty($data[$col])) {
                $packageid = $data[$col];
                $package_name = &get_instance()->perfex_saas_model->packages($packageid)->name;
                $row[0] = str_ireplace($label, $label . ' | ' . $package_name, $row[0]);
            }
            $row[] = '';
            return $row;
        }

        // Add package selection to invoice edit/create
        hooks()->add_action('before_render_invoice_template', 'perfex_saas_after_render_invoice_template_hook');
        function perfex_saas_after_render_invoice_template_hook($invoice)
        {
            $col_name = perfex_saas_column('packageid');
            if (empty($invoice->{$col_name})) return;
            $CI = &get_instance();
            $data = [
                'packages' => $CI->perfex_saas_model->packages(),
                'invoice' => $invoice,
                'col_name' => $col_name,
                'invoice_packageid' => $invoice->{$col_name}
            ];

            $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/select_package_invoice_template', $data);
        }


        /************Settings */
        // Ensure perfex saas setting is use as default when no settings group is defined
        hooks()->add_action('before_settings_group_view', 'perfex_saas_before_settings_group_view_hook');
        function perfex_saas_before_settings_group_view_hook($tab)
        {

            if (empty(get_instance()->input->get('group'))) { //root settings

                redirect(admin_url('settings?group=' . PERFEX_SAAS_MODULE_NAME));
            }
        }

        // Get modules whitelabeling settings
        hooks()->add_filter('before_settings_updated', 'perfex_saas_before_settings_updated_hook');
        function perfex_saas_before_settings_updated_hook($data)
        {
            if (isset($data['settings']['perfex_saas_custom_modules_name']))
                $data['settings']['perfex_saas_custom_modules_name'] = json_encode($data['settings']['perfex_saas_custom_modules_name']);

            return $data;
        }
    }
}



/********TENANT/INSTANCE SPECIFIC HOOKS ******/
if ($is_tenant) {

    // Call the function to register the limitation filters
    perfex_saas_register_limitation_filters();

    // Override instant options with shared option in package where applicable
    perfex_saas_init_shared_options();

    /**
     * Hook for tenant instance settings page.
     * It attempt to mask settings and  removes upgrade and system info links.
     *
     * @return void
     */
    hooks()->add_action('settings_group_end', 'perfex_saas_settings_group_end_hook');
    function perfex_saas_settings_group_end_hook()
    {
        // Output buffer contents
        $output = ob_get_contents();
        ob_end_clean();

        // Remove anchors with URLs ending in '?group=update' or '?group=info'
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // dont throw error
        $dom->loadHTML($output); // Load the HTML content
        libxml_clear_errors(); // clear errors if any
        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a[contains(@href, "?group=update") or contains(@href, "?group=info")]');
        foreach ($anchors as $anchor) {
            $anchor->parentNode->removeChild($anchor);
        }
        $output = $dom->saveHTML();

        // Parse and mask secret value from tenant instances
        $output = perfex_saas_mask_secret_values($output);

        // Start a new output buffer and send the modified output
        ob_start();

        // Output the modified content
        echo $output;
    }

    // Prevent saving enforced shared fields by overriding with master value
    hooks()->add_filter('before_settings_updated', 'perfex_saas_before_settings_updated_hook');
    function perfex_saas_before_settings_updated_hook($data)
    {
        $enforced_settings = perfex_saas_master_shared_settings(PERFEX_SAAS_ENFORCED_SHARED_FIELDS);
        foreach ($enforced_settings as $setting) {
            // Override with master value
            $data['settings'][$setting->name] = $setting->value;
        }
        return $data;
    }

    // Add limitation statistic widget to dashboard
    hooks()->add_action('before_start_render_dashboard_content', 'perfex_saas_dashboard_hook');
    function perfex_saas_dashboard_hook()
    {
        $CI = &get_instance();
        $CI->load->view(PERFEX_SAAS_MODULE_NAME . '/includes/quota_stats', []);
    }
}




// Manual run test or cron for development purpose only
if (!empty($CI->input->get(PERFEX_SAAS_MODULE_NAME . '_dev'))) {

    // Only permit this in development mode and user should be logged in as admin.
    $is_developer = ENVIRONMENT === 'development' && !perfex_saas_is_tenant() && is_admin();
    if (!$is_developer) {
        exit("This action can only be run in development mode");
    }

    $action = $CI->input->get('action');

    if ($action === 'test') {
        include_once(__DIR__ . '/test.php');
    }

    if ($action === 'cron') {
        perfex_saas_cron();
    }
    exit();
}
