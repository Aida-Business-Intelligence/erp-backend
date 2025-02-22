<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$tenant = perfex_saas_tenant();
$package_quota = $tenant->package_invoice->metadata->limitations ?? [];
$usage_limits = perfex_saas_get_tenant_quota_usage($tenant);
?>

<div class="widget relative tw-mb-8" id="widget-perfex_saas_top_stats" data-name="<?= _l('perfex_saas_dashboard_statistic'); ?>">
    <div class="widget-dragger ui-sortable-handle"></div>
    <h4><?= _l('perfex_saas_tenant_quota_dashboard'); ?></h4>
    <div class="row">

        <?php foreach ($usage_limits as $resources => $usage) : ?>
            <?php
            $quota = (int)($package_quota->{$resources} ?? 0);
            $unlimited = !isset($package_quota->{$resources}) || $quota === -1;
            $usage_percent = $unlimited ? 0 : ($quota > 0 ? number_format(($usage * 100) / $quota, 2) : 0);
            $color = $usage_percent < 50 ? "green" : ($usage_percent > 90 ? 'red' : '#ca8a03');
            ?>
            <div class="quick-stats-invoices col-xs-12 col-md-3 col-sm-4 tw-mb-2">
                <div class="top_stats_wrapper" <?= $usage_percent > 95 ? "style='border-color:$color;'" : ''; ?>>
                    <div class="tw-text-neutral-800 mtop5 tw-flex tw-items-center tw-justify-between">
                        <div class="tw-font-medium tw-inline-flex text-neutral-600 tw-items-center mr-2">
                            <?= _l('perfex_saas_limit_' . $resources); ?>
                        </div>
                        <span class="tw-font-semibold tw-text-neutral-600 tw-shrink-0"><?= $usage; ?>/<?= $unlimited ? '<i class="fa fa-infinity"></i>' : $quota; ?></span>
                    </div>
                    <div class="progress tw-mb-0 tw-mt-5 progress-bar-mini">
                        <div class="progress-bar no-percent-text not-dynamic" style="background:<?= $color; ?>" role="progressbar" aria-valuenow="<?= $usage_percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="<?php echo $usage_percent; ?>">
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>