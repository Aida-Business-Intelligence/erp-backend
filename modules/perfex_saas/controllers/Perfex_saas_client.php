<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This is a client class for managing instances and subscribing to a package.
 */
class Perfex_saas_client extends ClientsController
{
    /**
     * Common url to redirect to
     *
     * @var string
     */
    public $redirect_url = '';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        // Load essensial models
        $this->load->model('payment_modes_model');
        $this->load->model('invoices_model');
        $this->load->model('currencies_model');

        $this->redirect_url = base_url('clients?companies');

        if (!is_client_logged_in()) {

            return redirect($this->redirect_url);
        }
    }

    /**
     * Method to create a company instance
     *
     * @return void
     */
    public function create()
    {
        if (!$this->input->post()) {
            return show_404();
        }
        return $this->create_or_edit_company();
    }

    /**
     * Method to handle company editing
     *
     * @param string $slug
     * @return void
     */
    public function edit($slug)
    {
        if (!$this->input->post()) {
            return show_404();
        }
        $id = $this->get_auth_company_by_slug($slug)->id;
        return $this->create_or_edit_company($id);
    }

    /**
     * Method to deploy a company instance (AJAX)
     *
     * @return void
     */
    public function deploy()
    {
        echo json_encode(perfex_saas_deployer('', get_client_user_id()));
        exit();
    }

    /**
     * Method to delete a company instance
     *
     * @param string $slug
     * @return void
     */
    public function delete($slug)
    {
        $company = $this->get_auth_company_by_slug($slug);
        $id = $company->id;

        if ($this->input->post()) {
            $remove = perfex_saas_remove_company($company);
            if ($remove === true) {
                $this->perfex_saas_model->delete('companies', (int)$id);
                set_alert('success', _l('deleted', _l('perfex_saas_company')));
            }
        }

        return redirect($this->redirect_url);
    }

    /**
     * Method to subscribe to a package.
     * It assign the package to user and generate and invoice using perfex invoicing system.
     *
     * @param string $packageslug
     * @return void
     */
    public function subscribe($packageslug)
    {
        try {

            $clientid = get_client_user_id();
            $package = $this->perfex_saas_model->get_entity_by_slug('packages', $packageslug);
            $invoice = $this->perfex_saas_model->generate_company_invoice($clientid, $package->id);

            // Ensure we have the invoice created
            if (!$invoice) {
                set_alert('danger', _l('perfex_saas_error_creating_invoice'));
                return perfex_saas_redirect_back();
            }

            $this->db->where('clientid', $clientid);
            $companies = $this->perfex_saas_model->companies();
            if (empty($companies)) {

                if (get_option('perfex_saas_autocreate_first_company') == '1') {

                    // Create defalt company for the client
                    $company_name = get_client(get_client_user_id())->company;
                    $data = [
                        'name' => empty($company_name) ? 'Company#1' : $company_name,
                        'clientid' => $clientid
                    ];
                    $this->perfex_saas_model->create_or_update_company($data, $package);
                }
            }

            set_alert('success', _l('added_successfully', _l('invoice')));

            if ($package->trial_period < 1)
                return redirect(base_url("invoice/$invoice->id/$invoice->hash"));

            return redirect(base_url('clients?companies'));
        } catch (\Throwable $th) {

            set_alert('danger', $th->getMessage());
        }

        return perfex_saas_redirect_back();
    }

    /**
     * Method to login into an instance magically from the client dashboard.
     * It create auto login cookie (used by perfex core) and redirect to the company admin address.
     * Perfex pick the cookie and authorized. The cookie is localized to the company address only and inserted into db using the instance context.
     * Also when retrieving the cookie from db, the db_simple_query restrict the selection to the instance.
     *
     * @param string $slug
     * @return void
     */
    public function magic_auth($slug)
    {
        // Ensure we have an authenticated client
        if (!is_client_logged_in()) {
            perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), _l('perfex_saas_authentication_required_for_magic_login'), 404);
        }

        $company = $this->perfex_saas_model->get_entity_by_slug('companies', $slug, 'parse_company');
        if (!$company) {
            perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), _l('perfex_saas_page_not_found'), 404);
        }

        // Ensure the company belongs to the logged in client
        if ($company->clientid !== get_client_user_id()) {
            perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), '');
        }

        $this->load->helper('cookie');

        //impersonate the client and run create autologin cookie used by perfex itself
        perfex_saas_impersonate_instance($company, function () {
            $CI = &get_instance();
            $staff = $CI->db->select('staffid')->where('admin', 1)->get(db_prefix() . 'staff')->row();

            if (!$staff)
                perfex_saas_show_tenant_error(_l('perfex_saas_permission_denied'), _l('perfex_saas_instance_does_not_have_any_staff'), 500);

            $user_id = $staff->staffid;

            // Harness the perfex inbuilt auto login
            // @Ref: models/Authentication_model.php
            $staff = true;
            $key = substr(md5(uniqid(rand() . get_cookie($CI->config->item('sess_cookie_name')))), 0, 16);
            $CI->user_autologin->delete($user_id, $key, $staff);
            if ($CI->user_autologin->set($user_id, md5($key), $staff)) {
                set_cookie([
                    'name'  => 'autologin',
                    'value' => serialize([
                        'user_id' => $user_id,
                        'key'     => $key,
                    ]),
                    'expire' => 5000, // 5secs
                    'path' => '/' . perfex_saas_tenant_url_signature(perfex_saas_tenant_slug()) . '/',
                    'httponly' => true,
                ]);
                return true;
            }
        });

        // Redirect to the company address to claim the token.
        $company_admin_url = perfex_saas_tenant_base_url($company, 'admin', 'path');
        return redirect($company_admin_url);
    }

    /**
     * Check for slug availability - AJAX
     *
     * @param string $slug
     * @return string
     */
    public function check_slug($slug)
    {
        $company = $this->perfex_saas_model->get_company_by_slug($slug);
        echo json_encode(['exist' => empty($company)]);
        exit();
    }


    /**
     * Method to validate the client's invoice.
     *
     * @param $clientid
     * @return object|false The invoice object if valid, false otherwise.
     */
    private function validate_client_invoice($clientid)
    {
        $invoice = $this->perfex_saas_model->get_company_invoice($clientid);

        if (empty($invoice->db_scheme)) {
            set_alert('danger', _l('perfex_saas_no_invoice_client'));
            return false;
        }

        if ($invoice->status === Invoices_model::STATUS_OVERDUE) {
            set_alert('danger', _l('perfex_saas_clear_overdue_invoice_note'));
            return false;
        }

        return $invoice;
    }

    /**
     * Method to get a company by slug and ensure it belongs to the logged-in client.
     * Will redirect if failed.
     *
     * @param string $slug The slug of the company.
     * @return mixed The company object if found and authorized, or void otherwise.
     */
    private function get_auth_company_by_slug($slug)
    {
        $clientid = get_client_user_id();

        // Get company and validate
        $company = $this->perfex_saas_model->get_company_by_slug($slug, $clientid);

        if (empty($company)) {
            redirect($this->redirect_url);
        }

        if ($clientid != $company->clientid) {
            return access_denied('perfex_saas_companies');
        }

        return $company;
    }

    /**
     * Common method to handle create or edit form submission.
     * Client company form validation and execution are summarized in this method.
     *
     * @param string $id ID of the company to edit (optional)
     * @return void
     */
    private function create_or_edit_company($id = '')
    {
        $clientid = get_client_user_id();

        // Check if the client has a subscription i.e invoice and it's not overdue
        if (($invoice = $this->validate_client_invoice($clientid)) === false) {
            return redirect($this->redirect_url);
        }

        // Company form validation
        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', _l('perfex_saas_name'), 'required');
        if ($this->form_validation->run() === false) {
            set_alert('danger', validation_errors());
            return redirect($this->redirect_url);
        }

        try {
            $form_data = $this->input->post(NULL, true);

            $data = ['name' => $form_data['name']];
            $data['clientid'] = $clientid;

            $data['custom_domain'] = $form_data['custom_domain'] ?? '';
            $custom_domain = $data['custom_domain'];

            if (!empty($id)) {
                $data['id'] = $id;
            } else {
                // Creating new
                $data['slug'] = $form_data['slug'] ?? '';
            }

            // save to db
            $_id = $this->perfex_saas_model->create_or_update_company($data, $invoice);
            if ($_id) {

                // Notify supper admin on domain update
                if (!empty($custom_domain)) {
                    $autoapprove = (int)($invoice->metadata->autoapprove_custom_domain ?? 0);
                    if (!$autoapprove) {
                        $company = $this->perfex_saas_model->companies($_id);
                        if ($custom_domain !== $company->custom_domain) {
                            // Notify supper admin
                            $notifiedUsers = [];
                            $admin = perfex_saas_get_super_admin();
                            $staffid = $admin->staffid;
                            if (add_notification([
                                'touserid' => $staffid,
                                'description' => _l('perfex_saas_not_domain_request', $custom_domain),
                                'link' => 'perfex_saas/companies/edit/' . $company->id,
                                'additional_data' => serialize([$company->name])
                            ])) {
                                array_push($notifiedUsers, $staffid);
                            }
                            pusher_trigger_notification($notifiedUsers);
                        }
                    }
                }

                set_alert('success', _l(empty($id) ? 'added_successfully' : 'updated_successfully', _l('perfex_saas_company')));
                return redirect($this->redirect_url);
            }

            // Log error
            log_message('error', _l('perfex_saas_error_completing_action') . ':' . ($this->db->error() ?? ''));

            throw new \Exception(_l('perfex_saas_error_completing_action'), 1);
        } catch (\Exception $e) {
            set_alert('danger', $e->getMessage());
            return redirect($this->redirect_url);
        }
    }
}
