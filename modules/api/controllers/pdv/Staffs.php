<?php

defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../REST_Controller.php';

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
class Staffs extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Staff_model');
    }

    public function permissions_get($userid)
    {

        $permissions = $this->Staff_model->get_staff_permissions($userid);
        if (count($permissions)) {
            $this->response(['data' => $permissions], REST_Controller::HTTP_OK);
        }

        $this->response(['data' => 'Not permissios'], REST_Controller::HTTP_NOT_FOUND);
    }

    public function data_get($id = '')
    {
        $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
        $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->get('sortField') ?: 'staffid'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
        $type = $this->get('type') == null ? 'employee' : $this->get('type'); // Ordem, padrão crescente

        $data = $this->Staff_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, $type);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
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
    public function data_post()
    {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        \modules\api\core\Apiinit::the_da_vinci_code('api');
        // form validation
        $this->form_validation->set_rules('firstname', 'First Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Staff First Name'));
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
            $insert_data = [
                'firstname' => $this->input->post('firstname', TRUE),
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
                'type' => $this->Api_model->value($this->input->post('type', TRUE)),
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
                    'data' => $output,
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
    public function data_delete($id)
    {
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
    public function data_put($id)
    {
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

    //-----> REPRESENTATIVES - IMPLEMENTACAO

    public function list_post($id = '')
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'staffid';
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        $warehouse_id = $this->post('warehouse_id') ?: 0;

        // Garantir que a pesquisa seja aplicada corretamente na consulta
        $data = $this->Staff_model->get_api2($id, $page, $limit, $search, $sortField, $sortOrder, 'representative', $warehouse_id);

        // Verificando se há dados após o filtro
        if (empty($data['data'])) {
            $this->response(
                [
                    'status' => FALSE,
                    'message' => 'No data were found'
                ],
                REST_Controller::HTTP_NOT_FOUND
            );
        } else {
            $this->response(
                [
                    'status' => true,
                    'total' => (int) $data['total'], // Total de registros filtrados
                    'data' => array_values($data['data']) // Dados filtrados
                ],
                REST_Controller::HTTP_OK
            );
        }
    }


    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        // Recebendo e decodificando os dados
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Mapeando os dados de entrada diretamente para o array $input
        $input = [
            'role' => $_POST['role'] ?? null,
            'password' => $_POST['password'] ?? null,
            'profile_image' => $_POST['profile_image'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phonenumber' => $_POST['phonenumber'] ?? null,
            'firstname' => $_POST['firstname'] ?? null,
            'lastname' => $_POST['lastname'] ?? null,
            'facebook' => $_POST['facebook'] ?? null,
            'type' => $_POST['type'] ?? null,
            'linkedin' => $_POST['linkedin'] ?? null,
            'documentType' => $_POST['documentType'] ?? null,
            'warehouse_id' => $_POST['warehouse_id'] ?? null,
            // 'franqueado_id' => $_POST['franqueado_id'] ?? null,
            'vat' => $_POST['vat'] ?? null,
        ];

        // Validação do email, para garantir que o email seja único
        $this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[100]', array('is_unique' => 'This %s already exists please enter another email'));

        if ($this->form_validation->run() == FALSE) {
            // Se a validação falhar
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // Chama o método do modelo para adicionar o novo usuário
            $output = $this->Staff_model->add($input);

            if ($output > 0 && !empty($output)) {
                // Sucesso: usuário foi adicionado com sucesso
                $message = array('status' => 'success', 'message' => 'success', 'data' => $this->Staff_model->get($output));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // Erro: falha ao adicionar o usuário
                $message = array('status' => FALSE, 'message' => 'Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function remove_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['rows']) || empty($_POST['rows'])) {
            $message = array('status' => FALSE, 'message' => 'Invalid request: rows array is required');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = $_POST['rows'];
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            $output = $this->Staff_model->delete($id);
            if ($output === TRUE) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        if ($success_count > 0) {
            $message = array(
                'status' => TRUE,
                'message' => $success_count . ' customer(s) deleted successfully'
            );
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to delete customers',
                'failed_ids' => $failed_ids
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Client ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $client = $this->Staff_model->get($id);

        if ($client) {
            $this->response([
                'status' => TRUE,
                'data' => $client
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }
    public function update_post($id = '')
    {


        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid users ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            // update data
            $this->load->model('Staff_model');
            $output = $this->Staff_model->update($update_data, $update_data['staffid']);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Users Update Successful.', 'data' => $this->Staff_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Users Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }
}
