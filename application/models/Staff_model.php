<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Staff_model extends App_Model
{
    public function delete($id, $transfer_data_to)
    {
        if (!is_numeric($transfer_data_to)) {
            return false;
        }

        if ($id == $transfer_data_to) {
            return false;
        }

        hooks()->do_action('before_delete_staff_member', [
            'id' => $id,
            'transfer_data_to' => $transfer_data_to,
        ]);

        $name = get_staff_full_name($id);
        $transferred_to = get_staff_full_name($transfer_data_to);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'estimates', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('sale_agent', $id);
        $this->db->update(db_prefix() . 'estimates', [
            'sale_agent' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'invoices', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('sale_agent', $id);
        $this->db->update(db_prefix() . 'invoices', [
            'sale_agent' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'expenses', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'notes', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('userid', $id);
        $this->db->update(db_prefix() . 'newsfeed_post_comments', [
            'userid' => $transfer_data_to,
        ]);

        $this->db->where('creator', $id);
        $this->db->update(db_prefix() . 'newsfeed_posts', [
            'creator' => $transfer_data_to,
        ]);

        $this->db->where('staff_id', $id);
        $this->db->update(db_prefix() . 'projectdiscussions', [
            'staff_id' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'projects', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'creditnotes', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('staff_id', $id);
        $this->db->update(db_prefix() . 'credits', [
            'staff_id' => $transfer_data_to,
        ]);

        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'project_files', [
            'staffid' => $transfer_data_to,
        ]);

        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'proposal_comments', [
            'staffid' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'proposals', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'templates', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'task_comments', [
            'staffid' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->where('is_added_from_contact', 0);
        $this->db->update(db_prefix() . 'tasks', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'files', [
            'staffid' => $transfer_data_to,
        ]);

        $this->db->where('renewed_by_staff_id', $id);
        $this->db->update(db_prefix() . 'contract_renewals', [
            'renewed_by_staff_id' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'task_checklist_items', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('assigned', $id);
        $this->db->update(db_prefix() . 'task_checklist_items', [
            'assigned' => $transfer_data_to,
        ]);

        $this->db->where('finished_from', $id);
        $this->db->update(db_prefix() . 'task_checklist_items', [
            'finished_from' => $transfer_data_to,
        ]);

        $this->db->where('admin', $id);
        $this->db->update(db_prefix() . 'ticket_replies', [
            'admin' => $transfer_data_to,
        ]);

        $this->db->where('admin', $id);
        $this->db->update(db_prefix() . 'tickets', [
            'admin' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'leads', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('assigned', $id);
        $this->db->update(db_prefix() . 'leads', [
            'assigned' => $transfer_data_to,
        ]);

        $this->db->where('staff_id', $id);
        $this->db->update(db_prefix() . 'taskstimers', [
            'staff_id' => $transfer_data_to,
        ]);

        $this->db->where('addedfrom', $id);
        $this->db->update(db_prefix() . 'contracts', [
            'addedfrom' => $transfer_data_to,
        ]);

        $this->db->where('assigned_from', $id);
        $this->db->where('is_assigned_from_contact', 0);
        $this->db->update(db_prefix() . 'task_assigned', [
            'assigned_from' => $transfer_data_to,
        ]);

        $this->db->where('responsible', $id);
        $this->db->update(db_prefix() . 'leads_email_integration', [
            'responsible' => $transfer_data_to,
        ]);

        $this->db->where('responsible', $id);
        $this->db->update(db_prefix() . 'web_to_lead', [
            'responsible' => $transfer_data_to,
        ]);

        $this->db->where('responsible', $id);
        $this->db->update(db_prefix() . 'estimate_request_forms', [
            'responsible' => $transfer_data_to,
        ]);

        $this->db->where('assigned', $id);
        $this->db->update(db_prefix() . 'estimate_requests', [
            'assigned' => $transfer_data_to,
        ]);

        $this->db->where('created_from', $id);
        $this->db->update(db_prefix() . 'subscriptions', [
            'created_from' => $transfer_data_to,
        ]);

        $this->db->where('notify_type', 'specific_staff');
        $web_to_lead = $this->db->get(db_prefix() . 'web_to_lead')->result_array();

        foreach ($web_to_lead as $form) {
            if (!empty($form['notify_ids'])) {
                $staff = unserialize($form['notify_ids']);
                if (is_array($staff) && in_array($id, $staff) && ($key = array_search($id, $staff)) !== false) {
                    unset($staff[$key]);
                    $staff = serialize(array_values($staff));
                    $this->db->where('id', $form['id']);
                    $this->db->update(db_prefix() . 'web_to_lead', [
                        'notify_ids' => $staff,
                    ]);
                }
            }
        }

        $this->db->where('notify_type', 'specific_staff');
        $estimate_requests = $this->db->get(db_prefix() . 'estimate_request_forms')->result_array();

        foreach ($estimate_requests as $form) {
            if (!empty($form['notify_ids'])) {
                $staff = unserialize($form['notify_ids']);
                if (is_array($staff) && in_array($id, $staff) && ($key = array_search($id, $staff)) !== false) {
                    unset($staff[$key]);
                    $staff = serialize(array_values($staff));
                    $this->db->where('id', $form['id']);
                    $this->db->update(db_prefix() . 'estimate_request_forms', [
                        'notify_ids' => $staff,
                    ]);
                }
            }
        }


        $this->db->where('id', 1);
        $leads_email_integration = $this->db->get(db_prefix() . 'leads_email_integration')->row();

        if ($leads_email_integration->notify_type == 'specific_staff') {
            if (!empty($leads_email_integration->notify_ids)) {
                $staff = unserialize($leads_email_integration->notify_ids);
                if (is_array($staff) && in_array($id, $staff) && ($key = array_search($id, $staff)) !== false) {
                    unset($staff[$key]);
                    $staff = serialize(array_values($staff));
                    $this->db->where('id', 1);
                    $this->db->update(db_prefix() . 'leads_email_integration', [
                        'notify_ids' => $staff,
                    ]);
                }
            }
        }

        $this->db->where('assigned', $id);
        $this->db->update(db_prefix() . 'tickets', [
            'assigned' => 0,
        ]);

        $this->db->where('staff', 1);
        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'dismissed_announcements');

        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'newsfeed_comment_likes');

        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'newsfeed_post_likes');

        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'customer_admins');

        $this->db->where('fieldto', 'staff');
        $this->db->where('relid', $id);
        $this->db->delete(db_prefix() . 'customfieldsvalues');

        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'events');

        $this->db->where('touserid', $id);
        $this->db->delete(db_prefix() . 'notifications');

        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'user_meta');

        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'project_members');

        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'project_notes');

        $this->db->where('creator', $id);
        $this->db->or_where('staff', $id);
        $this->db->delete(db_prefix() . 'reminders');

        $this->db->where('staffid', $id);
        $this->db->delete(db_prefix() . 'staff_departments');

        $this->db->where('staffid', $id);
        $this->db->delete(db_prefix() . 'todos');

        $this->db->where('staff', 1);
        $this->db->where('user_id', $id);
        $this->db->delete(db_prefix() . 'user_auto_login');

        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'staff_permissions');

        $this->db->where('staffid', $id);
        $this->db->delete(db_prefix() . 'task_assigned');

        $this->db->where('staffid', $id);
        $this->db->delete(db_prefix() . 'task_followers');

        $this->db->where('staff_id', $id);
        $this->db->delete(db_prefix() . 'pinned_projects');

        $this->db->where('staffid', $id);
        $this->db->delete(db_prefix() . 'staff');
        log_activity('Staff Member Deleted [Name: ' . $name . ', Data Transferred To: ' . $transferred_to . ']');

        hooks()->do_action('staff_member_deleted', [
            'id' => $id,
            'transfer_data_to' => $transfer_data_to,
        ]);

        return true;
    }

    /**
     * Get staff member/s
     * @param  mixed $id Optional - staff id
     * @param  mixed $where where in query
     * @return mixed if id is passed return object else array
     */
    public function get($id = '', $where = [])
    {
        $select_str = '*,CONCAT(firstname,\' \',lastname) as full_name';

        // Adiciona campos do contrato se for uma consulta por ID
        if (is_numeric($id)) {
            $select_str .= ',(SELECT royalties FROM ' . db_prefix() . 'contracts WHERE id = ' . db_prefix() . 'staff.contractid) as royalties';
            $select_str .= ',(SELECT datestart FROM ' . db_prefix() . 'contracts WHERE id = ' . db_prefix() . 'staff.contractid) as datestart';
            $select_str .= ',(SELECT dateend FROM ' . db_prefix() . 'contracts WHERE id = ' . db_prefix() . 'staff.contractid) as dateend';
            // $select_str .= ',(SELECT duration_years FROM ' . db_prefix() . 'contracts WHERE id = ' . db_prefix() . 'staff.contractid) as duration_years';
        }

        // Código existente para notificações
        if (is_staff_logged_in() && $id != '' && $id == get_staff_user_id()) {
            $select_str .= ',(SELECT COUNT(*) FROM ' . db_prefix() . 'notifications WHERE touserid=' . get_staff_user_id() . ' and isread=0) as total_unread_notifications, (SELECT COUNT(*) FROM ' . db_prefix() . 'todos WHERE finished=0 AND staffid=' . get_staff_user_id() . ') as total_unfinished_todos';
        }

        $this->db->select($select_str);
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where('staffid', $id);
            $staff = $this->db->get(db_prefix() . 'staff')->row();

            if ($staff) {
                $staff->permissions = $this->get_staff_permissions($id);
            }

            return $staff;
        }

        $this->db->order_by('firstname', 'desc');
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }


    /**
     * Get staff permissions
     * @param  mixed $id staff id
     * @return array
     */
    public function get_staff_permissions($id)
    {
        // Fix for version 2.3.1 tables upgrade
        if (defined('DOING_DATABASE_UPGRADE')) {
            return [];
        }

        $permissions = $this->app_object_cache->get('staff-' . $id . '-permissions');

        if (!$permissions && !is_array($permissions)) {
            $this->db->where('staff_id', $id);
            $permissions = $this->db->get('staff_permissions')->result_array();

            $this->app_object_cache->add('staff-' . $id . '-permissions', $permissions);
        }

        return $permissions;
    }

    /**
     * Add new staff member
     * @param array $data staff $_POST data
     */
    public function add($data)
    {
        // Remover campos temporários se existirem
        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        // Aplicar filtros antes da criação
        $data = hooks()->apply_filters('before_create_staff_member', $data);

        // Verificar se email já existe
        $this->db->where('email', $data['email']);
        $email = $this->db->get(db_prefix() . 'staff')->row();
        if ($email) {
            return false; // Email já existe
        }

        // Configurações padrão para franqueados
        $data['admin'] = 0;
        $data['is_not_staff'] = 1; // Importante para franqueados
        $data['active'] = 1;
        $data['datecreated'] = date('Y-m-d H:i:s');

        // Tratar senha (se não for fornecida, cria uma temporária)
        if (!isset($data['password']) || empty($data['password'])) {
            $data['password'] = app_hash_password('temp_' . time());
        } else {
            $data['password'] = app_hash_password($data['password']);
        }

        // Salvar departamentos e permissões separadamente se existirem
        $departments = $data['departments'] ?? null;
        $permissions = $data['permissions'] ?? null;
        $custom_fields = $data['custom_fields'] ?? null;

        unset($data['departments'], $data['permissions'], $data['custom_fields']);

        // Inserir no banco de dados
        $this->db->insert(db_prefix() . 'staff', $data);
        $staffid = $this->db->insert_id();

        if (!$staffid) {
            return false;
        }

        // Criar slug para o usuário
        $slug = trim($data['firstname'] . ' ' . $data['lastname']);
        if (empty($slug)) {
            $slug = 'franqueado-' . $staffid;
        }

        $this->db->where('staffid', $staffid);
        $this->db->update(db_prefix() . 'staff', [
            'media_path_slug' => slug_it($slug)
        ]);

        // Processar departamentos
        if ($departments && is_array($departments)) {
            foreach ($departments as $department) {
                $this->db->insert(db_prefix() . 'staff_departments', [
                    'staffid' => $staffid,
                    'departmentid' => $department,
                ]);
            }
        }

        // Processar permissões
        $this->update_permissions($permissions ?? [], $staffid);

        // Processar campos customizados
        if ($custom_fields) {
            handle_custom_fields_post($staffid, $custom_fields);
        }

        // Registrar atividade
        log_activity('Novo Franqueado Adicionado [ID: ' . $staffid . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']');

        // Disparar evento de criação
        hooks()->do_action('staff_member_created', $staffid);

        return $staffid; // Retorna o ID numérico do novo registro
    }

    /**
     * Update staff member info
     * @param  array $data staff data
     * @param  mixed $id   staff id
     * @return boolean
     */
    public function update($data, $id)
    {

        unset($data['department']);
        unset($data['designation']);



        if (isset($data['fakeusernameremembered'])) {
            unset($data['fakeusernameremembered']);
        }
        if (isset($data['fakepasswordremembered'])) {
            unset($data['fakepasswordremembered']);
        }

        $data = hooks()->apply_filters('before_update_staff_member', $data, $id);



        if (is_admin()) {
            if (isset($data['administrator'])) {
                $data['admin'] = 1;
                unset($data['administrator']);
            } else {
                if ($id != get_staff_user_id()) {
                    if ($id == 1) {
                        return [
                            'cant_remove_main_admin' => true,
                        ];
                    }
                } else {
                    return [
                        'cant_remove_yourself_from_admin' => true,
                    ];
                }
                $data['admin'] = 0;
            }
        }

        $affectedRows = 0;
        if (isset($data['departments'])) {
            $departments = $data['departments'];
            unset($data['departments']);
        }

        $permissions = [];
        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = app_hash_password($data['password']);
            $data['last_password_change'] = date('Y-m-d H:i:s');
        }


        // if (isset($data['two_factor_auth_enabled'])) {
        //     $data['two_factor_auth_enabled'] = 1;
        // } else {
        //     $data['two_factor_auth_enabled'] = 0;
        // }

        if (isset($data['is_not_staff'])) {
            $data['is_not_staff'] = 1;
        } else {
            $data['is_not_staff'] = 0;
        }

        if (isset($data['admin']) && $data['admin'] == 1) {
            $data['is_not_staff'] = 0;
        }

        $this->load->model('departments_model');
        $staff_departments = $this->departments_model->get_staff_departments($id);
        if (sizeof($staff_departments) > 0) {
            if (!isset($data['departments'])) {
                $this->db->where('staffid', $id);
                $this->db->delete(db_prefix() . 'staff_departments');
            } else {
                foreach ($staff_departments as $staff_department) {
                    if (isset($departments)) {
                        if (!in_array($staff_department['departmentid'], $departments)) {
                            $this->db->where('staffid', $id);
                            $this->db->where('departmentid', $staff_department['departmentid']);
                            $this->db->delete(db_prefix() . 'staff_departments');
                            if ($this->db->affected_rows() > 0) {
                                $affectedRows++;
                            }
                        }
                    }
                }
            }
            if (isset($departments)) {
                foreach ($departments as $department) {
                    $this->db->where('staffid', $id);
                    $this->db->where('departmentid', $department);
                    $_exists = $this->db->get(db_prefix() . 'staff_departments')->row();
                    if (!$_exists) {
                        $this->db->insert(db_prefix() . 'staff_departments', [
                            'staffid' => $id,
                            'departmentid' => $department,
                        ]);
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
            }
        } else {
            if (isset($departments)) {
                foreach ($departments as $department) {
                    $this->db->insert(db_prefix() . 'staff_departments', [
                        'staffid' => $id,
                        'departmentid' => $department,
                    ]);
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
        }


        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'staff', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }

        if ($this->update_permissions((isset($data['admin']) && $data['admin'] == 1 ? [] : $permissions), $id)) {
            $affectedRows++;
        }

        if ($affectedRows > 0) {
            hooks()->do_action('staff_member_updated', $id);
            log_activity('Staff Member Updated [ID: ' . $id . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']');

            return true;
        }

        return false;
    }

    public function update_permissions($permissions, $id)
    {
        $this->db->where('staff_id', $id);
        $this->db->delete('staff_permissions');

        $is_staff_member = is_staff_member($id);

        foreach ($permissions as $feature => $capabilities) {
            foreach ($capabilities as $capability) {

                // Maybe do this via hook.
                if ($feature == 'leads' && !$is_staff_member) {
                    continue;
                }

                $this->db->insert('staff_permissions', ['staff_id' => $id, 'feature' => $feature, 'capability' => $capability]);
            }
        }

        return true;
    }

    public function update_profile($data, $id)
    {
        $data = hooks()->apply_filters('before_staff_update_profile', $data, $id);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = app_hash_password($data['password']);
            $data['last_password_change'] = date('Y-m-d H:i:s');
        }

        unset($data['two_factor_auth_enabled']);
        unset($data['google_auth_secret']);


        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'staff', $data);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('staff_member_profile_updated', $id);
            log_activity('Staff Profile Updated [Staff: ' . get_staff_full_name($id) . ']');

            return true;
        }

        return false;
    }

    /**
     * Change staff passwordn
     * @param  mixed $data   password data
     * @param  mixed $userid staff id
     * @return mixed
     */
    public function change_password($data, $userid)
    {
        $data = hooks()->apply_filters('before_staff_change_password', $data, $userid);

        $member = $this->get($userid);
        // CHeck if member is active
        if ($member->active == 0) {
            return [
                [
                    'memberinactive' => true,
                ],
            ];
        }

        // Check new old password
        if (!app_hasher()->CheckPassword($data['oldpassword'], $member->password)) {
            return [
                [
                    'passwordnotmatch' => true,
                ],
            ];
        }

        $data['newpasswordr'] = app_hash_password($data['newpasswordr']);

        $this->db->where('staffid', $userid);
        $this->db->update(db_prefix() . 'staff', [
            'password' => $data['newpasswordr'],
            'last_password_change' => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->affected_rows() > 0) {
            log_activity('Staff Password Changed [' . $userid . ']');

            return true;
        }

        return false;
    }

    /**
     * Change staff status / active / inactive
     * @param  mixed $id     staff id
     * @param  mixed $status status(0/1)
     */
    public function change_staff_status($id, $status)
    {
        $status = hooks()->apply_filters('before_staff_status_change', $status, $id);

        $this->db->where('staffid', $id);
        $this->db->update(db_prefix() . 'staff', [
            'active' => $status,
        ]);

        log_activity('Staff Status Changed [StaffID: ' . $id . ' - Status(Active/Inactive): ' . $status . ']');
    }

    public function get_logged_time_data($id = '', $filter_data = [])
    {
        if ($id == '') {
            $id = get_staff_user_id();
        }
        $result['timesheets'] = [];
        $result['total'] = [];
        $result['this_month'] = [];

        $first_day_this_month = date('Y-m-01'); // hard-coded '01' for first day
        $last_day_this_month = date('Y-m-t 23:59:59');

        $result['last_month'] = [];
        $first_day_last_month = date('Y-m-01', strtotime('-1 MONTH')); // hard-coded '01' for first day
        $last_day_last_month = date('Y-m-t 23:59:59', strtotime('-1 MONTH'));

        $result['this_week'] = [];
        $first_day_this_week = date('Y-m-d', strtotime('monday this week'));
        $last_day_this_week = date('Y-m-d 23:59:59', strtotime('sunday this week'));

        $result['last_week'] = [];

        $first_day_last_week = date('Y-m-d', strtotime('monday last week'));
        $last_day_last_week = date('Y-m-d 23:59:59', strtotime('sunday last week'));

        $this->db->select('task_id,start_time,end_time,staff_id,' . db_prefix() . 'taskstimers.hourly_rate,name,' . db_prefix() . 'taskstimers.id,rel_id,rel_type, billed');
        $this->db->where('staff_id', $id);
        $this->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id', 'left');
        $timers = $this->db->get(db_prefix() . 'taskstimers')->result_array();
        $_end_time_static = time();

        $filter_period = false;
        if (isset($filter_data['period-from']) && $filter_data['period-from'] != '' && isset($filter_data['period-to']) && $filter_data['period-to'] != '') {
            $filter_period = true;
            $from = to_sql_date($filter_data['period-from']);
            $from = date('Y-m-d', strtotime($from));
            $to = to_sql_date($filter_data['period-to']);
            $to = date('Y-m-d', strtotime($to));
        }

        foreach ($timers as $timer) {
            $start_date = date('Y-m-d', $timer['start_time']);

            $end_time = $timer['end_time'];
            $notFinished = false;
            if ($timer['end_time'] == null) {
                $end_time = $_end_time_static;
                $notFinished = true;
            }

            $total = $end_time - $timer['start_time'];

            $result['total'][] = $total;
            $timer['total'] = $total;
            $timer['end_time'] = $end_time;
            $timer['not_finished'] = $notFinished;

            if ($start_date >= $first_day_this_month && $start_date <= $last_day_this_month) {
                $result['this_month'][] = $total;
                if (isset($filter_data['this_month']) && $filter_data['this_month'] != '') {
                    $result['timesheets'][$timer['id']] = $timer;
                }
            }
            if ($start_date >= $first_day_last_month && $start_date <= $last_day_last_month) {
                $result['last_month'][] = $total;
                if (isset($filter_data['last_month']) && $filter_data['last_month'] != '') {
                    $result['timesheets'][$timer['id']] = $timer;
                }
            }
            if ($start_date >= $first_day_this_week && $start_date <= $last_day_this_week) {
                $result['this_week'][] = $total;
                if (isset($filter_data['this_week']) && $filter_data['this_week'] != '') {
                    $result['timesheets'][$timer['id']] = $timer;
                }
            }
            if ($start_date >= $first_day_last_week && $start_date <= $last_day_last_week) {
                $result['last_week'][] = $total;
                if (isset($filter_data['last_week']) && $filter_data['last_week'] != '') {
                    $result['timesheets'][$timer['id']] = $timer;
                }
            }

            if ($filter_period == true) {
                if ($start_date >= $from && $start_date <= $to) {
                    $result['timesheets'][$timer['id']] = $timer;
                }
            }
        }
        $result['total'] = array_sum($result['total']);
        $result['this_month'] = array_sum($result['this_month']);
        $result['last_month'] = array_sum($result['last_month']);
        $result['this_week'] = array_sum($result['this_week']);
        $result['last_week'] = array_sum($result['last_week']);

        return $result;
    }

    /*** API *///

    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC', $type = 'employee')
    {
        $this->load->model("roles_model");

        if (!is_numeric($id)) {
            // Aplicar filtro pelo tipo
            if (!empty($type)) {
                $this->db->where('type', $type);
            }

            // Filtro para pegar apenas os registros com active = 1
            $this->db->where('active', 1);

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('firstname', $search); // Busca pelo campo 'firstname'
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            // Contagem total de registros sem paginação
            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
            $this->db->where('active', 1); // Garante que o filtro também seja aplicado na contagem
            if (!empty($search)) {
                $this->db->group_start(); // Começa um agrupamento de condição
                $this->db->like('firstname', $search);
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->group_end(); // Fecha o agrupamento de condição
            }

            // Contar o total de registros sem limitação
            $total = $this->db->count_all_results(db_prefix() . 'staff');

            // Obter os dados com paginação
            $this->db->reset_query(); // Resetar consulta novamente antes de buscar os dados
            $this->db->where('active', 1); // Aplica o filtro 'active = 1' na busca de dados
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('firstname', $search);
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->group_end();
            }

            $this->db->order_by($sortField, $sortOrder);
            $offset = ($page - 1) * $limit;  // Calcula o offset corretamente
            $this->db->limit($limit, $offset); // Agora passa o offset corretamente
            $data = $this->db->get(db_prefix() . 'staff')->result_array();

            // Adicionar o nome do cargo (role) de cada staff
            $staff['role'] = '';
            foreach ($data as $key => $staff) {
                if ($staff['role'] > 0) {
                    $role = $this->roles_model->get($staff['role']); // Busca o nome do role

                    $data[$key]['role_name'] = $role->name;
                }
            }

            // Retornar os dados com o total correto
            return ['data' => $data, 'total' => $total];
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }
    }


    // public function get_api2($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC', $type = 'representative', $warehouse_id = 0, $franqueado_id = 0)
    // {
    //     $this->load->model("roles_model");

    //     if (!is_numeric($id)) {
    //         // Aplicar filtro pelo tipo
    //         if (!empty($type)) {
    //             $this->db->where('type', $type);
    //         }

    //         $this->db->select('tblstaff.*, tblcontracts.contract_name, tblcontracts.contract_url, tblcontracts.preview_contract');
    //         $this->db->from('tblstaff'); // Define a tabela principal como tblcontracts
    //         $this->db->join('tblcontracts', 'tblcontracts.id = tblstaff.contractid', 'left'); // JOIN com tblstaff
    //         // $this->db->where('contracts.franqueado_id', value: $franqueado_id);

    //         // Aplicar filtro pelo warehouse_id
    //         if (!empty($warehouse_id)) {
    //             $this->db->where('warehouse_id', $warehouse_id);
    //         }

    //         // Adicionar condições de busca
    //         if (!empty($search)) {
    //             $this->db->group_start(); // Começa um agrupamento de condição
    //             $this->db->like('firstname', $search);
    //             $this->db->or_like('lastname', $search);
    //             $this->db->or_like('email', $search);
    //             $this->db->or_like('phonenumber', $search);
    //             $this->db->or_like('vat', $search);
    //             $this->db->group_end(); // Fecha o agrupamento de condição
    //         }

    //         // Contagem total de registros sem paginação
    //         $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
    //         if (!empty($type)) {
    //             $this->db->where('type', $type);
    //         }
    //         if (!empty($warehouse_id)) {
    //             $this->db->where('warehouse_id', $warehouse_id);
    //         }
    //         if (!empty($search)) {
    //             $this->db->group_start();
    //             $this->db->like('firstname', $search);
    //             $this->db->or_like('lastname', $search);
    //             $this->db->or_like('email', $search);
    //             $this->db->or_like('phonenumber', $search);
    //             $this->db->or_like('vat', $search);
    //             $this->db->group_end();
    //         }

    //         // Contar o total de registros sem limitação
    //         $total = $this->db->count_all_results(db_prefix() . 'staff');

    //         // Obter os dados com paginação
    //         $this->db->reset_query(); // Resetar consulta novamente antes de buscar os dados
    //         if (!empty($type)) {
    //             $this->db->where('type', $type);
    //         }
    //         if (!empty($warehouse_id)) {
    //             $this->db->where('warehouse_id', $warehouse_id);
    //         }
    //         if (!empty($search)) {
    //             $this->db->group_start();
    //             $this->db->like('firstname', $search);
    //             $this->db->or_like('lastname', $search);
    //             $this->db->or_like('email', $search);
    //             $this->db->or_like('phonenumber', $search);
    //             $this->db->or_like('vat', $search);
    //             $this->db->group_end();
    //         }

    //         // JOIN com a tabela tblroles para obter o nome do cargo
    //         $this->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');

    //         // Aplica a ordenação
    //         if ($sortField === 'firstname') {
    //             // Ordenação por firstname, tratando valores nulos ou vazios
    //             $this->db->order_by("CASE WHEN firstname IS NULL OR firstname = '' THEN 1 ELSE 0 END, firstname", $sortOrder);
    //         } elseif ($sortField === 'role_name') {
    //             // Ordenação pelo nome do cargo (tblroles.name)
    //             $this->db->order_by(db_prefix() . 'roles.name', $sortOrder);
    //         } else {
    //             // Ordenação padrão
    //             $this->db->order_by($sortField, $sortOrder);
    //         }

    //         $offset = ($page - 1) * $limit;
    //         $this->db->limit($limit, $offset);
    //         $data = $this->db->get(db_prefix() . 'staff')->result_array();

    //         // Adicionar o nome do cargo (role) de cada staff
    //         foreach ($data as $key => $staff) {
    //             if ($staff['role'] > 0) {
    //                 $role = $this->roles_model->get($staff['role']);
    //                 $data[$key]['role_name'] = $role->name;
    //             }
    //         }

    //         return ['data' => $data, 'total' => $total];
    //     } else {
    //         return ['data' => (array) $this->get($id), 'total' => 1];
    //     }
    // }

    public function get_api2($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC', $type = 'representative', $warehouse_id = 0)
    {
        $this->load->model("roles_model");

        if (!is_numeric($id)) {
            // Contagem total de registros sem paginação
            $this->db->from(db_prefix() . 'staff');

            // Aplicar filtro pelo tipo
            if (!empty($type)) {
                $this->db->where('type', $type);
            }

            // Aplicar filtro pelo warehouse_id
            if (!empty($warehouse_id)) {
                $this->db->where('warehouse_id', $warehouse_id);
            }

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('firstname', $search);
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->or_like('vat', $search);
                $this->db->group_end();
            }

            $total = $this->db->count_all_results();

            // Obter os dados com paginação
            $this->db->select([
                db_prefix() . 'staff.*',
                db_prefix() . 'contracts.id as contract_id',
                db_prefix() . 'contracts.contract_name',
                db_prefix() . 'contracts.contract_url',
                db_prefix() . 'contracts.preview_contract',
                db_prefix() . 'contracts.royalties',
                db_prefix() . 'contracts.datestart',
                db_prefix() . 'contracts.datestart',
                db_prefix() . 'contracts.dateend',
                db_prefix() . 'roles.name as role_name'
            ]);

            $this->db->from(db_prefix() . 'staff');

            // JOIN com tblcontracts (LEFT JOIN pois nem todo staff pode ter contrato)
            $this->db->join(db_prefix() . 'contracts', db_prefix() . 'contracts.id = ' . db_prefix() . 'staff.contractid', 'left');

            // JOIN com tblroles
            $this->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');

            // Aplicar filtros novamente
            if (!empty($type)) {
                $this->db->where(db_prefix() . 'staff.type', $type);
            }

            // if (!empty($warehouse_id)) {
            //     $this->db->where('warehouse_id', $warehouse_id);
            // }

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('firstname', $search);
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->or_like('vat', $search);
                $this->db->group_end();
            }

            // Aplica a ordenação
            if ($sortField === 'firstname') {
                $this->db->order_by("CASE WHEN " . db_prefix() . "staff.firstname IS NULL OR " . db_prefix() . "staff.firstname = '' THEN 1 ELSE 0 END, " . db_prefix() . "staff.firstname", $sortOrder);
            } elseif ($sortField === 'role_name') {
                $this->db->order_by(db_prefix() . 'roles.name', $sortOrder);
            } else {
                $this->db->order_by(db_prefix() . 'staff.' . $sortField, $sortOrder);
            }

            $offset = ($page - 1) * $limit;
            $this->db->limit($limit, $offset);

            $data = $this->db->get()->result_array();

            return ['data' => $data, 'total' => $total];
        } else {
            // Caso seja busca por ID único
            $this->db->select([
                db_prefix() . 'staff.*',
                db_prefix() . 'contracts.id as contract_id',
                db_prefix() . 'contracts.contract_name',
                db_prefix() . 'contracts.contract_url',
                db_prefix() . 'contracts.preview_contract',
                db_prefix() . 'contracts.royalties',
                db_prefix() . 'contracts.datestart',
                db_prefix() . 'contracts.datestart',
                db_prefix() . 'contracts.dateend',
            ]);
            $this->db->from(db_prefix() . 'staff');
            $this->db->join(db_prefix() . 'contracts', db_prefix() . 'contracts.id = ' . db_prefix() . 'staff.contractid', 'left');
            $this->db->where(db_prefix() . 'staff.staffid', $id);

            $staff = $this->db->get()->row();

            if ($staff && $staff->role > 0) {
                $role = $this->roles_model->get($staff->role);
                $staff->role_name = $role->name;
            }

            return ['data' => (array) $staff, 'total' => 1];
        }
    }

    public function get_api3($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'staffid', $sortOrder = 'ASC', $type = 'pdv')
    {
        $this->load->model("roles_model");

        if (!is_numeric($id)) {
            // Aplicar filtro pelo tipo
            if (!empty($type)) {
                $this->db->where('type', $type); // Filtra por type = "pdv"
            }

            // Filtro para pegar apenas os registros com active = 1
            $this->db->where('active', 1);

            // Adicionar condições de busca
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('firstname', $search);
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->group_end();
            }

            // JOIN com a tabela tblroles para obter o nome do cargo
            $this->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');

            // Contagem total de registros sem paginação
            $total = $this->db->count_all_results(db_prefix() . 'staff');

            // Obter os dados com paginação e ordenação
            $this->db->reset_query();
            $this->db->where('active', 1);
            if (!empty($type)) {
                $this->db->where('type', $type); // Filtra por type = "pdv"
            }
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('firstname', $search);
                $this->db->or_like('lastname', $search);
                $this->db->or_like('email', $search);
                $this->db->or_like('phonenumber', $search);
                $this->db->group_end();
            }

            // JOIN com a tabela tblroles para obter o nome do cargo
            $this->db->join(db_prefix() . 'roles', db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role', 'left');

            // Aplica a ordenação
            if ($sortField === 'firstname') {
                // Ordenação por firstname, tratando valores nulos ou vazios
                $this->db->order_by("CASE WHEN firstname IS NULL OR firstname = '' THEN 1 ELSE 0 END, firstname", $sortOrder);
            } elseif ($sortField === 'role_name') {
                // Ordenação pelo nome do cargo (tblroles.name)
                $this->db->order_by(db_prefix() . 'roles.name', $sortOrder);
            } else {
                // Ordenação padrão
                $this->db->order_by($sortField, $sortOrder);
            }

            $offset = ($page - 1) * $limit;
            $this->db->limit($limit, $offset);
            $data = $this->db->get(db_prefix() . 'staff')->result_array();

            // Adicionar o nome do cargo (role) de cada staff
            foreach ($data as $key => $staff) {
                if ($staff['role'] > 0) {
                    $role = $this->roles_model->get($staff['role']);
                    $data[$key]['role_name'] = $role->name;
                }
            }

            return ['data' => $data, 'total' => $total];
        } else {
            return ['data' => (array) $this->get($id), 'total' => 1];
        }
    }

    // Método para atualizar o campo 'active' de múltiplos usuários
    public function update_active($staffids, $active)
    {
        $this->db->where_in('staffid', $staffids);
        $this->db->set('active', $active);
        return $this->db->update('tblstaff');
    }

    public function get_with_contract($id)
    {
        $this->db->select('*,
            (SELECT contract_url FROM ' . db_prefix() . 'contracts WHERE staffid = ' . db_prefix() . 'staff.staffid LIMIT 1) as contract_url,
            (SELECT datestart FROM ' . db_prefix() . 'contracts WHERE staffid = ' . db_prefix() . 'staff.staffid LIMIT 1) as datestart,
            (SELECT dateend FROM ' . db_prefix() . 'contracts WHERE staffid = ' . db_prefix() . 'staff.staffid LIMIT 1) as dateend,
            (SELECT royalties FROM ' . db_prefix() . 'contracts WHERE staffid = ' . db_prefix() . 'staff.staffid LIMIT 1) as royalties,
            (SELECT contract_value FROM ' . db_prefix() . 'contracts WHERE staffid = ' . db_prefix() . 'staff.staffid LIMIT 1) as contract_value
        ');
        $this->db->where('staffid', $id);
        return $this->db->get(db_prefix() . 'staff')->row();
    }

}
