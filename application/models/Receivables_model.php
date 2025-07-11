<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Receivables_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function table() {
        return db_prefix() . 'receivables';
    }

    public function get_receivable_by_id($id)
    {
        log_message('error', 'RECEIVABLES_MODEL_GET_BY_ID called with ID: ' . $id);
        
        // Primeiro, vamos buscar apenas os dados básicos da receita
        $this->db->select('r.*');
        $this->db->from($this->table() . ' as r');
        $this->db->where('r.id', $id);
        
        $query = $this->db->get();
        log_message('error', 'RECEIVABLES_MODEL_GET_BY_ID_BASIC_QUERY: ' . $this->db->last_query());
        
        $result = $query->row();
        log_message('error', 'RECEIVABLES_MODEL_GET_BY_ID_BASIC_RESULT: ' . print_r($result, true));
        
        if (!$result) {
            return null;
        }
        
        // Agora vamos buscar os dados relacionados separadamente
        try {
            // Buscar dados do cliente
            if ($result->clientid) {
                $this->db->select('company, vat, phonenumber, email_default, address, city, state');
                $this->db->from(db_prefix() . 'clients');
                $this->db->where('userid', $result->clientid);
                $client = $this->db->get()->row();
                if ($client) {
                    $result->company = $client->company;
                    $result->vat = $client->vat;
                    $result->phonenumber = $client->phonenumber;
                    $result->email_default = $client->email_default;
                    $result->address = $client->address;
                    $result->city = $client->city;
                    $result->state = $client->state;
                }
            }
            
            // Buscar nome da categoria
            if ($result->category) {
                $this->db->select('name');
                $this->db->from(db_prefix() . 'expenses_categories');
                $this->db->where('id', $result->category);
                $category = $this->db->get()->row();
                if ($category) {
                    $result->category_name = $category->name;
                }
            }
            
            // Buscar nome do modo de pagamento
            if ($result->paymentmode) {
                $this->db->select('name');
                $this->db->from(db_prefix() . 'payment_modes');
                $this->db->where('id', $result->paymentmode);
                $paymentMode = $this->db->get()->row();
                if ($paymentMode) {
                    $result->payment_mode_name = $paymentMode->name;
                }
            }
            
            // Buscar nome da conta bancária
            if ($result->bank_account_id) {
                $this->db->select('name');
                $this->db->from(db_prefix() . 'bank_accounts');
                $this->db->where('id', $result->bank_account_id);
                $bankAccount = $this->db->get()->row();
                if ($bankAccount) {
                    $result->bank_account_name = $bankAccount->name;
                }
            }
            
            // Buscar nome da origem
            if ($result->origin) {
                $this->db->select('name');
                $this->db->from(db_prefix() . 'origins');
                $this->db->where('id', $result->origin);
                $origin = $this->db->get()->row();
                if ($origin) {
                    $result->origin_name = $origin->name;
                }
            }
            
            // Buscar nome do warehouse
            if ($result->warehouse_id) {
                $this->db->select('warehouse_name');
                $this->db->from(db_prefix() . 'warehouse');
                $this->db->where('warehouse_id', $result->warehouse_id);
                $warehouse = $this->db->get()->row();
                if ($warehouse) {
                    $result->warehouse_name = $warehouse->warehouse_name;
                }
            }
            
        } catch (Exception $e) {
            log_message('error', 'RECEIVABLES_MODEL_GET_BY_ID_RELATED_DATA_ERROR: ' . $e->getMessage());
        }
        
        // Converter valores booleanos
        if ($result) {
            $result->billable = (bool) $result->billable;
            $result->consider_business_days = (bool) $result->consider_business_days;
            $result->custom_recurring = (bool) $result->custom_recurring;
            $result->create_invoice_billable = (bool) $result->create_invoice_billable;
            $result->send_invoice_to_customer = (bool) $result->send_invoice_to_customer;
        }
        
        return $result;
    }

    public function get_receivables($filters = [], $page = 0, $pageSize = 10, $sortField = 'date', $sortOrder = 'DESC')
    {
        $this->db->select('
            r.*,
            c.company as company,
            cat.name as category_name,
            pm.name as payment_mode_name,
            w.warehouse_name
        ');
        $this->db->from($this->table() . ' as r');
        $this->db->join(db_prefix() . 'clients as c', 'r.clientid = c.userid', 'left');
        $this->db->join(db_prefix() . 'expenses_categories  as cat', 'r.category = cat.id', 'left');
        $this->db->join(db_prefix() . 'payment_modes as pm', 'r.paymentmode = pm.id', 'left');
        $this->db->join(db_prefix() . 'warehouse as w', 'w.warehouse_id = r.warehouse_id', 'left');

        if (!empty($filters['warehouse_id'])) {
            $this->db->where('r.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('r.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('r.category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('r.expense_name', $filters['search']);
            $this->db->or_like('r.reference_no', $filters['search']);
            $this->db->or_like('r.note', $filters['search']);
            $this->db->group_end();
        }
        
        if (
            !empty($filters['startDate']) &&
            !empty($filters['endDate']) &&
            $filters['startDate'] === $filters['endDate']
        ) {
            $this->db->where('r.due_date', $filters['startDate']);
        } else {
            if (!empty($filters['startDate'])) {
                $this->db->where('r.due_date >=', $filters['startDate']);
            }
            if (!empty($filters['endDate'])) {
                $this->db->where('r.due_date <=', $filters['endDate']);
            }
        }

        $allowedSortFields = [
            'id' => 'r.id',
            'date' => 'r.due_date',
            'amount' => 'r.amount',
            'status' => 'r.status',
            'company' => 'c.company',
            'category_name' => 'cat.name',
            'payment_mode_name' => 'pm.name',
        ];

        $sortField = $sortField ?? 'id';
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';
        $sortFieldSql = isset($allowedSortFields[$sortField]) ? $allowedSortFields[$sortField] : 'r.id';
        $this->db->order_by($sortFieldSql, $sortOrder);
        $this->db->limit($pageSize, $page * $pageSize);

        return $this->db->get()->result();
    }

    public function count_receivables($filters = [])
    {
        $this->db->from($this->table() . ' as r');
        if (!empty($filters['warehouse_id'])) {
            $this->db->where('r.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('r.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('r.category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('r.expense_name', $filters['search']);
            $this->db->or_like('r.reference_no', $filters['search']);
            $this->db->or_like('r.note', $filters['search']);
            $this->db->group_end();
        }
        if (
            !empty($filters['startDate']) &&
            !empty($filters['endDate']) &&
            $filters['startDate'] === $filters['endDate']
        ) {
            $this->db->where('r.due_date', $filters['startDate']);
        } else {
            if (!empty($filters['startDate'])) {
                $this->db->where('r.due_date >=', $filters['startDate']);
            }
            if (!empty($filters['endDate'])) {
                $this->db->where('r.due_date <=', $filters['endDate']);
            }
        }
        return $this->db->count_all_results();
    }

    public function get_receivables_summary($warehouse_id)
    {
        $today = date('Y-m-d');
        $currentMonth = date('m');
        $currentYear = date('Y');

        $received_today = $this->sum_receivables_amount('paid', $warehouse_id, '=', $today);
        $received_today_count = $this->count_receivables_by_status('paid', $warehouse_id, '=', $today);

        $received = $this->sum_receivables_amount('paid', $warehouse_id);
        $received_count = $this->count_receivables_by_status('paid', $warehouse_id);

        $to_receive_month = $this->sum_receivables_in_month('pending', $warehouse_id, $currentMonth, $currentYear);
        $to_receive_month_count = $this->count_receivables_in_month('pending', $warehouse_id, $currentMonth, $currentYear);

        $to_receive = $this->sum_receivables_amount('pending', $warehouse_id, '>=');
        $to_receive_count = $this->count_receivables_by_status('pending', $warehouse_id, '>=');

        $overdue = $this->sum_receivables_amount('pending', $warehouse_id, '<');
        $overdue_count = $this->count_receivables_by_status('pending', $warehouse_id, '<');

        return [
            'received' => $received,
            'received_count' => $received_count,
            'received_today' => $received_today,
            'received_today_count' => $received_today_count,
            'to_receive' => $to_receive,
            'to_receive_count' => $to_receive_count,
            'to_receive_month' => $to_receive_month,
            'to_receive_month_count' => $to_receive_month_count,
            'overdue' => $overdue,
            'overdue_count' => $overdue_count,
        ];
    }

    private function sum_receivables_amount($status, $warehouse_id, $date_operator = null, $specific_date = null)
    {
        $this->db->select_sum('amount');
        $this->db->from($this->table());
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);
        if ($date_operator && !$specific_date) {
            $this->db->where('due_date ' . $date_operator, date('Y-m-d'));
        }
        if ($specific_date) {
            $this->db->where('due_date', $specific_date);
        }
        return (float) $this->db->get()->row()->amount;
    }

    private function count_receivables_by_status($status, $warehouse_id, $date_operator = null, $specific_date = null)
    {
        $this->db->from($this->table());
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);
        if ($date_operator && !$specific_date) {
            $this->db->where('due_date ' . $date_operator, date('Y-m-d'));
        }
        if ($specific_date) {
            $this->db->where('due_date', $specific_date);
        }
        return (int) $this->db->count_all_results();
    }

    private function sum_receivables_in_month($status, $warehouse_id, $month, $year)
    {
        $this->db->select_sum('amount');
        $this->db->from($this->table());
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);
        $this->db->where('MONTH(due_date)', $month);
        $this->db->where('YEAR(due_date)', $year);
        return (float) $this->db->get()->row()->amount;
    }

    private function count_receivables_in_month($status, $warehouse_id, $month, $year)
    {
        $this->db->from($this->table());
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);
        $this->db->where('MONTH(due_date)', $month);
        $this->db->where('YEAR(due_date)', $year);
        return (int) $this->db->count_all_results();
    }

    // Métodos auxiliares para warehouse, duplicatas, etc. podem ser adaptados conforme necessário
    public function get_warehouses()
    {
        return $this->db
            ->select('warehouse_id as id, warehouse_name as name')
            ->from(db_prefix() . 'warehouse')
            ->where('display', 1)
            ->order_by('warehouse_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_categories($warehouse_id, $search = '', $limit = 5, $type = 'receivable')
    {
        $this->db->select('id, name');
        $this->db->from(db_prefix() . 'expenses_categories');
        $this->db->where('type', $type);
        if ($warehouse_id) {
            $this->db->where('warehouse_id', $warehouse_id);
        }
        if ($search) {
            $this->db->like('name', $search);
        }
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    public function get_payment_modes()
    {
        return $this->db->get(db_prefix() . 'payment_modes')->result_array();
    }

    public function get_clients($warehouse_id = 0, $search = '', $limit = 5, $page = 0)
    {
        $this->db->select('userid as id, company as name, vat');
        $this->db->where('active', 1);
        $this->db->where('warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('company', $search);
            $this->db->or_like('vat', $search);
            $this->db->group_end();
        }

        $offset = 0; // sempre retorna os primeiros 5
        $this->db->limit($limit, $offset);

        return $this->db->get(db_prefix() . 'clients')->result_array();
    }

    // Exemplo de método para validação de duplicatas (ajuste conforme sua lógica)
    public function validate_duplicates($warehouse_id, $data, $mappedColumns)
    {
        // Implemente a lógica de validação de duplicatas para receitas
        return [];
    }

    // Métodos de add, update, delete podem ser implementados conforme necessidade

    public function get_receivables_by_day($params)
    {
        $warehouse_id = $params['warehouse_id'];
        $date = $params['date'];
        $page = $params['page'] ?? 1;
        $limit = $params['pageSize'] ?? 10;
        $offset = ($page - 1) * $limit;

        $this->db->select('
            r.*, 
            c.company as client,
            c.company as company,
            cat.name as category_name,
            pm.name as paymentmode,
            pm.name as payment_mode_name,
            w.warehouse_name
        ');
        $this->db->from($this->table() . ' as r');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = r.clientid', 'left');
        $this->db->join(db_prefix() . 'expenses_categories cat', 'cat.id = r.category', 'left');
        $this->db->join(db_prefix() . 'payment_modes pm', 'pm.id = r.paymentmode', 'left');
        $this->db->join(db_prefix() . 'warehouse w', 'w.warehouse_id = r.warehouse_id', 'left');
        $this->db->where('r.warehouse_id', $warehouse_id);
        $this->db->where('DATE(r.due_date)', $date);
        $this->db->order_by('r.due_date', 'DESC');

        // Contar total sem limite
        $total_query = clone $this->db;
        $total = $total_query->count_all_results();

        $this->db->limit($limit, $offset);
        $data = $this->db->get()->result_array();

        return [
            'data' => $data,
            'total' => $total
        ];
    }
}