<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
        hooks()->do_action('clients_authentication_constructor', $this);
    }

    public function index()
    {
        exit("OK");
    }

    /**
     * Open endpoint to check if a domain is recognized by the system.
     * This is intented for On demand TLS with servers like caddy for auto SSL generation for tenants.
     * 
     * Return 404 if no match, 200 (OK) if same as base domain and 200(Matched) when a match found (subdomain or custom domain)
     *
     * i.e http://perfexdomain.com/perfex_saas/api/caddy_domain_check?domain=ulutfa.crm.com
     * @return void
     */
    public function caddy_domain_check()
    {
        // Get the domain or subdomain and validate
        $domain = $this->input->get("domain", true);
        if (empty($domain)) {
            set_status_header(400);
            echo 'No domain provided';
            return;
        }

        if (perfex_saas_get_saas_default_host() === $domain) {
            set_status_header(200);
            echo "OK";
            return;
        }

        // Detect info if using subdomain or custom domain. Will return non empty 'slug' if subdomain other 'custom_domain' or false
        $tenant_info = perfex_saas_get_tenant_info_by_host($domain);
        if ($tenant_info) {
            $identified_by_slug = !empty($tenant_info['slug']);
            $field = $identified_by_slug ? 'slug' : 'custom_domain';
            $value = $identified_by_slug ? $tenant_info['slug'] : $tenant_info['custom_domain'];
            $tenant = perfex_saas_search_tenant_by_field($field, $value);
            if ($tenant) {
                set_status_header(200);
                echo "Matched";
                return;
            }
        }

        // Set 404
        set_status_header(404);
    }
}
