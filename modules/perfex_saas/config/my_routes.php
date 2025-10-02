<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once('my_hooks.php');

if (perfex_saas_is_tenant()) {

    $tenant = perfex_saas_tenant();
    // Ensure this custom routes is defined if the tenant is identified by request uri segment
    if ($tenant->http_identification_type === PERFEX_SAAS_TENANT_MODE_PATH) {
        $tenant_slug = $tenant->slug;
        $tenant_route_sig = perfex_saas_tenant_url_signature($tenant_slug); //i.e $tenant_route_sig

        // Clone existing static routes with saas id prefix
        foreach ($route as $key => $value) {
            $new_key = perfex_saas_tenant_url_signature($tenant_slug) . "/" . ($key == '/' ? '' : $key);
            $route[$new_key] = $value;
        }

        // Make catch-all static route for all the controllers method and modules using max of 7 levels.
        // Based on latest research perfex v3.4 7 level is more than sufficient (can increase with needs)
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4/$5/$6/$7';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4/$5/$6';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4/$5';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)/(:any)"]   = '$1/$2/$3/$4';
        $route["$tenant_route_sig/(:any)/(:any)/(:any)"]   = '$1/$2/$3';
        $route["$tenant_route_sig/(:any)/(:any)"]   = '$1/$2';
        $route["$tenant_route_sig/(:any)"]   = '$1';
        $route["$tenant_route_sig"]   = 'clients';
    }
}

if (!perfex_saas_is_tenant()) {

    // Admin perefex saas routes i.e pacakages and companies/instances management
    $route['admin/perfex_saas/(:any)'] = 'perfex_saas/admin/$1';
    $route['admin/perfex_saas/(:any)/(:any)'] = 'perfex_saas/admin/$1/$2';
    $route['admin/perfex_saas/(:any)/(:any)/(:any)'] = 'perfex_saas/admin/$1/$2/$3';


    // Client routes
    $route['clients/packages/(:any)/select'] = 'perfex_saas/perfex_saas_client/subscribe/$1';
    $route['clients/companies'] = 'perfex_saas/perfex_saas_client/companies';
    $route['clients/companies/(:any)'] = 'perfex_saas/perfex_saas_client/$1';
    $route['clients/companies/(:any)/(:any)'] = 'perfex_saas/perfex_saas_client/$1/$2';
    $route['clients/ps_magic/(:any)'] = 'perfex_saas/perfex_saas_client/magic_auth/$1';
}
