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
class Contracts extends REST_Controller
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Contracts_model');
    }

    /**
     * @api {get} api/contracts/:id Request Contract information
     * @apiVersion 0.3.0
     * @apiName GetContract
     * @apiGroup Contracts
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiParam {Number} id Contact unique ID
     *
     * @apiSuccess {Object} Contracts information.
     *
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     *	    {
     *	    "id": "1",
     *	    "content": "",
     *	    "description": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.",
     *	    "subject": "New Contract",
     *	    "client": "9",
     *	    "datestart": "2022-11-21",
     *	    "dateend": "2027-11-21",
     *	    "contract_type": "1",
     *	    "project_id": "0",
     *	    "addedfrom": "1",
     *	    "dateadded": "2022-11-21 12:45:58",
     *	    "isexpirynotified": "0",
     *	    "contract_value": "13456.00",
     *	    "trash": "0",
     *	    "not_visible_to_client": "0",
     *	    "hash": "31caaa36b9ea1f45a688c7e859d3ae70",
     *	    "signed": "0",
     *	    "signature": null,
     *	    "marked_as_signed": "0",
     *	    "acceptance_firstname": null,
     *	    "acceptance_lastname": null,
     *	    "acceptance_email": null,
     *	    "acceptance_date": null,
     *	    "acceptance_ip": null,
     *	    "short_link": null,
     *	    "name": "Development Contracts",
     *	    "userid": "9",
     *	    "company": "8web",
     *	    "vat": "",
     *	    "phonenumber": "",
     *	    "country": "0",
     *	    "city": "",
     *	    "zip": "",
     *	    "state": "",
     *	    "address": "",
     *	    "website": "",
     *	    "datecreated": "2022-08-11 14:07:26",
     *	    "active": "1",
     *	    "leadid": null,
     *	    "billing_street": "",
     *	    "billing_city": "",
     *	    "billing_state": "",
     *	    "billing_zip": "",
     *	    "billing_country": "0",
     *	    "shipping_street": "",
     *	    "shipping_city": "",
     *	    "shipping_state": "",
     *	    "shipping_zip": "",
     *	    "shipping_country": "0",
     *	    "longitude": null,
     *	    "latitude": null,
     *	    "default_language": "",
     *	    "default_currency": "0",
     *	    "show_primary_contact": "0",
     *	    "stripe_id": null,
     *	    "registration_confirmed": "1",
     *	    "type_name": "Development Contracts",
     *	    "attachments": [],
     *	    "customfields": [],
     *	    }
     */
    public function data_get($id = '')
    {
        // If the id parameter doesn't exist return all the
        $data = $this->Api_model->get_table('contracts', $id);
        // Check if the data store contains
        if ($data) {
            $data = $this->Api_model->get_api_custom_data($data, "contract", $id);
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code

        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code

        }
    }

    /**
     * @api {delete} api/contracts/:id Delete Contract
     * @apiVersion 0.3.0
     * @apiName DeleteContract
     * @apiGroup Contracts
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Contract Deleted Successfully
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Contract Deleted Successfully"
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Contract Delete Fail
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Contract Delete Fail"
     *     }
     */
    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Contract ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->load->model('contracts_model');
            $is_exist = $this->contracts_model->get($id);
            if (is_object($is_exist)) {
                $output = $this->contracts_model->delete($id);
                if ($output === TRUE) {
                    // success
                    $message = array('status' => TRUE, 'message' => 'Contract Deleted Successfully');
                    $this->response($message, REST_Controller::HTTP_OK);
                } else {
                    // error
                    $message = array('status' => FALSE, 'message' => 'Contract Delete Fail');
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            } else {
                $message = array('status' => FALSE, 'message' => 'Invalid Contract ID');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {post} api/contracts Add New Contract
     * @apiVersion 0.3.0
     * @apiName PostContract
     * @apiGroup Contracts
     *
     *  @apiHeader {String} Authorization Basic Access Authentication token.
     *
     *  @apiParam {String} subject                             Mandatory. Contract subject
     *	@apiParam {Date} datestart                             Mandatory. Contract start date
     *	@apiParam {Number} client                              Mandatory. Customer ID
     *	@apiParam {Date} dateend                               Optional.  Contract end date
     *	@apiParam {Number} contract_type                       Optional.  Contract type
     *  @apiParam {Number} contract_value             	 	   Optional.  Contract value
     *  @apiParam {String} description               	       Optional.  Contract description
     *  @apiParam {String} content              	 	       Optional.  Contract content
     *
     *  @apiParamExample {Multipart Form} Request-Example:
     *   [
     *		"subject"=>"Subject of the Contract,
     *		"datestart"=>"2022-11-11",
     *		"client"=>1,
     *		"dateend"=>"2023-11-11",
     *		"contract_type"=>1,
     *		"contract_value"=>12345,
     *		"description"=>"Lorem Ipsum is simply dummy text of the printing and typesetting industry",
     *		"content"=>"It has been the industry's standard dummy text ever since the 1500s"
     *	]
     *
     *
     * @apiSuccess {Boolean} status Request status.
     * @apiSuccess {String} message Contracts Added Successfully
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Contract Added Successfully"
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message Contract add fail
     * @apiError {String} message The Start date field is required
     * @apiError {String} message The Subject field is required
     * @apiError {String} message The Customer ID field is required
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Contract ID Exists"
     *     }
     *
     * @apiErrorExample Error-Response:
     *   HTTP/1.1 404 Not Found
     *    {
     *	    "status": false,
     *	    "error": {
     *	        "newitems[]": "The Start date field is required"
     *	    },
     *	    "message": "<p>The Start date field is required</p>\n"
     *	}
     *
     * @apiErrorExample Error-Response:
     *   HTTP/1.1 404 Not Found
     *    {
     *	    "status": false,
     *	    "error": {
     *	        "subtotal": "The Subject field is required"
     *	    },
     *	    "message": "<p>The Subject field is required</p>\n"
     *	}
     *
     *  @apiErrorExample Error-Response:
     *   HTTP/1.1 404 Not Found
     *    {
     *	    "status": false,
     *	    "error": {
     *	        "total": "The Customer ID is required"
     *	    },
     *	    "message": "<p>The Customer ID is required</p>\n"
     *	}
     *
     */
    public function data_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        $data = $this->input->post();
        $this->form_validation->set_rules('id', 'Contract ID', 'trim|numeric|greater_than[0]');
        $this->form_validation->set_rules('content', 'Content', 'trim');
        $this->form_validation->set_rules('description', 'Description', 'trim');
        $this->form_validation->set_rules('subject', 'Subject', 'trim|required');
        $this->form_validation->set_rules('client', 'Customer ID', 'trim|required|numeric|greater_than[0]');
        $this->form_validation->set_rules('contract_value', 'Contract Value', 'numeric');
        $this->form_validation->set_rules('datestart', 'Start date', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('dateend', 'End date', 'trim|max_length[255]');
        $this->form_validation->set_rules('contract_type', 'Contract type', 'trim|numeric|greater_than[0]');
        if ($this->form_validation->run() == FALSE) {
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->load->model('contracts_model');
            $id = $this->contracts_model->add($data);
            if ($id > 0 && !empty($id)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Contract Added Successfully');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Contract Add Fail');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function validate_contract_number($number, $contractid)
    {
        $isedit = 'false';
        if (!empty($contractid)) {
            $isedit = 'true';
        }
        $this->form_validation->set_message('validate_contract_number', 'The {field} is already in use');
        $original_number = null;
        $date = $this->input->post('date');
        if (!empty($contractid)) {
            $data = $this->Api_model->get_table('contracts', $contractid);
            $original_number = $data->number;
            if (empty($date)) {
                $date = $data->date;
            }
        }
        $number = trim($number);
        $number = ltrim($number, '0');
        if ($isedit == 'true') {
            if ($number == $original_number) {
                return TRUE;
            }
        }
        if (total_rows(db_prefix() . 'contracts', ['YEAR(date)' => date('Y', strtotime(to_sql_date($date))), 'number' => $number,]) > 0) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function list_post($id = '')
    {

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: ''; // Alterado para this->post
        $sortField = $this->post('sortField') ?: 'id'; // Alterado para this->post
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC'; // Alterado para this->post
        $warehouse_id = $this->post('warehouse_id') ?: 0;
        // $franqueado_id = $this->post('franqueado_id') ?: 0;

        $data = $this->Contracts_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, $warehouse_id);


        // var_dump($data);
        // exit;

        if ($data['total'] == 0) {

            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        } else {

            if ($data) {
                $this->response(['status' => true, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
            } else {
                $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
            }
        }
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
            if ($this->Contracts_model->delete2($id)) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        $this->response([
            'status' => $success_count > 0,
            'message' => $success_count . 'Contract(s) deleted successfully',
            'failed_ids' => $failed_ids
        ], $success_count > 0 ? REST_Controller::HTTP_OK : REST_Controller::HTTP_NOT_FOUND);
    }

    public function upload_contract_post()
    {
        try {
            // Verifica se há dados POST
            if (empty($_POST) && empty($_FILES)) {
                throw new Exception("Nenhum dado recebido");
            }

            // Obtém dados do formulário
            $staff_id = $this->input->post('staff_id');
            $warehouse_id = $this->input->post('warehouse_id');
            $royalties = $this->input->post('royalties');
            $contract_start_date = $this->input->post('datestart');
            $contract_duration = $this->input->post('duration_years');
            $preview_contract_link = $this->input->post('preview_contract');

            // Validações dos novos campos
            if (!is_numeric($royalties) || $royalties < 0 || $royalties > 100) {
                throw new Exception("Royalties deve ser um valor entre 0 e 100");
            }

            if (!strtotime($contract_start_date)) {
                throw new Exception("Data de início inválida");
            }

            if (!is_numeric($contract_duration) || $contract_duration <= 0) {
                throw new Exception("Duração do contrato inválida");
            }

            // Verifica se o arquivo foi enviado
            if (empty($_FILES['contract_file'])) {
                throw new Exception("Nenhum arquivo de contrato recebido");
            }

            $file = $_FILES['contract_file'];

            // Validações básicas
            if (!$staff_id || !is_numeric($staff_id)) {
                throw new Exception("ID do franqueado inválido");
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erro no upload do arquivo: " . $file['error']);
            }

            // Verifica tipo do arquivo
            $filename = $file['name'];
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed_types = ['pdf', 'doc', 'docx'];

            if (!in_array($extension, $allowed_types)) {
                throw new Exception("Tipo de arquivo não permitido. Apenas PDF, DOC e DOCX são aceitos");
            }

            // Cria diretório se não existir
            $upload_dir = FCPATH . 'uploads/contracts/franquias/' . $staff_id . '/';
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception("Falha ao criar diretório de upload");
                }
                // Adiciona arquivo de segurança
                file_put_contents($upload_dir . 'index.html', '<!-- Directory listing disabled -->');
            }

            // Gera nome único para o arquivo
            $unique_filename = uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $unique_filename;

            // Move o arquivo para o diretório de upload
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception("Falha ao salvar arquivo no servidor");
            }

            // Calcula datas do contrato
            $datestart = date('Y-m-d', strtotime($contract_start_date));
            $dateend = date('Y-m-d', strtotime("+$contract_duration years", strtotime($datestart)));

            // Remove o caminho absoluto para salvar apenas o caminho relativo
            $relative_path = 'uploads/contracts/franquias/' . $staff_id . '/' . $unique_filename;

            // Remove qualquer dupla barra na URL
            $base_url = rtrim(base_url(), '/');
            $full_url = $base_url . '/' . ltrim($relative_path, '/');

            // Prepara dados para inserção na tabela contracts
            $contract_data = [
                'subject' => 'Contrato Franquia #' . $staff_id,
                'description' => 'Contrato de franquia digital',
                'contract_url' => $full_url, // Salva o caminho relativo no banco
                'contract_name' => $filename,
                'preview_contract' => $preview_contract_link,
                'staffid' => $staff_id,
                'warehouse_id' => $warehouse_id,
                'royalties' => $royalties,
                'addedfrom' => get_staff_user_id(),
                'dateadded' => date('Y-m-d H:i:s'),
                'datestart' => $datestart,
                'dateend' => $dateend,
                'contract_value' => 0,
                'hash' => app_generate_hash(),
                'signed' => 0,
                'trash' => 0,
                'not_visible_to_client' => 1
            ];

            // Insere na tabela contracts
            $this->db->insert(db_prefix() . 'contracts', $contract_data);
            $contract_id = $this->db->insert_id();

            if (!$contract_id) {
                @unlink($upload_path);
                throw new Exception("Falha ao registrar contrato no banco de dados");
            }

            // Atualiza franqueado com o ID do contrato
            $this->db->where('staffid', $staff_id);
            $this->db->update(db_prefix() . 'staff', ['contractid' => $contract_id]);

            $this->response([
                'status' => true,
                'message' => 'Contrato cadastrado com sucesso',
                'file_url' => $full_url, // Retorna a URL completa para o frontend
                'preview_url' => $preview_url,
                'contract_id' => $contract_id
            ]);

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage(),
                'debug' => [
                    'php_user' => get_current_user(),
                    'upload_dir' => $upload_dir ?? null,
                    'dir_exists' => isset($upload_dir) ? file_exists($upload_dir) : null,
                    'dir_writable' => isset($upload_dir) ? is_writable($upload_dir) : null,
                    'parent_writable' => isset($upload_dir) ? is_writable(dirname($upload_dir)) : null,
                    'free_space' => isset($upload_dir) ? round(disk_free_space(dirname($upload_dir)) / (1024 * 1024)) . 'MB' : null
                ]
            ], 500);
        }
    }

    public function view_contract_get($staff_id)
    {
        try {
            $this->db->select('contract_url');
            $this->db->where('staffid', $staff_id);
            $contract = $this->db->get(db_prefix() . 'staff')->row();

            if (!$contract || empty($contract->contract_url)) {
                throw new Exception("Contrato não encontrado");
            }

            $file_path = FCPATH . $contract->contract_url;

            if (!file_exists($file_path)) {
                throw new Exception("Arquivo do contrato não encontrado");
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="contrato.pdf"');
            readfile($file_path);
            exit;

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    // Endpoint para remover contrato
    public function remove_contract_post($staff_id)
    {
        try {
            $this->db->where('staffid', $staff_id);
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            if (!$staff) {
                throw new Exception("Franqueado não encontrado");
            }

            // Remove o arquivo físico
            if (!empty($staff->contract_url)) {
                $file_path = FCPATH . $staff->contract_url;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Atualiza o banco de dados
            $this->db->where('staffid', $staff_id);
            $this->db->update(db_prefix() . 'staff', [
                'contract_url' => null,
                'contractid' => null
            ]);

            $this->response([
                'status' => true,
                'message' => 'Contrato removido com sucesso'
            ]);

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
