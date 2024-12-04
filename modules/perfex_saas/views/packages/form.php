<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?= isset($package) ? $package->name : _l('perfex_saas_new_package'); ?>
                    </span>
                </h4>
                <div class="panel_s invoice accounting-template">
                    <div class="panel-body">

                        <?= validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
                        <?php $this->load->view('authentication/includes/alerts'); ?>

                        <?= form_open($this->uri->uri_string(), ['id' => 'packages_form']); ?>

                        <?php if (isset($package)) echo form_hidden('id', $package->id); ?>

                        <div class="row">

                            <!-- Baseic package info -->
                            <div class="col-md-7">
                                <?php $value = (isset($package) ? $package->name : ''); ?>
                                <?= render_input('name', 'name', $value); ?>

                                <?php $value = (isset($package) ? $package->price : ''); ?>
                                <?= render_input('price', _l('perfex_saas_price') . ' (' . $this->currencies_model->get_base_currency()->name . ')', $value, 'number', ['min' => 0, 'step' => '0.01']); ?>

                                <!-- Invoice interval period handling -->
                                <?php if (isset($package)) $invoice = $package->metadata->invoice; ?>
                                <?php $is_custom_interval = isset($invoice) && $invoice->recurring == 'custom'; ?>
                                <div class="form-group select-placeholder">
                                    <label for="recurring" class="control-label">
                                        <small class="req text-danger">*</small>
                                        <?= _l('perfex_saas_invoice_add_edit_recurring'); ?>
                                    </label>
                                    <select class="selectpicker" data-width="100%" name="metadata[invoice][recurring]" data-none-selected-text="<?= _l('perfex_saas_dropdown_non_selected_tex'); ?>" required>
                                        <?php for ($i = 0; $i <= 12; $i++) : ?>
                                            <?php
                                            $selected = isset($invoice) && !$is_custom_interval && $invoice->recurring == $i ? 'selected' : '';

                                            $reccuring_string = $i == 0 ? _l('perfex_saas_invoice_add_edit_recurring_no') : ($i == 1 ? _l('invoice_add_edit_recurring_month', $i) : _l('invoice_add_edit_recurring_months', $i));
                                            ?>
                                            <option value="<?= $i == 0 ? '' : $i; ?>" <?= $selected; ?>>
                                                <?= $reccuring_string; ?>
                                            </option>
                                        <?php endfor ?>
                                        <!-- custom select -->
                                        <option value="custom" <?php if (isset($invoice) && $invoice->recurring != 0 && $is_custom_interval) {
                                                                    echo 'selected';
                                                                } ?>>
                                            <?= _l('perfex_saas_recurring_custom'); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- custom select inputs -->
                                <div class="row recurring_custom <?php if ((isset($invoice) && !$is_custom_interval) || (!isset($invoice))) {
                                                                        echo 'hide';
                                                                    } ?>">
                                    <div class="col-md-6">
                                        <?php $value = (isset($invoice) && $is_custom_interval ? $invoice->repeat_every_custom : 1); ?>
                                        <?= render_input('metadata[invoice][repeat_every_custom]', '', $value, 'number', ['min' => 1]); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <select name="metadata[invoice][repeat_type_custom]" id="repeat_type_custom" class="selectpicker" data-width="100%" data-none-selected-text="<?= _l('perfex_saas_dropdown_non_selected_tex'); ?>">
                                            <option value="day" <?php if ($is_custom_interval && $invoice->repeat_type_custom == 'day') {
                                                                    echo 'selected';
                                                                } ?>><?= _l('perfex_saas_invoice_recurring_days'); ?>
                                            </option>
                                            <option value="week" <?php if ($is_custom_interval == 1 && $invoice->repeat_type_custom == 'week') {
                                                                        echo 'selected';
                                                                    } ?>>
                                                <?= _l('perfex_saas_invoice_recurring_weeks'); ?></option>
                                            <option value="month" <?php if ($is_custom_interval && $invoice->repeat_type_custom == 'month') {
                                                                        echo 'selected';
                                                                    } ?>>
                                                <?= _l('perfex_saas_invoice_recurring_months'); ?></option>
                                            <option value="year" <?php if ($is_custom_interval && $invoice->repeat_type_custom == 'year') {
                                                                        echo 'selected';
                                                                    } ?>>
                                                <?= _l('perfex_saas_invoice_recurring_years'); ?></option>
                                        </select>
                                    </div>
                                </div>

                                <?php $value = (isset($package) ? $package->trial_period : ''); ?>
                                <?= render_input('trial_period', 'perfex_saas_trial_period', $value, 'number', ['step' => '1']); ?>

                                <?php $value = (isset($package->metadata->max_instance_limit) ? $package->metadata->max_instance_limit : 1); ?>
                                <?= render_input('metadata[max_instance_limit]', _l('perfex_saas_instance_cap') . perfex_saas_form_label_hint('perfex_saas_instance_cap_hint'), $value, 'number', ['step' => '1']); ?>


                                <?php $value = (isset($package) ? $package->description : ''); ?>
                                <?= render_textarea('description', 'perfex_saas_description', $value, [], [], 'tinymce tinymce-manual'); ?>

                                <!-- enable checkboxes -->
                                <div class="row tw-mt-8 tw-mb-8">
                                    <div class="col-sm-6 col-md-3">
                                        <?php $checked = isset($package) && $package->status == '1' ? 'checked' : ''; ?>
                                        <div class="checkbox checkbox-inline form-group" data-toggle="tooltip" data-title="<?= _l('perfex_saas_enabled_hint'); ?>">
                                            <input type="checkbox" value="1" name="status" <?= $checked ?>>
                                            <label for="ts_rel_to_project"><?= _l('perfex_saas_enabled?'); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-3">
                                        <?php $checked = isset($package) && $package->is_default == '1' ? 'checked' : ''; ?>
                                        <div class="checkbox checkbox-inline form-group" data-toggle="tooltip" data-title="<?= _l('perfex_saas_is_default_hint'); ?>">
                                            <input type="checkbox" value="1" name="is_default" <?= $checked ?>>
                                            <label for="ts_rel_to_project"><?= _l('perfex_saas_is_default'); ?></label>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-3">
                                        <?php $checked = isset($package) && $package->is_private == '1' ? 'checked' : ''; ?>
                                        <div class="checkbox checkbox-inline form-group" data-toggle="tooltip" data-title="<?= _l('perfex_saas_is_private_hint'); ?>">
                                            <input type="checkbox" value="1" name="is_private" <?= $checked ?>>
                                            <label for="ts_rel_to_project"><?= _l('perfex_saas_is_private'); ?></label>
                                        </div>
                                    </div>
                                </div>

                                <!-- modules selection -->
                                <div class="tw-mt-8 tw-mb-8">
                                    <?php $selected = (isset($package) ? $package->modules : ''); ?>
                                    <?php $modules = $this->perfex_saas_model->modules(); ?>
                                    <?= render_select('modules[]', $modules, ['system_name', ['custom_name']], 'modules', $selected, ['multiple' => 'true']); ?>
                                </div>

                                <div class="tw-mt-8 tw-mb-8">
                                    <?php $selected = (!empty($package->metadata->show_modules_list) ? $package->metadata->show_modules_list : 'yes'); ?>
                                    <?= render_select('metadata[show_modules_list]', $this->perfex_saas_model->yes_no_options(), ['key', ['label']], 'perfex_saas_show_modules_list', $selected); ?>
                                </div>

                            </div>

                            <!-- Right column -->
                            <div class="col-md-5">

                                <!-- Limitations -->
                                <div class="form-group">
                                    <h4 class="tw-mb-4">
                                        <?= _l('perfex_saas_limitations_package') . ' ' . perfex_saas_form_label_hint('perfex_saas_limitations_package_hint'); ?>
                                    </h4>
                                    <div class="row" id="package-qouta">
                                        <?php foreach (PERFEX_SAAS_LIMIT_FILTERS_TABLES_MAP as $event => $limit) : ?>
                                            <div class="col-md-6 col-xs-12">
                                                <?php $value = (isset($package->metadata->limitations->{$limit}) ? $package->metadata->limitations->{$limit} : -1); ?>
                                                <?php $unlimited = (int)$value == -1; ?>
                                                <div class="form-group">
                                                    <label for="<?= $limit; ?>" class="control-label">
                                                        <?php echo _l('perfex_saas_limit_' . $limit); ?>
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="number" value="<?= $value; ?>" step="1" min="-1" class="form-control" name="<?= "metadata[limitations][$limit]"; ?>" <?= $unlimited ? "readonly" : ""; ?>>
                                                        <span class="input-group-addon tw-border-l-0">

                                                            <a href="#metered" class="mark_metered" data-toggle="tooltip" data-title="<?= _l('perfex_saas_mark_limited'); ?>" onclick="return false;" style="<?= $unlimited ? '' : 'display:none'; ?>">
                                                                <i class="<?= 'fa fa-dashboard'; ?>"></i>
                                                            </a>

                                                            <a href="#infinity" class="mark_infinity" data-toggle="tooltip" data-title="<?= _l('perfex_saas_mark_unlimited'); ?>" onclick="return false;" style="<?= $unlimited ? 'display:none' : ''; ?>">
                                                                <i class="<?= 'fa fa-infinity'; ?>"></i>
                                                            </a>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                </div>


                                <!-- Subdomain -->
                                <div class="row">
                                    <div class="col-md-6 mtop10 border-right">
                                        <span><?= _l('perfex_saas_enable_subdomain') . perfex_saas_form_label_hint('perfex_saas_enable_subdomain_hint'); ?></span>
                                    </div>
                                    <div class="col-md-6 mtop10">
                                        <?php $checked = isset($package->metadata->enable_subdomain) && $package->metadata->enable_subdomain == '1'; ?>
                                        <div class="onoffswitch">
                                            <input type="checkbox" id="enable_subdomain" data-perm-id="ps1" class="onoffswitch-checkbox" <?= $checked ? 'checked' : ''; ?> value="1" name="metadata[enable_subdomain]">
                                            <label class="onoffswitch-label" for="enable_subdomain"></label>
                                        </div>
                                    </div>
                                </div>


                                <!-- Custom domain -->
                                <div class="row">
                                    <div class="col-md-6 mtop10 border-right">
                                        <span><?= _l('perfex_saas_enable_custom_domain') . perfex_saas_form_label_hint('perfex_saas_enable_custom_domain_hint'); ?></span>
                                    </div>
                                    <div class="col-md-6 mtop10">
                                        <?php $checked = isset($package->metadata->enable_custom_domain) && $package->metadata->enable_custom_domain == '1'; ?>
                                        <div class="onoffswitch">
                                            <input type="checkbox" id="enable_custom_domain" data-perm-id="ps2" class="onoffswitch-checkbox" <?= $checked ? 'checked' : ''; ?> value="1" name="metadata[enable_custom_domain]">
                                            <label class="onoffswitch-label" for="enable_custom_domain"></label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mtop10 border-right">
                                        <span><?= _l('perfex_saas_autoapprove_custom_domain') . perfex_saas_form_label_hint('perfex_saas_autoapprove_custom_domain_hint'); ?></span>
                                    </div>
                                    <div class="col-md-6 mtop10">
                                        <?php $checked = isset($package->metadata->autoapprove_custom_domain) && $package->metadata->autoapprove_custom_domain == '1'; ?>
                                        <div class="onoffswitch">
                                            <input type="checkbox" id="autoapprove_custom_domain" data-perm-id="ps3" class="onoffswitch-checkbox" <?= $checked ? 'checked' : ''; ?> value="1" name="metadata[autoapprove_custom_domain]">
                                            <label class="onoffswitch-label" for="autoapprove_custom_domain"></label>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                        <!-- End right column -->



                        <!-- DB scheme -->
                        <?php $value = (isset($package) ? $package->db_scheme : ''); ?>
                        <?php $db_schemes = $this->perfex_saas_model->db_schemes(); ?>
                        <?= render_select('db_scheme', $db_schemes, ['key', ['label']], 'perfex_saas_db_scheme', $value, [], [], '', '', false); ?>

                        <!-- DB pools -->
                        <div class="form-group tw-mt-10 tw-mb-8 db_pools <?= in_array($value, ['shard', 'single_pool']) ? '' : 'hidden'; ?>">
                            <label><?= _l('perfex_saas_db_pools'); ?></label>
                            <div class="tw-flex tw-items-center tw-mt-4 tw-justify-center">
                                <div class="tw-flex tw-space-x-2 pool-template tw-items-center">
                                    <?= render_input('db_pools[host][]', 'perfex_saas_db_host', '', 'text', ['placeholder' => 'localhost']); ?>
                                    <?= render_input('db_pools[user][]', 'perfex_saas_db_user', '', 'text', ['placeholder' => 'root']); ?>
                                    <?= render_input('db_pools[password][]', 'perfex_saas_db_password', '', 'text', ['placeholder' => 'password']); ?>
                                    <?= render_input('db_pools[dbname][]', 'perfex_saas_db_name', ''); ?>
                                </div>
                                <div class="tw-mt-2 tw-ml-2">
                                    <button type="button" class="btn pull-right btn-primary" id="add-pool"><i class="fa fa-check"></i></button>
                                </div>
                            </div>

                            <div id="pools" class="tw-flex tw-flex-col tw-items-center tw-mt-4 tw-justify-center tw-mx-auto">
                                <hr class="tw-mt-4 tw-mb-4" />
                                <?php $pools = !empty($package->db_pools) ? $package->db_pools : (!empty($this->session->flashdata('db_pools')) ? $this->session->flashdata('db_pools') : []); ?>
                                <?php if (!empty($pools)) : ?>
                                    <?php foreach ($pools as $key => $pool) : $pool = (object)$pool; ?>
                                        <div class="tw-flex tw-space-x-2 tw-items-center">
                                            <?= render_input('db_pools[host][]', '', $pool->host, 'text', ['placeholder' => 'localhost']); ?>
                                            <?= render_input('db_pools[user][]', '', $pool->user, 'text', ['placeholder' => 'root']); ?>
                                            <?= render_input('db_pools[password][]', '', $pool->password, 'text', ['placeholder' => 'password']); ?>
                                            <?= render_input('db_pools[dbname][]', '', $pool->dbname); ?>
                                            <div class="tw-mb-4">
                                                <button type="button" class="btn pull-right btn-danger remove-pool">
                                                    <i class="fa fa-times"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                        </div>

                        <a href="javascript:;" class="text-primary tw-mt-8 tw-mb-2 tw-block" onclick="$('.advance-settings').toggleClass('hidden')">
                            <?= _l('perfex_saas_advance_settings'); ?>
                        </a>
                        <div class="advance-settings hidden">

                            <!-- import from dump -->
                            <div class="tw-mt-8 tw-mb-8">
                                <?php $selected = (!empty($package->metadata->allow_create_from_dump) ? $package->metadata->allow_create_from_dump : 'no'); ?>
                                <?php $yes_no_options = $this->perfex_saas_model->yes_no_options(); ?>
                                <?= render_select('metadata[allow_create_from_dump]', $yes_no_options, ['key', ['label']], 'perfex_saas_allow_create_from_dump', $selected); ?>
                            </div>

                            <!-- shared settings selection -->
                            <div class="tw-mt-10 tw-mb-10">
                                <?php $selected_shared = (isset($package->metadata->shared_settings->shared) ? $package->metadata->shared_settings->shared : set_value('metadata[shared_settings][shared][]', [])); ?>
                                <?php $selected_masked = (isset($package->metadata->shared_settings->masked) ? $package->metadata->shared_settings->masked : set_value('metadata[shared_settings][masked][]', [])); ?>
                                <?php $options = $this->perfex_saas_model->shared_options(); ?>

                                <label for="sharedfilter"><?= _l('perfex_saas_package_shared_settings'); ?>
                                    <span data-toggle="tooltip" data-title="<?= _l('perfex_saas_package_shared_settings_hint'); ?>"><i class="fa fa-question-circle"></i></span></label>
                                <input id="sharedfilter" type="text" class="form-control tw-mb-2" placeholder="<?= _l('perfex_saas_filter_shared_settings'); ?>" />
                                <div class="shared_settings tw-overflow-y-auto" style="height:35vh">
                                    <div class="row w-ml-1 tw-mr-1">
                                        <?php foreach ($options as $option) :
                                            $key = $option->key;
                                            $name = $option->name; ?>
                                            <div class="col-md-4 col-sm-6 col-xs-12 item">
                                                <div class="tw-flex form-group">
                                                    <div data-toggle="tooltip" data-title="<?= _l('perfex_saas_share'); ?>">
                                                        <div class="checkbox checkbox-inline">
                                                            <input type="checkbox" name="metadata[shared_settings][shared][]" <?= in_array($key, $selected_shared) ? 'checked' : ''; ?> value="<?= $key ?>" />
                                                            <!-- ensure white space between the label -->
                                                            <label class="tw-capitalize"> </label>
                                                        </div>
                                                    </div>
                                                    <div data-toggle="tooltip" data-title="<?= _l('perfex_saas_mask'); ?>">
                                                        <div class="checkbox checkbox-inline">
                                                            <input type="checkbox" name="metadata[shared_settings][masked][]" <?= in_array($key, $selected_masked) ? 'checked' : ''; ?> value="<?= $key ?>" />
                                                            <!-- ensure white space between the label -->
                                                            <label class=""> </label>
                                                        </div>
                                                    </div>
                                                    <label class="tw-capitalize text-capitalize"><?= $name ?></label>
                                                </div>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <?php
                                $i = 0;
                                $selected = '';
                                if (!empty($invoice->sale_agent)) {
                                    foreach ($staff as $member) {

                                        if ($invoice->sale_agent == $member['staffid']) {
                                            $selected = $member['staffid'];
                                        }

                                        $i++;
                                    }
                                }
                                echo render_select('metadata[invoice][sale_agent]', $staff, ['staffid', ['firstname', 'lastname']], 'sale_agent_string', $selected);
                                ?>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" data-loading-text="<?= _l('perfex_saas_saving...'); ?>" data-form="#packages_form" class="btn btn-primary mtop15 mbot15"><?= _l('perfex_saas_submit'); ?></button>
                        </div>
                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    "use strict";

    $(document).ready(function() {
        perfexSaasPackageFormScript();
    });
</script>
</body>

</html>