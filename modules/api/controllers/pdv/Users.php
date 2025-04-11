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
class Users extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Staff_model');
    }

    /**
     * @api {get} api/client/:id Request customer information
     * @apiName GetCustomer
     * @apiGroup Customer
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id customer unique ID.
     *
     * @apiSuccess {Object} customer information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *          "id": "28",
     *          "name": "Test1",
     *          "description": null,
     *          "status": "1",
     *          "clientid": "11",
     *          "billing_type": "3",
     *          "start_date": "2019-04-19",
     *          "deadline": "2019-08-30",
     *          "customer_created": "2019-07-16",
     *          "date_finished": null,
     *          "progress": "0",
     *          "progress_from_tasks": "1",
     *          "customer_cost": "0.00",
     *          "customer_rate_per_hour": "0.00",
     *          "estimated_hours": "0.00",
     *          "addedfrom": "5",
     *          "rel_type": "customer",
     *          "potential_revenue": "0.00",
     *          "potential_margin": "0.00",
     *          "external": "E",
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */

    //    public function list_post($id = '')
//    {
//
//
//        /*
//          $this->load->model('clients_model');
//
//          $this->clients_model->add_import_items();
//          exit;
//         * 
//         */
//
//        $page = $this->post('page') ? (int) $this->post('page') : 1; // Página atual, padrão 1
//
//        $page = $page + 1;
//
//        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10; // Itens por página, padrão 10
//        $search = $this->post('search') ?: ''; // Parâmetro de busca, se fornecido
//        $sortField = $this->post('sortField') ?: 'staffid'; // Campo para ordenação, padrão 'id'
//        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
//        $type = $this->post('type') ?: 'pdv';
//        $data = $this->Staff_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, $type);
//        
////       var_dump($data);
//
//        if ($data['total'] == 0) {
//            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
//        } else {
//
//            if ($data) {
//                $this->response(['status' => true, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
//            } else {
//                $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
//            }
//        }
//    }
    public function list_post($id = '')
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'staffid';
        $sortOrder = strtoupper($this->post('sortOrder')) === 'DESC' ? 'DESC' : 'ASC';

        // Debug: Verifique os valores recebidos
        // var_dump($sortField, $sortOrder, $search, $page, $limit);

        // Busca os dados com ordenação
        $data = $this->Staff_model->get_api3($id, $page, $limit, $search, $sortField, $sortOrder);

        // Verifica se há dados retornados
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
                    'total' => (int) $data['total'],
                    'data' => $data['data']
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
            'linkedin' => $_POST['linkedin'] ?? null
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



    public function put_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['staffids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $staffids = $_POST['staffids'];
        $active = "0"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Staff_model->update_active($staffids, $active);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Users Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Users.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }
}
