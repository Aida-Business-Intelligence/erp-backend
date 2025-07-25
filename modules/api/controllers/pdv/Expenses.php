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

            $expense_document = null;
            $document_field = !empty($data['expense_document']) ? 'expense_document' : (!empty($data['expense_document']) ? 'expense_document' : null);

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
                        $expense_document = 'uploads/expenses/documents/' . $filename;
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
                'expense_document' => $expense_document,
                'order_number' => $data['order_number'] ?? null,
                'installment_number' => $data['installment_number'] ?? null,
                'nfe_key' => $data['nfe_key'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'origin' => $data['origin'] ?? null,
            ];

            $input = array_filter($input, function ($value) {
                return $value !== null;
            });

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
                    'document_url' => $expense_document ? base_url($expense_document) : null
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
                // Buscar documento antes de deletar
                $expense = $this->Expenses_model->gettwo($rowId);
                if ($expense && !empty($expense->expense_document)) {
                    $this->delete_expense_document_file($expense->expense_document);
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
        if ($expense && !empty($expense->expense_document)) {
            $this->delete_expense_document_file($expense->expense_document);
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
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public function update_put($id = '')
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

        // Atualizado: incluir todos os campos da tabela tblexpenses
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
            'recurring_type',
            'repeat_every',
            'recurring',
            'cycles',
            'total_cycles',
            'custom_recurring',
            'last_recurring_date',
            'create_invoice_billable',
            'send_invoice_to_customer',
            'recurring_from',
            'warehouse_id',
            'due_day',
            'installments',
            'consider_business_days',
            'week_day',
            'end_date',
            'due_day_2',
            'bank_account_id',
            'expense_document',
            'order_number',
            'installment_number',
            'nfe_key',
            'barcode'
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
        if (!empty($input['expense_document'])) {
            // Buscar o registro atual para pegar o caminho do arquivo antigo
            $current = $this->Expenses_model->gettwo($id);
            $old_document = $current && isset($current->expense_document) ? $current->expense_document : null;

            $document_data = $input['expense_document'];
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
                    $updateData['expense_document'] = 'uploads/expenses/documents/' . $filename;
                } else {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao salvar o documento no servidor'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
            else if (!empty($input['expense_document']) && !preg_match('/^data:(.+);base64,/', $input['expense_document'])) {
                $updateData['expense_document'] = $input['expense_document'];
            }
        }

        $success = $this->Expenses_model->updatetwo($updateData, $id);

        if (!$success) {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao atualizar despesa ou nenhum dado alterado'
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
        

        
        return $this->response([
            'status' => true,
            'data' => $expense
        ], REST_Controller::HTTP_OK);
    }

    public function pay_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        try {
            $raw_input = file_get_contents("php://input");
            $data = json_decode($raw_input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido: ' . json_last_error_msg());
            }
            if (empty($data['id'])) {
                throw new Exception('ID da despesa é obrigatório');
            }
            $expense_id = $data['id'];
            $updateData = [
                'status' => 'paid',
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'category' => $data['category_id'] ?? null,
                'note' => $data['note'] ?? null,
                'descricao_pagamento' => $data['descricao_pagamento'] ?? null, // novo campo
                'juros' => $data['juros'] ?? null,
                'desconto' => $data['desconto'] ?? null,
                'multa' => $data['multa'] ?? null,
                'valor_pago' => $data['valorPago'] ?? null,
            ];
            // Upload do comprovante (voucher)
            $voucher_path = null;
            if (!empty($data['comprovante'])) {
                $document_data = $data['comprovante'];
                if (preg_match('/^data:(.+);base64,/', $document_data, $matches)) {
                    $mime_type = $matches[1];
                    $document_data = substr($document_data, strpos($document_data, ',') + 1);
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
                        throw new Exception('Falha ao decodificar o comprovante');
                    }
                    if (strlen($document_data) > 5 * 1024 * 1024) {
                        throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
                    }
                    $upload_path = FCPATH . 'uploads/expenses/voucher/';
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
                    $filename = 'voucher_' . $expense_id . '_' . time() . '_' . uniqid() . '.' . $extension;
                    $file_path = $upload_path . $filename;
                    if (file_put_contents($file_path, $document_data)) {
                        $voucher_path = 'uploads/expenses/voucher/' . $filename;
                        $updateData['voucher'] = $voucher_path;
                    } else {
                        throw new Exception('Falha ao salvar o comprovante no servidor');
                    }
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
                'voucher_url' => $voucher_path ? base_url($voucher_path) : null
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}