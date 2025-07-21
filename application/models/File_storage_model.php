<?php

defined('BASEPATH') or exit('No direct script access allowed');

class File_storage_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }


    private function get_mime_type_storage_categories() {
        return [
            'Images' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
            ],
            'Media' => [
                'video/mp4',
                'audio/mpeg',
            ],
            'Documents' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ],
        ];
    }


    private function map_mime_type_to_storage_category($mime_type) {
        $mime_type = strtolower($mime_type);
        $categories_map = $this->get_mime_type_storage_categories();

        foreach ($categories_map as $category_name => $mime_types_allowed) {
            if (in_array($mime_type, $mime_types_allowed)) {
                return $category_name;
            }
        }
        return 'Other';
    }


    public function get_storage_overview_data() {
        $table_name = db_prefix() . 'files_manager';

        $this->db->select("type, SUM(size) as used_storage, COUNT(id) as files_count");
        $this->db->group_by("type");
        $query = $this->db->get($table_name);
        $results = $query->result_array();

        $storage_data_by_category = [];
        $predefined_categories = array_keys($this->get_mime_type_storage_categories());
        $predefined_categories[] = 'Other';

        foreach ($predefined_categories as $cat_name) {
            $storage_data_by_category[$cat_name] = [
                'name' => $cat_name,
                'usedStorage' => 0,
                'filesCount' => 0,
            ];
        }

        $total_used_storage_bytes = 0;
        $total_files_count = 0;

        foreach ($results as $row) {
            $category = $this->map_mime_type_to_storage_category($row['type']);

            $used_storage_bytes = (int)$row['used_storage'];
            $files_count = (int)$row['files_count'];

            $storage_data_by_category[$category]['usedStorage'] += $used_storage_bytes;
            $storage_data_by_category[$category]['filesCount'] += $files_count;

            $total_used_storage_bytes += $used_storage_bytes;
            $total_files_count += $files_count;
        }

        $formatted_data = array_values($storage_data_by_category);

        return [
            'total_used_storage_bytes' => $total_used_storage_bytes,
            'total_files_count' => $total_files_count,
            'data' => $formatted_data,
        ];
    }
}
