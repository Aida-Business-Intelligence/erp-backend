<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'name',
    get_sql_select_client_company(),
    'status',
    'clientid',
    'dsn',
    'metadata',
    'created_at',
    'updated_at',
];

$sTable       = perfex_saas_table('companies');
$sIndexColumn = 'id';

$clientTable = db_prefix() . 'clients';
$join = ['LEFT JOIN ' . $clientTable . ' ON ' . $clientTable . '.userid = ' . $sTable . '.clientid'];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], [$sTable . '.id', 'userid', 'slug']);

$output  = $result['output'];
$rResult = $result['rResult'];
$CI = &get_instance();

$customFields = $aColumns;
$customFields[1] = "company";

foreach ($rResult as $aRow) {
    $row = [];
    $aRow = (array) $CI->perfex_saas_model->parse_company((object)$aRow);
    $invoice = $CI->perfex_saas_model->get_company_invoice($aRow['clientid']);
    $viewLink = perfex_saas_tenant_admin_url((object)$aRow);
    $editLink = admin_url(PERFEX_SAAS_MODULE_NAME . '/companies/edit/' . $aRow['id']);

    for ($i = 0; $i < count($customFields); $i++) {
        $_data = $aRow[$customFields[$i]];

        if ($customFields[$i] == 'name') {
            $_data = '<a href="' . $viewLink . '" target="_blank">' . $_data . ' <i class="fa fa-external-link"></i></a>';
        } elseif ($customFields[$i] == 'company') {
            $_data = '<a href="' . admin_url('clients/client/' . $aRow['userid']) . '">' . $_data . '</a>';
        } elseif ($customFields[$i] == 'created_at' || $customFields[$i] == 'updated_at') {
            $_data = _d($_data);
        } elseif ($customFields[$i] == 'status') {
            $className = $_data == 'active' ? 'success' : 'danger';
            $_data = '<span class="badge tw-bg-' . $className . '-200">' . $_data . '</span>';
        } elseif ($customFields[$i] == 'clientid') {
            $_data = $invoice->name;
        } elseif ($customFields[$i] == 'dsn') {
            if (!empty($_data)) {
                $_data = perfex_saas_parse_dsn($_data);
                $_data = $_data['host'] . ':<b>' . $_data['dbname'] . '</b>';
            } else {
                $_data = '-';
            }
        } elseif ($customFields[$i] == 'metadata') {

            $disabled_modules = implode(', ', array_merge($_data->disabled_modules ?? [], $_data->admin_disabled_modules ?? []));
            $admin_approved_modules = implode(', ', $_data->admin_approved_modules ?? []);
            $_data = [];

            if (!empty($disabled_modules))
                $_data[] = '<strong>' . _l('perfex_saas_disabled_modules') . '</strong>: ' . $disabled_modules;
            if (!empty($admin_approved_modules))
                $_data[] = '<strong>' . _l('perfex_saas_admin_approved_modules') . '</strong>: ' . $admin_approved_modules;

            $_data = implode('<br/><br/>', $_data);
        }

        $row[] = $_data;
    }

    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    $options .= '<a href="' . $editLink . '" target="_blank" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
        <i class="fa fa-eye fa-lg"></i>
    </a>';

    if (has_permission('perfex_saas_companies', '', 'edit')) {
        $options .= '<a href="' . $editLink . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
            <i class="fa-regular fa-pen-to-square fa-lg"></i>
        </a>';
    }

    if (has_permission('perfex_saas_companies', '', 'delete')) {
        $options .= form_open(admin_url(PERFEX_SAAS_MODULE_NAME . '/companies/delete/' . $aRow['id'])) .
            form_hidden('id', $aRow['id']) .
            '<button class="tw-bg-transparent tw-border-0 tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
            <i class="fa-regular fa-trash-can fa-lg"></i>
        </button>' . form_close();
    }

    $options .= '</div>';

    $row[] = $options;

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
