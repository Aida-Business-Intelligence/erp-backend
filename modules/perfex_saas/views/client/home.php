<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php get_instance()->load->view('authentication/includes/alerts'); ?>

<?php
$CI = &get_instance();
$invoice = $CI->perfex_saas_model->get_company_invoice(get_client_user_id());
$companies = $CI->perfex_saas_model->companies();
$centered_size = empty($companies) ? 'col-md-4' : 'col-md-12';
$company_options = [];
$pending_custom_domain_notice = "<span data-toggle='tooltip' data-title='" . _l("perfex_saas_domain_pending") . "'><i class='fa fa-warning text-danger'></i></span>";
$next_slug = empty($companies) ? perfex_saas_generate_unique_slug(get_client(get_client_user_id())->company, 'companies') : '';
$can_import_from_dump = isset($invoice->metadata->allow_create_from_dump) && $invoice->metadata->allow_create_from_dump == 'yes';
$can_use_subdomain = (int)($invoice->metadata->enable_subdomain ?? 0);
$can_use_custom_domain = (int)($invoice->metadata->enable_custom_domain ?? 0);
$autolaunch = count($companies) === 1 && (time() - strtotime($companies[0]->created_at)) < 60 * 2 ? 'autolaunch' : ''; // auto launch if less than 60 secs*2
$input_class = "form-control tw-rounded tw-border tw-border-gray-300 tw-py-2 tw-px-3 tw-leading-tight focus:tw-outline-none focus:tw-shadow-outline tw-w-full";
?>

<div class="ps-container">

    <!-- Invoice notification -->
    <?php if (!empty($invoice) && $invoice->status != Invoices_model::STATUS_PAID) : ?>
        <?php if ($invoice->trial_period > 0 && date('Y-m-d', strtotime($invoice->duedate)) > date('Y-m-d')) : ?>
            <div class="alert alert-warning">
                <?= _l('perfex_saas_trial_invoice_not', [$invoice->name, _d($invoice->duedate), \Carbon\Carbon::parse($invoice->duedate)->diffInDays()]); ?>
                <a href="<?= base_url("invoice/$invoice->id/$invoice->hash"); ?>"><?= _l('perfex_saas_click_here_to_subscribe'); ?></a>
            </div>
        <?php else : ?>
            <div class="alert alert-danger">
                <?= _l('perfex_saas_trial_invoice_not', [$invoice->name, $invoice->trial_period, $invoice->duedate]); ?>
                <?= _l('perfex_saas_outstanding_invoice_not', $invoice->status); ?> <a href="<?= base_url("invoice/$invoice->id/$invoice->hash"); ?>"><?= _l('perfex_saas_click_here_to_pay'); ?></a>
            </div>
        <?php endif; ?>
    <?php endif ?>

    <!-- subscription management -->
    <div class="ps-view tw-mt-8" id="subscription" style="display:none;">
        <?php
        require(__DIR__ . '/../packages/list.php');
        ?>
    </div>


    <!-- company management -->
    <div class="ps-view tw-mt-8" id="companies" style="display:none;">
        <div class="<?= empty($companies) ? 'tw-w-full' : 'tw-grid tw-gap-3 tw-grid-cols-1 sm:tw-grid-cols-2 md:tw-grid-cols-3'; ?>">

            <?php foreach ($companies as $company) : $company_options[] = ['key' => $company->slug, 'name' => $company->name . " - $company->slug"]; ?>
                <div class="company panel_s tw-p-4 tw-py-2  tw-bg-neutral-150 hover:tw-bg-neutral-200 tw-flex tw-flex-col tw-justify-between tw-relative <?= $autolaunch; ?>">
                    <!--- Menu -->
                    <div class="dropdown tw-absolute tw-pr-2">
                        <a href="#" class="dropdown-toggle tw-pr-2" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-v fa-2x" data-toggle="tooltip" data-title="<?= _l('perfex_saas_more_option'); ?>" data-placement="bottom" data-original-title="" title=""></i>
                        </a>
                        <ul class="dropdown-menu animated fadeIn">
                            <?php if ($can_use_custom_domain) : ?>
                                <li class="edit-company-nav">
                                    <a href="#custom-domain" onclick="return false;"><?= _l('perfex_saas_client_add_custom_domain'); ?></a>
                                </li>
                            <?php endif; ?>
                            <li class="edit-company-nav">
                                <a href="#custom-domain" onclick="return false;"><?= _l('perfex_saas_client_edit_company'); ?></a>
                            </li>
                            <li class="customers-nav-item-logout">
                                <?php if ($company->status != 'pending') : ?>
                                    <?= form_open(base_url('clients/companies/delete/' . $company->slug)); ?>
                                    <?= form_hidden('id', $company->id); ?>
                                    <input type="submit" class="text-danger text-left tw-ml-2 tw-pt-2 _delete tw-w-full tw-bg-transparent !tw-border-0" value="<?= _l('perfex_saas_delete'); ?>">
                                    <?= form_close(); ?>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>

                    <!-- Details -->
                    <div class="panel_body tw-flex tw-flex-col tw-items-center tw-justify-center text-center">
                        <h3 class="tw-mb-0">
                            <?= $company->name; ?>
                        </h3>
                        <div class="info tw-flex tw-flex-col tw-w-full">
                            <small class="d-block tw-text-muted">(<?= $company->slug ?>)</small>

                            <div class="tw-flex tw-items-center tw-gap-2 links">
                                <?= !empty($company->metadata->pending_custom_domain) ? $pending_custom_domain_notice : ''; ?>
                                <div class="tw-flex tw-flex-col  tw-w-full">
                                    <a class="tw-mt-8 tw-mb-2 tw-text-ellipsis tw-truncate tw-w-full text-left !tw-max-w-xs" target="_blank" data-toggle="tooltip" data-title="<?= _l('perfex_saas_customer_link'); ?>" href="<?= perfex_saas_tenant_base_url($company); ?>">
                                        <?= perfex_saas_tenant_base_url($company); ?> <i class="fa fa-external-link"></i>
                                    </a>
                                    <a class="tw-mb-8 tw-text-ellipsis tw-truncate tw-w-full text-left !tw-max-w-xs" target="_blank" data-toggle="tooltip" data-title="<?= _l('perfex_saas_admin_link'); ?>" href="<?= perfex_saas_tenant_admin_url($company); ?>">
                                        <?= perfex_saas_tenant_admin_url($company); ?>
                                        <i class="fa fa-external-link"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="company-status">
                                <span class="badge badge-success <?= $company->status == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?= _l('perfex_saas_' . $company->status); ?>
                                    <?= $company->status == 'pending' ? '<i class="fa fa-spin fa-spinner"></i>' : ''; ?>
                                </span>
                            </div>
                        </div>


                        <!-- edit form -->
                        <div class="edit-form tw-w-full text-left tw-mt-4" style="display:none">
                            <?= form_open(base_url('clients/companies/edit/' . $company->slug), ['id' => 'company_edit_form']); ?>
                            <?= render_input('name', 'perfex_saas_company_name', $company->name, 'text', [], [], "text-left tw-mb-4 $centered_size", $input_class); ?>
                            <?= $can_use_custom_domain ? render_input('custom_domain', _l('perfex_saas_custom_domain') . perfex_saas_form_label_hint('perfex_saas_custom_domain_hint'), $company->custom_domain, 'text', [], [], "text-left tw-mb-4 $centered_size", $input_class) : ''; ?>
                            <div class="text-center">
                                <button type="button" class="btn btn-default mtop15 mbot15"><?= _l('perfex_saas_cancel'); ?></button>
                                <button type="submit" data-loading-text="<?= _l('perfex_saas_saving...'); ?>" data-form="#packages_form" class="btn btn-primary mtop15 mbot15"><?= _l('perfex_saas_submit'); ?></button>
                            </div>
                            <?= form_close(); ?>
                        </div>
                        <!-- end edit form -->

                    </div>
                    <div class="panel_footer tw-flex tw-justify-between tw-items-center tw-mt-4 tw-mb-3">
                        <div data-toggle="tooltip" data-title="<?= _l('perfex_saas_date_created'); ?>">
                            <i class="fa fa-calendar"></i>
                            <?= explode(' ', $company->created_at)[0]; ?>
                        </div>
                        <div class="tw-flex tw-space-x-2">

                            <?php if ($company->status == 'active') : ?>
                                <button data-toggle="tooltip" data-title="<?= _l('perfex_saas_view'); ?>" class="btn btn-primary tw-rounded-full view-company" data-slug="<?= $company->slug; ?>">
                                    <i class="fa fa-eye"></i>
                                </button>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            <?php endforeach ?>



            <!-- create company form -->
            <div class="panel_s tw-p-4 tw-py-2 tw-bg-neutral-100 tw-w-full tw-flex tw-flex-col tw-justify-center">
                <div class="panel_body">

                    <!-- Trigger -->
                    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center text-center" id="add-company-trigger">
                        <div class="tw-mt-8 tw-flex">
                            <button type="button" class="tw-bg-white tw-py-4 tw-px-4 tw-rounded-full tw-border-primary-600 add-company-btn"><i class="fas fa-plus fa-2x tw-px-4 tw-py-4"></i></button>
                        </div>
                        <h3 class="tw-mt-8">
                            <?= empty($companies) ?  _l('perfex_saas_let_us_create_your_first_company') : _l('perfex_saas_spin_up_another_awesome_crm'); ?>
                        </h3>
                        <button type="button" class="btn btn-primary tw-mb-8 add-company-btn">
                            <?= _l('perfex_saas_new_company'); ?>
                        </button>
                    </div>

                    <!-- actual form -->
                    <?php echo form_open_multipart('clients/companies/create', ['id' => "add-company-form", 'style' => 'display:none;']); ?>
                    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-mt-4 tw-mb-4">
                        <!-- company name -->
                        <?= render_input('name', 'perfex_saas_company_name', empty($companies) ? get_client(get_client_user_id())->company : '', 'text', [], [], "text-left tw-mb-4 $centered_size", $input_class); ?>
                        <!-- slug -->
                        <?= $can_use_subdomain ? render_input('slug', _l('perfex_saas_create_company_slug') . perfex_saas_form_label_hint('perfex_saas_create_company_slug_hint', perfex_saas_get_saas_default_host()), $next_slug, 'text', ['maxlength' => 50], [], "text-left tw-mb-4 $centered_size", $input_class) : ''; ?>

                        <?php if ($can_import_from_dump) : ?>
                            <div class="tw-mb-4 <?= $centered_size; ?>" data-toggle="tooltip" data-title="<?= _l('perfex_saas_create_company_from_dump'); ?>">
                                <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-relative">
                                    <input type="file" name="sql_file" id="sql_file" class="hidden" accept=".sql">
                                    <label for="sql_file" class="tw-cursor-pointer tw-flex tw-flex-col tw-items-center tw-justify-center tw-bg-white tw-w-full tw-rounded tw-border-2 tw-border-neutral-200 tw-border-solid">
                                        <span class="tw-rounded-full tw-bg-primary-600 hover:tw-bg-primary-500 tw-p-2 tw-px-3 tw-mt-3 tw-text-white">
                                            <i class="fas fa-file-upload"></i>
                                        </span>

                                        <span id="selected_file_name" class="tw-ml-2 tw-text-sm tw-mt-2 tw-mb-2">
                                            <?= _l('perfex_saas_click_to_choose_sql_dump_file'); ?>
                                        </span>
                                    </label>

                                </div>
                            </div>
                        <?php endif
                        ?>

                        <div class="tw-flex tw-justify-end tw-mt-2">
                            <button type="button" class="btn btn-secondary tw-mr-4" id="cancel-add-company"><?= _l('perfex_saas_cancel'); ?></button>
                            <button class="btn btn-primary" id="submit-company"><?= _l('perfex_saas_create'); ?></button>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
            <!-- end create company form -->
        </div>
    </div>



    <!-- Companies viewer modal -->
    <div class="modal view-company-modal animated fadeIn" id="view-company-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog tw-w-full tw-h-screen tw-mt-0" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" class="tw-text-danger-600 bold"><i class="fa fa-close"></i></span></button>
                    <div class="tw-flex tw-justify-end">
                        <div class="tw-flex col-md-2 col-xs-6">
                            <?= render_select('view-company', $company_options, ['key', ['name']], '', '0', [], [], 'tw-w-full', '', true); ?>
                        </div>
                        <h4 class="modal-title"></h4>
                    </div>
                </div>
                <div class="modal-body tw-m-0">
                    <div class="tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center first-loader">
                        <i class="fa fa-spin fa-spinner fa-4x"></i>
                    </div>
                    <iframe class="tw-w-full tw-h-full" id="company-viewer">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

</div>