<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Files_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($name, $type, $size, $folder_id, $file_path = null)
    {
        $data = [
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'folder_id' => $folder_id
        ];

        if ($file_path) {
            $data['file_path'] = $file_path;
        }

        $this->db->insert(db_prefix() . 'files_manager', $data);
        return $this->db->insert_id();
    }

    public function get_all()
    {
        return $this->db->get(db_prefix() . 'files_manager')->result_array();
    }

    public function get_file_by_id($id)
    {
        return $this->db->get_where(db_prefix() . 'files_manager', ['id' => $id])->row_array();
    }

    public function get_files_by_folder($folder_id)
    {
        return $this->db->get_where(db_prefix() . 'files_manager', ['folder_id' => $folder_id])->result_array();
    }

    public function update_file_name($id, $new_name)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'files_manager', ['name' => $new_name]);
        return $this->db->affected_rows() > 0;
    }

    public function folder_exists($folder_id)
    {
        $this->db->where('id', $folder_id);
        $query = $this->db->get(db_prefix() . 'folders');
        return $query->num_rows() > 0;
    }

    public function delete_file($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'files_manager');

        return $this->db->affected_rows() > 0;
    }
}
