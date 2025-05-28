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
        $success = $this->db->update(db_prefix() . 'expenses', ['status' => $status]);

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

}