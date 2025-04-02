<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Warehouse_model extends App_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */

    public function get($id = '', $where = [])
    {

        $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'warehouse')));

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'warehouse.warehouse_id', $id);
            $client = $this->db->get(db_prefix() . 'warehouse')->row();

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('warehouse_name', 'asc');

        return $this->db->get(db_prefix() . 'warehouse')->result_array();
    }

    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'warehouse_id', $sortOrder = 'ASC', $franqueado_id = 0)
    {
        $allowedSortFields = [
            'warehouse_code',
            'warehouse_name',
            'cnpj',
            'order',
            'display',
            'note',
            'cidade',
            'estado',
            'ie',
            'im',
            'cep',
            'complemento',
            'bairro',
            'numero',
            'endereco',
            'franqueado_id',
            'password_nfe',
        ];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'warehouse_id'; // Valor padrão seguro
        }

        if (!is_numeric($id)) {
            $this->db->select('*');
            $this->db->from(db_prefix() . 'warehouse');
            $this->db->where('warehouse.franqueado_id', value: $franqueado_id);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like(db_prefix() . 'warehouse.warehouse_code', $search);
                $this->db->or_like(db_prefix() . 'warehouse.warehouse_name', $search);
                $this->db->or_like(db_prefix() . 'warehouse.cnpj', $search);
                $this->db->or_like(db_prefix() . 'warehouse.order', $search);
                $this->db->or_like(db_prefix() . 'warehouse.display', $search);
                $this->db->or_like(db_prefix() . 'warehouse.note', $search);
                $this->db->or_like(db_prefix() . 'warehouse.cidade', $search);
                $this->db->or_like(db_prefix() . 'warehouse.estado', $search);
                $this->db->or_like(db_prefix() . 'warehouse.ie', $search);
                $this->db->or_like(db_prefix() . 'warehouse.im', $search);
                $this->db->or_like(db_prefix() . 'warehouse.cep', $search);
                // $this->db->or_like(db_prefix() . 'warehouse.complemento', $search);
                $this->db->or_like(db_prefix() . 'warehouse.bairro', $search);
                $this->db->or_like(db_prefix() . 'warehouse.numero', $search);
                $this->db->or_like(db_prefix() . 'warehouse.endereco', $search);
                $this->db->or_like(db_prefix() . 'warehouse.franqueado_id', $search);
                $this->db->group_end();

            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);

            $clients = $this->db->get()->result_array();

            $this->db->reset_query();
            $this->db->from(db_prefix() . 'warehouse');
            $this->db->where('warehouse.franqueado_id', $franqueado_id); // Filtro por franqueado_id na contagem

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like(db_prefix() . 'warehouse.warehouse_code', $search);
                $this->db->or_like(db_prefix() . 'warehouse.warehouse_name', $search);
                $this->db->or_like(db_prefix() . 'warehouse.cnpj', $search);
                $this->db->or_like(db_prefix() . 'warehouse.order', $search);
                $this->db->or_like(db_prefix() . 'warehouse.display', $search);
                $this->db->or_like(db_prefix() . 'warehouse.note', $search);
                $this->db->or_like(db_prefix() . 'warehouse.cidade', $search);
                $this->db->or_like(db_prefix() . 'warehouse.estado', $search);
                $this->db->or_like(db_prefix() . 'warehouse.ie', $search);
                $this->db->or_like(db_prefix() . 'warehouse.im', $search);
                $this->db->or_like(db_prefix() . 'warehouse.cep', $search);
                // $this->db->or_like(db_prefix() . 'warehouse.complemento', $search);
                $this->db->or_like(db_prefix() . 'warehouse.bairro', $search);
                $this->db->or_like(db_prefix() . 'warehouse.numero', $search);
                $this->db->or_like(db_prefix() . 'warehouse.endereco', $search);
                $this->db->or_like(db_prefix() . 'warehouse.franqueado_id', $search);
                $this->db->group_end();

            }

            $total = $this->db->count_all_results();

            return ['data' => !empty($clients) ? $clients : [], 'total' => $total];
        } else {
            $this->db->select('*');
            $this->db->from(db_prefix() . 'warehouse');
            $this->db->where(db_prefix() . 'warehouse.warehouse_id', $id);
            $this->db->where('warehouse.franqueado_id', value: $franqueado_id);

            $client = $this->db->get()->row();
            $total = $client ? 1 : 0;

            return ['data' => $client ? [(array) $client] : [], 'total' => $total];
        }
    }


    public function add($data)
    {
        // Definir os campos permitidos para inserção
        $allowed_fields = [
            'warehouse_code',
            'warehouse_name',
            'cnpj',
            'order',
            'display',
            'note',
            'cidade',
            'estado',
            'ie',
            'im',
            'cep',
            'complemento',
            'bairro',
            'numero',
            'endereco',
            'franqueado_id',
            'password_nfe',
        ];

        // Filtrar apenas os campos válidos
        $insert_data = array_intersect_key($data, array_flip($allowed_fields));

        // Inserir no banco de dados
        $this->db->insert(db_prefix() . 'warehouse', $insert_data);

        // Retornar ID se a inserção for bem-sucedida
        return ($this->db->affected_rows() > 0) ? $this->db->insert_id() : false;
    }


    public function update($data, $id)
    {
        // Definir os campos permitidos para atualização
        $allowed_fields = [
            'warehouse_code',
            'warehouse_name',
            'cnpj',
            'order',
            'display',
            'note',
            'cidade',
            'estado',
            'ie',
            'im',
            'cep',
            'complemento',
            'bairro',
            'numero',
            'endereco',
            'franqueado_id',
            'password_nfe',
        ];

        // Filtrar os dados permitidos
        $update_data = array_intersect_key($data, array_flip($allowed_fields));

        // Verificar se há algo para atualizar
        if (empty($update_data)) {
            return false;
        }

        // Atualizar os dados na tabela
        $this->db->where('warehouse_id', $id);
        $this->db->update(db_prefix() . 'warehouse', $update_data);

        // Retornar true se a atualização foi bem-sucedida
        return ($this->db->affected_rows() > 0);
    }



    public function delete($id)
    {
        // Verificar se o ID existe antes de deletar
        $this->db->where('warehouse_id', $id);
        $this->db->delete(db_prefix() . 'warehouse');

        // Retornar true se a exclusão foi bem-sucedida
        return ($this->db->affected_rows() > 0);
    }



}
