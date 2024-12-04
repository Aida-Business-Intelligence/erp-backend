<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (has_permission('perfex_saas_companies', '', 'create')) { ?>
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="<?php echo admin_url('perfex_saas/companies/create'); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('perfex_saas_new_company'); ?>
                    </a>
                </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('perfex_saas_name'),
                            _l('perfex_saas_clients_list_company'),
                            _l('perfex_saas_company_status'),
                            _l('perfex_saas_package'),
                            _l('perfex_saas_data_location'),
                            _l('perfex_saas_modules'),
                            _l('perfex_saas_date_created'),
                            _l('perfex_saas_last_updated'),
                            _l('perfex_saas_options'),
                        ], 'companies'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
"use strict";
$(function() {
    initDataTable('.table-companies', window.location.href, undefined, [5]);
});
</script>
</body>

</html>