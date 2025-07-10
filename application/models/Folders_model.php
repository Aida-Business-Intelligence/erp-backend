<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Folders_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_api($page = 1, $limit = 10, $search = '', $sort_field = 'id', $sort_order = 'ASC', $id = null)
    {
        $allowedSortFields = [
            'id',
            'name',
            'size',
            'files_count',
            'is_favorite',
            'created_at',
            'updated_at'
        ];

        $sort_field = in_array($sort_field, $allowedSortFields) ? $sort_field : 'id';
        $sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';

        if ($id !== null) {
            $this->db->select('*');
            $this->db->from(db_prefix() . 'folders');
            $this->db->where('id', $id);
            $folder = $this->db->get()->row();

            return [
                'data' => $folder ? [(array) $folder] : [],
                'total' => $folder ? 1 : 0
            ];
        }

        $this->db->select('*');
        $this->db->from(db_prefix() . 'folders');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sort_field, $sort_order);

        $this->db->limit($limit, ($page - 1) * $limit);

        $folders = $this->db->get()->result_array();

        $this->db->select('COUNT(*) as total');
        $this->db->from(db_prefix() . 'folders');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->group_end();
        }

        $total = $this->db->get()->row()->total;

        return [
            'data' => !empty($folders) ? $folders : [],
            'total' => (int) $total
        ];
    }

    public function get_by_id($id)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'folders');
        $this->db->where('id', $id);
        $folder = $this->db->get()->row();

        return $folder ? (array) $folder : null;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $result = $this->db->delete(db_prefix() . 'folders');
        return $result;
    }

}
