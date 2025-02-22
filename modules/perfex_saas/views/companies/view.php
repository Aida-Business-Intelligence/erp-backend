<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
            <?= _l('perfex_saas_company_view_heading'); ?>
        </h4>
        <div class="panel_s invoice accounting-template">
            <div class="panel-body">

                <?php if (!empty($company->metadata->pending_custom_domain)) : ?>
                    <div class="alert alert-warning text-center">
                        <div>
                            <?= _l('perfex_saas_pending_domain_request', [$company->name, $company->metadata->pending_custom_domain]); ?>
                        </div>
                        <div class="tw-mt-4"><strong><?= _l('perfex_saas_pending_domain_request_hint'); ?></strong></div>
                        <div class="tw-text-2xl tw-mt-4">
                            <strong><?= $company->metadata->pending_custom_domain; ?></strong>
                        </div>
                        <div class="tw-flex tw-mt-4 tw-justify-center">
                            <?php echo form_open(admin_url(PERFEX_SAAS_MODULE_NAME . '/companies/custom_domain'), ['id' => 'custom_domain', 'class' => 'tw-mr-4']); ?>
                            <?= form_hidden('id', $company->id); ?>
                            <div class="text-left">
                                <input name="cancel" type="submit" data-loading-text="<?= _l('perfex_saas_saving...'); ?>" data-form="#custom_domain_form" class="btn btn-danger mtop15 mbot15" value="<?= _l('perfex_saas_cancel'); ?>" />
                                <input name="approve" type="submit" data-loading-text="<?= _l('perfex_saas_saving...'); ?>" data-form="#custom_domain_form" class="btn btn-success mtop15 mbot15" value="<?= _l('perfex_saas_approve'); ?>" />

                            </div>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                <?php endif; ?>


                <div class="form-group">
                    <label for="slug"><?= _l('perfex_saas_slug'); ?></label>
                    <p class="form-control-static"><?= $company->slug; ?></p>
                </div>
                <div class="form-group">
                    <label for="slug"><?= _l('perfex_saas_custom_domain'); ?></label>
                    <p class="form-control-static"><?= $company->custom_domain ?? '-'; ?></p>
                </div>
                <div class="form-group">
                    <label for="company-name"><?= _l('perfex_saas_company_accessible_links'); ?></label>
                    <p class="form-control-static tw-flex tw-flex-col">
                        <?php foreach (perfex_saas_tenant_base_url($company, '', 'all') as $key => $value) : if (!$value) continue; ?>
                            <a href="<?= $value; ?>" target="_blank"><?= $value; ?></a>
                            <a href="<?= $value . 'admin'; ?>" target="_blank"><?= $value . 'admin'; ?></a>
                        <?php endforeach; ?>
                    </p>
                </div>

                <div class="form-group">
                    <label for="company-name"><?= _l('perfex_saas_date_created'); ?></label>
                    <p class="form-control-static"><?= $company->created_at; ?></p>
                </div>

            </div>
        </div>
    </div>
</div>