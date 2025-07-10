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
    public function list_post()
    {
        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ? trim($this->post('search')) : '';
        $sort_field = $this->post('sortField') ? trim($this->post('sortField')) : 'id';
        $sort_order = $this->post('sortOrder') ? trim($this->post('sortOrder')) : 'ASC';
        $id = $this->post('id') ? (int) $this->post('id') : null;

        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        $data = $this->Folders_model->get_api($page, $limit, $search, $sort_field, $sort_order, $id);

        if (empty($data['data'])) {
            $this->response([
                'status' => false,
                'message' => 'No folders found'
            ], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response([
                'status' => true,
                'total' => $data['total'],
                'data' => $data['data']
            ], REST_Controller::HTTP_OK);
        }
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

    public function update_name_put($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        log_message('debug', 'Received PUT data for update_name: ' . print_r($data, true));

        if (!$data || empty($id) || !is_numeric($id) || !isset($data['name'])) {
            $this->response([
                'status' => false,
                'message' => 'ID da pasta ou nome inválido'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $result = $this->Folders_model->update_name_api($id, $data['name']);

        if ($result['status']) {
            $this->response([
                'status' => true,
                'id' => $result['id'],
                'message' => $result['message']
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => $result['message']
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function favorite_put($id)
    {
        $data = json_decode(file_get_contents('php://input'), true);

        log_message('debug', 'Received PUT data for favorite: ' . print_r($data, true));

        if (!$data || !isset($data['is_favorite']) || empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => false,
                'message' => 'ID da pasta ou campo is_favorite inválido'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $result = $this->Folders_model->update_favorite_api($id, $data['is_favorite']);

        if ($result['status']) {
            $this->response([
                'status' => true,
                'id' => $result['id'],
                'message' => $result['message']
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => $result['message']
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
}
