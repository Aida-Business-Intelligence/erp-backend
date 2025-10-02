<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Receivables_ao_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function table()
    {
        return db_prefix() . 'receivables_ao';
    }

    public function get_receivable_by_id($id)
    {
        // Primeiro, vamos buscar apenas os dados básicos da receita
        $this->db->select('r.*');
        $this->db->from($this->table() . ' as r');
        $this->db->where('r.id', $id);

        $query = $this->db->get();

        $result = $query->row();

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
                $this->db->select('name, is_check, is_boleto');
                $this->db->from(db_prefix() . 'payment_modes');
                $this->db->where('id', $result->paymentmode);
                $paymentMode = $this->db->get()->row();
                if ($paymentMode) {
                    $result->payment_mode_name = $paymentMode->name;
                    $result->payment_mode = $paymentMode;
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
            if ($result->origin_id) {
                $this->db->select('name');
                $this->db->from(db_prefix() . 'origins');
                $this->db->where('id', $result->origin_id);
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

            // Carregar parcelas se existirem
            $this->load->model('Receivables_installments_model');
            $installments = $this->Receivables_installments_model->get_installments_by_receivable($id);

            // Adicionar parcelas aos dados da receita
            if (!empty($installments)) {
                $result->installments = $installments;
                // Buscar tipo_juros da primeira parcela que possuir esse campo
                $first_interest_installment = null;
                foreach ($installments as $inst) {
                    if (isset($inst['tipo_juros'])) {
                        $first_interest_installment = $inst;
                        break;
                    }
                }
                if ($first_interest_installment && isset($first_interest_installment['tipo_juros'])) {
                    $result->tipo_juros = $first_interest_installment['tipo_juros'];
                } else {
                    $result->tipo_juros = 'simples'; // fallback
                }
            } else {
                $result->tipo_juros = 'simples'; // fallback
            }

        } catch (Exception $e) {
        }

        // Converter valores booleanos
        if ($result) {
            $result->billable = (bool) $result->billable;
            $result->consider_business_days = (bool) $result->consider_business_days;
            $result->custom_recurring = (bool) $result->custom_recurring;
            $result->create_invoice_billable = (bool) $result->create_invoice_billable;
            $result->send_invoice_to_customer = (bool) $result->send_invoice_to_customer;
            $result->is_staff = (bool) $result->is_staff;
        }

        return $result;
    }

    public function get_receivables($filters = [], $page = 0, $pageSize = 10, $sortField = 'date', $sortOrder = 'DESC')
    {
        $this->db->select('
            r.*,
            r.receivable_identifier,
            CASE 
                WHEN r.is_client = 1 THEN c.company 
                WHEN r.is_client = 0 THEN CONCAT(s.firstname, " ", s.lastname)
                ELSE c.company 
            END as company,
            cat.name as category_name,
            pm.name as payment_mode_name,
            pm.is_check,
            pm.is_boleto,
            w.warehouse_name
        ');
        $this->db->from($this->table() . ' as r');
        $this->db->join(db_prefix() . 'clients as c', 'r.clientid = c.userid AND r.is_client = 1', 'left');
        $this->db->join(db_prefix() . 'staff as s', 'r.clientid = s.staffid AND r.is_client = 0', 'left');
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
            $this->db->like('r.receivable_identifier', $filters['search']);
            $this->db->or_like('c.company', $filters['search']);
            $this->db->or_like('s.firstname', $filters['search']);
            $this->db->or_like('s.lastname', $filters['search']);
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
            'company' => 'CASE WHEN r.is_client = 1 THEN c.company ELSE CONCAT(s.firstname, " ", s.lastname) END',
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
        $this->db->join(db_prefix() . 'clients as c', 'r.clientid = c.userid AND r.is_client = 1', 'left');
        $this->db->join(db_prefix() . 'staff as s', 'r.clientid = s.staffid AND r.is_client = 0', 'left');
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
            $this->db->like('r.receivable_identifier', $filters['search']);
            $this->db->or_like('c.company', $filters['search']);
            $this->db->or_like('s.firstname', $filters['search']);
            $this->db->or_like('s.lastname', $filters['search']);
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

        // Para valores recebidos, considerar parcelas recebidas e não apenas o campo amount
        $received_today = $this->sum_receivables_received_amount('received', $warehouse_id, '=', $today);
        $received_today_count = $this->count_receivables_by_status('received', $warehouse_id, '=', $today);

        $received = $this->sum_receivables_received_amount('received', $warehouse_id);
        $received_count = $this->count_receivables_by_status('received', $warehouse_id);

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

    public function get_payment_modes()
    {
        $this->db->select('id, name, is_credit_card, is_check, is_boleto');
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

    public function get_franchisees($warehouse_id = 0, $search = '', $limit = 5, $page = 0)
    {
        $this->db->select('staffid as id, CONCAT(firstname, " ", lastname) as name, vat');
        $this->db->where('active', 1);
        $this->db->where('type', 'franchisees');
        $this->db->where('warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('firstname', $search);
            $this->db->or_like('lastname', $search);
            $this->db->or_like('vat', $search);
            $this->db->group_end();
        }

        $offset = 0; // sempre retorna os primeiros 5
        $this->db->limit($limit, $offset);

        $result = $this->db->get(db_prefix() . 'staff')->result_array();

        log_message('debug', 'get_franchisees - warehouse_id: ' . $warehouse_id . ', search: ' . $search . ', result count: ' . count($result));
        log_message('debug', 'get_franchisees - SQL: ' . $this->db->last_query());

        return $result;
    }

    // Exemplo de método para validação de duplicatas (ajuste conforme sua lógica)
    public function validate_duplicates($warehouse_id, $data, $mappedColumns)
    {
        // Implemente a lógica de validação de duplicatas para receitas
        return [];
    }

    /**
     * Add new receivable
     * @param array $data All $_POST data
     * @return mixed
     */
    public function add($data)
    {
        $this->db->trans_start();

        // Separar dados de parcelas se existirem
        $installments = null;
        if (isset($data['installments'])) {
            $installments = $data['installments'];
            unset($data['installments']);
        }

        $this->db->insert(db_prefix() . 'receivables_ao', $data);
        $receivable_id = $this->db->insert_id();

        // SEMPRE criar pelo menos uma parcela na tabela de parcelas (padronização)
        if ($receivable_id) {
            $this->load->model('Receivables_installments_model');

            if ($installments) {
                // Se há parcelas definidas, usar elas
                $this->Receivables_installments_model->add_installments($receivable_id, $installments);
            } else {
                // Se não há parcelas, criar uma parcela única
                $single_installment = [
                    'numero_parcela' => 1,
                    'data_vencimento' => $data['due_date'] ?? $data['date'],
                    'valor_parcela' => $data['amount'],
                    'valor_com_juros' => $data['amount'],
                    'juros' => 0,
                    'percentual_juros' => 0,
                    'status' => 'Pendente',
                    'paymentmode_id' => $data['paymentmode'] ?? null,
                    'documento_parcela' => $data['receivable_identifier'] ?? null,
                    'observacoes' => $data['note'] ?? null,
                ];

                $this->Receivables_installments_model->add_installments($receivable_id, [$single_installment]);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return false;
        }

        return $receivable_id;
    }

    /**
     * Update receivable
     * @param array $data All $_POST data
     * @param int $id receivable id to update
     * @return boolean
     */
    public function update($data, $id)
    {
        $this->db->trans_start();

        // Separar dados de parcelas se existirem
        $installments = null;
        if (isset($data['installments'])) {
            $installments = $data['installments'];
            unset($data['installments']);
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'receivables_ao', $data);

        // Atualizar parcelas se fornecidas
        if ($installments !== null) {
            $this->load->model('Receivables_installments_model');
            $this->Receivables_installments_model->add_installments($id, $installments);
        }

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Delete receivable
     * @param int $id receivable id to delete
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->trans_start();

        // Deletar parcelas primeiro
        $this->load->model('Receivables_installments_model');
        $this->Receivables_installments_model->delete_installments_by_receivable($id);

        // Deletar receita
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'receivables_ao');

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_receivables_by_day($params)
    {
        $warehouse_id = $params['warehouse_id'];
        $date = $params['date'];
        $page = $params['page'] ?? 1;
        $limit = $params['pageSize'] ?? 10;
        $offset = ($page - 1) * $limit;

        $this->db->select('
            r.*, 
            CASE 
                WHEN r.is_client = 1 THEN c.company 
                WHEN r.is_client = 0 THEN CONCAT(s.firstname, " ", s.lastname)
                ELSE c.company 
            END as client,
            CASE 
                WHEN r.is_client = 1 THEN c.company 
                WHEN r.is_client = 0 THEN CONCAT(s.firstname, " ", s.lastname)
                ELSE c.company 
            END as company,
            cat.name as category_name,
            pm.name as paymentmode,
            pm.name as payment_mode_name,
            pm.is_check,
            pm.is_boleto,
            w.warehouse_name
        ');
        $this->db->from($this->table() . ' as r');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = r.clientid AND r.is_client = 1', 'left');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = r.clientid AND r.is_client = 0', 'left');
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

        // Garantir que o campo 'client' sempre traga o nome correto
        foreach ($data as &$row) {
            if (empty($row['client']) && !empty($row['clientid'])) {
                if ($row['is_client'] == 1) {
                    // Buscar nome do cliente
                    $this->db->select('company');
                    $this->db->from(db_prefix() . 'clients');
                    $this->db->where('userid', $row['clientid']);
                    $client = $this->db->get()->row();
                    $row['client'] = $client ? $client->company : null;
                } else {
                    // Buscar nome da franquia
                    $this->db->select('CONCAT(firstname, " ", lastname) as name');
                    $this->db->from(db_prefix() . 'staff');
                    $this->db->where('staffid', $row['clientid']);
                    $staff = $this->db->get()->row();
                    $row['client'] = $staff ? $staff->name : null;
                }
            }
        }
        unset($row);

        return [
            'data' => $data,
            'total' => $total
        ];
    }

    /**
     * Calcula o valor total recebido considerando parcelas recebidas
     */
    private function sum_receivables_received_amount($status, $warehouse_id, $date_operator = null, $specific_date = null)
    {
        // Se não há parcelas, usar o método antigo
        if (!$this->has_installments_table()) {
            return $this->sum_receivables_amount($status, $warehouse_id, $date_operator, $specific_date);
        }

        // Verificar se há parcelas recebidas
        $this->db->select('SUM(ai.valor_pago) as total_received');
        $this->db->from(db_prefix() . 'account_installments ai');
        $this->db->join($this->table() . ' r', 'r.id = ai.receivables_id');
        $this->db->where('r.warehouse_id', $warehouse_id);
        $this->db->where('ai.status', 'Pago');

        if ($date_operator && !$specific_date) {
            $this->db->where('ai.data_pagamento ' . $date_operator, date('Y-m-d'));
        }

        if ($specific_date) {
            $this->db->where('ai.data_pagamento', $specific_date);
        }

        $result = $this->db->get()->row();
        $total_from_installments = (float) ($result->total_received ?? 0);

        // Se não há parcelas ou parcelas recebidas, usar o método antigo
        if ($total_from_installments == 0) {
            return $this->sum_receivables_amount($status, $warehouse_id, $date_operator, $specific_date);
        }

        return $total_from_installments;
    }

    /**
     * Verifica se a tabela de parcelas existe
     */
    private function has_installments_table()
    {
        return $this->db->table_exists(db_prefix() . 'account_installments');
    }
}