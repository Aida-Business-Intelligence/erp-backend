<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Roles_model extends App_Model
{
    /**
     * Add new employee role
     * @param mixed $data
     */
    // public function add($data)
    // {
    //     $permissions = [];
    //     if (isset($data['permissions'])) {
    //         $permissions = $data['permissions'];
    //     }

    //     $data['permissions'] = serialize($permissions);

    //     $this->db->insert(db_prefix() . 'roles', $data);
    //     $insert_id = $this->db->insert_id();

    //     if ($insert_id) {
    //         log_activity('New Role Added [ID: ' . $insert_id . '.' . $data['name'] . ']');

    //         return $insert_id;
    //     }

    //     return false;
    // }

    public function add($data)
    {
        // Separa as permissões do restante dos dados
// Verifica se já existe um nível extra de "permissions" e remove
        $permissions = isset($data['permissions']['permissions']) ? $data['permissions']['permissions'] : $data['permissions'];

        // Serializa corretamente antes de salvar
        $data['permissions'] = serialize($permissions);


        // Insere os dados na tabela de roles
        $this->db->insert(db_prefix() . 'roles', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Role Added [ID: ' . $insert_id . ', Name: ' . $data['name'] . ']');
            return $insert_id;
        }

        return false;
    }

    /**
     * Update employee role
     * @param  array $data role data
     * @param  mixed $id   role id
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;
        $permissions = [];
        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
        }

        $data['permissions'] = serialize($permissions);

        $update_staff_permissions = false;
        if (isset($data['update_staff_permissions'])) {
            $update_staff_permissions = true;
            unset($data['update_staff_permissions']);
        }

        $this->db->where('roleid', $id);
        $this->db->update(db_prefix() . 'roles', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        if ($update_staff_permissions == true) {
            $this->load->model('staff_model');

            $staff = $this->staff_model->get('', [
                'role' => $id,
            ]);

            foreach ($staff as $member) {
                if ($this->staff_model->update_permissions($permissions, $member['staffid'])) {
                    $affectedRows++;
                }
            }
        }

        if ($affectedRows > 0) {
            log_activity('Role Updated [ID: ' . $id . ', Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Get employee role by id
     * @param  mixed $id Optional role id
     * @return mixed     array if not id passed else object
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {

            $role = $this->app_object_cache->get('role-' . $id);

            if ($role) {
                return $role;
            }

            $this->db->where('roleid', $id);

            $role = $this->db->get(db_prefix() . 'roles')->row();
            $permissions = [];
            if (@$role->permissions) {
                $permissions = !empty($role->permissions) ? unserialize($role->permissions) : [];
                @$role->permissions = $permissions;
            }


            $this->app_object_cache->add('role-' . $id, $role);

            return $role;
        }

        return $this->db->get(db_prefix() . 'roles')->result_array();
    }

    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC')
    {
        if (!is_numeric($id)) {

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('name', $search); // Busca pelo campo 'name'
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $this->db->order_by($sortField, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);
            $data = $this->db->get(db_prefix() . 'roles')->result_array();

            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            if (!empty($search)) {
                // Condições de busca para contar os resultados
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('name', $search); // Busca pelo campo 'name'
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            $total = count($data);

            return ['data' => $data, 'total' => $total]; // Retorne os clientes e o total
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }
    }

    /**
     * Delete employee role
     * @param  mixed $id role id
     * @return mixed
     */
    public function delete($id)
    {
        $current = $this->get($id);

        // Check first if role is used in table
        if (is_reference_in_table('role', db_prefix() . 'staff', $id)) {
            return [
                'referenced' => true,
            ];
        }

        $affectedRows = 0;
        $this->db->where('roleid', $id);
        $this->db->delete(db_prefix() . 'roles');

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        if ($affectedRows > 0) {
            log_activity('Role Deleted [ID: ' . $id);

            return true;
        }

        return false;
    }

    public function get_contact_permissions($id)
    {
        $this->db->where('userid', $id);

        return $this->db->get(db_prefix() . 'contact_permissions')->result_array();
    }

    public function get_role_staff($role_id)
    {
        $this->db->where('role', $role_id);

        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    public function update_role($data, $id)
    {
        // Definir os campos permitidos para atualização
        $allowed_fields = ['name', 'permissions'];

        // Filtrar os dados permitidos
        $update_data = array_intersect_key($data, array_flip($allowed_fields));

        // Verificar se há algo para atualizar
        if (empty($update_data)) {
            return false;
        }

        // Se permissions for um array, converter para o formato correto e serializar
        if (isset($update_data['permissions']) && is_array($update_data['permissions'])) {
            // Serializa as permissões antes de salvar
            $update_data['permissions'] = serialize($update_data['permissions']);
        }

        // Atualizar os dados na tabela
        $this->db->where('roleid', $id);
        $this->db->update(db_prefix() . 'roles', $update_data);

        // Retornar true se a atualização foi bem-sucedida
        return ($this->db->affected_rows() > 0);
    }



}
