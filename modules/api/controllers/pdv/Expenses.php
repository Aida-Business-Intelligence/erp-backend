<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../REST_Controller.php';

class Expenses extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Expenses_model');
    }

    public function categoriestwo_get()
    {
        try {
            $warehouse_id = $this->input->get('warehouse_id') ?: 0;
            $search = $this->input->get('search') ?: '';
            $pageSize = $this->input->get('pageSize') ?: 5;
            $categories = $this->Expenses_model->get_categories($warehouse_id, $search, $pageSize);

            $this->response([
                'success' => true,
                'data' => $categories
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function currencies_get()
    {
        try {
            $currencies = $this->Expenses_model->get_currencies();

            $this->response([
                'success' => true,
                'data' => $currencies
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function taxes_get()
    {
        try {
            $taxes = $this->Expenses_model->get_taxes();

            $this->response([
                'success' => true,
                'data' => $taxes
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function payment_modes_get()
    {
        try {
            $paymentModes = $this->Expenses_model->get_payment_modes();

            $this->response([
                'success' => true,
                'data' => $paymentModes
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function clients_get()
    {
        try {
            $warehouse_id = $this->input->get('warehouse_id') ?: 0;
            $search = $this->input->get('search') ?: '';
            $page = $this->input->get('page') ?: 0;
            $limit = $this->input->get('pageSize') ?: 5;

            $clients = $this->Expenses_model->get_clients($warehouse_id, $search, $limit, $page);

            $this->response([
                'success' => true,
                'data' => $clients
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function projects_get()
    {
        try {
            $client_id = $this->input->get('client_id') ?: 0;
            $warehouse_id = $this->input->get('warehouse_id') ?: 0;
            $search = $this->input->get('search') ?: '';
            $page = $this->input->get('page') ?: 0;
            $limit = $this->input->get('limit') ?: 10;

            if (empty($client_id)) {
                throw new Exception('Client ID is required', REST_Controller::HTTP_BAD_REQUEST);
            }

            $projects = $this->Expenses_model->get_projects($client_id, $warehouse_id, $search, $limit, $page);

            $this->response([
                'success' => true,
                'data' => $projects
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createtwo_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        try {
            // Obter o conteúdo raw da requisição
            $raw_input = file_get_contents("php://input");

            $data = json_decode($raw_input, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido: ' . json_last_error_msg());
            }

            if (empty($data)) {
                throw new Exception('Nenhum dado recebido');
            }

            $this->db->trans_start();

            $expenses_document = null;
            $document_field = !empty($data['expenses_document']) ? 'expenses_document' : (!empty($data['expense_document']) ? 'expense_document' : null);

            if ($document_field && !empty($data[$document_field])) {
                $document_data = $data[$document_field];

                // Verificar se é uma string base64 válida
                if (preg_match('/^data:(.+);base64,/', $document_data, $matches)) {
                    $mime_type = $matches[1];
                    $document_data = substr($document_data, strpos($document_data, ',') + 1);

                    // Validar tipos de arquivo permitidos
                    $allowed_types = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/jpg',
                        'image/png'
                    ];

                    if (!in_array($mime_type, $allowed_types)) {
                        throw new Exception('Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG');
                    }

                    $document_data = base64_decode($document_data);

                    if ($document_data === false) {
                        throw new Exception('Falha ao decodificar o documento');
                    }

                    if (strlen($document_data) > 5 * 1024 * 1024) {
                        throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
                    }

                    $upload_path = FCPATH . 'uploads/expenses/documents/';
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0755, true);
                    }

                    $extension_map = [
                        'application/pdf' => 'pdf',
                        'application/msword' => 'doc',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                        'image/jpeg' => 'jpg',
                        'image/jpg' => 'jpg',
                        'image/png' => 'png'
                    ];

                    $extension = $extension_map[$mime_type] ?? 'bin';

                    $filename = 'expense_' . time() . '_' . uniqid() . '.' . $extension;
                    $file_path = $upload_path . $filename;

                    if (file_put_contents($file_path, $document_data)) {
                        $expenses_document = 'uploads/expenses/documents/' . $filename;
                    } else {
                        throw new Exception('Falha ao salvar o documento no servidor');
                    }
                }
            }

            $input = [
                'category' => $data['category'] ?? null,
                'currency' => $data['currency'] ?? 1,
                'amount' => $data['amount'] ?? null,
                'tax' => $data['tax'] ?? null,
                'tax2' => $data['tax2'] ?? 0,
                'reference_no' => $data['reference_no'] ?? null,
                'note' => $data['note'] ?? null,
                'expense_identifier' => $data['expense_identifier'] ?? null,
                'clientid' => $data['clientid'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'billable' => isset($data['billable']) ? ($data['billable'] ? 1 : 0) : 0,
                'invoiceid' => $data['invoiceid'] ?? null,
                'paymentmode' => $data['paymentmode'] ?? null,
                'date' => $data['date'] ?? date('Y-m-d'),
                'due_date' => $data['due_date'] ?? null,
                'reference_date' => $data['reference_date'] ?? null,
                'recurring_type' => $data['recurring_type'] ?? null,
                'repeat_every' => $data['repeat_every'] ?? null,
                'recurring' => isset($data['recurring']) ? ($data['recurring'] ? 1 : 0) : 0,
                'cycles' => $data['cycles'] ?? 0,
                'total_cycles' => $data['total_cycles'] ?? 0,
                'custom_recurring' => isset($data['custom_recurring']) ? ($data['custom_recurring'] ? 1 : 0) : 0,
                'last_recurring_date' => $data['last_recurring_date'] ?? null,
                'create_invoice_billable' => isset($data['create_invoice_billable']) ? ($data['create_invoice_billable'] ? 1 : 0) : 0,
                'send_invoice_to_customer' => isset($data['send_invoice_to_customer']) ? ($data['send_invoice_to_customer'] ? 1 : 0) : 0,
                'recurring_from' => $data['recurring_from'] ?? null,
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => get_staff_user_id() ?? 1,
                'perfex_saas_tenant_id' => 'master',
                'status' => $data['status'] ?? 'pending',
                'warehouse_id' => $data['warehouse_id'] ?? 0,
                'expenses_document' => $expenses_document,
                'order_number' => $data['order_number'] ?? null,
                'installment_number' => $data['installment_number'] ?? null,
                'nfe_key' => $data['nfe_key'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'origin' => $data['origin'] ?? null,
            ];

            $input = array_filter($input, function ($value) {
                return $value !== null;
            });

            $expense_id = $this->Expenses_model->addtwo($input);

            if (!$expense_id) {
                throw new Exception('Falha ao criar a despesa/receita');
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Falha na transação do banco de dados');
            }

            $this->response([
                'status' => true,
                'message' => 'Despesa criada com sucesso',
                'data' => [
                    'id' => $expense_id,
                    'document_url' => $expenses_document ? base_url($expenses_document) : null
                ]
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'ERROR_CREATETWO: ' . $e->getMessage());

            $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
                'debug' => [
                    'input' => isset($raw_input) ? $raw_input : null,
                    'decoded' => isset($data) ? $data : null
                ]
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload_post()
    {
        try {
            $expense_id = $this->input->post('expense_id');
            $field_name = $this->input->post('field_name');

            if (empty($expense_id) || empty($field_name)) {
                throw new Exception("ID da despesa/receita e nome do campo são obrigatórios", REST_Controller::HTTP_BAD_REQUEST);
            }

            if (empty($_FILES['file'])) {
                throw new Exception("Nenhum arquivo enviado", REST_Controller::HTTP_BAD_REQUEST);
            }

            $result = $this->Expenses_model->upload_file($expense_id, $_FILES['file'], $field_name);

            if (!$result['success']) {
                throw new Exception("Falha ao fazer upload do arquivo", REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->response([
                'success' => true,
                'filename' => $result['filename']
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function warehouselist_get()
    {
        try {
            $warehouses = $this->Expenses_model->get_warehouses();

            $this->response([
                'success' => true,
                'data' => $warehouses
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateduplicates_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $input = $this->post();

        if (
            empty($input['warehouse_id']) ||
            empty($input['data']) ||
            empty($input['mappedColumns'])
        ) {
            return $this->response([
                'status' => false,
                'message' => 'Parâmetros obrigatórios ausentes'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $warehouse_id = $input['warehouse_id'];
        $data = $input['data'];
        $mappedColumns = $input['mappedColumns'];

        $duplicates = $this->Expenses_model->validate_duplicates($warehouse_id, $data, $mappedColumns);

        return $this->response([
            'status' => true,
            'duplicates' => $duplicates,
        ], REST_Controller::HTTP_OK);
    }

    public function list_post()
    {

        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        // Prepare parameters
        $params = [
            'warehouse_id' => $warehouse_id,
            'search' => $this->post('search') ?: '',
            'sortField' => $this->post('sortField') ?: 'id',
            'sortOrder' => $this->post('sortOrder'),
            'startDate' => $this->post('startDate'),
            'endDate' => $this->post('endDate'),
            'category' => $this->post('category'),
            'status' => $this->post('status'),
            'type' => $this->post('type'),
        ];

        // Pagination
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $page = $page + 1;
        $offset = ($page - 1) * $limit;

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        // Log parameters
        log_activity('Received parameters: ' . json_encode($params));

        // Get data from model
        $result = $this->Expenses_model->get_filtered_expenses_by_due_date($params);
        $data = $result['data'];
        $total = $result['total'];

        // Process results
        if (!empty($data)) {
            foreach ($data as &$expense) {
                if ($expense['recurring'] == 1) {
                    $expense['recurring_info'] = [
                        'recurring' => true,
                        'recurring_type' => $expense['recurring_type'],
                        'repeat_every' => $expense['repeat_every'],
                        'cycles_completed' => $expense['cycles'],
                        'total_cycles' => $expense['total_cycles'],
                        'custom_recurring' => $expense['custom_recurring'] == 1,
                        'last_recurring_date' => $expense['last_recurring_date'],
                    ];
                } else {
                    $expense['recurring_info'] = null;
                }
            }
        }

        $response = [
            'status' => TRUE,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
            'data' => $data
        ];

        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function list_by_date_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $params = [
            'warehouse_id' => $warehouse_id,
            'page' => (int) ($this->post('page') ?: 1),
            'pageSize' => (int) ($this->post('pageSize') ?: 10),
            'search' => $this->post('search') ?: '',
            'sortField' => $this->post('sortField') ?: db_prefix() . 'expenses.id',
            'sortOrder' => $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC',
            'start_date' => $this->post('start_date'),
            'end_date' => $this->post('end_date')
        ];

        $this->load->model('expenses_model');
        $result = $this->Expenses_model->get_expenses_by_due_date($params);

        // Enriquecer com informações de recorrência no controller (opcional)
        foreach ($result['data'] as &$expense) {
            $expense['recurring_info'] = ($expense['recurring'] == 1) ? [
                'recurring' => true,
                'recurring_type' => $expense['recurring_type'],
                'repeat_every' => $expense['repeat_every'],
                'cycles_completed' => $expense['cycles'],
                'total_cycles' => $expense['total_cycles'],
                'custom_recurring' => $expense['custom_recurring'] == 1,
                'last_recurring_date' => $expense['last_recurring_date'],
            ] : null;
        }

        $this->response([
            'status' => true,
            'total' => $result['total'],
            'page' => $params['page'],
            'limit' => $params['pageSize'],
            'total_pages' => ceil($result['total'] / $params['pageSize']),
            'data' => $result['data']
        ], REST_Controller::HTTP_OK);
    }

    public function list_by_day_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');
        $date = $this->post('date');
        $page = (int) ($this->post('page') ?: 1);
        $pageSize = (int) ($this->post('pageSize') ?: 10);

        if (empty($warehouse_id) || empty($date)) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id e date são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $params = [
            'warehouse_id' => $warehouse_id,
            'date' => $date,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        $result = $this->Expenses_model->get_expenses_by_day($params);

        return $this->response([
            'status' => true,
            'total' => $result['total'],
            'page' => $page,
            'limit' => $pageSize,
            'total_pages' => ceil($result['total'] / $pageSize),
            'data' => $result['data']
        ], REST_Controller::HTTP_OK);
    }


    private function calculate_recurring_dates($start_date, $recurring_type, $repeat_every, $range_start, $range_end, $total_cycles, $cycles_completed)
    {
        $dates = [];

        $current_date = strtotime($start_date);
        $range_start = $range_start ? strtotime($range_start) : 0;
        $range_end = $range_end ? strtotime($range_end) : PHP_INT_MAX;

        if (!$current_date || $current_date === false) {
            return $dates;
        }

        $cycles_remaining = ($total_cycles > 0) ? ($total_cycles - $cycles_completed) : 100; // Limit to 100 instances if infinite

        $max_iterations = 1000;
        $iteration = 0;

        while ($current_date <= $range_end && $cycles_remaining > 0 && $iteration < $max_iterations) {
            $iteration++;
            if ($current_date >= $range_start) {
                $dates[] = date('Y-m-d', $current_date);
                $cycles_remaining--;
            }

            switch ($recurring_type) {
                case 'day':
                    $interval = "{$repeat_every} days";
                    break;
                case 'week':
                    $interval = "{$repeat_every} weeks";
                    break;
                case 'month':
                    $interval = "{$repeat_every} months";
                    break;
                case 'year':
                    $interval = "{$repeat_every} years";
                    break;
                default:
                    return $dates;
            }

            $next_date = strtotime($interval, $current_date);

            if ($next_date === false || $next_date <= $current_date) {
                break;
            }

            $current_date = $next_date;
        }

        return $dates;
    }

    private function format_currency($value)
    {
        return number_format($value, 2);
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
            $input = $this->input->post();

            log_activity('Expense Create Input (multipart): ' . json_encode($input));
        } else {
            $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
            log_activity('Expense Create Input (json): ' . json_encode($input));
        }

        $required_fields = [];

        if (empty($input['category'])) {
            $required_fields[] = 'category';
        }
        if (empty($input['amount'])) {
            $required_fields[] = 'amount';
        }
        if (empty($input['date'])) {
            $required_fields[] = 'date';
        }
        if (empty($input['warehouse_id'])) {
            $required_fields[] = 'warehouse_id';
        }

        if (!empty($required_fields)) {
            $message = array(
                'status' => FALSE,
                'message' => 'Missing required fields: ' . implode(', ', $required_fields)
            );
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!empty($input['type']) && !in_array($input['type'], ['despesa', 'receita'])) {
            $message = array(
                'status' => FALSE,
                'message' => 'Invalid type: must be either "despesa" or "receita"'
            );
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = array(
            'category' => $input['category'],
            'amount' => $input['amount'],
            'date' => $input['date'],
            'warehouse_id' => $input['warehouse_id'],
            'note' => $input['note'] ?? '',
            'clientid' => isset($input['clientid']) && !empty($input['clientid']) ? $input['clientid'] : 0,
            'paymentmode' => $input['paymentmode'] ?? null,
            'tax' => $input['tax'] ?? null,
            'tax2' => $input['tax2'] ?? null,
            'currency' => $input['currency'] ?? 3,
            'reference_no' => $input['reference_no'] ?? null,
            'addedfrom' => get_staff_user_id(),
            'type' => $input['type'] ?? 'despesa',
            'status' => 'pending'
        );

        if (!empty($input['recurring']) && $input['recurring'] == 1) {
            if ($input['custom_recurring'] == 1) {
                $data['repeat_every'] = 'custom';
                $data['repeat_every_custom'] = $input['repeat_every'];
                $data['repeat_type_custom'] = $input['recurring_type'];
            } else {
                $data['repeat_every'] = $input['repeat_every'] . '-' . $input['recurring_type'];
            }

            if (isset($input['cycles'])) {
                $data['cycles'] = $input['cycles'];
            }
            if (isset($input['total_cycles'])) {
                $data['total_cycles'] = $input['total_cycles'];
            }
        }


        $expense_id = $this->Expenses_model->add($data);

        if (!$expense_id) {
            log_activity('Failed to create expense. DB Error: ' . $this->db->error()['message']);

            $message = array(
                'status' => FALSE,
                'message' => 'Falha ao criar despesa'
            );
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        if ($is_multipart && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];

            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowed_types)) {
                $this->Expenses_model->delete($expense_id);
                $this->response([
                    'status' => FALSE,
                    'message' => 'Tipo de arquivo não permitido. Tipos permitidos: JPG, PNG, PDF, DOC, DOCX'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $max_size = 5 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $this->Expenses_model->delete($expense_id);
                $this->response([
                    'status' => FALSE,
                    'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $upload_dir = './uploads/expenses/' . $expense_id . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $server_url = base_url();
                $relative_path = str_replace('./', '', $upload_path);
                $file_url = rtrim($server_url, '/') . '/' . $relative_path;

                $this->db->where('id', $expense_id);
                $this->db->update(db_prefix() . 'expenses', ['file' => $file_url]);
            } else {
                log_activity('Failed to move uploaded file for expense ' . $expense_id);
            }
        }

        $expense = $this->Expenses_model->get($expense_id);
        log_activity('Created expense: ' . json_encode($expense));

        $message = array(
            'status' => TRUE,
            'message' => 'Despesa criada com sucesso',
            'data' => $expense
        );
        $this->response($message, REST_Controller::HTTP_CREATED);
    }

    public function data_delete($id = '')
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $id = $this->security->xss_clean($id);

        if (empty($id) || !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->load->model('Expenses_model');
        $output = $this->Expenses_model->delete($id);

        if (!$output) {
            $message = array('status' => FALSE, 'message' => 'Expense Delete Failed');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $message = array('status' => TRUE, 'message' => 'Expense Deleted Successfully');
        $this->response($message, REST_Controller::HTTP_OK);
    }


    public function update_post()
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
            log_activity('Expense Update Input (multipart): ' . json_encode($_POST));
        } else {
            $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
            log_activity('Expense Update Input (json): ' . json_encode($_POST));
        }

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }

        $this->form_validation->set_data($_POST);

        if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
            $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $update_data = $this->input->post();
        $expense_id = $_POST['id'];

        if ($is_multipart && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file'];

            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!in_array($file['type'], $allowed_types)) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Tipo de arquivo não permitido. Tipos permitidos: JPG, PNG, PDF, DOC, DOCX'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $max_size = 5 * 1024 * 1024;
            if ($file['size'] > $max_size) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $upload_dir = './uploads/expenses/' . $expense_id . '/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $server_url = base_url();
                $relative_path = str_replace('./', '', $upload_path);
                $file_url = rtrim($server_url, '/') . '/' . $relative_path;

                $update_data['file'] = $file_url;
            } else {
                log_activity('Failed to move uploaded file for expense ' . $expense_id);
            }
        }

        // Processar o documento se existir
        if (!empty($update_data['expenses_document'])) {
            // Buscar o registro atual para pegar o caminho do arquivo antigo
            $current = $this->Expenses_model->gettwo($expense_id);
            $old_document = $current && isset($current->expenses_document) ? $current->expenses_document : null;

            $document_data = $update_data['expenses_document'];
            if (preg_match('/^data:(.+);base64,/', $document_data, $matches)) {
                $mime_type = $matches[1];
                $document_data = substr($document_data, strpos($document_data, ',') + 1);

                // Validar tipos de arquivo permitidos
                $allowed_types = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/jpg',
                    'image/png'
                ];

                if (!in_array($mime_type, $allowed_types)) {
                    return $this->response([
                        'status' => false,
                        'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

                $document_data = base64_decode($document_data);

                if ($document_data === false) {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao decodificar o documento'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

                // Verificar tamanho do arquivo (5MB)
                if (strlen($document_data) > 5 * 1024 * 1024) {
                    return $this->response([
                        'status' => false,
                        'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

                // Apagar o arquivo antigo, se existir e não for base64
                if ($old_document && strpos($old_document, 'data:') !== 0) {
                    $old_path = FCPATH . ltrim($old_document, '/');
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                }

                $upload_path = FCPATH . 'uploads/expenses/documents/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }

                $extension_map = [
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png'
                ];

                $extension = $extension_map[$mime_type] ?? 'bin';

                $filename = 'expense_' . time() . '_' . uniqid() . '.' . $extension;
                $file_path = $upload_path . $filename;

                if (file_put_contents($file_path, $document_data)) {
                    $update_data['expenses_document'] = 'uploads/expenses/documents/' . $filename;
                } else {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao salvar o documento no servidor'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
            // Se não for base64, mas vier um caminho relativo, atualize também
            else if (!empty($update_data['expenses_document']) && !preg_match('/^data:(.+);base64,/', $update_data['expenses_document'])) {
                $update_data['expenses_document'] = $update_data['expenses_document'];
            }
        }

        $this->load->model('Expenses_model');
        $output = $this->Expenses_model->update($update_data, $expense_id);

        if (!$output || empty($output)) {
            $message = array('status' => FALSE, 'message' => 'Expenses Update Fail.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $message = array(
            'status' => TRUE,
            'message' => 'Expenses Update Successful.',
            'data' => $this->Expenses_model->get($expense_id)
        );
        $this->response($message, REST_Controller::HTTP_OK);
    }

    public function totals_by_period_get()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->get('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');

        $this->db->select('SUM(amount) as total_amount, COUNT(*) as total_expenses');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->where('warehouse_id', $warehouse_id);

        if (!empty($start_date)) {
            $this->db->where('date >=', $start_date);
        }

        if (!empty($end_date)) {
            $this->db->where('date <=', $end_date);
        }

        $result = $this->db->get()->row();

        $this->response([
            'status' => TRUE,
            'data' => [
                'total_amount' => $result ? floatval($result->total_amount) : 0,
                'total_expenses' => $result ? (int) $result->total_expenses : 0,
                'period' => [
                    'start' => $start_date ?? 'all',
                    'end' => $end_date ?? 'all'
                ]
            ]
        ], REST_Controller::HTTP_OK);
    }


    public function categories_get()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->get('warehouse_id');
        $type = $this->get('type');

        if (empty($warehouse_id) || empty($type)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->select('id, name, description, warehouse_id, type');
            $this->db->from(db_prefix() . 'expenses_categories');
            $this->db->where('warehouse_id', $warehouse_id);
            $this->db->where('type', $type);
            $categories = $this->db->get()->result_array();

            $this->response([
                'status' => TRUE,
                'data' => $categories ?: []
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao buscar categorias',
                'error' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function category_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($input['name']) || empty($input['warehouse_id']) || empty($input['type'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Name, warehouse_id e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = [
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'warehouse_id' => $input['warehouse_id'],
            'type' => $input['type'],
            'perfex_saas_tenant_id' => 'master'
        ];

        try {
            $this->db->insert(db_prefix() . 'expenses_categories', $data);
            $category_id = $this->db->insert_id();

            if ($category_id) {
                $inserted_category = $this->db->get_where(db_prefix() . 'expenses_categories', ['id' => $category_id])->row_array();
                $this->response([
                    'status' => TRUE,
                    'message' => 'Categoria criada com sucesso',
                    'data' => $inserted_category
                ], REST_Controller::HTTP_CREATED);
            } else {
                throw new Exception('Failed to create category');
            }
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao criar categoria',
                'error' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function category_put($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Category ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($input['name']) || empty($input['type'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Name e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = [
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'warehouse_id' => $input['warehouse_id'] ?? 0,
            'type' => $input['type']
        ];

        try {
            $this->db->where('id', $id);
            $update_result = $this->db->update(db_prefix() . 'expenses_categories', $data);

            if ($update_result) {
                $updated_category = $this->db->get_where(db_prefix() . 'expenses_categories', ['id' => $id])->row_array();
                $this->response([
                    'status' => TRUE,
                    'message' => 'Categoria atualizada com sucesso',
                    'data' => $updated_category
                ], REST_Controller::HTTP_OK);
            } else {
                throw new Exception('Failed to update category');
            }
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao atualizar categoria',
                'error' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function category_delete($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Category ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->where('category', $id);
            $expense_count = $this->db->count_all_results(db_prefix() . 'expenses');

            if ($expense_count > 0) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Não é possível excluir a categoria pois existem despesas vinculadas a ela'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $this->db->where('id', $id);
            $delete_result = $this->db->delete(db_prefix() . 'expenses_categories');

            if ($delete_result) {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Categoria excluída com sucesso'
                ], REST_Controller::HTTP_OK);
            } else {
                throw new Exception('Failed to delete category');
            }
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao excluir categoria',
                'error' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function remove_delete()
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

            $output = $this->Expenses_model->delete($id);
            if ($output === TRUE) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        if ($success_count > 0) {
            $message = array(
                'status' => TRUE,
                'message' => $success_count . ' expense(s) deleted successfully'
            );
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to delete expenses',
                'failed_ids' => $failed_ids
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }


    public function payment_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            $input = $this->input->post();
        } else {
            $raw_input = file_get_contents("php://input");
            $input = json_decode($raw_input, true);
        }

        if (empty($input['id']) || empty($input['status'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Missing required fields: id and status'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!in_array($input['status'], ['pending', 'paid'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid status value. Must be "pending" or "paid"'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $expense = $this->db->get_where(db_prefix() . 'expenses', ['id' => $input['id']])->row();
        if (!$expense) {
            $this->response([
                'status' => FALSE,
                'message' => 'Expense not found'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $data = [];

        if ($input['status'] === 'paid') {
            if (empty($input['payment_date'])) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Payment date is required when marking as paid'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $data['last_recurring_date'] = $input['payment_date'];

            if ($is_multipart && isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['comprovante'];

                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!in_array($file['type'], $allowed_types)) {
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Tipo de arquivo não permitido. Tipos permitidos: JPG, PNG, PDF, DOC, DOCX'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }

                $max_size = 5 * 1024 * 1024;
                if ($file['size'] > $max_size) {
                    $this->response([
                        'status' => FALSE,
                        'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }

                $upload_dir = './uploads/expenses/' . $input['id'] . '/comprovante/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $extension;
                $upload_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $server_url = base_url();
                    $relative_path = str_replace('./', '', $upload_path);
                    $file_url = rtrim($server_url, '/') . '/' . $relative_path;
                    $data['comprovante'] = $file_url;
                } else {
                    log_activity('Failed to move uploaded payment receipt for expense ' . $input['id']);
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Failed to upload payment receipt'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    return;
                }
            }

            if ($expense->recurring == 1) {
                $new_cycles = (int) $expense->cycles + 1;
                $data['cycles'] = $new_cycles;

                if ($expense->total_cycles > 0 && $new_cycles >= $expense->total_cycles) {
                    $data['status'] = 'paid';
                } else {
                    $data['status'] = 'pending';
                }
            } else {
                $data['status'] = 'paid';
            }
        } else {
            $data['status'] = 'pending';
        }

        $this->db->where('id', $input['id']);
        $success = $this->db->update(db_prefix() . 'expenses', $data);

        if ($success) {
            $updated_expense = $this->db->get_where(db_prefix() . 'expenses', ['id' => $input['id']])->row();

            $this->response([
                'status' => TRUE,
                'message' => 'Payment status updated successfully',
                'data' => $updated_expense
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to update payment status'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function get_payment_mode_name($payment_mode_id)
    {
        $payment_methods = [
            1 => 'PIX',
            2 => 'Cartão',
            3 => 'Dinheiro',
            4 => 'Boleto',
            5 => 'Transferência',
            6 => 'Cheque',
            7 => 'Outros'
        ];

        return $payment_methods[$payment_mode_id] ?? 'Desconhecido';
    }

    public function get_get($id = '')
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $expense = $this->Expenses_model->get_expense_detailed($id);

        if (!$expense) {
            $this->response([
                'status' => FALSE,
                'message' => 'Expense not found'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Processar recorrência (pode permanecer aqui se for apenas lógica de exibição)
        $expense->recurring_info = ($expense->recurring == 1) ? [
            'recurring' => true,
            'recurring_type' => $expense->recurring_type,
            'repeat_every' => $expense->repeat_every,
            'cycles_completed' => $expense->cycles,
            'total_cycles' => $expense->total_cycles,
            'custom_recurring' => $expense->custom_recurring == 1,
            'last_recurring_date' => $expense->last_recurring_date,
        ] : null;

        $this->response([
            'status' => TRUE,
            'data' => $expense
        ], REST_Controller::HTTP_OK);
    }


    public function financial_report_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $current_month_start = date('Y-m-01');
        $current_month_end = date('Y-m-t');
        $previous_month_start = date('Y-m-01', strtotime('-1 month'));
        $previous_month_end = date('Y-m-t', strtotime('-1 month'));

        $this->db->select('
      COALESCE(SUM(amount), 0) as total_expenses,
      COUNT(id) as transaction_count
    ');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('date >=', $current_month_start);
        $this->db->where('date <=', $current_month_end);
        $current_month_data = $this->db->get()->row_array();

        $this->db->select('
      COALESCE(SUM(amount), 0) as total_expenses,
      COUNT(id) as transaction_count
    ');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->where('date >=', $previous_month_start);
        $this->db->where('date <=', $previous_month_end);
        $this->db->where('warehouse_id', $warehouse_id);
        $previous_month_data = $this->db->get()->row_array();

        $current_month_data = $current_month_data ?: ['total_expenses' => 0, 'transaction_count' => 0];
        $previous_month_data = $previous_month_data ?: ['total_expenses' => 0, 'transaction_count' => 0];

        $total_change_percent = $previous_month_data['total_expenses'] > 0
            ? (($current_month_data['total_expenses'] - $previous_month_data['total_expenses']) / $previous_month_data['total_expenses']) * 100
            : 0;


        $response = [
            'status' => true,
            'monthly_performance' => [
                'current_month' => [
                    'total_expenses' => floatval($current_month_data['total_expenses']),
                    'transaction_count' => (int) $current_month_data['transaction_count']
                ],
                'previous_month' => [
                    'total_expenses' => floatval($previous_month_data['total_expenses']),
                    'transaction_count' => (int) $previous_month_data['transaction_count']
                ]
            ]
        ];

        $this->response($response, REST_Controller::HTTP_OK);
    }

    public function category_get($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Category ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->select('id, name, description, warehouse_id');
            $this->db->from(db_prefix() . 'expenses_categories');
            $this->db->where('id', $id);
            $category = $this->db->get()->row();

            if (!$category) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Category not found'
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $this->db->where('category', $id);
            $expense_count = $this->db->count_all_results(db_prefix() . 'expenses');

            $response = [
                'status' => TRUE,
                'data' => [
                    'category' => $category,
                    'expense_count' => $expense_count
                ]
            ];

            $this->response($response, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao buscar categoria',
                'error' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function calendar_days_get()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $month = $this->get('month');
        $year = $this->get('year') ?: date('Y');
        $warehouse_id = $this->get('warehouse_id');

        if (empty($month) || !is_numeric($month) || $month < 1 || $month > 12) {
            $this->response([
                'status' => FALSE,
                'message' => 'Valid month (1-12) is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $start_date = sprintf('%d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date));
        $today = date('Y-m-d');

        $this->db->select('
      DATE(due_date) as expense_date,
      status,
      COUNT(*) as expense_count
    ');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('due_date >=', $start_date);
        $this->db->where('due_date <=', $end_date);
        $this->db->group_by('DATE(due_date), status');
        $results = $this->db->get()->result_array();

        $calendar_days = [];

        foreach ($results as $row) {
            $date = $row['expense_date'];
            $day = (int) date('d', strtotime($date));

            if (!isset($calendar_days[$day])) {
                $calendar_days[$day] = [
                    'day' => $day,
                    'has_paid' => false,
                    'has_pending' => false,
                    'has_late' => false
                ];
            }

            if ($row['status'] === 'paid') {
                $calendar_days[$day]['has_paid'] = true;
            } else if ($row['status'] === 'pending') {
                if (strtotime($date) < strtotime($today)) {
                    $calendar_days[$day]['has_late'] = true;
                } else {
                    $calendar_days[$day]['has_pending'] = true;
                }
            }
        }

        $calendar_days = array_values($calendar_days);

        $this->response([
            'status' => TRUE,
            'data' => [
                'year' => (int) $year,
                'month' => (int) $month,
                'days' => $calendar_days
            ]
        ], REST_Controller::HTTP_OK);
    }

    public function summary_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $summary = $this->Expenses_model->get_expenses_summary($warehouse_id);

        $total = $summary['paid'] + $summary['to_pay'] + $summary['overdue'];
        $paid_percent = $total > 0 ? round(($summary['paid'] / $total) * 100, 1) : 0;

        return $this->response([
            'status' => true,
            'data' => [
                'paid' => $summary['paid'],
                'paid_count' => $summary['paid_count'],
                'paid_today' => $summary['paid_today'],
                'paid_today_count' => $summary['paid_today_count'],
                'to_pay' => $summary['to_pay'],
                'to_pay_count' => $summary['to_pay_count'],
                'to_pay_month' => $summary['to_pay_month'],
                'to_pay_month_count' => $summary['to_pay_month_count'],
                'overdue' => $summary['overdue'],
                'overdue_count' => $summary['overdue_count'],
                'paid_percent' => $paid_percent
            ],
        ], REST_Controller::HTTP_OK);
    }

    public function delete_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $id = $this->post('id');
        $rows = $this->post('rows');
        $warehouse_id = $this->post('warehouse_id');
        // Removendo o parâmetro type já que a coluna não existe na tabela
        // $type = $this->post('type');

        // Exclusão em lote
        if (!empty($rows) && is_array($rows)) {
            $success = 0;
            $fail = 0;
            foreach ($rows as $rowId) {
                // Buscar documento antes de deletar
                $expense = $this->Expenses_model->gettwo($rowId);
                if ($expense && !empty($expense->expenses_document)) {
                    $this->delete_expense_document_file($expense->expenses_document);
                }
                $deleted = $this->Expenses_model->delete_expense($rowId, $warehouse_id);
                if ($deleted) {
                    $success++;
                } else {
                    $fail++;
                }
            }
            return $this->response([
                'status' => $fail === 0,
                'message' => $fail === 0 ? 'Registros deletados com sucesso' : "Alguns registros não foram deletados",
                'success_count' => $success,
                'fail_count' => $fail
            ], $fail === 0 ? REST_Controller::HTTP_OK : REST_Controller::HTTP_PARTIAL_CONTENT);
        }

        // Exclusão unitária
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório para deletar'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Buscar documento antes de deletar
        $expense = $this->Expenses_model->gettwo($id);
        if ($expense && !empty($expense->expenses_document)) {
            $this->delete_expense_document_file($expense->expenses_document);
        }

        $deleted = $this->Expenses_model->delete_expense($id, $warehouse_id);

        if ($deleted) {
            return $this->response([
                'status' => true,
                'message' => 'Registro deletado com sucesso'
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao deletar o registro'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Função auxiliar para deletar arquivo físico se não for base64
    private function delete_expense_document_file($document) {
        if (strpos($document, 'data:') === 0) {
            // Documento em base64, nada a deletar
            return;
        }
        $filePath = FCPATH . ltrim($document, '/');
        // Ajuste para garantir que o caminho use 'documents/'
        $filePath = str_replace('uploads/expenses/', 'uploads/expenses/documents/', $filePath);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function updatetwo_post($id = '')
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $content_type = $this->input->request_headers()['Content-Type'] ?? '';
        $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false;

        if ($is_multipart) {
            $input = $this->input->post();
        } else {
            $raw_input = file_get_contents("php://input");
            $input = json_decode($raw_input, true);
        }

        if (empty($input)) {
            return $this->response([
                'status' => false,
                'message' => 'Invalid input data'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $fields = [
            'expense_name',
            'type',
            'category',
            'amount',
            'date',
            'due_date', // ADICIONADO
            'reference_date', // ADICIONADO
            'paymentmode',
            'clientid',
            'note',
            'billable',
            'send_invoice_to_customer',
            'status',
            'recurring',
            'warehouse_id',
            'reference_no',
            'recurring_type',
            'repeat_every',
            'cycles',
            'total_cycles',
            'custom_recurring',
            'last_recurring_date',
            'create_invoice_billable',
            'recurring_from',
            'expense_identifier', // novo campo
        ];

        $updateData = [];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                if (in_array($field, ['billable', 'send_invoice_to_customer', 'recurring', 'custom_recurring', 'create_invoice_billable'])) {
                    $updateData[$field] = (!empty($input[$field]) && $input[$field] !== 'false') ? 1 : 0;
                } elseif (in_array($field, ['repeat_every', 'cycles', 'total_cycles'])) {
                    $updateData[$field] = is_numeric($input[$field]) ? $input[$field] : 0;
                } elseif (in_array($field, ['last_recurring_date'])) {
                    $updateData[$field] = !empty($input[$field]) ? $input[$field] : null;
                } else {
                    $updateData[$field] = $input[$field];
                }
            }
        }

        // Processar o documento se existir
        if (!empty($input['expenses_document'])) {
            // Buscar o registro atual para pegar o caminho do arquivo antigo
            $current = $this->Expenses_model->gettwo($id);
            $old_document = $current && isset($current->expenses_document) ? $current->expenses_document : null;

            $document_data = $input['expenses_document'];
            if (preg_match('/^data:(.+);base64,/', $document_data, $matches)) {
                $mime_type = $matches[1];
                $document_data = substr($document_data, strpos($document_data, ',') + 1);

                // Validar tipos de arquivo permitidos
                $allowed_types = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/jpg',
                    'image/png'
                ];

                if (!in_array($mime_type, $allowed_types)) {
                    return $this->response([
                        'status' => false,
                        'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

                $document_data = base64_decode($document_data);

                if ($document_data === false) {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao decodificar o documento'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

                // Verificar tamanho do arquivo (5MB)
                if (strlen($document_data) > 5 * 1024 * 1024) {
                    return $this->response([
                        'status' => false,
                        'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }

                // Apagar o arquivo antigo, se existir e não for base64
                if ($old_document && strpos($old_document, 'data:') !== 0) {
                    $old_path = FCPATH . ltrim($old_document, '/');
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                }

                $upload_path = FCPATH . 'uploads/expenses/documents/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }

                $extension_map = [
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png'
                ];

                $extension = $extension_map[$mime_type] ?? 'bin';

                $filename = 'expense_' . time() . '_' . uniqid() . '.' . $extension;
                $file_path = $upload_path . $filename;

                if (file_put_contents($file_path, $document_data)) {
                    $updateData['expenses_document'] = 'uploads/expenses/documents/' . $filename;
                } else {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao salvar o documento no servidor'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
            else if (!empty($input['expenses_document']) && !preg_match('/^data:(.+);base64,/', $input['expenses_document'])) {
                $updateData['expenses_document'] = $input['expenses_document'];
            }
        }

        $success = $this->Expenses_model->updatetwo($updateData, $id);

        if (!$success) {
            return $this->response([
                'status' => false,
                'message' => 'Failed to update expense/receita or no changes made'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response([
            'status' => true,
            'message' => 'Despesa/Receita atualizada com sucesso',
            'data' => $this->Expenses_model->gettwo($id)
        ], REST_Controller::HTTP_OK);
    }

    public function client_get($expenseId = '')
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($expenseId) || !is_numeric($expenseId)) {
            return $this->response([
                'status' => false,
                'message' => 'Expense ID is required and must be numeric'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $client = $this->Expenses_model->get_client_by_expense_id($expenseId);

        if (!$client) {
            return $this->response([
                'status' => false,
                'message' => 'Client not found for this expense'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response([
            'status' => true,
            'data' => $client
        ], REST_Controller::HTTP_OK);
    }

    public function categorytwo_get($id = '')
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = $this->Expenses_model->get_expense_category($id);

        if (!$data) {
            return $this->response([
                'status' => false,
                'message' => 'Categoria não encontrada para esta despesa/receita.'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response([
            'status' => true,
            'data' => $data,
        ], REST_Controller::HTTP_OK);
    }
}