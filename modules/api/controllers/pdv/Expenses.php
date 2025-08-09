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
        $this->load->library('storage_s3');
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
            $type = $this->input->get('type') ?: 'suppliers'; // 'suppliers', 'clients'

            $clients = $this->Expenses_model->get_clients($warehouse_id, $search, $limit, $page, $type);

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

    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        try {
            // Verificar se é multipart/form-data ou JSON
            $headers = $this->input->request_headers();
            $content_type = $headers['Content-Type'] ?? $headers['content-type'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES) || isset($_POST['data']);
            
            // Log para debug
            log_message('debug', 'Content-Type: ' . $content_type);
            log_message('debug', 'Is multipart: ' . ($is_multipart ? 'true' : 'false'));
            log_message('debug', 'FILES: ' . json_encode($_FILES));
            log_message('debug', 'POST keys: ' . json_encode(array_keys($_POST)));
            log_message('debug', 'POST data exists: ' . (isset($_POST['data']) ? 'true' : 'false'));

            if ($is_multipart) {
                // Processar dados do FormData - usar $_POST diretamente como no Produto.php
                if (isset($_POST['data'])) {
                    $_POST = json_decode($_POST['data'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('JSON inválido: ' . json_last_error_msg());
                    }
                    $data = $_POST;
                } else {
                    throw new Exception('Campo "data" não encontrado na requisição multipart');
                }
            } else {
                // Processar dados JSON
                $raw_input = file_get_contents("php://input");
                $data = json_decode($raw_input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('JSON inválido: ' . json_last_error_msg() . ' - Input: ' . $raw_input . ' - Content-Type: ' . $content_type);
                }
                $_POST = $data;
            }



            if (empty($data)) {
                throw new Exception('Nenhum dado recebido');
            }

            $this->db->trans_start();

            // Inicializar cliente S3
            $s3 = $this->storage_s3->getClient();
            $expense_document = null;

            // Processar documento da despesa se enviado via multipart
            if ($is_multipart && isset($_FILES['expense_document']) && $_FILES['expense_document']['error'] === UPLOAD_ERR_OK && $_FILES['expense_document']['size'] > 0) {
                $file = $_FILES['expense_document'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validar tipos de arquivo permitidos
                $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG');
                }

                // Verificar tamanho do arquivo (5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
                }

                $unique_filename = 'expense_' . time() . '_' . uniqid() . '.' . $file_extension;
                $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $data['warehouse_id'] . '/expenses/documents/' . $unique_filename;

                try {
                    // Upload para S3
                    $s3->putObject([
                        'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                        'Key' => $blobName,
                        'SourceFile' => $file['tmp_name'],
                        'ACL' => 'public-read',
                    ]);

                    // Constrói a URL do arquivo
                    $expense_document = "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
                } catch (Exception $e) {
                    throw new Exception('Falha ao fazer upload do documento para S3: ' . $e->getMessage());
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
                'project_id' => $data['project_id'] ?? 0,
                'billable' => isset($data['billable']) ? ($data['billable'] ? 1 : 0) : 0,
                'invoiceid' => $data['invoiceid'] ?? null,
                'paymentmode' => $data['paymentmode'] ?? null,
                'date' => $data['date'] ?? date('Y-m-d'),
                'due_date' => $data['due_date'] ?? null,
                'reference_date' => $data['reference_date'] ?? null,
                'recurring_type' => null,
                'repeat_every' => null,
                'recurring' => 0,
                'cycles' => 0,
                'total_cycles' => 0,
                'custom_recurring' => 0,
                'last_recurring_date' => null,
                'create_invoice_billable' => isset($data['create_invoice_billable']) ? ($data['create_invoice_billable'] ? 1 : 0) : 0,
                'send_invoice_to_customer' => isset($data['send_invoice_to_customer']) ? ($data['send_invoice_to_customer'] ? 1 : 0) : 0,
                'recurring_from' => null,
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => get_staff_user_id() ?? 1,
                'perfex_saas_tenant_id' => 'master',
                'status' => $data['status'] ?? 'pending',
                'warehouse_id' => $data['warehouse_id'] ?? 0,
                'expense_document' => $expense_document,
                'order_number' => $data['order_number'] ?? null,
                'installment_number' => $data['installment_number'] ?? null,
                'nfe_key' => $data['nfe_key'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'origin' => $data['origin'] ?? null,
                'is_client' => isset($data['is_client']) ? ($data['is_client'] ? 1 : 0) : 0,
                'juros' => $data['juros'] ?? 0,
            ];

            // Processar parcelas se fornecidas
            $installments = null;
            if (isset($data['num_parcelas']) && $data['num_parcelas'] > 1) {
                $installments = $this->process_installments($data);
                $input['installments'] = $installments;
            }

            // Filtrar apenas valores null, mas manter campos opcionais como expense_document
            $input = array_filter($input, function ($value, $key) {
                // Campos que podem ser null
                $nullable_fields = ['expense_document', 'tax', 'tax2', 'reference_no', 'note', 'clientid', 'invoiceid', 'bank_account_id', 'order_number', 'installment_number', 'nfe_key', 'barcode'];
                
                if (in_array($key, $nullable_fields)) {
                    return true; // Manter o campo mesmo se for null
                }
                
                return $value !== null;
            }, ARRAY_FILTER_USE_BOTH);

            $expense_id = $this->Expenses_model->add($input);

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
                    'document_url' => $expense_document
                ]
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            // log_message('error', 'ERROR_CREATETWO: ' . $e->getMessage());

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
        // log_activity('Received parameters: ' . json_encode($params));

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
                // Deletar arquivos S3 antes de deletar o registro
                $this->delete_expense_files_from_s3($rowId);
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

        // Deletar arquivos S3 antes de deletar o registro
        $this->delete_expense_files_from_s3($id);

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

    // Função auxiliar para deletar arquivo do S3
    private function delete_expense_document_file($document) {
        if (strpos($document, 'data:') === 0) {
            // Documento em base64, nada a deletar
            return;
        }
        
        // Se for URL do S3, deletar do S3
        if (strpos($document, 'https://') === 0) {
            try {
                $s3 = $this->storage_s3->getClient();
                $key = str_replace("https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/", "", $document);
                $s3->deleteObject([
                    'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                    'Key' => $key
                ]);
            } catch (Exception $e) {
                log_message('error', 'Erro ao deletar arquivo do S3: ' . $e->getMessage());
            }
        } else {
            // Fallback para arquivo local (legado)
            $filePath = FCPATH . ltrim($document, '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // Função para deletar todos os arquivos S3 relacionados a uma despesa
    private function delete_expense_files_from_s3($expense_id) {
        // Buscar a despesa para obter o documento principal
        $expense = $this->Expenses_model->gettwo($expense_id);
        if ($expense && !empty($expense->expense_document)) {
            $this->delete_expense_document_file($expense->expense_document);
        }

        // Buscar e deletar todos os comprovantes das parcelas
        $this->load->model('Expenses_installments_model');
        $installments = $this->Expenses_installments_model->get_installments_by_expense($expense_id);
        
        if ($installments) {
            foreach ($installments as $installment) {
                if (!empty($installment['comprovante'])) {
                    $this->delete_expense_document_file($installment['comprovante']);
                }
            }
        }
    }

    public function update_post($id = '')
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $headers = $this->input->request_headers();
        $content_type = $headers['Content-Type'] ?? $headers['content-type'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
        $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES) || isset($_POST['data']);
        


        if ($is_multipart) {
            // Processar dados do FormData - usar $_POST diretamente como no Produto.php
            if (isset($_POST['data'])) {
                $_POST = json_decode($_POST['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->response([
                        'status' => false,
                        'message' => 'JSON inválido: ' . json_last_error_msg()
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                $input = $_POST;
            } else {
                return $this->response([
                    'status' => false,
                    'message' => 'Campo "data" não encontrado na requisição multipart',
                    'debug' => [
                        'post_keys' => array_keys($_POST),
                        'post_data' => $_POST
                    ]
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            // Processar dados JSON
            $raw_input = file_get_contents("php://input");
            $input = json_decode($raw_input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->response([
                    'status' => false,
                    'message' => 'JSON inválido: ' . json_last_error_msg() . ' - Input: ' . $raw_input . ' - Content-Type: ' . $content_type
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $_POST = $input;
        }

        if (empty($input)) {
            return $this->response([
                'status' => false,
                'message' => 'Invalid input data - Input vazio ou inválido',
                'debug' => [
                    'is_multipart' => $is_multipart,
                    'post_data' => isset($_POST['data']) ? $_POST['data'] : 'não encontrado',
                    'raw_input' => isset($raw_input) ? $raw_input : 'não aplicável'
                ]
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Campos da tabela tblexpenses (excluindo campos de parcelamento que vão para tblexpenses_installments)
        $fields = [
            'expense_name',
            'type',
            'category',
            'currency',
            'amount',
            'tax',
            'tax2',
            'reference_no',
            'note',
            'expense_identifier',
            'clientid',
            'project_id',
            'billable',
            'invoiceid',
            'paymentmode',
            'date',
            'due_date',
            'reference_date',
            'create_invoice_billable',
            'send_invoice_to_customer',
            'warehouse_id',
            'bank_account_id',
            'expense_document',
            'order_number',
            'installment_number',
            'nfe_key',
            'barcode',
            'is_client',
            'juros',
        ];

        $updateData = [];

        foreach ($fields as $field) {
            if (isset($input[$field])) {
                if (in_array($field, ['billable', 'send_invoice_to_customer', 'create_invoice_billable', 'is_client'])) {
                    $updateData[$field] = (!empty($input[$field]) && $input[$field] !== 'false') ? 1 : 0;
                } elseif ($field === 'bank_account_id' && (empty($input[$field]) || $input[$field] === '')) {
                    $updateData[$field] = null;
                } else {
                    $updateData[$field] = $input[$field];
                }
            }
        }

        // Processar parcelas se fornecidas (igual ao create)
        $installments = null;
        if (isset($input['num_parcelas']) && $input['num_parcelas'] > 1) {
            $installments = $this->process_installments($input);
            // Não adicionar ao updateData para não tentar salvar no banco
        }

        // Processar o documento se existir via multipart
        if ($is_multipart && isset($_FILES['expense_document']) && $_FILES['expense_document']['error'] === UPLOAD_ERR_OK) {
            // Buscar o registro atual para pegar o caminho do arquivo antigo
            $current = $this->Expenses_model->gettwo($id);
            $old_document = $current && isset($current->expense_document) ? $current->expense_document : null;

            $file = $_FILES['expense_document'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validar tipos de arquivo permitidos
            $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
            if (!in_array($file_extension, $allowed_extensions)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            // Verificar tamanho do arquivo (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                return $this->response([
                    'status' => false,
                    'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            // Apagar o arquivo antigo do S3, se existir
            if ($old_document && strpos($old_document, 'https://') === 0) {
                try {
                    $s3 = $this->storage_s3->getClient();
                    $old_key = str_replace("https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/", "", $old_document);
                    $s3->deleteObject([
                        'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                        'Key' => $old_key
                    ]);
                } catch (Exception $e) {
                    // Log do erro, mas não falhar a operação
                    log_message('error', 'Erro ao deletar arquivo antigo do S3: ' . $e->getMessage());
                }
            }

            $warehouse_id = $current ? $current->warehouse_id : 0;
            $unique_filename = 'expense_' . time() . '_' . uniqid() . '.' . $file_extension;
            $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $warehouse_id . '/expenses/documents/' . $unique_filename;

            try {
                // Inicializar cliente S3
                $s3 = $this->storage_s3->getClient();
                
                // Upload para S3
                $s3->putObject([
                    'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                    'Key' => $blobName,
                    'SourceFile' => $file['tmp_name'],
                    'ACL' => 'public-read',
                ]);

                // Constrói a URL do arquivo
                $updateData['expense_document'] = "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
            } catch (Exception $e) {
                return $this->response([
                    'status' => false,
                    'message' => 'Falha ao fazer upload do documento para S3: ' . $e->getMessage()
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // Debug: verificar dados
        log_message('debug', 'UpdateData: ' . json_encode($updateData));
        log_message('debug', 'UpdateData count: ' . count($updateData));
        log_message('debug', 'Installments: ' . ($installments ? 'yes' : 'no'));
        
        // Verificar se há dados para atualizar
        if (empty($updateData)) {
            // Se não há dados para atualizar, apenas processar parcelas se necessário
            $success = true;
            log_message('debug', 'No data to update, setting success = true');
        } else {
            // Sempre considerar sucesso, mesmo se não houve alterações
            $this->Expenses_model->updatetwo($updateData, $id);
            $success = true;
            log_message('debug', 'Update completed, setting success = true');
        }

        // Atualizar parcelas se necessário
        if ($success && $installments) {
            $this->load->model('Expenses_installments_model');
            $this->Expenses_installments_model->delete_installments_by_expense($id);
            $this->Expenses_installments_model->add_installments($id, $installments);
        }

        if (!$success) {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao atualizar despesa'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        return $this->response([
            'status' => true,
            'message' => 'Despesa atualizada com sucesso',
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

    public function get_get($id = null)
    {
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $expense = $this->Expenses_model->gettwo($id);
        if (!$expense) {
            return $this->response([
                'status' => false,
                'message' => 'Despesa não encontrada'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        
        // Carregar parcelas se existirem
        $this->load->model('Expenses_installments_model');
        $installments = $this->Expenses_installments_model->get_installments_by_expense($id);
        
        // Adicionar parcelas aos dados da despesa
        if (!empty($installments)) {
            $expense->installments = $installments;

            // --- AJUSTE PARA PADRONIZAR CAMPOS DE JUROS ---
            if (
                (empty($expense->juros) || $expense->juros == 0 || $expense->juros == '0.00' || $expense->juros == '0') &&
                isset($installments[0]['percentual_juros']) &&
                $installments[0]['percentual_juros'] > 0
            ) {
                $expense->juros = $installments[0]['percentual_juros'];
                // Tenta pegar a partir de qual parcela começa o juros
                $expense->juros_apartir = 1;
                foreach ($installments as $inst) {
                    if (isset($inst['percentual_juros']) && $inst['percentual_juros'] > 0) {
                        $expense->juros_apartir = $inst['numero_parcela'];
                        break;
                    }
                }
            }
            // --- FIM DO AJUSTE ---
        }
        
        return $this->response([
            'status' => true,
            'data' => $expense
        ], REST_Controller::HTTP_OK);
    }

    /**
     * Obter parcelas de uma despesa
     * @param int $id ID da despesa
     */
    public function installments_get($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        try {
            if (!$id) {
                throw new Exception('ID da despesa é obrigatório');
            }

            $this->load->model('Expenses_installments_model');
            
            $installments = $this->Expenses_installments_model->get_installments_by_expense($id);
            $summary = $this->Expenses_installments_model->get_installments_summary($id);

            return $this->response([
                'status' => true,
                'data' => [
                    'installments' => $installments,
                    'summary' => $summary
                ]
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function pay_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        try {
            // Verificar se é multipart/form-data ou JSON
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);

            if ($is_multipart) {
                // Processar dados do FormData - usar $_POST diretamente como no Produto.php e reatribuir $_POST
                if (isset($_POST['data'])) {
                    $data = json_decode($_POST['data'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('JSON inválido: ' . json_last_error_msg());
                    }
                    $_POST = $data; // Reatribuir $_POST
                } else {
                    throw new Exception('Campo "data" não encontrado na requisição multipart');
                }
            } else {
                // Processar dados JSON
                $raw_input = file_get_contents("php://input");
                $data = json_decode($raw_input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('JSON inválido: ' . json_last_error_msg());
                }
                $_POST = $data; // Reatribuir $_POST
            }
            if (empty($data['id'])) {
                throw new Exception('ID da despesa é obrigatório');
            }
            
            $expense_id = $data['id'];
            $installment_id = $data['installment_id'] ?? null;
            $installment_numbers = $data['installment_numbers'] ?? null;
            
            // Se há installment_numbers, é pagamento de múltiplas parcelas
            if ($installment_numbers && is_array($installment_numbers)) {
                return $this->pay_multiple_installments($expense_id, $installment_numbers, $data);
            }
            
            // Se há installment_id, é pagamento de parcela única
            if ($installment_id) {
                return $this->pay_installment($expense_id, $installment_id, $data);
            }
            
            // Pagamento da despesa completa
            $updateData = [
                'status' => 'paid',
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'category' => $data['category_id'] ?? null,
                'note' => $data['note'] ?? null,
                'descricao_pagamento' => $data['descricao_pagamento'] ?? null,
                'juros' => $data['juros'] ?? null,
                'desconto' => $data['desconto'] ?? null,
                'multa' => $data['multa'] ?? null,
                'valor_pago' => $data['valorPago'] ?? null,
                'check_identifier' => $data['check_identifier'] ?? null,
                'boleto_identifier' => $data['boleto_identifier'] ?? null,
            ];
            // Upload do comprovante (voucher) - agora via multipart/form-data
            $voucher_path = null;
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);
            
            if ($is_multipart && isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['comprovante'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validar tipos de arquivo permitidos
                $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG');
                }

                // Verificar tamanho do arquivo (5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
                }

                // Buscar warehouse_id da despesa
                $expense = $this->Expenses_model->gettwo($expense_id);
                $warehouse_id = $expense ? $expense->warehouse_id : 0;

                $unique_filename = 'voucher_' . $expense_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
                $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $warehouse_id . '/expenses/vouchers/' . $expense_id . '/' . $unique_filename;

                try {
                    // Inicializar cliente S3
                    $s3 = $this->storage_s3->getClient();
                    
                    // Upload para S3
                    $s3->putObject([
                        'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                        'Key' => $blobName,
                        'SourceFile' => $file['tmp_name'],
                        'ACL' => 'public-read',
                    ]);

                    // Constrói a URL do arquivo
                    $voucher_path = "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
                    $updateData['voucher'] = $voucher_path;
                } catch (Exception $e) {
                    throw new Exception('Falha ao fazer upload do comprovante para S3: ' . $e->getMessage());
                }
            }
            $this->load->model('Expenses_model');
            $success = $this->Expenses_model->updatetwo($updateData, $expense_id);
            if (!$success) {
                throw new Exception('Falha ao baixar o lançamento');
            }
            return $this->response([
                'status' => true,
                'message' => 'Pagamento baixado com sucesso',
                'voucher_url' => $voucher_path
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Pagar múltiplas parcelas em sequência
     * @param int $expense_id ID da despesa
     * @param array $installment_numbers Array com números das parcelas
     * @param array $data Dados do pagamento
     * @return mixed
     */
    private function pay_multiple_installments($expense_id, $installment_numbers, $data)
    {
        try {
            $this->load->model('Expenses_installments_model');
            
            // Verificar se as parcelas são sequenciais
            sort($installment_numbers);
            for ($i = 1; $i < count($installment_numbers); $i++) {
                if ($installment_numbers[$i] !== $installment_numbers[$i-1] + 1) {
                    throw new Exception('As parcelas devem ser sequenciais. Não é possível pular parcelas.');
                }
            }
            
            // Buscar todas as parcelas da despesa
            $all_installments = $this->Expenses_installments_model->get_installments_by_expense($expense_id);
            
            // Filtrar apenas as parcelas selecionadas
            $selected_installments = array_filter($all_installments, function($installment) use ($installment_numbers) {
                return in_array($installment['numero_parcela'], $installment_numbers);
            });
            
            if (count($selected_installments) !== count($installment_numbers)) {
                throw new Exception('Algumas parcelas selecionadas não foram encontradas');
            }
            
            // Verificar se todas as parcelas estão pendentes
            foreach ($selected_installments as $installment) {
                if ($installment['status'] === 'Pago') {
                    throw new Exception("A parcela {$installment['numero_parcela']} já foi paga");
                }
            }
            
            // Upload do comprovante (voucher) - será usado para todas as parcelas
            $voucher_path = null;
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);
            
            if ($is_multipart && isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $voucher_path = $this->upload_voucher($expense_id, $_FILES['comprovante']);
            }
            
            $success_count = 0;
            $failed_count = 0;
            $errors = [];
            
            // Processar cada parcela
            foreach ($selected_installments as $installment) {
                try {
                    $payment_data = [
                        'data_pagamento' => $data['payment_date'] ?? date('Y-m-d'),
                        'valor_pago' => $data['valorPago'] ?? $installment['valor_com_juros'],
                        'banco_id' => $data['bank_account_id'] ?? null,
                        'observacoes' => $data['note'] ?? null,
                        'juros_adicional' => $data['juros'] ?? 0,
                        'desconto' => $data['desconto'] ?? 0,
                        'multa' => $data['multa'] ?? 0,
                        'id_cheque' => $data['check_identifier'] ?? null,
                        'id_boleto' => $data['boleto_identifier'] ?? null,
                        'comprovante' => $voucher_path,
                    ];
                    
                    $success = $this->Expenses_installments_model->pay_installment($installment['id'], $payment_data);
                    if ($success) {
                        $success_count++;
                    } else {
                        $failed_count++;
                        $errors[] = "Falha ao processar parcela {$installment['numero_parcela']}";
                    }
                } catch (Exception $e) {
                    $failed_count++;
                    $errors[] = "Erro na parcela {$installment['numero_parcela']}: " . $e->getMessage();
                }
            }
            
            // Verificar se todas as parcelas foram pagas
            $summary = $this->Expenses_installments_model->get_installments_summary($expense_id);
            if ($summary['parcelas_pendentes'] == 0) {
                // Marcar despesa como paga
                $this->load->model('Expenses_model');
                $this->Expenses_model->updatetwo([
                    'status' => 'paid',
                    'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                ], $expense_id);
            }
            
            if ($failed_count > 0) {
                return $this->response([
                    'status' => false,
                    'message' => "Algumas parcelas não puderam ser processadas",
                    'success_count' => $success_count,
                    'failed_count' => $failed_count,
                    'errors' => $errors,
                    'voucher_url' => $voucher_path
                ], REST_Controller::HTTP_PARTIAL_CONTENT);
            }
            
            return $this->response([
                'status' => true,
                'message' => "Todas as parcelas foram pagas com sucesso",
                'success_count' => $success_count,
                'voucher_url' => $voucher_path,
                'installment_summary' => $summary
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Atualizar due_date de todas as despesas que têm parcelas
     * Endpoint para correção em massa
     */
    public function update_due_dates_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        
        try {
            $this->load->model('Expenses_installments_model');
            
            $stats = $this->Expenses_installments_model->update_all_expenses_due_dates();
            
            return $this->response([
                'status' => true,
                'message' => 'Atualização de due_dates concluída com sucesso',
                'data' => $stats
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Teste da funcionalidade de atualização de due_date
     * Endpoint para testar a funcionalidade
     */
    public function test_due_date_update_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        
        try {
            $raw_input = file_get_contents("php://input");
            $data = json_decode($raw_input, true);
            
            if (empty($data['expense_id'])) {
                throw new Exception('ID da despesa é obrigatório');
            }
            
            $expense_id = $data['expense_id'];
            $this->load->model('Expenses_installments_model');
            
            // Buscar dados da despesa antes da atualização
            $expense_before = $this->db->get_where(db_prefix() . 'expenses', ['id' => $expense_id])->row();
            
            // Atualizar o due_date
            $success = $this->Expenses_installments_model->update_expense_due_date($expense_id);
            
            // Buscar dados da despesa após a atualização
            $expense_after = $this->db->get_where(db_prefix() . 'expenses', ['id' => $expense_id])->row();
            
            // Buscar parcelas da despesa
            $installments = $this->Expenses_installments_model->get_installments_by_expense($expense_id);
            
            return $this->response([
                'status' => true,
                'message' => 'Teste concluído com sucesso',
                'data' => [
                    'success' => $success,
                    'expense_before' => $expense_before,
                    'expense_after' => $expense_after,
                    'installments' => $installments,
                    'due_date_changed' => $expense_before->due_date !== $expense_after->due_date
                ]
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Corrigir valores existentes no banco de dados
     * Endpoint para correção em massa dos valores após implementação dos novos campos
     */
    public function fix_existing_values_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        
        try {
            $this->load->model('Expenses_installments_model');
            
            $stats = $this->Expenses_installments_model->fix_existing_values();
            
            return $this->response([
                'status' => true,
                'message' => 'Correção de valores existentes concluída com sucesso',
                'data' => $stats
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Processar dados de parcelas
     * @param array $data Dados da requisição
     * @return array Array com as parcelas processadas
     */
    private function process_installments($data)
    {
        $num_parcelas = $data['num_parcelas'] ?? 1;
        $valor_original = $data['valor_original'] ?? $data['amount'] ?? 0; // Usar valor_original se disponível, senão amount
        $juros = $data['juros'] ?? 0;
        $juros_apartir = $data['juros_apartir'] ?? 1;
        $tipo_juros = $data['tipo_juros'] ?? 'simples';
        $data_vencimento = $data['due_date'] ?? date('Y-m-d');
        $paymentmode_id = $data['paymentmode'] ?? 0;

        $installments = [];
        $valor_parcela = $valor_original / $num_parcelas;

        if ($tipo_juros === 'composto') {
            // Juros compostos: aplicado sobre o valor acumulado
            $valor_acumulado = $valor_parcela;
            for ($i = 1; $i <= $num_parcelas; $i++) {
                $tem_juros = $i >= $juros_apartir;
                $juros_parcela = 0;
                $valor_com_juros = $valor_parcela;
                
                if ($tem_juros) {
                    $juros_parcela = $valor_acumulado * ($juros / 100);
                    $valor_com_juros = $valor_parcela + $juros_parcela;
                    $valor_acumulado += $juros_parcela;
                }

                // Calcular data de vencimento da parcela
                $data_vencimento_parcela = date('Y-m-d', strtotime($data_vencimento . ' + ' . ($i - 1) . ' months'));

                $installments[] = [
                    'numero_parcela' => $i,
                    'data_vencimento' => $data_vencimento_parcela,
                    'valor_parcela' => $valor_parcela,
                    'valor_com_juros' => $valor_com_juros,
                    'juros' => $juros_parcela,
                    'juros_adicional' => 0, // Será preenchido no momento do pagamento
                    'desconto' => 0, // Será preenchido no momento do pagamento
                    'multa' => 0, // Será preenchido no momento do pagamento
                    'percentual_juros' => $tem_juros ? $juros : 0,
                    'tipo_juros' => $tipo_juros,
                    'paymentmode_id' => $paymentmode_id,
                    'documento_parcela' => $data['expense_identifier'] ?? null,
                    'observacoes' => $data['note'] ?? null,
                ];
            }
        } else {
            // Juros simples: aplicado sobre o valor original da parcela
            for ($i = 1; $i <= $num_parcelas; $i++) {
                $tem_juros = $i >= $juros_apartir;
                $juros_parcela = $tem_juros ? $valor_parcela * ($juros / 100) : 0;
                $valor_com_juros = $valor_parcela + $juros_parcela;

                // Calcular data de vencimento da parcela
                $data_vencimento_parcela = date('Y-m-d', strtotime($data_vencimento . ' + ' . ($i - 1) . ' months'));

                $installments[] = [
                    'numero_parcela' => $i,
                    'data_vencimento' => $data_vencimento_parcela,
                    'valor_parcela' => $valor_parcela,
                    'valor_com_juros' => $valor_com_juros,
                    'juros' => $juros_parcela,
                    'juros_adicional' => 0, // Será preenchido no momento do pagamento
                    'desconto' => 0, // Será preenchido no momento do pagamento
                    'multa' => 0, // Será preenchido no momento do pagamento
                    'percentual_juros' => $tem_juros ? $juros : 0,
                    'tipo_juros' => $tipo_juros,
                    'paymentmode_id' => $paymentmode_id,
                    'documento_parcela' => $data['expense_identifier'] ?? null,
                    'observacoes' => $data['note'] ?? null,
                ];
            }
        }

        return $installments;
    }

    /**
     * Pagar uma parcela específica
     * @param int $expense_id ID da despesa
     * @param int $installment_id ID da parcela
     * @param array $data Dados do pagamento
     * @return mixed
     */
    private function pay_installment($expense_id, $installment_id, $data)
    {
        try {
            $this->load->model('Expenses_installments_model');
            
            // Verificar se a parcela existe
            $installment = $this->Expenses_installments_model->get_installment($installment_id);
            if (!$installment) {
                throw new Exception('Parcela não encontrada');
            }
            
            // Verificar se a parcela pertence à despesa
            if ($installment->expenses_id != $expense_id) {
                throw new Exception('Parcela não pertence à despesa informada');
            }
            
            // Preparar dados do pagamento
            $payment_data = [
                'data_pagamento' => $data['payment_date'] ?? date('Y-m-d'),
                'valor_pago' => $data['valorPago'] ?? $installment->valor_com_juros,
                'banco_id' => $data['bank_account_id'] ?? null,
                'observacoes' => $data['note'] ?? null,
                'juros_adicional' => $data['juros'] ?? 0,
                'desconto' => $data['desconto'] ?? 0,
                'multa' => $data['multa'] ?? 0,
                'id_cheque' => $data['check_identifier'] ?? null,
                'id_boleto' => $data['boleto_identifier'] ?? null,
            ];
            
            // Upload do comprovante (voucher)
            $voucher_path = null;
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);
            
            if ($is_multipart && isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $voucher_path = $this->upload_voucher($expense_id, $_FILES['comprovante']);
            }
            
            // Adicionar o comprovante aos dados de pagamento
            $payment_data['comprovante'] = $voucher_path;
            
            // Realizar o pagamento
            $success = $this->Expenses_installments_model->pay_installment($installment_id, $payment_data);
            if (!$success) {
                throw new Exception('Falha ao processar pagamento da parcela');
            }
            
            // Verificar se todas as parcelas foram pagas
            $summary = $this->Expenses_installments_model->get_installments_summary($expense_id);
            if ($summary['parcelas_pendentes'] == 0) {
                // Marcar despesa como paga
                $this->load->model('Expenses_model');
                $this->Expenses_model->updatetwo([
                    'status' => 'paid',
                    'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                ], $expense_id);
            }
            
            return $this->response([
                'status' => true,
                'message' => 'Parcela paga com sucesso',
                'voucher_url' => $voucher_path,
                'installment_summary' => $summary
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Upload de voucher para S3
     * @param int $expense_id ID da despesa
     * @param array $file Dados do arquivo do $_FILES
     * @return string|null URL do arquivo ou null
     */
    private function upload_voucher($expense_id, $file)
    {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validar tipos de arquivo permitidos
        $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG');
        }

        // Verificar tamanho do arquivo (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
        }

        // Buscar warehouse_id da despesa
        $expense = $this->Expenses_model->gettwo($expense_id);
        $warehouse_id = $expense ? $expense->warehouse_id : 0;

        $unique_filename = 'voucher_' . $expense_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $warehouse_id . '/expenses/vouchers/' . $expense_id . '/' . $unique_filename;

        try {
            // Inicializar cliente S3
            $s3 = $this->storage_s3->getClient();
            
            // Upload para S3
            $s3->putObject([
                'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                'Key' => $blobName,
                'SourceFile' => $file['tmp_name'],
                'ACL' => 'public-read',
            ]);

            // Constrói a URL do arquivo
            return "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
        } catch (Exception $e) {
            throw new Exception('Falha ao fazer upload do comprovante para S3: ' . $e->getMessage());
        }
    }
}