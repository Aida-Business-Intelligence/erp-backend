<?php
defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Banking extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Banking_model');
    }

    /* ===================== CONTAS ===================== */

    // POST /api/banking/accounts/list
    public function accounts_list_post()
    {
        $page  = (int) ($this->post('page') ?: 1);
        $limit = (int) ($this->post('pageSize') ?: 10);
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = ($this->post('sortOrder') === 'ASC') ? 'ASC' : 'DESC';
        $warehouse_id = (int) ($this->post('warehouse_id') ?: 0);

        if ($warehouse_id <= 0) {
            return $this->response(['status'=>false,'message'=>'warehouse_id é obrigatório'], 400);
        }

        $data = $this->Banking_model->accounts_list($page, $limit, $search, $sortField, $sortOrder, $warehouse_id);

        return $this->response([
            'status' => true,
            'total'  => $data['total'],
            'data'   => $data['data']
        ], 200);
    }

    public function accounts_get()
    {
        $id = (int) ($this->get('id') ?: 0);
        if ($id <= 0) {
            return $this->response(['status'=>false,'message'=>'id inválido'], 400);
        }
        $row = $this->db->get_where(db_prefix().'banking_accounts', ['id'=>$id])->row_array();
        if (!$row) {
            return $this->response(['status'=>false,'message'=>'Conta não encontrada'], 404);
        }
        return $this->response(['status'=>true,'data'=>$row], 200);
    }

    // POST /api/banking/accounts/create
    public function accounts_create_post()
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $this->post();
        if (empty($payload['warehouse_id']) || empty($payload['bank_name']) || empty($payload['account_number'])) {
            return $this->response(['status'=>false,'message'=>'Campos obrigatórios: warehouse_id, bank_name, account_number'], 400);
        }

        $id = $this->Banking_model->account_create($payload);
        return $id
            ? $this->response(['status'=>true,'data'=>['id'=>$id]], 200)
            : $this->response(['status'=>false,'message'=>'Falha ao criar conta'], 500);
    }

    // POST /api/banking/accounts/update/{id}
    public function accounts_update_post($id = '')
    {
        $id = (int) $id;
        $payload = json_decode(file_get_contents('php://input'), true) ?: $this->post();
        if ($id <= 0) return $this->response(['status'=>false,'message'=>'ID inválido'], 400);

        $ok = $this->Banking_model->account_update($id, $payload);
        return $ok
            ? $this->response(['status'=>true,'message'=>'Conta atualizada'], 200)
            : $this->response(['status'=>false,'message'=>'Nada para atualizar ou conta inexistente'], 404);
    }

    // POST /api/banking/accounts/remove
    public function accounts_remove_post()
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $this->post();
        $ids = isset($payload['rows']) && is_array($payload['rows']) ? array_filter($payload['rows'], 'is_numeric') : [];
        if (!$ids) return $this->response(['status'=>false,'message'=>'rows inválido'], 400);

        $deleted = $this->Banking_model->account_delete_many($ids);
        return $this->response(['status'=>true,'deleted'=>$deleted], 200);
    }

    /* ===================== TAXAS (CONFIG) ===================== */

    // GET /api/banking/fees?warehouse_id=1&account_id=5
    public function fees_get()
    {
        $warehouse_id = (int) ($this->get('warehouse_id') ?: 0);
        $account_id   = $this->get('account_id') !== null ? (int)$this->get('account_id') : null;
        if ($warehouse_id <= 0) return $this->response(['status'=>false,'message'=>'warehouse_id é obrigatório'], 400);

        $config = $this->Banking_model->fees_get($warehouse_id, $account_id);
        return $this->response(['status'=>true,'data'=>$config], 200);
    }

    // POST /api/banking/fees/upsert
    public function fees_upsert_post()
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $this->post();
        if (empty($payload['warehouse_id'])) {
            return $this->response(['status'=>false,'message'=>'warehouse_id é obrigatório'], 400);
        }

        $ok = $this->Banking_model->fees_upsert($payload);
        return $ok
            ? $this->response(['status'=>true,'message'=>'Configurações salvas'], 200)
            : $this->response(['status'=>false,'message'=>'Falha ao salvar configurações'], 500);
    }

    /* ===================== TRANSAÇÕES ===================== */

    // POST /api/banking/transactions/list
    public function transactions_list_post()
    {
        $page  = (int) ($this->post('page') ?: 1);
        $limit = (int) ($this->post('pageSize') ?: 10);
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'transacted_at';
        $sortOrder = ($this->post('sortOrder') === 'ASC') ? 'ASC' : 'DESC';
        $warehouse_id = (int) ($this->post('warehouse_id') ?: 0);
        $account_id   = (int) ($this->post('account_id') ?: 0);
        $start_date   = $this->post('start_date') ?: null;
        $end_date     = $this->post('end_date') ?: null;
        $method       = $this->post('method') ?: null;
        $type         = $this->post('type') ?: null; // income/expense

        if ($warehouse_id <= 0 || $account_id <= 0) {
            return $this->response(['status'=>false,'message'=>'warehouse_id e account_id são obrigatórios'], 400);
        }

        $data = $this->Banking_model->transactions_list($page,$limit,$search,$sortField,$sortOrder,$warehouse_id,$account_id,$start_date,$end_date,$method,$type);
        return $this->response(['status'=>true,'total'=>$data['total'],'data'=>$data['data']], 200);
    }

    // POST /api/banking/transactions/create
    public function transactions_create_post()
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: $this->post();

        $required = ['warehouse_id','account_id','type','method','gross_amount','transacted_at'];
        foreach ($required as $f) if (!isset($payload[$f]) || $payload[$f]==='') {
            return $this->response(['status'=>false,'message'=>"Campo obrigatório faltando: {$f}"], 400);
        }

        // Calcula liquido a partir das configs
        $calc = $this->Banking_model->calc_net_amount(
            (float)$payload['gross_amount'],
            $payload['method'],
            !empty($payload['anticipation']),                     // booleano
            $payload['warehouse_id'],
            $payload['account_id'],
            isset($payload['anticipation_percent_override']) ? (float)$payload['anticipation_percent_override'] : null
        );

        $payload['fee_percent_applied'] = $calc['fee_percent_applied'];
        $payload['anticipation_applied'] = $calc['anticipation_applied'];
        $payload['anticipation_percent_applied'] = $calc['anticipation_percent_applied'];
        $payload['net_amount'] = $payload['type']==='expense'
            ? 0 - $calc['net_amount']    // despesas deixam net negativo
            : $calc['net_amount'];

        $payload['created_by'] = $this->session->userdata('staff_user_id') ?? 0;

        $id = $this->Banking_model->transaction_create($payload);
        return $id
            ? $this->response(['status'=>true,'data'=>['id'=>$id]], 200)
            : $this->response(['status'=>false,'message'=>'Falha ao criar transação'], 500);
    }

    /* ===================== SALDO ===================== */

    // GET /api/banking/balance?warehouse_id=1&account_id=2&start_date=2025-01-01&end_date=2025-01-31
    public function balance_get()
    {
        $warehouse_id = (int) ($this->get('warehouse_id') ?: 0);
        $account_id   = (int) ($this->get('account_id') ?: 0);
        if ($warehouse_id<=0 || $account_id<=0) {
            return $this->response(['status'=>false,'message'=>'warehouse_id e account_id são obrigatórios'], 400);
        }

        $start_date = $this->get('start_date') ?: null;
        $end_date   = $this->get('end_date') ?: null;

        $summary = $this->Banking_model->balance_summary($warehouse_id,$account_id,$start_date,$end_date);
        return $this->response(['status'=>true,'data'=>$summary], 200);
    }
}
