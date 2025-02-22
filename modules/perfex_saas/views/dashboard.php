<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12 tw-mb-4">
    <?php if ((time() - (int)get_option('perfex_saas_cron_last_success_runtime')) > (60 * 60 * 24)) : ?>
    <div class="alert alert-danger">
        <?= _l("perfex_saas_cron_has_not_run_for_a_while"); ?>
    </div>
    <?php endif; ?>
</div>
<div class="widget relative tw-mb-8" id="widget-perfex_saas_top_stats"
    data-name="<?= _l('perfex_saas_dashboard_statistic'); ?>">
    <div class="widget-dragger ui-sortable-handle"></div>

    <div class="row">
        <div class="quick-stats-invoices col-xs-12 col-md-6 col-sm-6 col-lg-4 tw-mb-2">
            <div class="top_stats_wrapper">
                <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                    <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center"> <i
                            class="fa fa-university tw-mr-2"></i> <?= _l('perfex_saas_companies'); ?>
                    </div>
                    <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?= $total_companies; ?></span>
                </div>
            </div>
        </div>

        <div class="quick-stats-invoices col-xs-12 col-md-6 col-sm-6 col-lg-4 tw-mb-2">
            <div class="top_stats_wrapper">
                <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                    <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center mr-2"> <i
                            class="fa fa-list tw-mr-2"></i> <?= _l('perfex_saas_packages'); ?>
                    </div>
                    <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?= $total_packages; ?></span>
                </div>
            </div>
        </div>
        <div class="quick-stats-invoices col-xs-12 col-md-6 col-sm-6 col-lg-4 tw-mb-2">
            <div class="top_stats_wrapper">
                <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                    <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center mr-2"> <i
                            class="fa-regular fa-file-lines tw-mr-2"></i>
                        <?= _l('perfex_saas_recurring_invoices'); ?>
                    </div>
                    <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?= $total_subscriptions; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>