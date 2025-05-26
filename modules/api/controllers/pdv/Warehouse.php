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
        $this->load->model('Invoice_items_model');
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
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: ''; // Alterado para this->post
        $sortField = $this->post('sortField') ?: 'id'; // Alterado para this->post
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC'; // Alterado para this->post
        $franqueado_id = $this->post('franqueado_id') ?: 0;

        // Chamada ao modelo
        $data = $this->Warehouse_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, $franqueado_id);

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
    public function list_get($id = '')
    {

        // Chamada ao modelo
        $data = $this->Warehouse_model->get($id);

        // Verifica se encontrou dados
        if (empty($data)) {
            $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response($data, REST_Controller::HTTP_OK);
        }
    }


    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            $_POST = $this->input->post();
            log_activity('Warehouse Create Input (multipart): ' . json_encode($_POST));
        } else {
            $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
            log_activity('Warehouse Create Input (json): ' . json_encode($_POST));
        }

        if (empty($_POST)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid input data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Ajustando os campos de entrada para os campos reais da tabela
        $_input = [
            'warehouse_code' => $_POST['warehouse_code'] ?? null,
            'warehouse_name' => $_POST['warehouse_name'] ?? null,
            'cnpj' => $_POST['cnpj'] ?? null,
            'type' => $_POST['type'] ?? null,
            'razao_social' => $_POST['razao_social'] ?? null,
            // 'order' => $_POST['order'] ?? null,
            'display' => $_POST['display'] ?? null,
            'note' => $_POST['note'] ?? null,
            'cidade' => $_POST['cidade'] ?? null,
            'estado' => $_POST['estado'] ?? null,
            'ie' => $_POST['ie'] ?? null,
            'im' => $_POST['im'] ?? null,
            'cep' => $_POST['cep'] ?? null,
            'complemento' => $_POST['complemento'] ?? null,
            'bairro' => $_POST['bairro'] ?? null,
            'numero' => $_POST['numero'] ?? null,
            'endereco' => $_POST['endereco'] ?? null,
            'franqueado_id' => $_POST['franqueado_id'] ?? null,
            'password_nfe' => $_POST['password_nfe'] ?? null
        ];

        // Validação dos campos
        $this->form_validation->set_data($_input);
        $this->form_validation->set_rules('warehouse_name', 'Warehouse Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('endereco', 'Endereco', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('display', 'Display', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('cidade', 'Cidade', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('estado', 'Estado', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('bairro', 'bairro', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('cnpj', 'Cnpj', 'trim|required|max_length[20]');
        $this->form_validation->set_rules('cep', 'Cep', 'trim|required|max_length[9]|regex_match[/^\d{5}-\d{3}$/]');

        if ($this->form_validation->run() === FALSE) {
            $this->response(['status' => FALSE, 'error' => $this->form_validation->error_array()], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $output = $this->Warehouse_model->add($_input);
        if (!$output) {
            $this->response(['status' => FALSE, 'message' => 'Failed to create warehouse'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        if ($is_multipart && isset($_FILES['arquivo_nfe']) && $_FILES['arquivo_nfe']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['arquivo_nfe'];

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension !== 'pfx') {
                log_activity('Invalid file type uploaded for warehouse ' . $output . '. Only PFX files are allowed.');
            } else {
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($file['size'] <= $max_size) {
                    $upload_dir = './uploads/warehouse/' . $output . '/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $filename = uniqid() . '.pfx';
                    $upload_path = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $server_url = base_url();
                        $relative_path = str_replace('./', '', $upload_path);
                        $file_url = rtrim($server_url, '/') . '/' . $relative_path;

                        $this->db->where('warehouse_id', $output);
                        $this->db->update(db_prefix() . 'warehouse', ['arquivo_nfe' => $file_url]);
                    } else {
                        log_activity('Failed to move uploaded file for warehouse ' . $output);
                    }
                } else {
                    log_activity('File too large for warehouse ' . $output . '. Maximum size is 5MB.');
                }
            }
        } 
        
        ini_set('display_errors', 1);
		ini_set('display_startup_erros', 1);
		error_reporting(E_ALL);
       
         $warehouse = $this->Warehouse_model->get($output);
        if($warehouse){
           
            
            if($warehouse->type == 'franquia' || $warehouse->type == 'filial' || $warehouse->type == 'distribuidor' ){
                
              $produtos = $this->Invoice_items_model->get_by_type($warehouse->type);
              foreach($produtos as $prod){
                  $prod->warehouse_id = $warehouse->warehouse_id;
                  $this->Invoice_items_model->add($prod);
              }
             
                      
             
            }
           
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Warehouse created successfully',
            'data' => $warehouse
        ], REST_Controller::HTTP_OK);
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

    public function update_post($id = '')
    {
        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            $_POST = $this->input->post();
            log_activity('Warehouse Update Input (multipart): ' . json_encode($_POST));
        } else {
            $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
            log_activity('Warehouse Update Input (json): ' . json_encode($_POST));
        }

        if (empty($_POST) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid Warehouse ID or Data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Get current warehouse data
        $current_warehouse = $this->Warehouse_model->get($id);
        if (!$current_warehouse) {
            $this->response(['status' => FALSE, 'message' => 'Warehouse not found'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Ajustar os campos permitidos para atualização
        $update_data = array_intersect_key($_POST, array_flip([
            'warehouse_code',
            'warehouse_name',
            'cnpj',
            'type',
            'razao_social',
            // 'order',
            'display',
            'note',
            'cidade',
            'estado',
            'ie',
            'im',
            'cep',
            'complemento',
            'bairro',
            'numero',
            'endereco',
            'franqueado_id',
            'password_nfe',
        ]));

        // First update the warehouse data through the model
        $output = $this->Warehouse_model->update($update_data, $id);

        if ($is_multipart && isset($_FILES['arquivo_nfe']) && $_FILES['arquivo_nfe']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['arquivo_nfe'];

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension !== 'pfx') {
                log_activity('Invalid file type uploaded for warehouse ' . $id . '. Only PFX files are allowed.');
            } else {
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($file['size'] <= $max_size) {
                    $upload_dir = './uploads/warehouse/' . $id . '/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $old_file = $current_warehouse->arquivo_nfe;
                    if ($old_file) {
                        $old_file_path = str_replace(base_url(), './', $old_file);
                        if (file_exists($old_file_path)) {
                            unlink($old_file_path);
                        }
                    }

                    $filename = uniqid() . '.pfx';
                    $upload_path = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $server_url = base_url();
                        $relative_path = str_replace('./', '', $upload_path);
                        $file_url = rtrim($server_url, '/') . '/' . $relative_path;

                        $this->db->where('warehouse_id', $id);
                        $this->db->update(db_prefix() . 'warehouse', ['arquivo_nfe' => $file_url]);
                    } else {
                        log_activity('Failed to move uploaded file for warehouse ' . $id);
                    }
                } else {
                    log_activity('File too large for warehouse ' . $id . '. Maximum size is 5MB.');
                }
            }
        }

        $updated_warehouse = $this->Warehouse_model->get($id);

        $this->response([
            'status' => TRUE,
            'message' => 'Warehouse updated successfully',
            'data' => $updated_warehouse
        ], REST_Controller::HTTP_OK);
    }

    public function remove_post()
    {
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
