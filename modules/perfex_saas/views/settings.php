<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->model('emails_model');

$modules = $CI->perfex_saas_model->modules();
$reserved_slugs = get_option('perfex_saas_reserved_slugs');

$label2 = _l('perfex_saas_reserved_slugs') . perfex_saas_form_label_hint('perfex_saas_reserved_slugs_hint');
$email_templates = $CI->emails_model->get(["`slug` LIKE" => "company-instance%", 'language' => 'english'], 'result');

?>

<div role="tabpanel" class="tab-pane" id="perfex_saas">

    <div class="tw-flex tw-flex-col">

        <!-- dont add 'settings' array, will be added by the function 'render_yes_no_option' -->
        <?php render_yes_no_option('perfex_saas_enable_auto_trial', _l('perfex_saas_enable_auto_trial'), _l('perfex_saas_enable_auto_trial_hint')); ?>
        <?php render_yes_no_option('perfex_saas_autocreate_first_company', _l('perfex_saas_autocreate_first_company'), _l('perfex_saas_autocreate_first_company_hint')); ?>
        <div class="tw-mt-4 tw-mb-4">
            <hr />
        </div>
        <?php render_yes_no_option('perfex_saas_control_client_menu', _l('perfex_saas_control_client_menu'), _l('perfex_saas_control_client_menu_hint')); ?>
        <div class="tw-mt-4 tw-mb-4">
            <hr />
        </div>
        <?php echo render_input('settings[perfex_saas_reserved_slugs]', $label2, empty($reserved_slugs) ? 'www,app,deal,controller,master,ww3,hack' : $reserved_slugs); ?>

        <div class="tw-mt-4 tw-mb-4">
            <hr />
        </div>
        <div class="">
            <label><?= _l('perfex_saas_email_templates'); ?></label>
            <ul class="tw-mt-4">
                <?php foreach ($email_templates as $t) : ?>
                    <li>
                        <a href="<?= admin_url('emails/email_template/' . $t->emailtemplateid); ?>" target="_blank">
                            <i class="fa fa-pen"></i><!-- <i class="fa fa-external-link"></i>--> <?= $t->name ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="tw-mb-4">
            <hr />
        </div>
        <div class="tw-mt-4 tw-mb-4">
            <label><?= _l('perfex_saas_custom_modules_name'); ?></label>
            <div class="shared_settings tw-overflow-y-auto tw-mt-4" style="height:35vh">
                <?php foreach ($modules as $key => $value) : ?>
                    <div class="row tw-mb-4 tw-flex tw-items-center">
                        <label class="col-sm-4"><?= $value['headers']['module_name']; ?></label>
                        <div class="col-sm-6 ">
                            <input name="settings[perfex_saas_custom_modules_name][<?= $value['system_name']; ?>]" value="<?= $value['custom_name']; ?>" class="form-control" />
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</div>