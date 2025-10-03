<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Impostos extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Impostos_model');
    }

    public function list_post()
    {        
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ? $this->post('sortField') : 'id';
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        $settingsFiscalId = $this->post('settingsFiscalId') ? (int) $this->post('settingsFiscalId') : 0;

        if ($settingsFiscalId <= 0) {
            $this->response(['status' => FALSE, 'message' => 'SettingsFiscal ID é obrigatório'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = $this->Impostos_model->get_api($page, $limit, $search, $sortField, $sortOrder, $settingsFiscalId);

        if (empty($data['data'])) {
            $this->response(['status' => FALSE, 'message' => 'Nenhum imposto encontrado'], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => TRUE, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        }
    }

    public function create_post()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->response(['status' => false, 'message' => 'JSON inválido'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        if (empty($input)) {
            $this->response(['status' => false, 'message' => 'Payload vazio'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        
        $insert_data = $input;

        $insert_data['createdBy'] = $this->authservice->user->staffid ?? 0;
        $insert_data['createdAt'] = date('Y-m-d H:i:s');
        $insert_data['updatedAt'] = date('Y-m-d H:i:s');

        try {
            $operation_id = $this->Impostos_model->add($insert_data);

            if ($operation_id) {
                $this->response([
                    'status' => true,
                    'message' => 'Imposto criado com sucesso',
                    'data' => ['id' => $operation_id]
                ], REST_Controller::HTTP_CREATED);
            } else {
                throw new Exception('Falha ao inserir no banco de dados');
            }
        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => 'Erro ao criar imposto: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'ID da operação inválido'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $operation = $this->Impostos_model->get($id);

        if ($operation) {
            $this->response(['status' => TRUE, 'data' => $operation], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Nenhum dado encontrado'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function update_post($id = '')
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Dados ou ID do imposto inválidos'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!$this->Impostos_model->get($id)) {
            $this->response(['status' => FALSE, 'message' => 'Imposto não encontrado'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $update_data = $input;

        // Adiciona campo de auditoria para atualização
        $update_data['updatedAt'] = date('Y-m-d H:i:s');
        // Remove campos que não devem ser atualizados
        unset($update_data['id'], $update_data['createdBy'], $update_data['createdAt']);

        $output = $this->Impostos_model->update($update_data, $id);

        if (!$output) {
             // Retorna OK mesmo se nada mudou, pois a intenção de salvar foi "bem-sucedida"
            $this->response(['status' => TRUE, 'message' => 'Nenhum dado foi modificado.', 'data' => $this->Impostos_model->get($id)], REST_Controller::HTTP_OK);
            return;
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Imposto atualizado com sucesso',
            'data' => $this->Impostos_model->get($id)
        ], REST_Controller::HTTP_OK);
    }

    public function remove_post()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['rows']) || !is_array($input['rows'])) {
            $this->response(['status' => FALSE, 'message' => 'Requisição inválida: o array "rows" é obrigatório'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = array_filter($input['rows'], 'is_numeric');
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            if ($this->Impostos_model->delete($id)) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }
        
        if ($success_count > 0) {
            $this->response([
                'status' => TRUE,
                'message' => $success_count . ' imposto(s) deletada(s) com sucesso.',
                'failed_ids' => $failed_ids
            ], REST_Controller::HTTP_OK);
        } else {
             $this->response([
                'status' => FALSE,
                'message' => 'Nenhum imposto foi deletado.',
                'failed_ids' => $failed_ids
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }
}