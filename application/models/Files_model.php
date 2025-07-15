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

}
