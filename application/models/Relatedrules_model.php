<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Relatedrules_model extends App_Model
{
    // Definindo os nomes corretos das tabelas para fácil manutenção
    private $table_name = 'related_rules';
    private $condicoes_table_name = 'condicoes';

    public function __construct()
    {
        parent::__construct();
    }

    public function get($id)
    {
        $this->db->where('id', $id);
        $rule = $this->db->get(db_prefix() . $this->table_name)->row();

        if ($rule) {
            $this->db->where('regraRelacionadaId', $id);
            $condicoes = $this->db->get(db_prefix() . $this->condicoes_table_name)->result();
            $rule->condicoes = $condicoes;

            $this->db->where('ownerId', $id);
            $this->db->where('ownerType', 'related_rule');
            $impostos = $this->db->get(db_prefix() . 'impostos')->result();
            $rule->impostos = $impostos;
        }
        return $rule;
    }

    public function get_by_operation($settingsFiscalId)
    {
        $this->db->where('settingsFiscalId', $settingsFiscalId);
        $this->db->order_by('ordem', 'ASC');
        $rules = $this->db->get(db_prefix() . $this->table_name)->result();

        if (empty($rules)) {
            return [];
        }

        $rule_ids = array_map(function($r) { return $r->id; }, $rules);

        $this->db->where_in('regraRelacionadaId', $rule_ids);
        $all_condicoes = $this->db->get(db_prefix() . $this->condicoes_table_name)->result();

        $this->db->where_in('ownerId', $rule_ids);
        $this->db->where('ownerType', 'related_rule');
        $all_impostos = $this->db->get(db_prefix() . 'impostos')->result();

        $condicoes_map = [];
        foreach ($all_condicoes as $condicao) {
            $condicoes_map[$condicao->regraRelacionadaId][] = $condicao;
        }
        $impostos_map = [];
        foreach ($all_impostos as $imposto) {
            $impostos_map[$imposto->ownerId][] = $imposto;
        }

        foreach ($rules as $rule) {
            $rule->condicoes = $condicoes_map[$rule->id] ?? [];
            $rule->impostos = $impostos_map[$rule->id] ?? [];
        }

        return $rules;
    }

    public function add($data)
    {
        $this->db->insert(db_prefix() . $this->table_name, $data);
        return $this->db->insert_id();
    }

    public function update($data, $id)
    {
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . $this->table_name, $data);
    }

    public function delete($id)
    {
        $this->delete_condicoes_by_rule($id);
        
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . $this->table_name);
        return ($this->db->affected_rows() > 0);
    }

    public function get_impostos_by_owner($ownerId, $ownerType)
    {
        $this->db->where('ownerId', $ownerId)->where('ownerType', $ownerType);
        return $this->db->get(db_prefix() . 'impostos')->result();
    }

    public function delete_imposto($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'impostos');
        return ($this->db->affected_rows() > 0);
    }

    public function delete_condicoes_by_rule($ruleId)
    {
        $this->db->where('regraRelacionadaId', $ruleId);
        $this->db->delete(db_prefix() . $this->condicoes_table_name);
    }
}