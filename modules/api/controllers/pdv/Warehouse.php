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

        // Detecta o tipo de conteúdo da requisição
        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

        // Carrega os dados em uma variável intermediária, sem sobrescrever $_POST
        if ($is_multipart) {
            $input_data = $this->input->post();
            log_activity('Warehouse Create Input (multipart): ' . json_encode($input_data));
        } else {
            $raw = file_get_contents("php://input");
            $clean = $this->security->xss_clean($raw);
            $input_data = json_decode($clean, true);
            log_activity('Warehouse Create Input (json): ' . json_encode($input_data));
        }

        // Se não houver dados, retorna erro
        if (empty($input_data)) {
            $this->response([
                'status'  => FALSE,
                'message' => 'Invalid input data'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Lista de campos obrigatórios (validação estrita)
        $required_fields = [
            'razao_social',
            'cnpj',
            'warehouse_name',
            'ie',
            'im',
            'cnae',
            'crt',
            'endereco',
            'numero',
            'bairro',
            'cidade',
            'ccidade',
            'cep',
            'estado',
            'codigoUF',
            'telefone'
        ];

        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($input_data[$field]) || trim($input_data[$field]) === '') {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $this->response([
                'status'         => FALSE,
                'message'        => 'Campos obrigatórios ausentes ou nulos',
                'missing_fields' => $missing_fields
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Monta array para inserção no banco
        $insert_payload = [
            'warehouse_code'       => $input_data['warehouse_code']    ?? null,
            'warehouse_name'       => $input_data['warehouse_name'],
            'razao_social'         => $input_data['razao_social'],
            'type'                 => $input_data['type']             ?? null,
            'note'                 => $input_data['note']             ?? null,
            'franqueado_id'        => $input_data['franqueado_id']    ?? null,
            'cnpj'                 => $input_data['cnpj'],
            'im'                   => $input_data['im'],
            'ie'                   => $input_data['ie'],
            'cep'                  => $input_data['cep'],
            'endereco'             => $input_data['endereco'],
            'numero'               => $input_data['numero'],
            'complemento'          => $input_data['complemento']      ?? null,
            'bairro'               => $input_data['bairro'],
            'cidade'               => $input_data['cidade'],
            'estado'               => $input_data['estado'],
            'display'              => $input_data['display']          ?? 1,
            'password_nfe'         => $input_data['password_nfe']      ?? null,
            'cnae'                 => $input_data['cnae'],
            'crt'                  => $input_data['crt'],
            'warehouse_number'     => $input_data['warehouse_number'] ?? null,
            'telefone'             => $input_data['telefone'],
            'dt_cto_certifcado_a2' => $input_data['dt_cto_certifcado_a2'] ?? null,
            'tpAmb'                => $input_data['tpAmb']            ?? 2,
            'ccidade'              => $input_data['ccidade'],
            'codigoUF'             => $input_data['codigoUF'],
            'situacao_tributaria'  => $input_data['situacao_tributaria'] ?? null,
            'cscid'                => $input_data['cscid']            ?? null,
            'csc'                  => $input_data['csc']              ?? null,
        ];

        // Insere no banco
        $new_id = $this->Warehouse_model->add($insert_payload);
        if (!$new_id) {
            $this->response([
                'status'  => FALSE,
                'message' => 'Failed to create warehouse'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        // Se multipart e foi enviado arquivo .pfx, faz upload
        if ($is_multipart && isset($_FILES['arquivo_nfe']) && $_FILES['arquivo_nfe']['error'] === UPLOAD_ERR_OK) {
            $file      = $_FILES['arquivo_nfe'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($extension !== 'pfx') {
                log_activity('Invalid file type for warehouse ' . $new_id);
            } else {
                $max_size = 5 * 1024 * 1024; // 5MB
                if ($file['size'] <= $max_size) {
                    $upload_dir = './uploads/warehouse/' . $new_id . '/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $filename    = uniqid() . '.pfx';
                    $upload_path = $upload_dir . $filename;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $file_url = base_url(str_replace('./', '', $upload_path));
                        $this->db->where('warehouse_id', $new_id);
                        $this->db->update(db_prefix() . 'warehouse', ['arquivo_nfe' => $file_url]);
                    } else {
                        log_activity('Erro ao mover certificado para warehouse ' . $new_id);
                    }
                } else {
                    log_activity('Arquivo de certificado excede 5MB: warehouse ' . $new_id);
                }
            }
        }

        // Replicação de produtos, se aplicável
        $warehouse = $this->Warehouse_model->get($new_id);
        if ($warehouse && in_array($warehouse->type, ['franquia', 'filial', 'distribuidor'])) {
            $produtos = $this->Invoice_items_model->get_by_type($warehouse->type);
            foreach ($produtos as $prod) {
                $prod->warehouse_id = $warehouse->warehouse_id;
                $this->Invoice_items_model->add((array) $prod);
            }
        }

        // Retorna sucesso com os dados do novo warehouse
        $this->response([
            'status'  => TRUE,
            'message' => 'Warehouse created successfully',
            'data'    => $warehouse
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

        // Verifica se a warehouse existe
        $current_warehouse = $this->Warehouse_model->get($id);
        if (!$current_warehouse) {
            $this->response(['status' => FALSE, 'message' => 'Warehouse not found'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Validação de campos obrigatórios
        $required_fields = [
            'razao_social',
            'cnpj',
            'warehouse_name',
            'ie',
            'im',
            'cnae',
            'crt',
            'endereco',
            'numero',
            'bairro',
            'cidade',
            'ccidade',
            'cep',
            'estado',
            'codigoUF',
            'telefone'
        ];

        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            $this->response([
                'status'         => FALSE,
                'message'        => 'Campos obrigatórios ausentes ou nulos',
                'missing_fields' => $missing_fields
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Filtra apenas os campos permitidos para atualização
        $update_data = array_intersect_key($_POST, array_flip([
            'warehouse_code',
            'warehouse_name',
            'warehouse_number',
            'razao_social',
            'type',
            'note',
            'franqueado_id',
            'cnpj',
            'im',
            'ie',
            'cnae',
            'crt',
            'tpAmb',
            'situacao_tributaria',
            'cscid',
            'csc',
            'telefone',
            'dt_cto_certifcado_a2',
            'cep',
            'endereco',
            'numero',
            'complemento',
            'bairro',
            'cidade',
            'estado',
            'ccidade',
            'codigoUF',
            'display',
            'password_nfe',
        ]));

        // Atualiza os dados
        $output = $this->Warehouse_model->update($update_data, $id);

        // Upload do certificado, se enviado
        if ($is_multipart && isset($_FILES['arquivo_nfe']) && $_FILES['arquivo_nfe']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['arquivo_nfe'];

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($extension !== 'pfx') {
                log_activity('Invalid file type uploaded for warehouse ' . $id . '. Only PFX files are allowed.');
            } else {
                $max_size = 5 * 1024 * 1024;
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
                        $file_url = base_url(str_replace('./', '', $upload_path));
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
            'status'  => TRUE,
            'message' => 'Warehouse updated successfully',
            'data'    => $updated_warehouse
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
