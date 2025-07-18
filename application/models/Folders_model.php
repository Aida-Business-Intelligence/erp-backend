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

    public function get_folders($order_by = 'created_at', $order_direction = 'desc', $id = null, $search = null, $limit = null, $offset = null)
    {
        $allowed_orders = ['created_at', 'updated_at', 'name', 'size', 'files_count'];
        if (!in_array($order_by, $allowed_orders)) {
            $order_by = 'created_at';
        }

        $order_direction = (strtolower($order_direction) === 'asc') ? 'asc' : 'desc';

        if ($id !== null) {
            $this->db->where('id', $id);
        }

        if ($search !== null) {
            $this->db->like('name', $search);
        }

        $this->db->order_by($order_by, $order_direction);

        if ($limit !== null && is_numeric($limit) && $limit > 0) {
            $this->db->limit($limit, $offset);
        }

        $query = $this->db->get(db_prefix() . 'folders');
        return $query->result_array();
    }

    public function count_folders($id = null, $search = null)
    {
        if ($id !== null) {
            $this->db->where('id', $id);
        }

        if ($search !== null) {
            $this->db->like('name', $search);
        }

        return $this->db->count_all_results(db_prefix() . 'folders');
    }

    public function folder_exists($folder_id)
    {
        $this->db->where('id', $folder_id);
        $query = $this->db->get(db_prefix() . 'folders');
        return $query->num_rows() > 0;
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


public function update_folderUpdate_api($id, $size = null, $files_count = null, $name = null, $is_favorite = null)
{
    if (!is_numeric($id) || $id <= 0) {
        return [
            'status' => false,
            'message' => 'ID da pasta inválido'
        ];
    }

    if ($size === null && $files_count === null && $name === null && $is_favorite === null) {
        return [
            'status' => false,
            'message' => 'Pelo menos um campo (size, files_count, name ou is_favorite) é obrigatório'
        ];
    }

    if ($size !== null && (!is_numeric($size) || $size < 0)) {
        return [
            'status' => false,
            'message' => 'Tamanho da pasta é inválido'
        ];
    }

    if ($files_count !== null && (!is_numeric($files_count) || $files_count < 0)) {
        return [
            'status' => false,
            'message' => 'Contagem de arquivos é inválida'
        ];
    }

    if ($name !== null && empty(trim($name))) {
        return [
            'status' => false,
            'message' => 'Nome da pasta é inválido'
        ];
    }

    if ($is_favorite !== null && !is_bool($is_favorite)) {
        return [
            'status' => false,
            'message' => 'O campo is_favorite deve ser true ou false'
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
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($size !== null) {
        $update_data['size'] = $size;
    }
    if ($files_count !== null) {
        $update_data['files_count'] = $files_count;
    }
    if ($name !== null) {
        $update_data['name'] = trim($name);
    }
    if ($is_favorite !== null) {
        $update_data['is_favorite'] = $is_favorite ? 1 : 0;
    }

    $this->db->where('id', $id);
    $this->db->update(db_prefix() . 'folders', $update_data);

    if ($this->db->affected_rows() >= 0) {
        return [
            'status' => true,
            'id' => $id,
            'size' => $size !== null ? $size : $folder->size,
            'files_count' => $files_count !== null ? $files_count : $folder->files_count,
            'name' => $name !== null ? trim($name) : $folder->name,
            'is_favorite' => $is_favorite !== null ? $is_favorite : (bool)$folder->is_favorite,
            'message' => 'Pasta atualizada com sucesso'
        ];
    }

    return [
        'status' => false,
        'message' => 'Falha ao atualizar a pasta'
    ];
}

}
