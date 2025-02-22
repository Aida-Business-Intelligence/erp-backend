<?php defined('BASEPATH') or exit('No direct script access allowed');

class Companies extends AdminController
{

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display the list of all companies.
     */
    function index()
    {
        // Check for permission
        if (!has_permission('perfex_saas_companies', '', 'view')) {
            return access_denied('perfex_saas_companies');
        }

        // Return the table data for ajax request
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path(PERFEX_SAAS_MODULE_NAME, 'companies/table'));
        }

        // Show list of comapnies
        $data['companies'] = $this->perfex_saas_model->companies();
        $data['title'] = _l('perfex_saas_companies');
        $this->load->view('companies/manage', $data);
    }

    /**
     * Create a new company.
     */
    function create()
    {
        // Check permission to creat
        if (!has_permission('perfex_saas_companies', '', 'create')) {
            return access_denied('perfex_saas_companies');
        }

        // Save company data
        if ($this->input->post()) {

            // Perform validation and save the new company
            $this->load->library('form_validation');
            $this->form_validation->set_rules('name', _l('perfex_saas_name'), 'required');
            $this->form_validation->set_rules('clientid',  _l('perfex_saas_customer'), 'required');
            $this->form_validation->set_rules('db_scheme', _l('perfex_saas_db_scheme'), 'required');

            if ($this->form_validation->run() !== false) {

                $form_data = $this->input->post(NULL, true);

                try {

                    // Get the client invoice
                    $invoice  = $this->perfex_saas_model->get_company_invoice($form_data['clientid']);

                    // Require that the client the company is beign created for by admin has an invoice
                    if (!isset($invoice->db_scheme) || empty($invoice->db_scheme)) {
                        throw new \Exception(_l('perfex_saas_no_invoice_client'), 1);
                    }

                    $form_data['dsn'] = '';
                    $db_scheme = $form_data['db_scheme'];

                    // Use the invoice package db scheme
                    if ($db_scheme == 'package') {

                        $form_data['dsn'] = ''; // Dsn will be determined in model base on invoice package
                    }

                    if ($db_scheme == 'single' || $db_scheme == 'multitenancy') {

                        $invoice->db_scheme = $db_scheme; // make temporary update of the invoice scheme reflecting the selected scheme
                        $form_data['dsn'] = ''; // Dsn will be determined in model base on the invoice scheme
                    }

                    // Use provided custom database.
                    if ($db_scheme == 'shard') {

                        // Validate the provided custom db credentails
                        $validation = perfex_saas_is_valid_dsn($form_data['db_pools']);

                        if ($validation !== true)
                            throw new \Exception($validation . ' using dsn: ' . $form_data['dsn'], 1);

                        // Add the credential to dsn
                        $form_data['dsn'] = perfex_saas_dsn_to_string($form_data['db_pools']);
                    }

                    // Save only, deployment will be made as another job
                    $_id = $this->perfex_saas_model->create_or_update_company($form_data, $invoice);
                    if ($_id) {

                        set_alert('success', _l('added_successfully', _l('perfex_saas_company')));
                        return redirect(admin_url(PERFEX_SAAS_MODULE_NAME . '/companies'));
                    }

                    // Log error
                    log_message('error', _l('perfex_saas_error_completing_action') . ':' . ($this->db->error() ?? ''));

                    throw new \Exception(_l('perfex_saas_error_completing_action'), 1);
                } catch (\Exception $e) {

                    set_alert('danger', $e->getMessage());
                    return perfex_saas_redirect_back();
                }
            }
        }

        // Show form to create a new company
        $data['title'] = _l('perfex_saas_companies');
        $this->load->view('companies/form', $data);
    }

    /**
     * Edit an existing company.
     *
     * @param string $id The ID of the company to edit
     */
    function edit($id)
    {
        if (!has_permission('perfex_saas_companies', '', 'edit')) {
            return access_denied('perfex_saas_companies');
        }

        if ($this->input->post()) {

            // Make some validation
            $this->load->library('form_validation');
            $this->form_validation->set_rules('name', _l('perfex_saas_name'), 'required');
            $this->form_validation->set_rules('clientid',  _l('perfex_saas_customer'), 'required');

            if ($this->form_validation->run() !== false) {

                $form_data = $this->input->post(NULL, true);

                try {

                    // Get the client invoice
                    $invoice  = $this->perfex_saas_model->get_company_invoice($form_data['clientid']);

                    // Require that the client the company is beign created for by admin has an invoice
                    if (!isset($invoice->db_scheme) || empty($invoice->db_scheme)) {
                        throw new \Exception(_l('perfex_saas_no_invoice_client'), 1);
                    }

                    // Save and make deployment another job
                    $_id = $this->perfex_saas_model->create_or_update_company($form_data, $invoice);
                    if ($_id) {

                        set_alert('success', _l('updated_successfully', _l('perfex_saas_company')));
                        return redirect(admin_url(PERFEX_SAAS_MODULE_NAME . '/companies'));
                    }

                    // Log error
                    log_message('error', _l('perfex_saas_error_completing_action') . ':' . ($this->db->error() ?? ''));

                    throw new \Exception(_l('perfex_saas_error_completing_action'), 1);
                } catch (\Throwable $th) {

                    set_alert('danger', $th->getMessage());
                    return perfex_saas_redirect_back();
                }
            }
        }

        // Show form to edit the new company
        $data['company'] = $this->perfex_saas_model->companies($id);
        $data['title'] = _l('perfex_saas_companies');
        $this->load->view('companies/form', $data);
    }

    function custom_domain()
    {
        if (!has_permission('perfex_saas_companies', '', 'edit')) {
            return access_denied('perfex_saas_companies');
        }

        if ($this->input->post()) {
            try {

                $form_data = $this->input->post(NULL, true);
                $id = $form_data['id'];
                $company = $this->perfex_saas_model->companies($id);
                $data = ['id' => $company->id, 'metadata' => ['pending_custom_domain' => '']];
                $approve = !empty($form_data['approve']);
                if ($approve) {
                    $data['custom_domain'] = $company->metadata->pending_custom_domain;
                }

                // Get the client invoice
                $invoice  = $this->perfex_saas_model->get_company_invoice($company->clientid);

                // Save and make deployment another job
                $_id = $this->perfex_saas_model->create_or_update_company($data, $invoice);
                if ($_id) {

                    set_alert('success', _l('updated_successfully', _l('perfex_saas_custom_domain')));
                    return redirect(admin_url(PERFEX_SAAS_MODULE_NAME . '/companies/edit/' . $_id));
                }

                // Log error
                log_message('error', _l('perfex_saas_error_completing_action') . ':' . ($this->db->error() ?? ''));

                throw new \Exception(_l('perfex_saas_error_completing_action'), 1);
            } catch (\Throwable $th) {

                set_alert('danger', $th->getMessage());
                return perfex_saas_redirect_back();
            }
        }
    }

    /**
     * Delete a company.
     *
     * @param string $id The ID of the company to delete
     * @return mixed Result of the deletion
     */
    function delete($id)
    {
        if (!has_permission('perfex_saas_companies', '', 'delete')) {
            return access_denied('perfex_saas_companies');
        }

        $id = (int)$id;

        $company = $this->perfex_saas_model->companies($id);
        $remove = perfex_saas_remove_company($company);
        if ($remove === true) {
            $this->perfex_saas_model->delete('companies', $id);
            set_alert('success', _l('deleted', _l('perfex_saas_company')));
        } else {
            set_alert('danger', $remove);
        }

        return redirect(admin_url(PERFEX_SAAS_MODULE_NAME . '/companies'));
    }


    /**
     * Method to handle deploy service for a company instance (AJAX)
     *
     * @param string $company_id
     * @return void
     */
    public function deploy($company_id = '')
    {

        echo json_encode(perfex_saas_deployer($company_id));
        exit();
    }
}
