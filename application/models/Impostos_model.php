<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Impostos_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adiciona uma nova operação fiscal.
     * @param array $data Dados da operação em camelCase.
     * @return int|false O ID do registro inserido ou false em caso de falha.
     */
    public function add($data)
    {
        $this->db->insert(db_prefix() . 'impostos', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }
        
        return false;
    }

    public function get($id = '', $where = [])
    {
        $this->db->from(db_prefix() . 'impostos');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get()->row();
        }

        $this->db->order_by('createdAt', 'DESC');
        return $this->db->get()->result();
    }

    public function get_api($page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'DESC', $settingsFiscalId = 0)
    {
        $this->db->from(db_prefix() . 'impostos');
        
        $this->db->where('settingsFiscalId', $settingsFiscalId);

        // Filtro de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('id', $search);
            $this->db->or_like('settingsFiscalId', $search);
            $this->db->or_like('cst', $search);
            $this->db->or_like('csosn', $search);
            $this->db->or_like('enqIpi', $search);
            $this->db->group_end();
        }

        // Ordenação
        $this->db->order_by($sortField, $sortOrder);

        // Paginação
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);

        $results = $this->db->get()->result();

        // --- Contagem total (com os mesmos filtros) ---
        $this->db->from(db_prefix() . 'impostos');
        
        $this->db->where('settingsFiscalId', $settingsFiscalId);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('id', $search);
            $this->db->or_like('settingsFiscalId', $search);
            $this->db->or_like('cst', $search);
            $this->db->or_like('csosn', $search);
            $this->db->or_like('enqIpi', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results();

        return [
            'data' => $results,
            'total' => $total
        ];
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'impostos', $data);
        return ($this->db->affected_rows() > 0);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'impostos');
        return ($this->db->affected_rows() > 0);
    }
}