<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Folders extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Folders_model');
    }

    public function create_post()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        log_message('debug', 'Received POST data: ' . print_r($data, true));

        if (!$data || empty($data['name'])) {
            $this->response([
                'status' => false,
                'message' => 'Folder name is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $result = $this->Folders_model->create_api($data);

        if ($result['status']) {
            $this->response([
                'status' => true,
                'id' => $result['id'],
                'folder_url' => $result['folder_url'],
                'message' => $result['message']
            ], REST_Controller::HTTP_CREATED);
        } else {
            $this->response([
                'status' => false,
                'message' => $result['message'],
                'debug' => isset($result['debug']) ? $result['debug'] : null
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }



    public function list_get()
{
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $order_by = $this->get('order_by') ?? 'created_at';
    $order_direction = $this->get('order_direction') ?? 'desc';
    $id = $this->get('id') ?? null;
    $search = $this->get('search') ? trim($this->get('search')) : null;

    $limit = $this->get('limit') ? (int)$this->get('limit') : null;
    $page = $this->get('page') ? (int)$this->get('page') : 1;

    $offset = null;
    if ($limit !== null && $page > 0) {
        $offset = ($page - 1) * $limit;
    }

    $allowed_order_by = ['created_at', 'updated_at', 'name', 'size', 'files_count'];
    $allowed_order_direction = ['asc', 'desc'];

    if (!in_array($order_by, $allowed_order_by)) {
        $this->response([
            'status' => false,
            'message' => 'Invalid order_by parameter. Allowed values: ' . implode(', ', $allowed_order_by)
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if (!in_array(strtolower($order_direction), $allowed_order_direction)) {
        $this->response([
            'status' => false,
            'message' => 'Invalid order_direction parameter. Allowed values: asc, desc'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if ($id !== null) {
        if (!is_numeric($id) || !ctype_digit(strval($id)) || $id <= 0) {
            $this->response([
                'status' => false,
                'message' => 'Invalid id parameter. Must be a positive integer.'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        if (!$this->Folders_model->folder_exists($id)) {
            $this->response([
                'status' => false,
                'message' => 'Folder with provided ID does not exist'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }
    }

    if ($limit !== null && (!is_numeric($limit) || $limit <= 0)) {
        $this->response([
            'status' => false,
            'message' => 'Invalid limit parameter. Must be a positive integer.'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if (!is_numeric($page) || $page <= 0) {
        $this->response([
            'status' => false,
            'message' => 'Invalid page parameter. Must be a positive integer.'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $total_folders = $this->Folders_model->count_folders($id, $search);

    $folders = $this->Folders_model->get_folders($order_by, $order_direction, $id, $search, $limit, $offset);

    $response_data = [
        'status' => true,
        'message' => 'Folders retrieved successfully',
        'total_folders' => $total_folders,
        'data' => []
    ];

    if ($limit !== null) {
        $total_pages = ceil($total_folders / $limit);
        $response_data['pagination'] = [
            'current_page' => $page,
            'items_per_page' => $limit,
            'total_pages' => (int)$total_pages,
            'has_next_page' => ($page < $total_pages),
            'has_previous_page' => ($page > 1)
        ];
    }

    if ($folders) {
        $formatted_folders = array_map(function ($folder) {
            $folder['is_favorite'] = (bool)$folder['is_favorite'];
            $folder['files_count'] = (int)$folder['files_count'];
            return $folder;
        }, $folders);

        $response_data['data'] = $formatted_folders;
    }

    $this->response($response_data, REST_Controller::HTTP_OK);
}

    public function get_get($id)
    {
        $folder = $this->Folders_model->get_by_id($id);

        if (!$folder) {
            $this->response([
                'status' => false,
                'message' => 'Folder not found'
            ], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response([
                'status' => true,
                'data' => $folder
            ], REST_Controller::HTTP_OK);
        }
    }

 public function delete_delete($id)
{
    if (empty($id)) {
        $this->response([
            'status' => false,
            'message' => 'ID da pasta não fornecido'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $result = $this->Folders_model->delete_api($id);

    if ($result['status']) {
        $this->response([
            'status' => true,
            'message' => $result['message']
        ], REST_Controller::HTTP_OK);
    } else {
        $this->response([
            'status' => false,
            'message' => $result['message'],
            'debug' => isset($result['debug']) ? $result['debug'] : null
        ], REST_Controller::HTTP_BAD_REQUEST);
    }
}

public function folderUpdate_put($id)
{
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($id) || !is_numeric($id)) {
        $this->response([
            'status' => false,
            'message' => 'ID da pasta inválido'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if (!$data || (!isset($data['size']) && !isset($data['files_count']) && !isset($data['name']) && !isset($data['is_favorite']))) {
        $this->response([
            'status' => false,
            'message' => 'Pelo menos um campo (size, files_count, name ou is_favorite) é obrigatório'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $result = $this->Folders_model->update_folderUpdate_api($id, $data['size'] ?? null, $data['files_count'] ?? null, $data['name'] ?? null, $data['is_favorite'] ?? null);

    if ($result['status']) {
        $this->response([
            'status' => true,
            'id' => (int)$result['id'],
            'size' => $result['size'],
            'files_count' => (int)$result['files_count'],
            'name' => $result['name'],
            'is_favorite' => $result['is_favorite'],
            'message' => $result['message'],
        ], REST_Controller::HTTP_OK);
    } else {
        $this->response([
            'status' => false,
            'message' => $result['message']
        ], REST_Controller::HTTP_BAD_REQUEST);
    }
}

}
