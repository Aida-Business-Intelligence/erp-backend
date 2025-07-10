<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Folders_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create_api($data)
    {
        if (empty($data['name'])) {
            return [
                'status' => false,
                'message' => 'Folder name is required'
            ];
        }

        $insert_data = [
            'name' => $data['name'],
            'size' => isset($data['size']) ? (int)$data['size'] : null,
            'files_count' => isset($data['files_count']) ? (int)$data['files_count'] : null,
            'is_favorite' => isset($data['is_favorite']) ? (bool)$data['is_favorite'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $insert_data = array_filter($insert_data, function($value) {
            return !is_null($value);
        });

        $this->db->insert(db_prefix() . 'folders', $insert_data);
        $folder_id = $this->db->insert_id();

        if (!$folder_id) {
            return [
                'status' => false,
                'message' => 'Falha ao registrar pasta no banco de dados'
            ];
        }

        $upload_dir = FCPATH . 'uploads/folders/' . $folder_id . '/';
        try {
            if (!file_exists($upload_dir)) {
                $parent_dir = dirname($upload_dir);
                if (!is_writable($parent_dir)) {
                    $this->db->where('id', $folder_id);
                    $this->db->delete(db_prefix() . 'folders');
                    return [
                        'status' => false,
                        'message' => 'Diretório pai não é gravável',
                        'debug' => [
                            'upload_dir' => $upload_dir,
                            'parent_dir' => $parent_dir,
                            'parent_writable' => is_writable($parent_dir),
                            'parent_permissions' => substr(sprintf('%o', fileperms($parent_dir)), -4),
                            'parent_owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($parent_dir))['name'] : fileowner($parent_dir),
                            'free_space' => round(disk_free_space($parent_dir) / (1024 * 1024)) . 'MB',
                            'fcpath' => FCPATH
                        ]
                    ];
                }

                if (!mkdir($upload_dir, 0777, true)) {
                    $this->db->where('id', $folder_id);
                    $this->db->delete(db_prefix() . 'folders');
                    return [
                        'status' => false,
                        'message' => 'Falha ao criar diretório físico',
                        'debug' => [
                            'upload_dir' => $upload_dir,
                            'dir_exists' => file_exists($upload_dir),
                            'parent_writable' => is_writable($parent_dir),
                            'parent_permissions' => substr(sprintf('%o', fileperms($parent_dir)), -4),
                            'parent_owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($parent_dir))['name'] : fileowner($parent_dir),
                            'free_space' => round(disk_free_space($parent_dir) / (1024 * 1024)) . 'MB',
                            'fcpath' => FCPATH
                        ]
                    ];
                }

                file_put_contents($upload_dir . 'index.html', '<!-- Directory listing disabled -->');
            }
        } catch (Exception $e) {
            $this->db->where('id', $folder_id);
            $this->db->delete(db_prefix() . 'folders');
            return [
                'status' => false,
                'message' => 'Erro ao criar diretório: ' . $e->getMessage(),
                'debug' => [
                    'upload_dir' => $upload_dir,
                    'dir_exists' => file_exists($upload_dir),
                    'parent_writable' => is_writable(dirname($upload_dir)),
                    'parent_permissions' => substr(sprintf('%o', fileperms(dirname($upload_dir))), -4),
                    'parent_owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner(dirname($upload_dir)))['name'] : fileowner($upload_dir),
                    'free_space' => round(disk_free_space(dirname($upload_dir)) / (1024 * 1024)) . 'MB',
                    'fcpath' => FCPATH,
                    'error_code' => $e->getCode()
                ]
            ];
        }

        $relative_path = 'uploads/folders/' . $folder_id . '/';
        $base_url = rtrim(base_url(), '/');
        $folder_url = $base_url . '/' . ltrim($relative_path, '/');

        return [
            'status' => true,
            'id' => $folder_id,
            'folder_url' => $folder_url,
            'message' => 'Pasta criada com sucesso'
        ];
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

            if ($folder) {
                $folder = (array) $folder;
                $folder['is_favorite'] = $folder['is_favorite'] == '1' ? true : false;
                return [
                    'data' => [$folder],
                    'total' => 1
                ];
            }

            return [
                'data' => [],
                'total' => 0
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

        foreach ($folders as &$folder) {
            $folder['is_favorite'] = $folder['is_favorite'] == '1' ? true : false;
        }

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

        if ($folder) {
            $folder = (array) $folder;
            $folder['is_favorite'] = $folder['is_favorite'] == '1' ? true : false;
            return $folder;
        }

        return null;
    }

    public function delete_api($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            return [
                'status' => false,
                'message' => 'ID da pasta inválido'
            ];
        }

        $this->db->where('id', $id);
        $folder = $this->db->get(db_prefix() . 'folders')->row();
        if (!$folder) {
            return [
                'status' => false,
                'message' => 'Pasta não encontrada'
            ];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'folders');
        if ($this->db->affected_rows() === 0) {
            return [
                'status' => false,
                'message' => 'Falha ao deletar pasta do banco de dados'
            ];
        }

        $upload_dir = FCPATH . 'uploads/folders/' . $id . '/';
        try {
            if (file_exists($upload_dir)) {
                $delete_dir = function ($dir) use (&$delete_dir) {
                    $files = array_diff(scandir($dir), ['.', '..']);
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        is_dir($path) ? $delete_dir($path) : unlink($path);
                    }
                    return rmdir($dir);
                };

                if (!is_writable($upload_dir)) {
                    return [
                        'status' => false,
                        'message' => 'Diretório não é gravável',
                        'debug' => [
                            'upload_dir' => $upload_dir,
                            'dir_exists' => file_exists($upload_dir),
                            'dir_writable' => is_writable($upload_dir),
                            'parent_writable' => is_writable(dirname($upload_dir)),
                            'permissions' => substr(sprintf('%o', fileperms($upload_dir)), -4),
                            'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($upload_dir))['name'] : fileowner($upload_dir)
                        ]
                    ];
                }

                if (!$delete_dir($upload_dir)) {
                    return [
                        'status' => false,
                        'message' => 'Falha ao deletar diretório físico',
                        'debug' => [
                            'upload_dir' => $upload_dir,
                            'dir_exists' => file_exists($upload_dir),
                            'dir_writable' => is_writable($upload_dir),
                            'parent_writable' => is_writable(dirname($upload_dir)),
                            'permissions' => substr(sprintf('%o', fileperms($upload_dir)), -4),
                            'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($upload_dir))['name'] : fileowner($upload_dir)
                        ]
                    ];
                }
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Erro ao deletar diretório: ' . $e->getMessage(),
                'debug' => [
                    'upload_dir' => $upload_dir,
                    'dir_exists' => file_exists($upload_dir),
                    'dir_writable' => is_writable($upload_dir),
                    'parent_writable' => is_writable(dirname($upload_dir)),
                    'permissions' => file_exists($upload_dir) ? substr(sprintf('%o', fileperms($upload_dir)), -4) : null,
                    'owner' => file_exists($upload_dir) && function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($upload_dir))['name'] : null,
                    'error_code' => $e->getCode()
                ]
            ];
        }

        return [
            'status' => true,
            'message' => 'Pasta deletada com sucesso'
        ];
    }

    public function update_name_api($id, $name)
    {
        if (!is_numeric($id) || $id <= 0) {
            return [
                'status' => false,
                'message' => 'ID da pasta inválido'
            ];
        }

        if (empty($name)) {
            return [
                'status' => false,
                'message' => 'Nome da pasta é obrigatório'
            ];
        }

        $this->db->where('id', $id);
        $folder = $this->db->get(db_prefix() . 'folders')->row();
        if (!$folder) {
            return [
                'status' => false,
                'message' => 'Pasta não encontrada'
            ];
        }

        $update_data = [
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'folders', $update_data);

        if ($this->db->affected_rows() >= 0) {
            return [
                'status' => true,
                'id' => $id,
                'message' => 'Nome da pasta atualizado com sucesso'
            ];
        }

        return [
            'status' => false,
            'message' => 'Falha ao atualizar nome da pasta'
        ];
    }

    public function update_favorite_api($id, $is_favorite)
    {
        if (!is_numeric($id) || $id <= 0) {
            return [
                'status' => false,
                'message' => 'ID da pasta inválido'
            ];
        }

        if (!isset($is_favorite) || !is_bool((bool)$is_favorite)) {
            return [
                'status' => false,
                'message' => 'O campo is_favorite deve ser um valor booleano'
            ];
        }

        $this->db->where('id', $id);
        $folder = $this->db->get(db_prefix() . 'folders')->row();
        if (!$folder) {
            return [
                'status' => false,
                'message' => 'Pasta não encontrada'
            ];
        }

        $update_data = [
            'is_favorite' => (bool)$is_favorite,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'folders', $update_data);

        if ($this->db->affected_rows() >= 0) {
            return [
                'status' => true,
                'id' => $id,
                'message' => 'Status de favorito atualizado com sucesso'
            ];
        }

        return [
            'status' => false,
            'message' => 'Falha ao atualizar status de favorito'
        ];
    }


}
