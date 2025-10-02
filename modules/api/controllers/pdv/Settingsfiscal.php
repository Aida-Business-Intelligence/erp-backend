<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class SettingsFiscal extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Settingsfiscal_model');
    }

    public function list_post()
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ? $this->post('sortField') : 'id';
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        $warehouse_id = $this->post('warehouseId') ? (int) $this->post('warehouseId') : 0;

        if ($warehouse_id <= 0) {
            $this->response(['status' => FALSE, 'message' => 'Warehouse ID é obrigatório'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = $this->Settingsfiscal_model->get_api($page, $limit, $search, $sortField, $sortOrder, $warehouse_id);

        if (empty($data['data'])) {
            $this->response(['status' => FALSE, 'message' => 'Nenhuma operação fiscal encontrada'], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => TRUE, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        }
    }

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'ID da operação inválido'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $operation = $this->Settingsfiscal_model->get($id);

        if ($operation) {
            $this->response(['status' => TRUE, 'data' => $operation], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Nenhum dado encontrado'], REST_Controller::HTTP_NOT_FOUND);
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

        $impostos = $input['impostos'] ?? [];
        unset($input['impostos']);
        
        unset($input['relatedRules']); 

        $insert_data = $input;
        $insert_data['createdBy'] = $this->authservice->user->staffid ?? 0;
        $insert_data['createdAt'] = date('Y-m-d H:i:s');
        $insert_data['updatedAt'] = date('Y-m-d H:i:s');

        try {
            $operation_id = $this->Settingsfiscal_model->add($insert_data);
            if ($operation_id) {
                if (!empty($impostos)) {
                    foreach ($impostos as $imposto) {
                        $imposto_data = [
                            'ownerId'   => $operation_id,
                            'ownerType' => 'settings_fiscal',
                            'nome'      => $imposto['nome'],
                            'cst'       => $imposto['cst'] ?? null,
                            'csosn'     => $imposto['csosn'] ?? null,
                            'engIpi'    => $imposto['engIpi'] ?? null,
                            'createdBy' => $insert_data['createdBy'],
                            'createdAt' => $insert_data['createdAt'],
                            'updatedAt' => $insert_data['updatedAt'],
                        ];
                        $this->db->insert(db_prefix() . 'impostos', $imposto_data);
                        $imposto_id = $this->db->insert_id();

                        if ($imposto_id) {
                            $detalhes = "Imposto '" . str_replace("_", " ", $imposto_data['nome']) . "' criado com CST='{$imposto_data['cst']}', CSOSN='{$imposto_data['csosn']}', Enq.IPI='{$imposto_data['engIpi']}'.";
                            $this->_log_imposto_change($imposto_id, $insert_data['createdBy'], 'CRIACAO', $detalhes);
                        }
                    }
                }
                $this->response(['status' => true, 'message' => 'Operação criada com sucesso', 'data' => ['id' => $operation_id]], REST_Controller::HTTP_CREATED);
            } else {
                throw new Exception('Falha ao inserir no banco de dados');
            }
        } catch (Exception $e) {
            $this->response(['status' => false, 'message' => 'Erro ao criar operação: ' . $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_post($id = '')
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Dados ou ID da operação inválidos'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!$this->Settingsfiscal_model->get($id)) {
            $this->response(['status' => FALSE, 'message' => 'Operação não encontrada'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        try {
            $impostos_novos = $input['impostos'] ?? [];
            unset($input['impostos']);
            
            unset($input['relatedRules']);

            $update_data = $input;
            $update_data['updatedAt'] = date('Y-m-d H:i:s');
            unset($update_data['id'], $update_data['createdBy'], $update_data['createdAt']);
            $this->Settingsfiscal_model->update($update_data, $id);

            $impostos_antigos = $this->Settingsfiscal_model->get_impostos_by_owner($id, 'settings_fiscal');
            
            $mapa_antigos = [];
            foreach ($impostos_antigos as $imposto) {
                $mapa_antigos[$imposto->id] = $imposto;
            }

            $currentUser = $this->authservice->user->staffid ?? 0;
            $now = date('Y-m-d H:i:s');

            foreach ($impostos_novos as $imposto_novo) {
                if (!isset($imposto_novo['id']) || isset($imposto_novo['isNew'])) {
                    $imposto_data = [
                        'ownerId' => $id, 'ownerType' => 'settings_fiscal',
                        'nome' => $imposto_novo['nome'], 'cst' => $imposto_novo['cst'],
                        'csosn' => $imposto_novo['csosn'], 'engIpi' => $imposto_novo['engIpi'],
                        'createdBy' => $currentUser, 'createdAt' => $now, 'updatedAt' => $now,
                    ];
                    $this->db->insert(db_prefix() . 'impostos', $imposto_data);
                    $imposto_id = $this->db->insert_id();
                    $detalhes = "Imposto '" . str_replace("_", " ", $imposto_data['nome']) . "' criado com CST='{$imposto_data['cst']}', CSOSN='{$imposto_data['csosn']}', Enq.IPI='{$imposto_data['engIpi']}'.";
                    $this->_log_imposto_change($imposto_id, $currentUser, 'CRIACAO', $detalhes);
                } else {
                    $imposto_id = $imposto_novo['id'];
                    if (isset($mapa_antigos[$imposto_id])) {
                        $imposto_antigo = $mapa_antigos[$imposto_id];
                        $alteracoes = [];
                        if ($imposto_antigo->cst !== $imposto_novo['cst']) { $alteracoes[] = "Campo 'CST' alterado de '{$imposto_antigo->cst}' para '{$imposto_novo['cst']}'"; }
                        if ($imposto_antigo->csosn !== $imposto_novo['csosn']) { $alteracoes[] = "Campo 'CSOSN' alterado de '{$imposto_antigo->csosn}' para '{$imposto_novo['csosn']}'"; }
                        if ($imposto_antigo->engIpi !== $imposto_novo['engIpi']) { $alteracoes[] = "Campo 'Enq. IPI' alterado de '{$imposto_antigo->engIpi}' para '{$imposto_novo['engIpi']}'"; }

                        if (!empty($alteracoes)) {
                            $this->db->where('id', $imposto_id)->update(db_prefix() . 'impostos', ['cst' => $imposto_novo['cst'], 'csosn' => $imposto_novo['csosn'], 'engIpi' => $imposto_novo['engIpi'], 'updatedAt' => $now]);
                            $this->_log_imposto_change($imposto_id, $currentUser, 'ALTERACAO', implode('; ', $alteracoes));
                        }
                        unset($mapa_antigos[$imposto_id]);
                    }
                }
            }

            if (!empty($mapa_antigos)) {
                foreach ($mapa_antigos as $imposto_para_deletar) {
                    $this->Settingsfiscal_model->delete_imposto($imposto_para_deletar->id);
                    $nome_formatado = str_replace("_", " ", $imposto_para_deletar->nome);
                    $this->_log_imposto_change($imposto_para_deletar->id, $currentUser, 'DELECAO', "Imposto '{$nome_formatado}' foi removido.");
                }
            }
            
            $this->response(['status' => TRUE, 'message' => 'Operação atualizada com sucesso', 'data' => $this->Settingsfiscal_model->get($id)], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response(['status'  => false, 'message' => 'Erro ao atualizar operação: ' . $e->getMessage()], 500);
        }
    }


    public function duplicate_post($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'ID da Operação inválido'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $original_operation = $this->Settingsfiscal_model->get($id);

        if (!$original_operation) {
            $this->response(['status' => FALSE, 'message' => 'Operação original não encontrada'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $impostos_originais = $original_operation->impostos;
        unset($original_operation->impostos);
        
        unset($original_operation->relatedRules);

        $new_data = (array) $original_operation;
        unset($new_data['id']);
        
        $new_data['createdBy'] = $this->authservice->user->staffid ?? 0;
        $new_data['createdAt'] = date('Y-m-d H:i:s');
        $new_data['updatedAt'] = date('Y-m-d H:i:s');
        $new_data['nomeOperacao'] = ($new_data['nomeOperacao'] ?? 'Operação') . ' - Cópia';

        try {
            $new_operation_id = $this->Settingsfiscal_model->add($new_data);
            if ($new_operation_id) {
                if (!empty($impostos_originais)) {
                    foreach ($impostos_originais as $imposto) {
                        $imposto_data = [
                            'ownerId'   => $new_operation_id,
                            'ownerType' => 'settings_fiscal',
                            'nome'      => $imposto->nome,
                            'cst'       => $imposto->cst,
                            'csosn'     => $imposto->csosn,
                            'engIpi'    => $imposto->engIpi,
                            'createdBy' => $new_data['createdBy'],
                            'createdAt' => $new_data['createdAt'],
                            'updatedAt' => $new_data['updatedAt'],
                        ];
                        $this->db->insert(db_prefix() . 'impostos', $imposto_data);
                    }
                }
                $this->response(['status'  => true, 'message' => 'Operação duplicada com sucesso', 'data'    => ['id' => $new_operation_id]], REST_Controller::HTTP_CREATED);
            } else {
                throw new Exception('Falha ao inserir a cópia no banco de dados');
            }
        } catch (Exception $e) {
            $this->response(['status'  => false, 'message' => 'Erro ao duplicar operação: ' . $e->getMessage()], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            if ($this->Settingsfiscal_model->delete($id)) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }
        
        if ($success_count > 0) {
            $this->response([
                'status' => TRUE,
                'message' => $success_count . ' operação(ões) deletada(s) com sucesso.',
                'failed_ids' => $failed_ids
            ], REST_Controller::HTTP_OK);
        } else {
             $this->response([
                'status' => FALSE,
                'message' => 'Nenhuma operação foi deletada.',
                'failed_ids' => $failed_ids
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    private function _log_imposto_change($imposto_id, $operador_id, $acao, $detalhes)
    {
        $data = [
            'impostoId'  => $imposto_id,
            'operadorId' => $operador_id,
            'acao'       => $acao,
            'detalhes'   => $detalhes,
        ];
        $this->db->insert(db_prefix() . 'imposto_historico', $data);
    }
}
