<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Gptw_model extends App_Model
{

    private $contact_columns;

    public function __construct()
    {
        parent::__construct();

        //        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'project_emails', 'ticket_emails', 'is_primary']);

        //        $this->load->model(['client_vault_entries_model', 'client_groups_model', 'statement_model']);
    }


    /**
     * Add a new supplier
     * @param array $data Supplier data
     * @return int|bool Supplier ID or false on failure
     */
    public function add_api_search($data)
    {
        //        $data['is_supplier'] = 1;

        $this->db->insert(db_prefix() . 'gptw_search', $data);

        return $this->db->insert_id();
    }

    public function add_api_good($data)
    {
        //        $data['is_supplier'] = 1;

        $this->db->insert(db_prefix() . 'gptw_good', $data);

        return $this->db->insert_id();
    }

    public function add_api_recognition($data)
    {
        //        $data['is_supplier'] = 1;

        $this->db->insert(db_prefix() . 'gptw_recognition', $data);

        return $this->db->insert_id();
    }

    public function add_api_training($data)
    {
        //        $data['is_supplier'] = 1;

        $this->db->insert(db_prefix() . 'gptw_training', $data);

        return $this->db->insert_id();
    }

    public function add_api_feedbacks($data)
    {
        //        $data['is_supplier'] = 1;

        $this->db->insert(db_prefix() . 'gptw_feedbacks', $data);

        return $this->db->insert_id();
    }


    public function update_api_search($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'gptw_search', $data);
        return $this->db->affected_rows() > 0;
    }

    public function update_api_good($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'gptw_good', $data);
        return $this->db->affected_rows() > 0;
    }

    public function update_api_feedbacks($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'gptw_feedbacks', $data);
        return $this->db->affected_rows() > 0;
    }

    public function update_api_recognition($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'gptw_recognition', $data);
        return $this->db->affected_rows() > 0;
    }

    public function update_api_training($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'gptw_training', $data);
        return $this->db->affected_rows() > 0;
    }



    /**
     * Delete a supplier
     * @param int $id Supplier ID
     * @return bool True on success, false on failure
     */
    public function delete_api_search($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'gptw_search');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Deleta um registro na tabela gptw_good pelo ID.
     */
    public function delete_api_good($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'gptw_good');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Deleta um registro na tabela gptw_feedbacks pelo ID.
     */
    public function delete_api_feedbacks($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'gptw_feedbacks');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Deleta um registro na tabela gptw_recognition pelo ID.
     */
    public function delete_api_recognition($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'gptw_recognition');

        return $this->db->affected_rows() > 0;
    }

    /**
     * Deleta um registro na tabela gptw_training pelo ID.
     */
    public function delete_api_training($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'gptw_training');

        return $this->db->affected_rows() > 0;
    }



    public function get($id = '', $where = [])
    {
        //        $select_str = '*,CONCAT(firstname,\' \',lastname) as full_name';

        // Used to prevent multiple queries on logged in staff to check the total unread notifications in core/AdminController.php
//        if (is_staff_logged_in() && $id != '' && $id == get_staff_user_id()) {
//            $select_str .= ',(SELECT COUNT(*) FROM ' . db_prefix() . 'notifications WHERE touserid=' . get_staff_user_id() . ' and isread=0) as total_unread_notifications, (SELECT COUNT(*) FROM ' . db_prefix() . 'todos WHERE finished=0 AND staffid=' . get_staff_user_id() . ') as total_unfinished_todos';
//        }

        //        $this->db->select($select_str);

        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $search = $this->db->get(db_prefix() . 'gptw_search')->row();

            //            if ($search) {
//                $search->permissions = $this->get_staff_permissions($id);
//            }

            return $search;
        }

        $this->db->order_by('id', 'asc');

        return $this->db->get(db_prefix() . 'gptw_search')->result_array();

    }


    public function get_api_search($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC')
    {
        error_reporting(-1);
        ini_set('display_errors', 1);
        if (!is_numeric($id)) {

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search); // Busca pelo campo 'company'
                $this->db->or_like('public', $search);
                //$this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $data = $this->db->get(db_prefix() . 'gptw_search')->result_array();

            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            if (!empty($search)) {
                // Condições de busca para contar os resultados
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search);
                $this->db->or_like('public', $search);
                //                $this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $total = count($data);

            return ['data' => $data, 'total' => $total]; // Retorne os clientes e o total
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }

    }

    public function get_api_recognition($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC')
    {
        error_reporting(-1);
        ini_set('display_errors', 1);
        if (!is_numeric($id)) {

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search); // Busca pelo campo 'company'
                $this->db->or_like('public', $search);
                //$this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $data = $this->db->get(db_prefix() . 'gptw_recognition')->result_array();

            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            if (!empty($search)) {
                // Condições de busca para contar os resultados
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search);
                $this->db->or_like('public', $search);
                //                $this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $total = count($data);

            return ['data' => $data, 'total' => $total]; // Retorne os clientes e o total
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }

    }

    public function get_api_good($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC')
    {
        error_reporting(-1);
        ini_set('display_errors', 1);
        if (!is_numeric($id)) {

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search); // Busca pelo campo 'company'
                $this->db->or_like('public', $search);
                //$this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $data = $this->db->get(db_prefix() . 'gptw_good')->result_array();

            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            if (!empty($search)) {
                // Condições de busca para contar os resultados
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search);
                $this->db->or_like('public', $search);
                //                $this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $total = count($data);

            return ['data' => $data, 'total' => $total]; // Retorne os clientes e o total
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }

    }

    public function get_api_training($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC')
    {
        error_reporting(-1);
        ini_set('display_errors', 1);
        if (!is_numeric($id)) {

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search); // Busca pelo campo 'company'
                $this->db->or_like('public', $search);
                //$this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $data = $this->db->get(db_prefix() . 'gptw_training')->result_array();

            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            if (!empty($search)) {
                // Condições de busca para contar os resultados
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search);
                $this->db->or_like('public', $search);
                //                $this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $total = count($data);

            return ['data' => $data, 'total' => $total]; // Retorne os clientes e o total
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }

    }
    public function get_api_feedbacks($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC')
    {
        error_reporting(-1);
        ini_set('display_errors', 1);
        if (!is_numeric($id)) {

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search); // Busca pelo campo 'company'
                $this->db->or_like('public', $search);
                //$this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $data = $this->db->get(db_prefix() . 'gptw_feedbacks')->result_array();

            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            if (!empty($search)) {
                // Condições de busca para contar os resultados
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('tittle', $search);
                $this->db->or_like('public', $search);
                //                $this->db->or_like('email', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $total = count($data);

            return ['data' => $data, 'total' => $total]; // Retorne os clientes e o total
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }

    }
}
