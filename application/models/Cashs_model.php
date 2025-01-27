<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Cashs_model extends App_Model {

    public function __construct() {
        parent::__construct();

    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */
    public function get($id = '', $where = []) {
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
    
     public function get_by_number($id) {

        $this->db->from(db_prefix() . 'cashs');
        $this->db->where('cashs.number', $id);
        $client = $this->db->get()->row();

        return $client;
    
}

    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC') {

    if (!is_numeric($id)) {
        // JOIN com a tabela staff
        $this->db->select('cashs.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'cashs');
        $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left'); // LEFT JOIN para vincular as tabelas

        // Adicionar condições de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('cashs.status', $search);
            $this->db->or_like('cashs.id', $search);
            $this->db->or_like('cashs.open_value', $search);
            $this->db->or_like('cashs.balance', $search);
            $this->db->or_like('cashs.user_id', $search);
            $this->db->or_like('cashs.open_cash', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);

        $this->db->limit($limit, ($page - 1) * $limit);

        // Obtenha os registros com as informações do staff
        $clients = $this->db->get()->result_array();

        // Contar o total de registros (considerando a busca)
        $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
        $this->db->from(db_prefix() . 'cashs');
        $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('cashs.status', $search);
            $this->db->or_like('cashs.id', $search);
            $this->db->or_like('cashs.open_value', $search);
            $this->db->or_like('cashs.balance', $search);
            $this->db->or_like('cashs.user_id', $search);
            $this->db->or_like('cashs.open_cash', $search);
            $this->db->group_end();
        }

        $this->db->select('COUNT(*) as total');
        $total = $this->db->get()->row()->total;

        return ['data' => $clients, 'total' => $total]; // Retorne os dados e o total
    } else {
        $this->db->select('cashs.*, staff.firstname, staff.lastname');
        $this->db->from(db_prefix() . 'cashs');
        $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left');
        $this->db->where('cashs.id', $id);

        $client = $this->db->get()->row();
        $total = $client ? 1 : 0;

        return ['data' => (array) $client, 'total' => $total];
    }
}

public function get_inactive() {
    // JOIN com a tabela staff
  //  $this->db->select('cashs.*, staff.firstname, staff.lastname');
    $this->db->from(db_prefix() . 'cashs');
 //   $this->db->join(db_prefix() . 'staff', 'cashs.user_id = staff.staffid', 'left'); // LEFT JOIN para vincular as tabelas

    // Filtra somente as caixas ativas (status=1)
    $this->db->where('cashs.status', '0');

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

public function get_extracts($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC') {

    if (!is_numeric($id)) {
        // JOIN com a tabela staff
        $this->db->from(db_prefix() . 'cashextracts as cashs');
        $this->db->select('cashs.*, clients.company');
//        $this->db->join(db_prefix() . 'staff', 'cashs.user_id = clients.userid', 'left');
        $this->db->join(db_prefix() . 'clients', 'cashs.client_id = clients.userid', 'left');

        // Adicionar condições de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('cashs.type', $search);
            $this->db->or_like('cashs.total', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);

        $this->db->limit($limit, ($page - 1) * $limit);

        // Obtenha os registros com as informações do staff
        $clients = $this->db->get()->result_array();
        
        foreach ($clients as $key => $client){
            $items= $this->get_items_cashs($client['cash_id']);
            $clients[$key]['items'] = $items;
        }

        // Contar o total de registros (considerando a busca)
        $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
        $this->db->from(db_prefix() . 'cashextracts as cashs');
        $this->db->select('cashs.*, clients.company');
//        $this->db->join(db_prefix() . 'staff', 'cashs.user_id = clients.userid', 'left');
        $this->db->join(db_prefix() . 'clients', 'cashs.client_id = clients.userid', 'left');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('cashs.type', $search);
            $this->db->or_like('cashs.total', $search);
            $this->db->group_end();
        }

        $this->db->select('COUNT(*) as total');
        $total = $this->db->get()->row()->total;

        return ['data' => $clients, 'total' => $total]; // Retorne os dados e o total
    } else {
        $this->db->from(db_prefix() . 'cashextracts as cashs');
        $this->db->select('cashs.*, clients.company');
//      $this->db->join(db_prefix() . 'staff', 'cashs.user_id = clients.userid', 'left');
        $this->db->join(db_prefix() . 'clients', 'cashs.client_id = clients.userid', 'left');
        $this->db->where('cashs.id', $id);

        $client = $this->db->get()->row();
        $total = $client ? 1 : 0;

        return ['data' => (array) $client, 'total' => $total];
    }
}
            
            public function add_extract($data)
    {
        $this->db->insert(db_prefix().'cashextracts', $data);

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
    
            


    public function delete($id) {
        // Verifica se o ID é válido e se é numérico
        if (is_numeric($id)) {
            // Sanitize o ID para evitar ataques de injeção
            $id = $this->security->xss_clean($id);

            // Deleta o caixa com o ID fornecido
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'cashs');

            // Verifique se a exclusão foi bem-sucedida
            if ($this->db->affected_rows() > 0) {
                return true; // Sucesso
            }
        }

        // Se falhou, retorne false
        return false;
    }

    public function update($data, $id){
        $this->db->where('id', $id);
        return $this->db->update('cashs', $data);
    }
    
    public function update_by_number($data, $number){
        $this->db->where('number', $number);
        return $this->db->update('cashs', $data);
    }
    
    public function update_extracts($data, $id){
        $this->db->where('id', $id);
        return $this->db->update('cashextracts', $data);
    }

}