<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Origins_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function table() {
        return db_prefix() . 'origins';
    }

    public function list($warehouse_id, $search = '', $page = 1, $pageSize = 5) {
        $offset = ($page - 1) * $pageSize;
        $this->db->select('id, name, description');
        $this->db->from($this->table());
        $this->db->where('warehouse_id', $warehouse_id);
        if ($search) {
            $this->db->like('name', $search);
        }
        $total = $this->db->count_all_results('', false);
        $this->db->order_by('name', 'ASC');
        $this->db->limit($pageSize, $offset);
        $origins = $this->db->get()->result_array();
        return [
            'data' => $origins,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }

    public function create($name, $warehouse_id, $description = null) {
        $data = [
            'name' => $name,
            'warehouse_id' => $warehouse_id,
            'description' => $description
        ];
        $this->db->insert($this->table(), $data);
        return $this->db->insert_id();
    }

    public function delete($id) {
        $this->db->where('id', $id);
        $this->db->delete($this->table());
        return $this->db->affected_rows() > 0;
    }
} 