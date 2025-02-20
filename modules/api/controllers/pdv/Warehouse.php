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
class Warehouse extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Warehouse_model');
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
    public function list_post($id = '')
    {
        $page = (int) $this->post('page') ?: 1; // Página atual, padrão 1
        $limit = (int) $this->post('pageSize') ?: 10; // Itens por página, padrão 10
        $search = $this->post('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->post('sortField') ?: 'warehouse_id'; // Campo para ordenação, padrão 'warehouse_id'
        $sortOrder = strtolower($this->post('sortOrder')) === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente

        // Chamada ao modelo
        $data = $this->Warehouse_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        // Verifica se encontrou dados
        if (empty($data['data'])) {
            $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response([
                'status' => TRUE,
                'total' => $data['total'],
                'data' => $data['data']
            ], REST_Controller::HTTP_OK);
        }
    }

    public function create_post() {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid input data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Ajustando os campos de entrada para os campos reais da tabela
        $_input = [
            'warehouse_code' => $_POST['warehouse_code'] ?? null,
            'warehouse_name' => $_POST['warehouse_name'] ?? null,
            'warehouse_address' => $_POST['warehouse_address'] ?? null,
            'order' => $_POST['order'] ?? null,
            'display' => $_POST['display'] ?? null,
            'note' => $_POST['note'] ?? null,
            'city' => $_POST['city'] ?? null,
            'state' => $_POST['state'] ?? null,
            'zip_code' => $_POST['zip_code'] ?? null,
            'country' => $_POST['country'] ?? null,
            'franqueado_id' => $_POST['franqueado_id'] ?? null
        ];

        // Validação dos campos
        $this->form_validation->set_data($_input);
        $this->form_validation->set_rules('warehouse_name', 'Warehouse Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('warehouse_address', 'Warehouse Address', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('order', 'Order', 'trim|required|numeric');
        $this->form_validation->set_rules('display', 'Display', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('city', 'City', 'trim|required|max_length[100]');
//        $this->form_validation->set_rules('note', 'Note', 'trim|required|max_length[100]');
//        $this->form_validation->set_rules('franqueado_id', 'FranqueadoID', 'trim|required|max_length[100]');
//        $this->form_validation->set_rules('order', 'Order', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('state', 'State', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('zip_code', 'Zip Code', 'trim|required|max_length[10]');
        $this->form_validation->set_rules('country', 'Country', 'trim|required|numeric');

        if ($this->form_validation->run() === FALSE) {
            $this->response(['status' => FALSE, 'error' => $this->form_validation->error_array()], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $output = $this->Warehouse_model->add($_input);
        if ($output) {
            $this->response(['status' => TRUE, 'message' => 'Warehouse created successfully', 'data' => $this->Warehouse_model->get($output)], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to create warehouse'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

//    public function get_get($id = '') {
//        if (!is_numeric($id)) {
//            $this->response(['status' => FALSE, 'message' => 'Invalid Warehouse ID'], REST_Controller::HTTP_BAD_REQUEST);
//            return;
//        }
//
//        $warehouse = $this->Warehouse_model->get($id);
//        if ($warehouse) {
//            $this->response(['status' => TRUE, 'data' => $warehouse], REST_Controller::HTTP_OK);
//        } else {
//            $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
//        }
//    }
    
    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Client ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $client = $this->Warehouse_model->get($id);

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

    public function update_post($id = '') {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid Warehouse ID or Data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Ajustar os campos permitidos para atualização
        $update_data = array_intersect_key($_POST, array_flip([
            'warehouse_code', 
            'warehouse_name', 
            'warehouse_address',  
            'display',
            'order',
            'note',
            'city', 
            'state', 
            'zip_code', 
            'country', 
            'franqueado_id',
        ]));

        // Verificar se há dados para atualizar
        if (empty($update_data)) {
            $this->response(['status' => FALSE, 'message' => 'No valid data to update'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Atualizar o armazém
        $output = $this->Warehouse_model->update($update_data, $id);
        if ($output) {
            $this->response(['status' => TRUE, 'message' => 'Warehouse updated successfully', 'data' => $this->Warehouse_model->get($id)], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to update warehouse'], REST_Controller::HTTP_NOT_FOUND);
        }
    }
    
    public function remove_post() {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['rows']) || !is_array($_POST['rows'])) {
            $this->response(['status' => FALSE, 'message' => 'Invalid request: rows array is required'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = array_filter($_POST['rows'], 'is_numeric');
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            if ($this->Warehouse_model->delete($id)) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        $this->response([
            'status' => $success_count > 0,
            'message' => $success_count . ' warehouse(s) deleted successfully',
            'failed_ids' => $failed_ids
        ], $success_count > 0 ? REST_Controller::HTTP_OK : REST_Controller::HTTP_NOT_FOUND);
    }

}
