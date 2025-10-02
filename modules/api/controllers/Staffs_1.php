<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require __DIR__ . '/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Staffs extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Api_model');
        $this->load->model('Authentication_model');
    }

    public function auth_post() {

        echo 3; exit;

        if ($this->input->post()) {

            $email = $this->input->post('email');
            $password = $this->input->post('password', false);

            $data = $this->Authentication_model->login($email, $password, true, true, true);
            
       

            if (is_array($data) && isset($data['memberinactive'])) {


                $this->response([
                    'status' => FALSE,
                    'message' => _l('admin_auth_inactive_account')
                        ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
            } elseif (is_array($data) && isset($data['two_factor_auth'])) {

                $this->session->set_userdata('_two_factor_auth_established', true);

                if ($data['user']->two_factor_auth_enabled == 1) {
                    $this->Authentication_model->set_two_factor_auth_code($data['user']->staffid);
                    $sent = send_mail_template('staff_two_factor_auth_key', $data['user']);

                    if (!$sent) {
                        set_alert('danger', _l('two_factor_auth_failed_to_send_code'));
                        redirect(admin_url('authentication'));
                    } else {
                        $this->session->set_userdata('_two_factor_auth_staff_email', $email);
                        set_alert('success', _l('two_factor_auth_code_sent_successfully', $email));
                        redirect(admin_url('authentication/two_factor'));
                    }
                } else {

                    // Load Authorization Library or Load in autoload config file
                    $this->load->library('Authorization_Token');

                    $payload = [
                        'user' => $email,
                        'name' => $password,
                    ];
                    // generate a token
                    $data['token'] = $this->authorization_token->generateToken($payload);

                    $this->response($data, REST_Controller::HTTP_OK);
                    exit;
                }
            } elseif ($data == false) {

                    $this->response([
                    'status' => FALSE,
                        'error' => _l('admin_auth_invalid_email_or_password'),
                    'message' =>_l('admin_auth_invalid_email_or_password')
                        ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
                
             
                exit;
            } else {

                $this->response($data, REST_Controller::HTTP_OK);
            }
             exit;
        }
    }

    /**
     * @api {get} api/staffs/:id Request Staff information
     * @apiName GetStaff
     * @apiGroup Staff
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Staff unique ID.
     *
     * @apiSuccess {Object} Staff information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "staffid": "8",
     *          "email": "data1.gsts@gmail.com",
     *          "firstname": "Đào Quang Dân",
     *          "lastname": "",
     *          "facebook": "",
     *          "linkedin": "",
     *          "phonenumber": "",
     *          "skype": "",
     *          "password": "$2a$08$ySLokLAM.AqmW9ZjY2YREO0CIrd5K4Td\/Bpfp8d9QJamWNUfreQuK",
     *          "datecreated": "2019-02-25 09:11:31",
     *          "profile_image": "8.png",
     *         ...
     *     }
     *
     * @apiError StaffNotFound The id of the Staff was not found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_get2($id = '') {
        // If the id parameter doesn't exist return all the
        $data = $this->Api_model->get_table('staffs', $id);

        // Check if the data store contains
        if ($data) {
            $data = $this->Api_model->get_api_custom_data($data, "staff", $id);

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
                    ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    public function data_get($id = '') {
        // Obtendo os dados

        if ($id == 1) {
            $type = 'representada';
            $id = '';
            $where = array('type' => $type);
        } elseif ($id == 2) {

            $type = 'representante';
            $id = '';
            $where = array('type' => $type);
        } elseif ($id == 3) {


            $id = '';
            $where = [];
        }



        $data = $this->Api_model->get_table('staffs', $id, $where);

        if ($data) {
            // Personalizando a resposta para o novo formato
            $formattedData = $this->format_response($data);

            $this->response($formattedData, REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data found'
                    ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

// Função para formatar os dados
    private function format_response($data) {
        $tableRows = [];

        // Criando o array formatado
        foreach ($data as $item) {
            $tableRows[] = [
                'id' => (int) $item['staffid'],
                'name' => $item['firstname'] . ' ' . $item['lastname'], // Concatenando o nome e sobrenome
                'code' => (int) $item['staffid'],
                'cnpjCpf' => '30503942855', // Adicionar aqui a lógica do CNPJ/CPF se houver
                'city' => 'SP', // Adicionar a cidade se houver
                'status' => $item['active'] == "1" ? "active" : "inactive", // Status baseado no campo 'active'
                'role' => $this->map_role($item['role']), // Mapeamento de função
                'phoneNumber' => '+5511980102250',
                'zipCode' => '048130100',
                'email' => $item['email'],
                'state' => 'SP',
                'city' => 'Sao Paulo',
                'country' => 'Brazil',
                'address' => 'Rua MArio Rossi',
                'company' => $item['firstname'] . ' ' . $item['lastname']
            ];
        }

        return [
            'TABLE_ROWS' => $tableRows,
            'OPTIONS_1' => [
                ['value' => 'all', 'label' => 'All', 'color' => 'default'],
                ['value' => 'active', 'label' => 'Active', 'color' => 'success'],
                ['value' => 'inactive', 'label' => 'Inactive', 'color' => 'warning'],
                ['value' => 'block', 'label' => 'Block', 'color' => 'error']
            ],
            'OPTIONS_2' => ['active', 'block'],
            'TABLE_HEAD' => [
                ['id' => 'codigo', 'label' => 'Código'],
                ['id' => 'name', 'label' => 'Nome da empresa'],
                ['id' => 'cnpjCpf', 'label' => 'CNPJ'],
                ['id' => 'city', 'label' => 'Cidade'],
                ['id' => 'email', 'label' => 'Contato'],
                ['id' => 'email', 'label' => 'Telefone'],
                ['id' => 'role', 'label' => 'Perfil'],
                ['id' => 'acoes', 'label' => 'Ações']
            ]
        ];
    }

// Função para mapear os papéis (roles)
    private function map_role($role) {
        switch ($role) {
            case '1':
                return 'admin';
            case '2':
                return 'user';
            case '3':
                return 'guest';
            default:
                return 'user'; // Valor padrão
        }
    }

    /**
     * @api {get} api/staffs/search/:keysearch Search Staff Information
     * @apiName GetStaffSearch
     * @apiGroup Staff
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} keysearch Search keywords.
     *
     * @apiSuccess {Object} Staff information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "staffid": "8",
     *          "email": "data1.gsts@gmail.com",
     *          "firstname": "Đào Quang Dân",
     *          "lastname": "",
     *          "facebook": "",
     *          "linkedin": "",
     *          "phonenumber": "",
     *          "skype": "",
     *          "password": "$2a$08$ySLokLAM.AqmW9ZjY2YREO0CIrd5K4Td\/Bpfp8d9QJamWNUfreQuK",
     *          "datecreated": "2019-02-25 09:11:31",
     *          "profile_image": "8.png",
     *         ...
     *     }
     *
     * @apiError StaffNotFound The id of the Staff was not found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '') {
        $data = $this->Api_model->search('staff', $key);
        // Check if the data store contains
        if ($data) {
            $data = $this->Api_model->get_api_custom_data($data, "staff");

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
                    ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {post} api/staffs Add New Staff
     * @apiName PostStaffs
     * @apiGroup Staff
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} firstname             Mandatory Staff Name.
     * @apiParam {String} email                 Mandatory Staff Related.
     * @apiParam {String} password              Mandatory Staff password.
     * @apiParam {Number} [hourly_rate]         Optional hourly rate.
     * @apiParam {String} [phonenumber]         Optional Staff phonenumber.
     * @apiParam {String} [facebook]            Optional  Staff facebook.
     * @apiParam {String} [linkedin]            Optional  Staff linkedin.
     * @apiParam {String} [skype]               Optional Staff skype.
     * @apiParam {String} [default_language]    Optional Staff default language.
     * @apiParam {String} [email_signature]     Optional Staff email signature.
     * @apiParam {String} [direction]           Optional Staff direction.
     * @apiParam {String} [send_welcome_email]  Optional Staff send welcome email.
     * @apiParam {Number[]} [departments]  Optional Staff departments.
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *     array (size=15)
     *     'firstname' => string '4' (length=1)
     *     'email' => string 'a@gmail.com' (length=11)
     *     'hourly_rate' => string '0' (length=1)
     *     'phonenumber' => string '' (length=0)
     *     'facebook' => string '' (length=0)
     *     'linkedin' => string '' (length=0)
     *     'skype' => string '' (length=0)
     *     'default_language' => string '' (length=0)
     *     'email_signature' => string '' (length=0)
     *     'direction' => string '' (length=0)
     *    'departments' => 
     *       array (size=5)
     *         0 => string '1' (length=1)
     *         1 => string '2' (length=1)
     *         2 => string '3' (length=1)
     *         3 => string '4' (length=1)
     *         4 => string '5' (length=1)
     *     'send_welcome_email' => string 'on' (length=2)
     *     'fakeusernameremembered' => string '' (length=0)
     *     'fakepasswordremembered' => string '' (length=0)
     *     'password' => string '1' (length=1)
     *     'role' => string '18' (length=2)
     *
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Staff add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Staff add successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Staff add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Staff add fail."
     *     }
     * 
     */
    public function data_post() {


        \modules\api\core\Apiinit::the_da_vinci_code('api');
        // form validation
        $this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email', array('is_unique' => 'This %s already exists please enter another Staff Email'));
        $this->form_validation->set_rules('password', 'Password', 'trim|required', array('is_unique' => 'This %s already exists please enter another Staff password'));
        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $departments = $this->Api_model->value($this->input->post('departments', TRUE));

            $firstname = $this->input->post('firstname') == null ? $this->input->post('company') : $this->input->post('firstname');
            $insert_data = [
                'firstname' => $firstname,
                'email' => $this->input->post('email', TRUE),
                'password' => $this->input->post('password', TRUE),
                'lastname' => '',
                'hourly_rate' => $this->Api_model->value($this->input->post('hourly_rate', TRUE)),
                'phonenumber' => $this->Api_model->value($this->input->post('phonenumber', TRUE)),
                'facebook' => $this->Api_model->value($this->input->post('facebook', TRUE)),
                'linkedin' => $this->Api_model->value($this->input->post('linkedin', TRUE)),
                'skype' => $this->Api_model->value($this->input->post('skype', TRUE)),
                'default_language' => $this->Api_model->value($this->input->post('default_language', TRUE)),
                'email_signature' => $this->Api_model->value($this->input->post('email_signature', TRUE)),
                'direction' => $this->Api_model->value($this->input->post('direction', TRUE)),
                'send_welcome_email' => $this->Api_model->value($this->input->post('send_welcome_email', TRUE)),
                'role' => '1',
                'permissions' => array(
                    'bulk_pdf_exporter' => array('view'),
                    'contracts' => array('create', 'edit', 'delete'),
                    'credit_notes' => array('create', 'edit', 'delete'),
                    'customers' => array('view', 'create', 'edit', 'delete'),
                    'email_templates' => array('view', 'edit'),
                    'estimates' => array('create', 'edit', 'delete'),
                    'expenses' => array('create', 'edit', 'delete'),
                    'invoices' => array('create', 'edit', 'delete'),
                    'items' => array('view', 'create', 'edit', 'delete'),
                    'knowledge_base' => array('view', 'create', 'edit', 'delete'),
                    'payments' => array('view', 'create', 'edit', 'delete'),
                    'projects' => array('view', 'create', 'edit', 'delete'),
                    'proposals' => array('create', 'edit', 'delete'),
                    'contracts' => array('view'),
                    'roles' => array('view', 'create', 'edit', 'delete'),
                    'settings' => array('view', 'edit'),
                    'staff' => array('view', 'create', 'edit', 'delete'),
                    'subscriptions' => array('create', 'edit', 'delete'),
                    'tasks' => array('view', 'create', 'edit', 'delete'),
                    'checklist_templates' => array('create', 'delete'),
                    'leads' => array('view', 'delete'),
                    'goals' => array('view', 'create', 'edit', 'delete'),
                    'surveys' => array('view', 'create', 'edit', 'delete'),
                )
            ];
            if ($departments != '') {
                $insert_data['departments'] = $departments;
            }
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->Api_model->value($this->input->post('custom_fields', TRUE));
            }
            // insert data
            $this->load->model('staff_model');
            $output = $this->staff_model->add($insert_data);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Staff add successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Staff add fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/staffs/:id Delete a Staff
     * @apiName DeleteStaff
     * @apiGroup Staff
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Staff unique ID.
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Staff registration successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Staff Delete."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Not register your accout.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Staff Not Delete."
     *     }
     */
    public function data_delete($id) {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array(
                'status' => FALSE,
                'message' => 'Invalid Staff ID'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $this->load->model('staff_model');
            $output = $this->staff_model->delete($id, 0);
            if ($output === TRUE) {
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Staff Delete Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Staff Delete Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/staffs/:id Update a Staff
     * @apiName PutStaff
     * @apiGroup Staff
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} firstname             Mandatory Staff Name.
     * @apiParam {String} email                 Mandatory Staff Related.
     * @apiParam {String} password              Mandatory Staff password.
     * @apiParam {Number} [hourly_rate]         Optional hourly rate.
     * @apiParam {String} [phonenumber]         Optional Staff phonenumber.
     * @apiParam {String} [facebook]            Optional  Staff facebook.
     * @apiParam {String} [linkedin]            Optional  Staff linkedin.
     * @apiParam {String} [skype]               Optional Staff skype.
     * @apiParam {String} [default_language]    Optional Staff default language.
     * @apiParam {String} [email_signature]     Optional Staff email signature.
     * @apiParam {String} [direction]           Optional Staff direction.
     * @apiParam {Number[]} [departments]  Optional Staff departments.
     *
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *     "firstname": "firstname",
     *     "email": "aa454@gmail.com",
     *     "hourly_rate": "0.00",
     *     "phonenumber": "",
     *     "facebook": "",
     *     "linkedin": "",
     *     "skype": "",
     *     "default_language": "",
     *     "email_signature": "",
     *     "direction": "",
     *     "departments": {
     *          "0": "1",
     *          "1": "2"
     *      },
     *     "password": "123456"
     *  }
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Staff Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Staff Update Successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Staff Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Staff Update Fail."
     *     }
     */
    public function data_put($id) {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST) || !isset($_POST)) {
            $message = array(
                'status' => FALSE,
                'message' => 'Data Not Acceptable OR Not Provided'
            );
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);

        if (empty($id) && !is_numeric($id)) {
            $message = array(
                'status' => FALSE,
                'message' => 'Invalid Staff ID'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {

            $update_data = $this->input->post();
            $update_data['lastname'] = '';
            // update data
            $this->load->model('staff_model');
            $output = $this->staff_model->update($update_data, $id);

            if ($output > 0 && !empty($output)) {
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Staff Update Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Staff Update Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }
}
