<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php $invoice = &get_instance()->perfex_saas_model->get_company_invoice(get_client_user_id()); ?>

<script>
    "use strict";

    const PERFEX_SAAS_MAGIC_AUTH_BASE_URL = '<?= base_url('clients/ps_magic/'); ?>';
    const PERFEX_SAAS_DEFAULT_HOST = '<?= perfex_saas_get_saas_default_host(); ?>';
    const PERFEX_SAAS_ACTIVE_SEGMENT = window.location.search || '<?= empty($invoice) ? "?subscription" : "?companies"; ?>';
    const PERFEX_SAAS_CONTROL_CLIENT_MENU = <?= (int)get_option('perfex_saas_control_client_menu'); ?>;
</script>

<!-- Module custom client script -->
<script src="<?= module_dir_url(PERFEX_SAAS_MODULE_NAME, 'assets/js/client.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?= module_dir_url(PERFEX_SAAS_MODULE_NAME, 'assets/css/client.css'); ?>" />

<!-- style control for client menu visibility -->
<?php if ((int)get_option('perfex_saas_control_client_menu')) : ?>
    <style>
        .section-client-dashboard>dl:first-of-type,
        .projects-summary-heading,
        .submenu.customer-top-submenu {
            display: none;
        }
    </style>
<?php endif; ?>