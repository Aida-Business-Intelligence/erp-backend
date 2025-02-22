<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Perfex_saas_company_merge_fields extends App_merge_fields
{
    /**
     * This function builds an array of custom email templates keys.
     * The provided keys will be available in perfex email template editor for the supported templates.
     * @return array
     */
    public function build()
    {
        // List of email templates used by the plugin
        $templates = [
            'company-instance-deployed',
            'company-instance-deployed-for-admin',
            'company-instance-removed',
            'company-instance-removed-for-admin',
        ];
        $available = [];
        return [
            [
                'name'      => 'Instance name',
                'key'       => '{instance_name}', // Key for instance name
                'available' => $available,
                'templates' => $templates,
            ],
            [
                'name'      => 'Instance slug',
                'key'       => '{instance_slug}', // Key for instance slug
                'available' => $available,
                'templates' => $templates,
            ],
            [
                'name'      => 'Instance status',
                'key'       => '{instance_status}', // Key for instance status
                'available' => $available,
                'templates' => $templates,
            ],
            [
                'name'      => 'Instance url',
                'key'       => '{instance_url}', // Key for instance URL
                'available' => $available,
                'templates' => $templates,
            ],
            [
                'name'      => 'Instance admin url',
                'key'       => '{instance_admin_url}', // Key for instance admin URL
                'available' => $available,
                'templates' => $templates,
            ],
        ];
    }

    /**
     * Format merge fields for company instance
     * @param  object $company
     * @return array
     */
    public function format($company)
    {
        return $this->instance($company);
    }

    /**
     * Company Instance merge fields
     * @param  object $company
     * @return array
     */
    public function instance($company)
    {
        $fields['{instance_url}'] = perfex_saas_tenant_base_url($company);
        $fields['{instance_admin_url}']   = perfex_saas_tenant_admin_url($company);
        $data = ['slug' => $company->slug, 'name' => $company->name, 'status' => $company->status];
        foreach ($data as $key => $value) {
            $fields["{instance_$key}"] = $value;
        }
        return $fields;
    }
}
