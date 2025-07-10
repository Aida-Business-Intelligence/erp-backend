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

    public function list_post()
    {
        // Retrieve and validate input parameters
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
        $result = $this->Folders_model->delete($id);

        if (!$result) {
            $this->response([
                'status' => false,
                'message' => 'Failed to delete folder or folder not found'
            ], REST_Controller::HTTP_BAD_REQUEST);
        } else {
            $this->response([
                'status' => true,
                'message' => 'Folder deleted successfully'
            ], REST_Controller::HTTP_OK);
        }
    }
}
