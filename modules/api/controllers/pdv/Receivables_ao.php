<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Receivables_ao extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Receivables_ao_model');
    }

    public function warehouselist_get()
    {
        try {
            $warehouses = $this->Receivables_ao_model->get_warehouses();

            $this->response([
                'success' => true,
                'data' => $warehouses
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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

        $duplicates = $this->Receivables_ao_model->validate_duplicates(
            $input['warehouse_id'],
            $input['data'],
            $input['mappedColumns']
        );

        return $this->response([
            'status' => true,
            'duplicates' => $duplicates,
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

        $summary = $this->Receivables_ao_model->get_receivables_summary($warehouse_id);

        $total = $summary['received'] + $summary['to_receive'] + $summary['overdue'];
        $received_percent = $total > 0 ? round(($summary['received'] / $total) * 100, 1) : 0;

        return $this->response([
            'status' => true,
            'data' => [
                'received' => $summary['received'],
                'received_count' => $summary['received_count'],
                'received_today' => $summary['received_today'],
                'received_today_count' => $summary['received_today_count'],
                'to_receive' => $summary['to_receive'],
                'to_receive_count' => $summary['to_receive_count'],
                'to_receive_month' => $summary['to_receive_month'],
                'to_receive_month_count' => $summary['to_receive_month_count'],
                'overdue' => $summary['overdue'],
                'overdue_count' => $summary['overdue_count'],
                'received_percent' => $received_percent
            ],
        ], REST_Controller::HTTP_OK);
    }


    public function list_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $filters = [
            'warehouse_id' => $this->post('warehouse_id'),
            'search' => $this->post('search'),
            'category' => $this->post('category'),
            'status' => $this->post('status'),
            'startDate' => $this->post('startDate'),
            'endDate' => $this->post('endDate'),
        ];

        if (empty($filters['warehouse_id'])) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $page = (int) ($this->post('page') ?? 0);
        $pageSize = (int) ($this->post('pageSize') ?? 10);
        $sortField = $this->post('sortField') ?? 'id';
        $sortOrder = strtolower($this->post('sortOrder')) === 'desc' ? 'DESC' : 'ASC';

        $data = $this->Receivables_ao_model->get_receivables($filters, $page, $pageSize, $sortField, $sortOrder);
        $total = $this->Receivables_ao_model->count_receivables($filters);

        return $this->response([
            'status' => true,
            'data' => $data,
            'total' => $total,
            'page' => $page + 1,
            'limit' => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ], REST_Controller::HTTP_OK);
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
            $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
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

        $expense = $this->db->get_where(db_prefix() . 'expenses_ao', ['id' => $input['id']])->row();
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
        $success = $this->db->update(db_prefix() . 'expenses_ao', $data);

        if ($success) {
            $updated_expense = $this->db->get_where(db_prefix() . 'expenses_ao', ['id' => $input['id']])->row();

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

}