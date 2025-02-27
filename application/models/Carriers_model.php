<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Carriers_model extends App_Model
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
        $this->db->select(implode(',', prefixed_table_fields_array(db_prefix() . 'carriers')));

        //        $this->db->join(db_prefix() . 'countries', '' . db_prefix() . 'countries.country_id = ' . db_prefix() . 'clients.country', 'left');
//        $this->db->join(db_prefix() . 'contacts', '' . db_prefix() . 'contacts.userid = ' . db_prefix() . 'clients.userid AND is_primary = 1', 'left');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'carriers.id', $id);
            $client = $this->db->get(db_prefix() . 'carriers')->row();

            //            if ($client && get_option('company_requires_vat_number_field') == 0) {
//                $client->vat = null;
//            }

            $GLOBALS['client'] = $client;

            return $client;
        }

        $this->db->order_by('nome', 'asc');

        return $this->db->get(db_prefix() . 'carriers')->result_array();
    }


    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'ASC')
    {
        if (!is_numeric($id)) {
            $this->db->select('*'); // Seleciona todos os campos
            $this->db->from(db_prefix() . 'carriers'); // Define a tabela

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like(db_prefix() . 'carriers.nome', $search);
                $this->db->or_like(db_prefix() . 'carriers.id', $search);
                $this->db->or_like(db_prefix() . 'carriers.tipo', $search);
                $this->db->or_like(db_prefix() . 'carriers.cnpj_placa', $search);
                $this->db->or_like(db_prefix() . 'carriers.cidade', $search);
                $this->db->or_like(db_prefix() . 'carriers.estado', $search);
                $this->db->or_like(db_prefix() . 'carriers.motorista', $search);
                $this->db->group_end();
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);

            $clients = $this->db->get()->result_array();

            // Contagem de total de registros
            $this->db->reset_query();
            $this->db->from(db_prefix() . 'carriers');

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like(db_prefix() . 'carriers.nome', $search);
                $this->db->or_like(db_prefix() . 'carriers.id', $search);
                $this->db->or_like(db_prefix() . 'carriers.tipo', $search);
                $this->db->or_like(db_prefix() . 'carriers.cnpj_placa', $search);
                $this->db->or_like(db_prefix() . 'carriers.cidade', $search);
                $this->db->or_like(db_prefix() . 'carriers.estado', $search);
                $this->db->or_like(db_prefix() . 'carriers.motorista', $search);
                $this->db->group_end();
            }

            $total = $this->db->count_all_results(); // Contagem correta

            return ['data' => $clients, 'total' => $total];
        } else {
            $this->db->select('*');
            $this->db->from(db_prefix() . 'carriers');
            $this->db->where(db_prefix() . 'carriers.id', $id);

            $client = $this->db->get()->row();
            $total = $client ? 1 : 0;

            return ['data' => (array) $client, 'total' => $total];
        }
    }

    public function add($data)
    {
        // Definir os campos permitidos para inserção
        $allowed_fields = [
            'nome',
            'tipo',
            'vat',
            'cidade',
            'estado',
            'status',
            'franqueado_id',
            'documentType',
            'placa',
            'renavam',
            'marca',
            'modelo',
            'ano',
            'tipo_veiculo',
            'capacidade',
            'ultima_manutencao',
            'proxima_manutencao',
            'observacoes',
            'nome_motorista',
            'cpf_motorista',
            'rg_motorista',
            'data_nascimento',
            'cnh_motorista',
            'categoria_cnh',
            'validade_cnh',
            'mopp',
            'endereco_motorista',
            'cidade_motorista',
            'estado_motorista',
            'cep_motorista',
            'telefone_motorista',
            'celular_motorista',
            'email_motorista'
        ];

        // Filtrar apenas os campos válidos
        $insert_data = array_intersect_key($data, array_flip($allowed_fields));

        // Garantir que campos opcionais fiquem como NULL se não forem enviados
        foreach ($allowed_fields as $field) {
            if (!isset($insert_data[$field])) {
                $insert_data[$field] = NULL;
            }
        }

        // Garantir que 'tipo' tenha um valor válido
        if (!in_array($insert_data['tipo'], ['terceiro', 'proprio'])) {
            return false; // Falha na validação
        }

        // Inserir no banco de dados
        $this->db->insert(db_prefix() . 'carriers', $insert_data);

        // Retornar ID se a inserção for bem-sucedida
        return ($this->db->affected_rows() > 0) ? $this->db->insert_id() : false;
    }


    // public function update($data, $id)
    // {
    //     // Definir os campos permitidos para atualização
    //     $allowed_fields = [
    //         'nome',
    //         'tipo',
    //         'vat',
    //         'cidade',
    //         'estado',
    //         'status',
    //         'franqueado_id',
    //         'documentType',
    //         'placa',
    //         'renavam',
    //         'marca',
    //         'modelo',
    //         'ano',
    //         'tipo_veiculo',
    //         'capacidade',
    //         'ultima_manutencao',
    //         'proxima_manutencao',
    //         'observacoes',
    //         'nome_motorista',
    //         'cpf_motorista',
    //         'rg_motorista',
    //         'data_nascimento',
    //         'cnh_motorista',
    //         'categoria_cnh',
    //         'validade_cnh',
    //         'mopp',
    //         'endereco_motorista',
    //         'cidade_motorista',
    //         'estado_motorista',
    //         'cep_motorista',
    //         'telefone_motorista',
    //         'celular_motorista',
    //         'email_motorista'
    //     ];

    //     // Filtrar apenas os campos válidos
    //     $insert_data = array_intersect_key($data, array_flip($allowed_fields));

    //     // Garantir que campos opcionais fiquem como NULL se não forem enviados
    //     foreach ($allowed_fields as $field) {
    //         if (!isset($insert_data[$field])) {
    //             $insert_data[$field] = NULL;
    //         }
    //     }

    //     // Verificar se há algo para atualizar
    //     if (empty($update_data)) {
    //         return false;
    //     }

    //     // Garantir que 'tipo' tenha um valor válido se for passado
    //     if (isset($update_data['tipo']) && !in_array($update_data['tipo'], ['terceiro', 'proprio'])) {
    //         return false; // Falha na validação
    //     }

    //     // Atualizar os dados na tabela
    //     $this->db->where('id', $id);
    //     $this->db->update(db_prefix() . 'carriers', $update_data);

    //     // Retornar true se a atualização foi bem-sucedida
    //     return ($this->db->affected_rows() > 0);
    // }

    public function update($data, $id)
    {
        // Definir os campos permitidos para atualização
        $allowed_fields = [
            'nome',
            'tipo',
            'vat',
            'cidade',
            'estado',
            'status',
            'franqueado_id',
            'documentType',
            'placa',
            'renavam',
            'marca',
            'modelo',
            'ano',
            'tipo_veiculo',
            'capacidade',
            'ultima_manutencao',
            'proxima_manutencao',
            'observacoes',
            'nome_motorista',
            'cpf_motorista',
            'rg_motorista',
            'data_nascimento',
            'cnh_motorista',
            'categoria_cnh',
            'validade_cnh',
            'mopp',
            'endereco_motorista',
            'cidade_motorista',
            'estado_motorista',
            'cep_motorista',
            'telefone_motorista',
            'celular_motorista',
            'email_motorista'
        ];

        // Filtrar apenas os campos válidos
        $insert_data = array_intersect_key($data, array_flip($allowed_fields));

        // Garantir que campos opcionais fiquem como NULL se não forem enviados
        foreach ($allowed_fields as $field) {
            if (!isset($insert_data[$field])) {
                $insert_data[$field] = NULL;
            }
        }

        // Verificar se há algo para atualizar
        if (empty($insert_data)) {
            return false;
        }

        // Atualizar os dados na tabela
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'carriers', $insert_data);

        // Retornar true se a atualização foi bem-sucedida
        return ($this->db->affected_rows() > 0);
    }


    public function delete($id)
    {
        // Verificar se o ID existe antes de deletar
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'carriers');

        // Retornar true se a exclusão foi bem-sucedida
        return ($this->db->affected_rows() > 0);
    }



}
