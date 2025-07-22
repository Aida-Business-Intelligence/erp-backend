<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Origins extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Origins_model');
    }

    public function list_get()
    {
        try {
            $warehouse_id = $this->input->get('warehouse_id');
            $search = $this->input->get('search') ?: '';
            $page = (int)($this->input->get('page') ?? 1);
            $pageSize = (int)($this->input->get('pageSize') ?? 5);
            if (empty($warehouse_id)) {
                return $this->response([
                    'success' => false,
                    'message' => 'warehouse_id é obrigatório'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $result = $this->Origins_model->list($warehouse_id, $search, $page, $pageSize);
            return $this->response([
                'success' => true,
                'data' => $result['data'],
                'total' => $result['total'],
                'page' => $result['page'],
                'pageSize' => $result['pageSize']
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create_post()
    {
        try {
            $input = $this->input->post();
            if (empty($input)) {
                $input = json_decode(file_get_contents('php://input'), true);
            }
            if (empty($input['name']) || empty($input['warehouse_id'])) {
                return $this->response([
                    'success' => false,
                    'message' => 'Nome e warehouse_id são obrigatórios'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $id = $this->Origins_model->create($input['name'], $input['warehouse_id'], $input['description'] ?? null);
            return $this->response([
                'success' => true,
                'data' => ['id' => $id]
            ], REST_Controller::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_post()
    {
        try {
            $id = $this->input->post('id');
            if (empty($id)) {
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
            }
            if (empty($id)) {
                return $this->response([
                    'success' => false,
                    'message' => 'ID é obrigatório'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $success = $this->Origins_model->delete($id);
            if ($success) {
                return $this->response([
                    'success' => true,
                    'message' => 'Origem excluída com sucesso'
                ], REST_Controller::HTTP_OK);
            } else {
                return $this->response([
                    'success' => false,
                    'message' => 'Falha ao excluir origem'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            return $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 