<?php

if (is_admin()) {
    // Inject sidebar menu and links for perfshield module
    hooks()->add_action('admin_init', 'perfshield_module_init_menu_items');
    function perfshield_module_init_menu_items()
    {
        if (has_permission('perfshield', '', 'view')) {
            get_instance()->app_menu->add_sidebar_menu_item('perfshield', [
                'slug'     => 'perfshield',
                'name'     => _l('perfshield'),
                'icon'     => 'fa fa-solid fa-shield-halved',
                'position' => 20,
            ]);

            get_instance()->app_menu->add_sidebar_children_item('perfshield', [
                'slug'     => 'perfshield_dashboard',
                'name'     => _l('perfshield_dashboard'),
                'href'     =>  admin_url('perfshield'),
                'position' => 1,
            ]);

            get_instance()->app_menu->add_sidebar_children_item('perfshield', [
                'slug'     => 'perfshield_settings',
                'name'     => _l('settings'),
                'href'     => admin_url('perfshield/bruteForceSettings'),
                'position' => 2,
            ]);
        }
    }
}
