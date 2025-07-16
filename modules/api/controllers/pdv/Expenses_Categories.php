<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Expenses_Categories extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Expenses_Categories_model');
    }

    // Listar/buscar categorias
    public function index_get()
    {
        $warehouse_id = $this->input->get('warehouse_id');
        $type = $this->input->get('type');
        $search = $this->input->get('search') ?: '';
        $limit = $this->input->get('pageSize') ?: 10;
        if (empty($warehouse_id) || empty($type)) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $categories = $this->Expenses_Categories_model->search($warehouse_id, $type, $search, $limit);
        return $this->response([
            'status' => true,
            'data' => $categories
        ], REST_Controller::HTTP_OK);
    }

    // Obter categoria por ID
    public function show_get($id = null)
    {
        $warehouse_id = $this->input->get('warehouse_id');
        $type = $this->input->get('type');
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $category = $this->Expenses_Categories_model->get($id, $warehouse_id, $type);
        if (!$category) {
            return $this->response([
                'status' => false,
                'message' => 'Categoria não encontrada'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        return $this->response([
            'status' => true,
            'data' => $category
        ], REST_Controller::HTTP_OK);
    }

    // Criar categoria
    public function index_post()
    {
        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($input['name']) || empty($input['warehouse_id']) || empty($input['type'])) {
            return $this->response([
                'status' => false,
                'message' => 'name, warehouse_id e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $data = [
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'warehouse_id' => $input['warehouse_id'],
            'type' => $input['type'],
            'perfex_saas_tenant_id' => 'master'
        ];
        $id = $this->Expenses_Categories_model->add($data);
        if ($id) {
            $category = $this->Expenses_Categories_model->get($id);
            return $this->response([
                'status' => true,
                'message' => 'Categoria criada com sucesso',
                'data' => $category
            ], REST_Controller::HTTP_CREATED);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao criar categoria'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Atualizar categoria
    public function index_put($id = null)
    {
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($input['name']) || empty($input['type'])) {
            return $this->response([
                'status' => false,
                'message' => 'name e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $data = [
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'warehouse_id' => $input['warehouse_id'] ?? 0,
            'type' => $input['type']
        ];
        $success = $this->Expenses_Categories_model->update($id, $data);
        if ($success) {
            $category = $this->Expenses_Categories_model->get($id);
            return $this->response([
                'status' => true,
                'message' => 'Categoria atualizada com sucesso',
                'data' => $category
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao atualizar categoria ou nenhum dado alterado'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Deletar categoria
    public function index_delete($id = null)
    {
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $result = $this->Expenses_Categories_model->delete($id);
        if (is_array($result) && isset($result['referenced']) && $result['referenced']) {
            return $this->response([
                'status' => false,
                'message' => 'Não é possível excluir a categoria pois existem despesas/receitas vinculadas a ela'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        if ($result) {
            return $this->response([
                'status' => true,
                'message' => 'Categoria excluída com sucesso'
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao excluir categoria'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Listar categorias (GET /api/expenses_categories/list)
    public function list_get()
    {
        $warehouse_id = $this->input->get('warehouse_id');
        $type = $this->input->get('type');
        $search = $this->input->get('search') ?: '';
        $limit = $this->input->get('pageSize') ?: 20;

        if (empty($warehouse_id) || empty($type)) {
            return $this->response([
                'success' => false,
                'message' => 'warehouse_id e type são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Expenses_Categories_model');
        $categories = $this->Expenses_Categories_model->get_categories($warehouse_id, $search, $limit, $type);

        return $this->response([
            'success' => true,
            'data' => $categories
        ], REST_Controller::HTTP_OK);
    }

    // Buscar categoria por ID (GET /api/expenses_categories/item/{id})
    public function item_get($id = null)
    {
        if (empty($id)) {
            return $this->response([
                'success' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $warehouse_id = $this->input->get('warehouse_id');
        $type = $this->input->get('type');

        $this->load->model('Expenses_Categories_model');
        $category = $this->Expenses_Categories_model->get($id, $warehouse_id, $type);

        if (!$category) {
            return $this->response([
                'success' => false,
                'message' => 'Categoria não encontrada'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response([
            'success' => true,
            'data' => $category
        ], REST_Controller::HTTP_OK);
    }
} 