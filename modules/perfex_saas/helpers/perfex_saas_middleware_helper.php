<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tenant Middleware function to handle tenant-related checks.
 *
 * This function performs various checks and validations for the tenant.
 * It checks if the tenant has an overdue invoice, if the tenant is active,
 * if the requested module is allowed for the tenant, and if the requested controller
 * is restricted for the tenant. It also handles restricted routes in the settings controller.
 *
 * @throws Exception Throws an exception if an error occurs or if access is denied.
 */
function perfex_saas_tenant_middleware()
{
    if (perfex_saas_is_tenant()) {
        $tenant = perfex_saas_tenant();
        if ($tenant) {
            $invoice = isset($tenant->package_invoice) ? $tenant->package_invoice : null;

            // Check for an overdue invoice
            if ($invoice && !$invoice->is_private && $invoice->status == "4") {
                perfex_saas_show_tenant_error(_l('perfex_saas_clear_overdue_invoice_mid'), _l('perfex_saas_clear_overdue_invoice_message_mid') . '<br/>' . '<a class="btn btn-round btn-primary tw-my-5" href="' . APP_BASE_URL_DEFAULT . '/clients/invoices">' . _l('perfex_saas_clear_overdue_invoice_btn') . '</a>', 400);
            }

            // Check if the tenant is active
            if ($tenant->status != 'active') {
                perfex_saas_show_tenant_error(ucfirst(_l('perfex_saas_' . $tenant->status)), _l('perfex_saas_company_not_active_mid') . ' <a href="' . APP_BASE_URL_DEFAULT . '/clients/tickets">' . _l('perfex_saas_clich_here') . '</a>', 400);
            }

            // Get the current CodeIgniter instance
            $ci = &get_instance();

            // Get the list of modules allowed for the tenant
            $modules = perfex_saas_tenant_modules($tenant, false);

            // Get the active module and controller
            $activeModule = $ci->router->fetch_module();
            $controller = $ci->router->fetch_class();

            // Check if the controller is 'settings'
            if ($controller === 'settings') {
                // Disable route for update|info from tenant setting
                if (in_array($ci->input->get('group'), ['update', 'info'])) {
                    perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied_mid'), _l('perfex_saas_restricted_settings_group_mid'));
                }
            }

            // Check if the active module is allowed for the tenant
            if ($activeModule && !in_array($activeModule, $modules)) {
                perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied_mid'), _l('perfex_saas_restricted_module_mid'));
            }

            // Check if the controller is restricted
            $restricted_classes = ['mods'];
            if (in_array($controller, $restricted_classes)) {
                $ci->session->set_flashdata('message-danger', _l('perfex_saas_permission_denied_mid'));
                perfex_saas_redirect_back();
                exit();
            }
        }
    }
}

/**
 * Function to dynamically load tenant-specific modules.
 *
 * This function loads the modules that are allowed for the tenant.
 * It iterates through the tenant modules and includes the module PHP file
 * if it exists. This allows for dynamic loading of tenant-specific modules.
 */
function perfex_saas_load_tenant_modules()
{
    // Get the current tenant
    $tenant = perfex_saas_tenant();

    if ($tenant && isset($tenant->slug)) {
        // Get the list of modules allowed for the tenant
        $tenant_modules = perfex_saas_tenant_modules($tenant);

        foreach ($tenant_modules as $module) {
            $file = APP_MODULES_PATH . $module . '/' . $module . '.php';

            // Check if the module file exists
            if (file_exists($file)) {
                // Include the module PHP file
                require_once($file);
            }
        }
    }
}


/**
 * Attach Hooks function to register and attach hooks for specific actions.
 *
 * This function registers hooks for various actions and attaches the corresponding
 * middleware or module loading functions to those hooks.
 */
function perfex_saas_attach_hooks()
{
    // Register hooks for middleware
    hooks()->add_action('app_admin_head', 'perfex_saas_tenant_middleware');
    hooks()->add_action('app_admin_authentication_head', 'perfex_saas_tenant_middleware');
    hooks()->add_action('app_customers_head', 'perfex_saas_tenant_middleware');
    hooks()->add_action('app_external_form_head', 'perfex_saas_tenant_middleware');
    hooks()->add_action('elfinder_tinymce_head', 'perfex_saas_tenant_middleware');

    // Register hook for module loading
    hooks()->add_action('modules_loaded', 'perfex_saas_load_tenant_modules');
}

/**
 * Perfex SAAS Middleware function.
 *
 * This function serves as a middleware entry point for Perfex SAAS. It calls the
 * `perfex_saas_attach_hooks()` function to register and attach hooks for various actions.
 */
function perfex_saas_middleware()
{
    perfex_saas_attach_hooks();
}
