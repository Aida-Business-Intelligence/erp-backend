<?php

defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
$packages = isset($packages) ? $packages : $CI->perfex_saas_model->packages();
$currency_name = get_base_currency()->name;
$overdue = isset($invoice) && $invoice->status == Invoices_model::STATUS_OVERDUE;
$is_client = is_client_logged_in();

?>


<div class="tw-grid tw-gap-3 tw-grid-cols-1 sm:tw-grid-cols-2 md:tw-grid-cols-3">
    <?php foreach ($packages as $package) :

        $subscribed = !empty($invoice->{perfex_saas_column('packageid')}) && $invoice->{perfex_saas_column('packageid')} == $package->id;

        if ($is_client && $package->is_private && !$subscribed) continue; ?>

        <div class="panel_s tw-p-4 tw-py-2 <?= $package->is_default == '1' ? 'tw-bg-neutral-300' : 'tw-bg-neutral-100' ?> tw-flex tw-flex-col tw-justify-between">
            <div class="panel_body tw-flex tw-flex-col tw-items-center tw-justify-center text-center">
                <h3>
                    <?= $package->name; ?>
                    <?php if ($subscribed) : ?>
                        <i class="fa fa-check-circle text-success"></i>
                    <?php endif; ?>
                </h3>
                <div>
                    <span class="tw-bg-neutral-700 tw-text-lg tw-text-white badge badge-primary tw-font-bold">
                        <?= $package->price; ?>
                        <?= $currency_name; ?>
                    </span>
                </div>
                <div class="tw-mt-2 tw-mb-4"><?= $package->description; ?></div>
                <?php if (($package->metadata->show_modules_list ?? 'yes') == 'yes') : ?>
                    <div class="tw-flex tw-justify-center tw-w-full">
                        <ul class="tw-grid tw-grid-cols-2">
                            <?php foreach ($package->modules as $key => $value) : ?>
                                <li class="text-left text-capitalize <?= ($key + 1) % 2 ? 'tw-mr-2' : 'tw-ml-2'; ?>">
                                    <span><i class="fa fa-check"></i></span>
                                    <?= $CI->perfex_saas_model->get_module_custom_name($value); ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif ?>
            </div>
            <?php if ($is_client) : ?>

                <div class="panel_footer tw-flex tw-justify-center tw-mt-4">

                    <?php if (empty($invoice)) : ?>

                        <a href="<?= base_url('clients/packages/' . $package->slug . '/select'); ?>" class="btn btn-primary">
                            <?= $package->trial_period > 0 ? _l('perfex_saas_start_trial', $package->trial_period) : _l('perfex_saas_subscribe'); ?>
                        </a>

                    <?php elseif ($subscribed) : ?>

                        <a href="<?= base_url("invoice/$invoice->id/$invoice->hash"); ?>">
                            <i class="fa fa-eye"></i>
                            <?= _l('perfex_saas_view_subscription_invoice'); ?>
                        </a>

                    <?php else : ?>

                        <a href="<?= $overdue ? 'javascript:;' : base_url('clients/packages/' . $package->slug . '/select'); ?>" class="btn btn-primary" <?= $overdue ? 'disabled data-toggle="tooltip" data-title="' . _l('perfex_saas_clear_overdue_invoice_note') . '"' : '' ?>>
                            <?= $overdue ? _l('perfex_saas_clear_overdue_invoice_btn') : _l('perfex_saas_upgrade'); ?>
                        </a>

                    <?php endif ?>

                </div>
            <?php else : ?>
                <div class="panel_footer tw-flex tw-justify-between tw-mt-4">
                    <div class="tw-flex  tw-space-x-2">

                        <span data-title="<?= _l('perfex_saas_total_db_pools'); ?>" data-toggle="tooltip">
                            <i class="fa fa-database"></i>
                            <?= count($package->db_pools); ?>
                        </span>

                        <span data-title="<?= _l('perfex_saas_total_instances_on_pool'); ?>" data-toggle="tooltip">
                            <i class="fa fa-users"></i>
                            <?= @$package->metadata->total_population ?? '0'; ?>
                        </span>


                    </div>
                    <div class="tw-flex tw-space-x-2">

                        <?php if (has_permission('perfex_saas_packages', '', 'create')) : ?>
                            <!-- copy to clipboad -->
                            <a href="#" data-success-text="<?= _l('perfex_saas_copied'); ?>" data-text="<?= site_url('authentication/register') . '?ps_plan=' . $package->slug; ?>" onclick="return false;" data-toggle="tooltip" data-title="<?= _l('perfex_saas_package_copy_to_clipboard'); ?>" class="btn btn-secondary btn-xs copy-to-clipboard">
                                <i class="fa fa-share-alt"></i>
                            </a>
                            <!-- clone -->
                            <a href="<?= admin_url('perfex_saas/packages/clone/' . $package->id); ?>" data-toggle="tooltip" data-title="<?= _l('perfex_saas_clone'); ?>" class="btn btn-secondary btn-xs"><i class="fa fa-copy"></i></a>
                        <?php endif ?>

                        <?php if (has_permission('perfex_saas_packages', '', 'edit')) : ?>
                            <a href="<?= admin_url('perfex_saas/packages/edit/' . $package->id); ?>" data-toggle="tooltip" data-title="<?= _l('perfex_saas_edit'); ?>" class="btn btn-primary btn-xs"><i class="fa fa-pen"></i></a>
                        <?php endif ?>

                        <?php if (has_permission('perfex_saas_packages', '', 'delete')) : ?>
                            <?= form_open(admin_url('perfex_saas/packages/delete')); ?>
                            <?= form_hidden('id', $package->id); ?>
                            <button class="btn btn-danger btn-xs  _delete" data-toggle="tooltip" data-title="<?= _l('perfex_saas_delete'); ?>"><i class="fa fa-trash"></i></button>
                            <?= form_close(); ?>
                        <?php endif ?>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach ?>
</div>