<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Cashs_model extends App_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'clients')) . ',' . get_sql_select_client_company());

        $this->db->join(db_prefix() . 'countries', '' . db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {
            $this->db->where('staffid', $id);
            $client = $this->db->get(db_prefix() . 'clients')->row();

            if ($client && get_option('company_requires_vat_number_field') == 0) {
                $client->vat = null;
            }

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('open_cash', 'asc');

        return $this->db->get(db_prefix() . 'cashs')->result_array();
    }

    public function get_by_number($id)
    {

        $this->db->from(db_prefix() . 'cashs');
        $this->db->where('cashs.number', $id);
        $client = $this->db->get()->row();

        return $client;
    }

    public function get_by_id($id)
    {

        $this->db->from(db_prefix() . 'cashs');
        $this->db->where('cashs.id', $id);
        $client = $this->db->get()->row();

        return $client;
    }

    public function get_by_id2($id)
    {
        $this->db->from(db_prefix() . 'cashs');
        $this->db->where('id', $id);
        return $this->db->get()->row();
    }

    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC', $warehouse_id = 0)
    {
        if (!is_numeric($id)) {
            $this->db->select('cashs.*, staff.firstname, staff.lastname');
            $this->db->from(db_prefix() . 'cashs');
            $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left');
            $this->db->where('cashs.warehouse_id', $warehouse_id);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('cashs.status', $search);
                $this->db->or_like('cashs.id', $search);
                $this->db->or_like('cashs.open_cash', $search);
                $this->db->or_like('cashs.balance', $search);
                $this->db->or_like('cashs.number', $search);
                $this->db->or_like('cashs.user_id', $search);
                $this->db->group_end();
            }

            // Ordenar por 'id' em ordem decrescente
            $this->db->order_by('id', 'DESC');
            $this->db->limit($limit, ($page - 1) * $limit);

            $clients = $this->db->get()->result_array();

            $this->db->reset_query();

            $this->db->from(db_prefix() . 'cashs');
            $this->db->where('cashs.warehouse_id', $warehouse_id); // Filtro por franqueado_id na contagem
            $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left');

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('cashs.status', $search);
                $this->db->or_like('cashs.id', $search);
                $this->db->or_like('cashs.open_cash', $search);
                $this->db->or_like('cashs.balance', $search);
                $this->db->or_like('cashs.number', $search);
                $this->db->or_like('cashs.user_id', $search);
                $this->db->group_end();
            }

            $total = $this->db->count_all_results();

            return ['data' => $clients, 'total' => $total];
        } else {
            $this->db->select('cashs.*, staff.firstname, staff.lastname');
            $this->db->from(db_prefix() . 'cashs');
            $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left');
            $this->db->where('cashs.id', $id);
            $this->db->where('cashs.warehouse_id', $warehouse_id);

            $client = $this->db->get()->row();
            $total = $client ? 1 : 0;

            return ['data' => (array) $client, 'total' => $total];
        }
    }
    public function get_inactive($warehouse_id)
    {
        // JOIN com a tabela staff
        //  $this->db->select('cashs.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'cashs');
        //   $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left'); // LEFT JOIN para vincular as tabelas
        // Filtra somente as caixas ativas (status=1)
        $this->db->where('cashs.status', '0');
        $this->db->where('cashs.active', '0');
        $this->db->where('cashs.warehouse_id', $warehouse_id);

        // Ordena os resultados
        $this->db->order_by('cashs.number');

        // Executa a consulta e obtém os resultados
        $clients = $this->db->get()->result_array();

        // Retorne os dados com o total de resultados
        return [
            'status' => true, // Indica que a operação foi bem-sucedida
            'total' => count($clients), // Conta o total de resultados
            'data' => $clients
        ];
    }

    public function get_transactions($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC', $filters = null, $cash_id, $warehouse_id = '')
    {
        $this->db->from(db_prefix() . 'cashextracts as c');
        $this->db->select('c.id as extract_id, c.*, clients.company, clients.vat, tblcashs.number'); // Especifica o alias para o id
        $this->db->join(db_prefix() . 'clients', 'c.client_id = clients.userid', 'left');
        $this->db->join(db_prefix() . 'cashs as tblcashs', 'c.cash_id = tblcashs.id', 'left');
        $this->db->join(db_prefix() . 'itemcash as items', 'c.cash_id = items.cash_id', 'left');

        // Filtro pelo warehouse_id
        if ($warehouse_id) {
            $this->db->where('c.warehouse_id', $warehouse_id);
        }

        // Filtro pelo cash_id (ID do caixa)
        if ($cash_id) {
            $this->db->where('c.cash_id', $cash_id); // Filtra diretamente pelo ID do caixa
        }

        // Filtros adicionais
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $this->db->where('DATE(c.datesale) >=', date('Y-m-d', strtotime($filters['start_date'])));
        }

        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(c.datesale) <=', date('Y-m-d', strtotime($filters['end_date'])));
        }

        if (isset($filters['status']) && is_array($filters['status']) && !empty($filters['status'])) {
            $this->db->where_in('c.status', $filters['status']);
        }

        if (isset($filters['payment_type']) && !empty($filters['payment_type'])) {
            $this->db->like('c.form_payments', $filters['payment_type']);
        }

        // Filtro por ID específico (se necessário)
        if (!empty($id)) {
            $this->db->where('c.id', $id);
            $client = $this->db->get()->row();
            return ['data' => $client ? (array) $client : [], 'total' => $client ? 1 : 0];
        }

        // Filtro de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('c.type', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('c.doc', $search);
            $this->db->or_like('c.total', $search);
            $this->db->or_like('c.nota', $search);
            $this->db->or_like('c.operacao', $search);
            $this->db->or_like('items.description', $search);
            $this->db->or_like('items.long_description', $search);
            $this->db->group_end();
        }

        // Agrupamento e ordenação
        $this->db->group_by('c.id');
        $this->db->order_by($sortField, $sortOrder);

        // Paginação
        $total_query = clone $this->db;
        $total = $total_query->count_all_results();

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $this->db->limit($limit, $offset);
        }

        // Obtém as transações
        $clients = $this->db->get()->result_array();


        return [
            'data' => $clients,
            'total' => $total
        ];
    }

    public function get_transactions2($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC', $filters = null, $cash_id)
    {
        $this->db->from(db_prefix() . 'cashextracts as c');
        $this->db->select('c.*, clients.company, clients.vat, tblcashs.number');
        $this->db->join(db_prefix() . 'clients', 'c.client_id = clients.userid', 'left');
        $this->db->join(db_prefix() . 'cashs as tblcashs', 'c.cash_id = tblcashs.id', 'left');
        $this->db->join(db_prefix() . 'itemcash as items', 'c.cash_id = items.cash_id', 'left');

        // Filtro pelo cash_id
        if ($cash_id) {
            $this->db->where('c.cash_id', $cash_id);
        }

        // Filtros adicionais (data, status, etc.)
        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $this->db->where('DATE(c.datesale) >=', date('Y-m-d', strtotime($filters['start_date'])));
        }

        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(c.datesale) <=', date('Y-m-d', strtotime($filters['end_date'])));
        }

        if (isset($filters['status']) && is_array($filters['status']) && !empty($filters['status'])) {
            $this->db->where_in('c.status', $filters['status']);
        }

        if (isset($filters['payment_type']) && !empty($filters['payment_type'])) {
            $this->db->like('c.form_payments', $filters['payment_type']);
        }

        // Filtro por ID (se fornecido)
        if (!empty($id)) {
            $this->db->where('c.id', $id);
            $client = $this->db->get()->row();
            return ['data' => $client ? (array) $client : [], 'total' => $client ? 1 : 0];
        }

        // Filtro de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('c.type', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('c.doc', $search);
            $this->db->or_like('c.total', $search);
            $this->db->or_like('c.nota', $search);
            $this->db->or_like('c.operacao', $search);
            $this->db->or_like('items.description', $search);
            $this->db->or_like('items.long_description', $search);
            $this->db->group_end();
        }

        $this->db->group_by('c.id');

        // Contagem total de registros
        $total_query = clone $this->db;
        $total = $total_query->count_all_results();

        // Ordenação
        $this->db->order_by($sortField, $sortOrder);

        // Paginação
        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $this->db->limit($limit, $offset);
        }

        // Executa a consulta
        $clients = $this->db->get()->result_array();

        return [
            'data' => $clients,
            'total' => $total
        ];
    }

    // Nova função para buscar itens pelo item_order
    public function get_items_cashs_by_order($sale_id)
    {
        $this->db->from(db_prefix() . 'itemcash');
        $this->db->where('item_order', $sale_id); // Filtra pelo item_order correspondente ao ID da venda
        return $this->db->get()->result_array();
    }

    public function get_extracts($cash_id, $id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC')
    {
        if (!is_numeric($id)) {
            // Construir consulta base para obtenção de registros
            $this->db->from(db_prefix() . 'cashextracts as cashs');
            $this->db->select('cashs.*, clients.company');
            $this->db->join(db_prefix() . 'clients', 'cashs.client_id = clients.userid', 'left');

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('cashs.type', $search);
                $this->db->or_like('cashs.total', $search);
                $this->db->group_end();
            }

            // Aplicar ordenação e paginação
            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $this->db->where('cashs.cash_id', $cash_id);

            // Obter registros paginados
            $clients = $this->db->get()->result_array();

            // Obter itens relacionados para cada registro
            foreach ($clients as $key => $client) {
                $items = $this->get_items_cashs($client['cash_id']);
                $clients[$key]['items'] = $items;
            }

            // Resetar a consulta para contar total de registros
            $this->db->reset_query();

            // Construir consulta para contagem total de registros
            $this->db->from(db_prefix() . 'cashextracts as cashs');
            $this->db->join(db_prefix() . 'clients', 'cashs.client_id = clients.userid', 'left');
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('cashs.type', $search);
                $this->db->or_like('cashs.total', $search);
                $this->db->group_end();
            }
            $this->db->where('cashs.cash_id', $cash_id);

            $total = $this->db->count_all_results();

            return ['data' => $clients, 'total' => $total];
        } else {
            // Obter registro específico por ID
            $this->db->from(db_prefix() . 'cashextracts as cashs');
            $this->db->select('cashs.*, clients.company');
            $this->db->join(db_prefix() . 'clients', 'cashs.client_id = clients.userid', 'left');
            $this->db->where('cashs.id', $id);

            $client = $this->db->get()->row();
            $total = $client ? 1 : 0;

            return ['data' => (array) $client, 'total' => $total];
        }
    }

    public function count_extracts($cash_id, $search = '')
    {
        $this->db->from(db_prefix() . 'cashextracts');

        // Adicionar condições de busca, se necessário
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('type', $search);
            $this->db->or_like('total', $search);
            $this->db->group_end();
        }

        // Filtrar pelo 'cash_id'
        $this->db->where('cash_id', $cash_id);

        // Retornar a contagem total de registros
        return $this->db->count_all_results();
    }

    public function add_extract($data)
    {
        $this->db->insert(db_prefix() . 'cashextracts', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('Extract insert user:, ', $data['user_id']);

            return $insert_id;
        }

        return false;
    }

    public function get_items_cashs($id)
    {
        //        $this->db->select('
        //            sum(qty)as qty,sum(qty_provided) as qty_provided
        //        ');
        $this->db->where('cash_id', $id);

        return $this->db->get(db_prefix() . 'itemcash')->result_array();
    }

    public function get_items_cashs2($cash_id, $item_order = null)
    {
        $this->db->from(db_prefix() . 'itemcash');
        $this->db->where('cash_id', $cash_id);

        if ($item_order !== null) {
            $this->db->where('item_order', $item_order);
        }

        return $this->db->get()->result_array();
    }

    public function delete($id)
    {
        // Verifica se o ID é válido e se é numérico
        // Verifica se o ID é válido e se é numérico
        if (is_numeric($id)) {
            // Sanitize o ID para evitar ataques de injeção
            $id = $this->security->xss_clean($id);

            // Deleta o caixa com o ID fornecido
            $this->db->where('id', $id);
            $this->db->update('cashs', array('active', 1));

            // Verifique se a exclusão foi bem-sucedida
            if ($this->db->affected_rows() > 0) {
                return true; // Sucesso
            }
        }

        // Se falhou, retorne false
        return false;
    }


    public function delete_finaly($id)
    {



        $this->db->where('id', $id);
        return $this->db->delete('cashs');
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        return $this->db->update('cashs', $data);
    }

    public function update_by_number($data, $number)
    {

        $this->db->where('number', $number);
        return $this->db->update('cashs', $data);
    }

    public function update_by_id($data, $id)
    {

        $this->db->where('id', $id);
        return $this->db->update('cashs', $data);
    }

    public function update_extracts($data, $id)
    {
        $this->db->where('id', $id);
        return $this->db->update('cashextracts', $data);
    }

    public function update_itemstocks($qtde, $item_id, $warehouse_id)
    {
        // Retrieve the current quantity
        $this->db->select('stock, id');
        $this->db->from(db_prefix() . 'items');
        $this->db->where('id', $item_id);
        $this->db->where('warehouse_id', $warehouse_id);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $row = $query->row();
            $currentQuantity = $row->qtde;
            $updatedQuantity = $currentQuantity - $qtde;

            // Update the quantity in the database
            $this->db->where('id', $item_id);
            $this->db->where('warehouse_id', $warehouse_id);
            $this->db->set('stock', $updatedQuantity);
            $this->db->update(db_prefix() . 'items');

            // Return the ID of the updated record
            return $row->id;
        } else {
            // Handle case where no record is found
            return false; // or handle as necessary
        }
    }

    public function update_itemstocksmov($data, $id)
    {

        $this->db->where('id', $id);
        return $this->db->update('itemstocksmov', $data);
    }

    public function add1($data)
    {



        $data['hash'] = app_generate_hash();
        $detalhes_caixa = $this->get_by_number($data['cash_id']);
        $data['cash_id'] = $detalhes_caixa->id;

        $items = [];

        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $this->db->insert(db_prefix() . 'cashextracts', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {

            foreach (json_decode($data['form_payments']) as $payment) {

                $data_payment = array(
                    'cashid' => $detalhes_caixa->id,
                    'amount' => $payment->value,
                    'parcelas' => $payment->parcelas,
                    'paymentmethod' => $payment->type,
                    'note' => 'pagamento',
                    'transactionid' => app_generate_hash()
                );

                if (strtolower($payment->type) == "dinheiro") {

                    $update_data['balance_dinheiro'] += $detalhes_caixa->balance_dinheiro + $payment->value;
                }
                $update_data['balance'] += $detalhes_caixa->balance + $payment->value;
                $this->update($update_data, $detalhes_caixa->id);

                $this->db->insert(db_prefix() . 'cashpaymentrecords', $data_payment);
            }


            foreach ($items as $key => $item) {

                $this->db->insert(db_prefix() . 'itemcash', [
                    'description' => $item['description'],
                    'long_description' => nl2br($item['description']),
                    'qty' => $item['qty'],
                    'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                    'item_id' => $item['id'],
                    'cash_id' => $detalhes_caixa->id,
                    'item_order' => $item['item_order'],
                    'unit' => $item['unit'],
                ]);

                $warehouse_id = 1;

                $id_itemstocks = $this->update_itemstocks($item['qty'], $item['id'], $data['warehouse_id']);

                $data_itemstocksmov = array(
                    'warehouse_id' => $data['warehouse_id'],
                    'transaction_id' => $id_itemstocks,
                    'cash_id' => $data['cash_id'],
                    'qtde' => $item['qty'],
                    'transaction_id' => $detalhes_caixa->id,
                    'hash' => $data['hash'],
                    'user_id' => $data['user_id'],
                    'obs' => 'pagamento',
                    'type_transaction' => 'cash'
                );
                $this->db->insert(db_prefix() . 'itemstocksmov', $data_itemstocksmov);
            }


            return $insert_id;
        }

        return false;
    }


    public function add($data)
    {


        // Iniciar transação
        $this->db->trans_start();

        // Gera hash e obtém detalhes da caixa
        $data['hash'] = app_generate_hash();
        $detalhes_caixa = $this->get_by_id($data['cash_id']);
        $data['cash_id'] = $detalhes_caixa->id;

        $items = isset($data['newitems']) ? $data['newitems'] : [];
        $data['items'] = json_encode($items);
        unset($data['newitems']);

        // Insere a nova entrada de caixa
        $this->db->insert(db_prefix() . 'cashextracts', $data);
        $insert_id = $this->db->insert_id();




        if ($insert_id) {
            $update_data = [
                'balance' => $detalhes_caixa->balance,
                'balance_dinheiro' => $detalhes_caixa->balance_dinheiro
            ];

            foreach (json_decode($data['form_payments']) as $payment) {
                $data_payment = [
                    'cashid' => $detalhes_caixa->id,
                    'amount' => $payment->value,
                    'parcelas' => $payment->parcelas,
                    'paymentmethod' => $payment->type,
                    'note' => 'pagamento',
                    'transactionid' => app_generate_hash()
                ];

                // Atualiza os saldos com base no método de pagamento
                if (strtolower($payment->type) == "dinheiro") {
                    $update_data['balance_dinheiro'] += $payment->value;
                }
                $update_data['balance'] += $payment->value;

                // Atualiza os dados da caixa
                $this->update($update_data, $detalhes_caixa->id);
                // Insere registro de pagamento
                $this->db->insert(db_prefix() . 'cashpaymentrecords', $data_payment);
            }

            foreach ($items as $item) {
                $this->db->insert(db_prefix() . 'itemcash', [
                    'description' => $item['description'],
                    'long_description' => nl2br($item['description']),
                    'qty' => $item['qty'],
                    'rate' => number_format($item['rate'], get_decimal_places(), '.', ''),
                    'item_id' => $item['id'],
                    'cash_id' => $detalhes_caixa->id,
                    'item_order' => $item['item_order'],
                    'unit' => $item['unit']
                ]);


                $id_itemstocks = $this->update_itemstocks($item['qty'], $item['id'], $data['warehouse_id']);

                $data_itemstocksmov = [

                    'warehouse_id' => $data['warehouse_id'],
                    'transaction_id' => $id_itemstocks,
                    'cash_id' => $data['cash_id'],
                    'qtde' => $item['qty'],
                    'transaction_id' => $detalhes_caixa->id,
                    'hash' => $data['hash'],
                    'user_id' => $data['user_id'],
                    'obs' => 'pagamento',
                    'type_transaction' => 'cash'
                ];
                $this->db->insert(db_prefix() . 'itemstocksmov', $data_itemstocksmov);
            }

            // Finaliza a transação
            $this->db->trans_complete();

            // Verifica se a transação foi bem-sucedida
            if ($this->db->trans_status() === TRUE) {
                return $insert_id;
            } else {
                // Transação falhou, rollback é automático
                return false;
            }
        }

        return false;
    }

    public function get_cash_extracts()
    {
        $this->db->select('DATE(datesale) as sale_date, SUM(total) as total_sum');
        $this->db->from('tblcashextracts');
        $this->db->where('datesale >=', 'DATE_SUB(CURDATE(), INTERVAL 1 WEEK)', false);
        $this->db->where('type', 'credit');
        $this->db->group_by('sale_date');  // Agrupa por data sem horário
        $query = $this->db->get();
        return $query->result_array();
    }
}

