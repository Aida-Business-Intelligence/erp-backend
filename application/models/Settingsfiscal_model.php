<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settingsfiscal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add($data)
    {
        $this->db->insert(db_prefix() . 'settings_fiscal', $data);
        $insert_id = $this->db->insert_id();
        return $insert_id ? $insert_id : false;
    }
    
    public function get($id = '', $where = [])
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $operation = $this->db->get(db_prefix() . 'settings_fiscal')->row();

            if ($operation) {
                $this->db->where('ownerId', $id);
                $this->db->where('ownerType', 'settings_fiscal');
                $operation->impostos = $this->db->get(db_prefix() . 'impostos')->result();

                $this->db->where('settingsFiscalId', $id);
                $related_rules = $this->db->get(db_prefix() . 'related_rules')->result();

                if (!empty($related_rules)) {
                    $rule_ids = array_map(function($r) { return $r->id; }, $related_rules);

                    $this->db->where_in('regraRelacionadaId', $rule_ids);
                    $all_condicoes = $this->db->get(db_prefix() . 'condicoes')->result();

                    $this->db->where_in('ownerId', $rule_ids);
                    $this->db->where('ownerType', 'related_rule');
                    $all_impostos_regras = $this->db->get(db_prefix() . 'impostos')->result();

                    $condicoes_map = [];
                    foreach ($all_condicoes as $c) { $condicoes_map[$c->regraRelacionadaId][] = $c; }
                    
                    $impostos_map = [];
                    foreach ($all_impostos_regras as $i) { $impostos_map[$i->ownerId][] = $i; }

                    foreach ($related_rules as $rule) {
                        $rule->condicoes = $condicoes_map[$rule->id] ?? [];
                        $rule->impostos = $impostos_map[$rule->id] ?? [];
                    }
                }
                
                $operation->relatedRules = $related_rules;
            }

            return $operation;
        }

        $this->db->from(db_prefix() . 'settings_fiscal');
        
        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        $this->db->order_by('createdAt', 'DESC');
        return $this->db->get()->result();
    }


    public function get_api($page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'DESC', $warehouse_id = 0)
    {
        $this->db->from(db_prefix() . 'settings_fiscal');
        $this->db->where('warehouseId', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('nomeRegraFiscal', $search);
            $this->db->or_like('tipoDanfe', $search);
            $this->db->or_like('modeloDocumentoFiscal', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);

        $operations = $this->db->get()->result();
        
        if (empty($operations)) {
            return ['data' => [], 'total' => 0];
        }

        $operation_ids = array_map(function($op) { return $op->id; }, $operations);

        $this->db->where_in('ownerId', $operation_ids);
        $this->db->where('ownerType', 'settings_fiscal');
        $all_impostos = $this->db->get(db_prefix() . 'impostos')->result();

        $this->db->where_in('settingsFiscalId', $operation_ids);
        $all_related_rules = $this->db->get(db_prefix() . 'related_rules')->result();

        $condicoes_map = [];
        $impostos_regras_map = [];

        if (!empty($all_related_rules)) {
            $rule_ids = array_map(function($r) { return $r->id; }, $all_related_rules);

            $this->db->where_in('regraRelacionadaId', $rule_ids);
            $all_condicoes = $this->db->get(db_prefix() . 'condicoes')->result();

            $this->db->where_in('ownerId', $rule_ids);
            $this->db->where('ownerType', 'related_rule');
            $all_impostos_regras = $this->db->get(db_prefix() . 'impostos')->result();

            foreach ($all_condicoes as $c) { $condicoes_map[$c->regraRelacionadaId][] = $c; }
            foreach ($all_impostos_regras as $i) { $impostos_regras_map[$i->ownerId][] = $i; }
        }
        
        $rules_map = [];
        foreach ($all_related_rules as $rule) {
            $rule->condicoes = $condicoes_map[$rule->id] ?? [];
            $rule->impostos = $impostos_regras_map[$rule->id] ?? [];
            $rules_map[$rule->settingsFiscalId][] = $rule;
        }

        $impostos_map = [];
        foreach ($all_impostos as $imposto) {
            $impostos_map[$imposto->ownerId][] = $imposto;
        }
        
        foreach ($operations as $operation) {
            $operation->impostos = $impostos_map[$operation->id] ?? [];
            $operation->relatedRules = $rules_map[$operation->id] ?? []; 
        }

        $this->db->reset_query(); 
        $this->db->from(db_prefix() . 'settings_fiscal');
        $this->db->where('warehouseId', $warehouse_id);
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('nomeRegraFiscal', $search);
            $this->db->or_like('tipoDanfe', $search);
            $this->db->or_like('modeloDocumentoFiscal', $search);
            $this->db->group_end();
        }
        $total = $this->db->count_all_results();

        return [
            'data' => $operations,
            'total' => $total
        ];
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'settings_fiscal', $data);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'settings_fiscal');
        return ($this->db->affected_rows() > 0);
    }

    public function get_impostos_by_owner($ownerId, $ownerType)
    {
        $this->db->where('ownerId', $ownerId);
        $this->db->where('ownerType', $ownerType);
        return $this->db->get(db_prefix() . 'impostos')->result();
    }
}


