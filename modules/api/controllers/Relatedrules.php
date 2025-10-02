<?php
defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class RelatedRules extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Relatedrules_model');
    }

    public function list_get($settingsFiscalId = '')
    {
        if (empty($settingsFiscalId) || !is_numeric($settingsFiscalId)) {
            $this->response(['status' => FALSE, 'message' => 'ID da Operação Fiscal é obrigatório.'], 400);
            return;
        }
        $rules = $this->Relatedrules_model->get_by_operation($settingsFiscalId);
        $this->response(['status' => TRUE, 'data' => $rules], 200);
    }

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'ID da Regra é obrigatório.'], 400);
            return;
        }
        $rule = $this->Relatedrules_model->get($id);
        $this->response(['status' => TRUE, 'data' => $rule], 200);
    }

    public function create_post()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input)) { $this->response(['status' => false, 'message' => 'Payload vazio'], 400); return; }

        $impostos = $input['impostos'] ?? [];
        $condicoes = $input['condicoes'] ?? [];
        unset($input['impostos'], $input['condicoes']);


        $insert_data = $input;
        $currentUser = $this->session->userdata('staff_user_id') ?? 0;
        $now = date('Y-m-d H:i:s');

        $insert_data['createdBy'] = $currentUser;
        $insert_data['createdAt'] = $now;
        $insert_data['updatedAt'] = $now;

        
        if (!empty($insert_data['dataInicial'])) { $insert_data['dataInicial'] = date('Y-m-d', strtotime($insert_data['dataInicial'])); }
        if (!empty($insert_data['dataFinal'])) { $insert_data['dataFinal'] = date('Y-m-d', strtotime($insert_data['dataFinal'])); }

        try {
            $rule_id = $this->Relatedrules_model->add($insert_data);

            if ($rule_id) {
                if (!empty($impostos)) {
                    foreach ($impostos as $imposto) {
                        $imposto_data = [
                            'ownerId' => $rule_id, 'ownerType' => 'related_rule', 'nome' => $imposto['nome'],
                            'cst' => $imposto['cst'], 'csosn' => $imposto['csosn'], 'engIpi' => $imposto['engIpi'],
                            'createdBy' => $currentUser, 'createdAt' => $now, 'updatedAt' => $now
                        ];
                        $this->db->insert(db_prefix() . 'impostos', $imposto_data);
                        $imposto_id = $this->db->insert_id();
                        $detalhes = "Imposto '" . str_replace("_", " ", $imposto['nome']) . "' criado com CST='{$imposto['cst']}', CSOSN='{$imposto['csosn']}', Enq.IPI='{$imposto['engIpi']}'.";
                        $this->_log_imposto_change($imposto_id, $currentUser, 'CRIACAO', $detalhes);
                    }

                    if (!empty($condicoes)) {
                        foreach ($condicoes as $condicao) {
                            $condicao_data = [
                                'regraRelacionadaId' => $rule_id,
                                'sequencia'          => $condicao['sequencia'],
                                'campo'              => $condicao['campo'],
                                'operador'           => $condicao['operador'],
                                'valor'              => $condicao['valor'],
                                'valorOpcional'      => $condicao['valorOpcional'],
                                'createdBy'          => $insert_data['createdBy'],
                            ];
                            $this->db->insert(db_prefix() . 'condicoes', $condicao_data);
                        }
                    }
                }
                $this->response(['status' => true, 'message' => 'Regra criada com sucesso.', 'data' => ['id' => $rule_id]], 201);
            } else {
                throw new Exception('Erro ao criar a regra.');
            }
        } catch (Exception $e) {
            $this->response(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function update_post($id = '')
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($id) || !is_numeric($id) || empty($input)) { 
            $this->response(['status' => false, 'message' => 'Dados inválidos.'], 400); 
            return; 
        }

        try {
            $impostos_novos = $input['impostos'] ?? [];
            $condicoes_novas = $input['condicoes'] ?? [];
            unset($input['impostos'], $input['condicoes'], $input['id'], $input['createdBy'], $input['createdAt']);
            
            $update_data = $input;
            $now = date('Y-m-d H:i:s');
            $update_data['updatedAt'] = $now;
            if (!empty($update_data['dataInicial'])) { $update_data['dataInicial'] = date('Y-m-d', strtotime($update_data['dataInicial'])); }
            if (!empty($update_data['dataFinal'])) { $update_data['dataFinal'] = date('Y-m-d', strtotime($update_data['dataFinal'])); }
            
            $this->Relatedrules_model->update($update_data, $id);

            $this->Relatedrules_model->delete_condicoes_by_rule($id);
            if (!empty($condicoes_novas)) {
                $currentUser = $this->session->userdata('staff_user_id') ?? 0;
                foreach ($condicoes_novas as $condicao) {
                    $condicao_data = [
                        'regraRelacionadaId' => $id,
                        'sequencia'          => $condicao['sequencia'
                    ],
                        'campo'              => $condicao['campo'],
                        'operador'           => $condicao['operador'],
                        'valor'              => $condicao['valor'],
                        'valorOpcional'      => $condicao['valorOpcional'],
                        'createdBy'          => $currentUser,
                    ];
                    $this->db->insert(db_prefix() . 'condicoes', $condicao_data);
                }
            }

            $impostos_antigos = $this->Relatedrules_model->get_impostos_by_owner($id, 'related_rule');
            $mapa_antigos = [];
            foreach ($impostos_antigos as $imposto) { 
                $mapa_antigos[$imposto->id] = $imposto; 
            }
            
            $currentUser = $this->session->userdata('staff_user_id') ?? 0;

            foreach ($impostos_novos as $imposto_novo) {
                if (!isset($imposto_novo['id']) || isset($imposto_novo['isNew'])) {
                    $imposto_data = [
                        'ownerId' => $id, 'ownerType' => 'related_rule', 'nome' => $imposto_novo['nome'], 
                        'cst' => $imposto_novo['cst'], 'csosn' => $imposto_novo['csosn'], 'engIpi' => $imposto_novo['engIpi'],
                        'createdBy' => $currentUser, 'createdAt' => $now, 'updatedAt' => $now
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
                        if ($imposto_antigo->cst !== $imposto_novo['cst']) { $alteracoes[] = "CST alterado de '{$imposto_antigo->cst}' para '{$imposto_novo['cst']}'"; }
                        if ($imposto_antigo->csosn !== $imposto_novo['csosn']) { $alteracoes[] = "CSOSN alterado de '{$imposto_antigo->csosn}' para '{$imposto_novo['csosn']}'"; }
                        if ($imposto_antigo->engIpi !== $imposto_novo['engIpi']) { $alteracoes[] = "Enq. IPI alterado de '{$imposto_antigo->engIpi}' para '{$imposto_novo['engIpi']}'"; }
                        
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
                    $this->Relatedrules_model->delete_imposto($imposto_para_deletar->id);
                    $this->_log_imposto_change($imposto_para_deletar->id, $currentUser, 'DELECAO', "Imposto '{$imposto_para_deletar->nome}' foi removido.");
                }
            }

            $this->response(['status' => true, 'message' => 'Regra atualizada com sucesso.'], 200);
        } catch (Exception $e) {
            $this->response(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function delete_post($id = '')
    {
        if (empty($id) || !is_numeric($id)) { $this->response(['status' => false, 'message' => 'ID inválido.'], 400); return; }

        if ($this->Relatedrules_model->delete($id)) {
            $this->response(['status' => true, 'message' => 'Regra deletada com sucesso.'], 200);
        } else {
            $this->response(['status' => false, 'message' => 'Erro ao deletar a regra.'], 500);
        }
    }

    private function _log_imposto_change($imposto_id, $operador_id, $acao, $detalhes)
    {
        $data = [
            'impostoId'  => $imposto_id, 'operadorId' => $operador_id,
            'acao'       => $acao, 'detalhes'   => $detalhes,
        ];
        $this->db->insert(db_prefix() . 'imposto_historico', $data);
    }

}
