<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Receivables extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Receivables_model');
    }

    public function warehouselist_get()
    {
        try {
            $warehouses = $this->Receivables_model->get_warehouses();

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

        $duplicates = $this->Receivables_model->validate_duplicates(
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

        $summary = $this->Receivables_model->get_receivables_summary($warehouse_id);

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
            'search'       => $this->post('search'),
            'category'     => $this->post('category'),
            'status'       => $this->post('status'),
            'startDate'    => $this->post('startDate'),
            'endDate'      => $this->post('endDate'),
        ];

        if (empty($filters['warehouse_id'])) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $page      = (int) ($this->post('page') ?? 0);
        $pageSize  = (int) ($this->post('pageSize') ?? 10);
        $sortField = $this->post('sortField') ?? 'id';
        $sortOrder = strtolower($this->post('sortOrder')) === 'desc' ? 'DESC' : 'ASC';

        $data = $this->Receivables_model->get_receivables($filters, $page, $pageSize, $sortField, $sortOrder);
        $total = $this->Receivables_model->count_receivables($filters);

        return $this->response([
            'status'      => true,
            'data'        => $data,
            'total'       => $total,
            'page'        => $page + 1,
            'limit'       => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ], REST_Controller::HTTP_OK);
    }

    public function payment_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $id = $this->post('id');
        $status = $this->post('status');

        if (empty($id) || !in_array($status, ['pending', 'paid'])) {
            return $this->response([
                'status' => false,
                'message' => 'ID e status são obrigatórios (pending ou paid)'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->db->where('id', $id);
        $success = $this->db->update(db_prefix() . 'receivables', ['status' => $status]);

        if ($success) {
            return $this->response([
                'status' => true,
                'message' => 'Status atualizado com sucesso'
            ], REST_Controller::HTTP_OK);
        }

        return $this->response([
            'status' => false,
            'message' => 'Falha ao atualizar status'
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }

    // Métodos CRUD básicos para Receitas
    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Campos obrigatórios mínimos
        $required = [];
        if (empty($input['category'])) $required[] = 'category';
        if (empty($input['amount'])) $required[] = 'amount';
        if (empty($input['date'])) $required[] = 'date';
        if (empty($input['warehouse_id'])) $required[] = 'warehouse_id';
        if (!empty($required)) {
            return $this->response([
                'status' => false,
                'message' => 'Campos obrigatórios: ' . implode(', ', $required)
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $data = [
            'category' => $input['category'],
            'currency' => $input['currency'] ?? 1,
            'amount' => $input['amount'],
            'tax' => $input['tax'] ?? null,
            'tax2' => $input['tax2'] ?? 0,
            'reference_no' => $input['reference_no'] ?? null,
            'note' => $input['note'] ?? null,
            'expense_name' => $input['expense_name'] ?? null,
            'payment_number' => $input['payment_number'] ?? null,
            'clientid' => $input['clientid'] ?? null,
            'project_id' => $input['project_id'] ?? 0,
            'billable' => isset($input['billable']) ? ($input['billable'] ? 1 : 0) : 0,
            'invoiceid' => $input['invoiceid'] ?? null,
            'paymentmode' => $input['paymentmode'] ?? 0,
            'date' => $input['date'],
            'due_date' => $input['due_date'] ?? null,
            'reference_date' => $input['reference_date'] ?? null,
            'order_number' => $input['order_number'] ?? null,
            'installment_number' => $input['installment_number'] ?? null,
            'nfe_key' => $input['nfe_key'] ?? null,
            'barcode' => $input['barcode'] ?? null,
            'origin' => $input['origin'] ?? null,
            'recurring_type' => $input['recurring_type'] ?? null,
            'repeat_every' => $input['repeat_every'] ?? null,
            'recurring' => isset($input['recurring']) ? ($input['recurring'] ? 1 : 0) : 0,
            'cycles' => $input['cycles'] ?? 0,
            'total_cycles' => $input['total_cycles'] ?? 0,
            'custom_recurring' => isset($input['custom_recurring']) ? ($input['custom_recurring'] ? 1 : 0) : 0,
            'last_recurring_date' => $input['last_recurring_date'] ?? null,
            'create_invoice_billable' => isset($input['create_invoice_billable']) ? ($input['create_invoice_billable'] ? 1 : 0) : 0,
            'send_invoice_to_customer' => isset($input['send_invoice_to_customer']) ? ($input['send_invoice_to_customer'] ? 1 : 0) : 0,
            'recurring_from' => $input['recurring_from'] ?? null,
            'dateadded' => date('Y-m-d H:i:s'),
            'addedfrom' => get_staff_user_id() ?? 1,
            'perfex_saas_tenant_id' => 'master',
            'type' => 'receita',
            'status' => $input['status'] ?? 'pending',
            'warehouse_id' => $input['warehouse_id'],
            'expenses_document' => $input['expenses_document'] ?? null,
            'expense_document' => $input['expense_document'] ?? null,
        ];
        $data = array_filter($data, function ($v) { return $v !== null; });
        $this->db->insert(db_prefix() . 'receivables', $data);
        $id = $this->db->insert_id();
        if ($id) {
            return $this->response([
                'status' => true,
                'message' => 'Receita criada com sucesso',
                'data' => ['id' => $id]
            ], REST_Controller::HTTP_CREATED);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao criar receita'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_post($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($input)) {
            return $this->response([
                'status' => false,
                'message' => 'Dados inválidos'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'receivables', $input);
        if ($this->db->affected_rows() > 0) {
            return $this->response([
                'status' => true,
                'message' => 'Receita atualizada com sucesso'
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao atualizar receita ou nenhum dado alterado'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        $id = $this->post('id');
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório para deletar'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'receivables');
        if ($this->db->affected_rows() > 0) {
            return $this->response([
                'status' => true,
                'message' => 'Receita deletada com sucesso'
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao deletar receita'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}