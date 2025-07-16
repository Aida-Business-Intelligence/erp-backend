<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Expenses_Categories_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = null, $warehouse_id = null, $type = null)
    {
        if ($id !== null) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'expenses_categories')->row_array();
        }
        if ($warehouse_id !== null) {
            $this->db->where('warehouse_id', $warehouse_id);
        }
        if ($type !== null) {
            $this->db->where('type', $type);
        }
        $this->db->order_by('name', 'asc');
        return $this->db->get(db_prefix() . 'expenses_categories')->result_array();
    }

    public function search($warehouse_id, $type, $search = '', $limit = 10)
    {
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('type', $type);
        if (!empty($search)) {
            $this->db->like('name', $search);
        }
        $this->db->order_by('name', 'asc');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'expenses_categories')->result_array();
    }

    public function add($data)
    {
        $this->db->insert(db_prefix() . 'expenses_categories', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'expenses_categories', $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        // Verifica se existe despesa/receita vinculada
        $this->db->where('category', $id);
        $count = $this->db->count_all_results(db_prefix() . 'expenses');
        if ($count > 0) {
            return ['referenced' => true];
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'expenses_categories');
        return $this->db->affected_rows() > 0;
    }

    public function get_categories($warehouse_id, $search = '', $limit = 20, $type = null)
    {
        $this->db->where('warehouse_id', $warehouse_id);
        if ($type !== null) {
            $this->db->where('type', $type);
        }
        if (!empty($search)) {
            $this->db->like('name', $search);
        }
        $this->db->order_by('name', 'asc');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'expenses_categories')->result_array();
    }

    public function get_category($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'expenses_categories')->row_array();
    }
} 