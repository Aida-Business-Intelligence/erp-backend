<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Clients_model extends App_Model
{

    private $contact_columns;

    public function __construct()
    {
        parent::__construct();

        $this->contact_columns = hooks()->apply_filters('contact_columns', ['firstname', 'lastname', 'email', 'phonenumber', 'title', 'password', 'send_set_password_email', 'donotsendwelcomeemail', 'permissions', 'direction', 'invoice_emails', 'estimate_emails', 'credit_note_emails', 'contract_emails', 'task_emails', 'project_emails', 'ticket_emails', 'is_primary']);

        $this->load->model(['client_vault_entries_model', 'client_groups_model', 'statement_model']);
    }

    /**
     * Get client object based on passed clientid if not passed clientid return array of all clients
     * @param  mixed $id    client id
     * @param  array  $where
     * @return mixed
     */

    public function get_supplier($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC')
    {
        // Base query to get only suppliers
        $this->db->where('is_supplier', 1);

        if (!is_numeric($id)) {
            // Add search conditions
            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('company', $search);
                $this->db->or_like('billing_city', $search);
                $this->db->or_like('billing_state', $search);
                $this->db->or_like('vat', $search);
                // Search CNPJ without formatting
                $this->db->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%" . $this->db->escape_like_str($search) . "%'");
                $this->db->group_end();
            }

            // Implement sorting
            $this->db->order_by($sortField, $sortOrder);

            // Implement pagination
            $this->db->limit($limit, ($page - 1) * $limit);

            // Get suppliers
            $suppliers = $this->db->get(db_prefix() . 'clients')->result_array();

            // Count total suppliers (considering search)
            $this->db->reset_query();

            // Re-add supplier condition for count
            $this->db->where('is_supplier', 1);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('company', $search);
                $this->db->or_like('billing_city', $search);
                $this->db->or_like('billing_state', $search);
                $this->db->or_like('vat', $search);
                $this->db->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%" . $this->db->escape_like_str($search) . "%'");
                $this->db->group_end();
            }

            $this->db->select('COUNT(*) as total');
            $total = $this->db->get(db_prefix() . 'clients')->row()->total;

            return ['data' => $suppliers, 'total' => $total];
        } else {
            // Get single supplier
            $this->db->where('userid', $id);
            $supplier = $this->db->get(db_prefix() . 'clients')->row();

            $total = 0;
            if ($supplier) {
                $total = 1;
            }

            return ['data' => (array) $supplier, 'total' => $total];
        }
    }
    /**
     * Add a new supplier
     * @param array $data Supplier data
     * @return int|bool Supplier ID or false on failure
     */
    public function add_supplier($data)
    {
        $data['is_supplier'] = 1;

        $this->db->insert(db_prefix() . 'clients', $data);

        return $this->db->insert_id();
    }

    /**
     * Update an existing supplier
     * @param int $id Supplier ID
     * @param array $data Supplier data
     * @return bool True on success, false on failure
     */
    public function update_supplier($id, $data)
    {
        // Primeiro verifica se o supplier existe
        $this->db->where('userid', $id);
        $supplier = $this->db->get(db_prefix() . 'clients')->row();
        if (!$supplier) {
            return false;
        }

        // Atualiza dados principais
        $mainData = [
            'company' => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'cep' => $data['cep'] ?? null,
            'country' => isset($data['country']) ? (int)$data['country'] : 0,
            'zip' => $data['cep'] ?? null, // Note que na tabela o campo é 'zip' mas no payload é 'cep'
            'active' => ($data['status'] === 'active') ? 1 : 0,
            'documentType' => isset($data['document_type']) ? strtoupper($data['document_type']) : null,
            'vat' => $data['document_number'] ?? null, // Mapeando document_number para vat
            'warehouse_id' => $data['warehouse_id'] ?? 0,
            'company_type' => $data['company_type'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'segment' => $data['segment'] ?? null,
            'company_size' => $data['company_size'] ?? null,
            'observations' => $data['observations'] ?? null,
            'commercial_conditions' => $data['commercial_conditions'] ?? null,
            'commission_type' => $data['commission_type'] ?? null,
            'commission_base_percentage' => isset($data['commission_base_percentage']) ? (float)$data['commission_base_percentage'] : 0,
            'commission_payment_type' => $data['commission_payment_type'] ?? null,
            'commission_due_day' => isset($data['commission_due_day']) ? (int)$data['commission_due_day'] : null,
            'agent_commission_type' => $data['agent_commission_type'] ?? null,
            'agent_commission_base_percentage' => isset($data['agent_commission_base_percentage']) ? (float)$data['agent_commission_base_percentage'] : 0,
            'agent_commission_payment_type' => $data['agent_commission_payment_type'] ?? null,
            'agent_commission_due_day' => isset($data['agent_commission_due_day']) ? (int)$data['agent_commission_due_day'] : null,
            'tipo_frete' => $data['freight_type'] ?? 'na',
            'freight_value' => isset($data['freight_value']) ? (float)$data['freight_value'] : null,
            'min_payment_term' => isset($data['min_payment_term']) ? (int)$data['min_payment_term'] : null,
            'max_payment_term' => isset($data['max_payment_term']) ? (int)$data['max_payment_term'] : null,
            'min_order_value' => isset($data['min_order_value']) ? (float)$data['min_order_value'] : null,
            'max_order_value' => isset($data['max_order_value']) ? (float)$data['max_order_value'] : null,
            'inscricao_estadual' => $data['inscricao_estadual'] ?? null,
            'inscricao_municipal' => $data['inscricao_municipal'] ?? null,
            'code' => $data['code'] ?? null,
            'profile_image' => $data['profile_image'] ?? null,
        ];

        $this->db->where('userid', $id);
        $updated = $this->db->update(db_prefix() . 'clients', $mainData);

        if (!$updated) {
            return false;
        }

        // Atualiza contatos
        $this->db->where('userid', $id)->delete(db_prefix() . 'contacts');
        if (!empty($data['contacts']) && is_array($data['contacts'])) {
            $contactsData = [];
            foreach ($data['contacts'] as $contact) {
                $contactsData[] = [
                    'userid' => $id,
                    'firstname' => $contact['name'] ?? '',
                    'phonenumber' => $contact['phone'] ?? '',
                    'email' => $contact['email'] ?? '',
                    'is_primary' => $contact['is_primary'] ?? 0,
                ];

                // Se for o contato primário, atualiza também os dados principais
                if (!empty($contact['is_primary'])) {
                    $this->db->where('userid', $id);
                    $this->db->update(db_prefix() . 'clients', [
                        'phonenumber' => $contact['phone'] ?? null,
                        'email_default' => $contact['email'] ?? null,
                    ]);
                }
            }

            if (!empty($contactsData)) {
                $this->db->insert_batch(db_prefix() . 'contacts', $contactsData);
            }
        }

        // Atualiza documentos
        $this->db->where('supplier_id', $id)->delete(db_prefix() . 'document_supplier');
        if (!empty($data['documents']) && is_array($data['documents'])) {
            $documentsData = [];
            foreach ($data['documents'] as $doc) {
                $documentsData[] = [
                    'supplier_id' => $id,
                    'type' => $doc['type'] ?? '',
                    'document' => $doc['number'] ?? '',
                ];
            }

            if (!empty($documentsData)) {
                $this->db->insert_batch(db_prefix() . 'document_supplier', $documentsData);
            }
        }

        return true;
    }


    /**
     * Delete a supplier
     * @param int $id Supplier ID
     * @return bool True on success, false on failure
     */
    public function delete_supplier($id)
    {
        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'clients');

        return $this->db->affected_rows() > 0;
    }


    public function get($id)
    {
        $this->db->where('userid', $id);
        $this->db->where('is_supplier', 1);
        $supplier = $this->db->get(db_prefix() . 'clients')->row_array();

        if (!$supplier) return null;

        // documentos
        $this->db->where('supplier_id', $id);
        $documents = $this->db->get(db_prefix() . 'document_supplier')->result_array();

        $all_documents = array_merge(
            [[
                'type' => strtolower($supplier['documentType']),
                'number' => $supplier['vat']
            ]],
            array_map(function ($doc) {
                return [
                    'type' => strtolower($doc['type']),
                    'number' => $doc['document']
                ];
            }, $documents)
        );

        // emails
        $this->db->where('supplier_id', $id);
        $additional_emails = $this->db->get(db_prefix() . 'email_supplier')->result_array();

        $all_emails = array_merge(
            [$supplier['email_default']],
            array_column($additional_emails, 'email')
        );
        
        // contatos
        $this->db->where('userid', $id);
        $this->db->where('is_primary !=', 1); // evita duplicar o contato padrão
        $contacts = $this->db->get(db_prefix() . 'contacts')->result_array();

        $all_contacts = array_merge(
            [[
                'name' => $supplier['company'],
                'phone' => $supplier['phonenumber'],
                'email' => $supplier['email_default'] ?? null,
            ]],
            array_map(function ($c) {
                return [
                    'name' => $c['firstname'],
                    'phone' => $c['phonenumber'],
                    'email' => $c['email'],
                ];
            }, $contacts)
        );

        // resposta final
        return [
            'userid' => $supplier['userid'],
            'code' => $supplier['code'],
            'name' => $supplier['company'],
            'address' => $supplier['address'],
            'cep' => $supplier['cep'],
            'city' => $supplier['city'],
            'state' => $supplier['state'],
            'country' => $supplier['country'],
            'phonenumber' => $supplier['phonenumber'],
            'vat' => $supplier['vat'],
            'documentType' => $supplier['documentType'],
            'email_default' => $supplier['email_default'],
            'paymentTerm' => $supplier['payment_terms'],
            'status' => $supplier['active'] ? 'active' : 'inactive',
            'documents' => $all_documents,
            'emails' => array_filter($all_emails),
            'contacts' => $all_contacts,
            'warehouse_id' => $supplier['warehouse_id'],
            'inscricao_estadual' => $supplier['inscricao_estadual'],
            'inscricao_municipal' => $supplier['inscricao_municipal'],
            'company_type' => $supplier['company_type'],
            'business_type' => $supplier['business_type'],
            'segment' => $supplier['segment'],
            'company_size' => $supplier['company_size'],
            'observations' => $supplier['observations'],
            'commission' => (float) $supplier['commission'],
            'commercial_conditions' => $supplier['commercial_conditions'],
            'commission_type' => $supplier['commission_type'],
            'commission_base_percentage' => (float) $supplier['commission_base_percentage'],
            'commission_payment_type' => $supplier['commission_payment_type'],
            'commission_due_day' => (int) $supplier['commission_due_day'],
            'agent_commission_type' => $supplier['agent_commission_type'],
            'agent_commission_base_percentage' => (float) $supplier['agent_commission_base_percentage'],
            'agent_commission_payment_type' => $supplier['agent_commission_payment_type'],
            'agent_commission_due_day' => (int) $supplier['agent_commission_due_day'],
            'tipo_frete' => isset($supplier['tipo_frete']) ? $supplier['tipo_frete'] : 'na',
            'freight_value' => isset($supplier['freight_value']) ? (float) $supplier['freight_value'] : null,
            'min_payment_term' => isset($supplier['min_payment_term']) ? (int) $supplier['min_payment_term'] : null,
            'max_payment_term' => isset($supplier['max_payment_term']) ? (int) $supplier['max_payment_term'] : null,
            'min_order_value' => isset($supplier['min_order_value']) ? (float) $supplier['min_order_value'] : null,
            'max_order_value' => isset($supplier['max_order_value']) ? (float) $supplier['max_order_value'] : null,
            'image_url' => !empty($supplier['profile_image']) ? base_url($supplier['profile_image']) : null,
        ];
    }

    public function get_api_supplier($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC')
    {
        $table = db_prefix() . 'clients';

        // Busca por ID
        if (is_numeric($id)) {
            $client = $this->get($id);
            return ['data' => $client ? (array) $client : [], 'total' => $client ? 1 : 0];
        }

        // Consulta principal
        $this->db->from($table);
        $this->db->where('is_supplier', 1);

        // Filtros de busca
        if (!empty($search)) {
            $search = $this->db->escape_like_str($search);
            $this->db->group_start()
                ->like('company', $search)
                ->or_like('billing_city', $search)
                ->or_like('billing_state', $search)
                ->or_like('vat', $search)
                ->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%$search%'")
                ->group_end();
        }

        // Contagem total antes da paginação
        $total = $this->db->count_all_results('', false); // false mantém a consulta atual

        // Ordenação e Paginação
        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        // Busca dos registros
        $clients = $this->db->get()->result_array();

        return ['data' => $clients, 'total' => $total];
    }


    //    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC')
    //    {
    //
    //        if (!is_numeric($id)) {
    //            // Adicionar condições de busca
    //            if (!empty($search)) {
    //                $this->db->group_start(); // Começa um agrupamento de condição
    //                $this->db->like('company', $search); // Busca pelo campo 'company'
    //                $this->db->or_like('billing_city', $search);
    //                $this->db->or_like('billing_state', $search);
    //                $this->db->or_like('vat', $search); // Busca pelo campo 'vat'
    //                // Pesquisa o CNPJ sem formatação
    //                $this->db->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%" . $this->db->escape_like_str($search) . "%'");
    //                $this->db->group_end(); // Fecha o agrupamento de condição
    //            }
    //
    //            // Implementar lógica para ordenação
    //            $this->db->order_by($sortField, $sortOrder);
    //
    //            // Implementar a limitação e o deslocamento
    //            $this->db->limit($limit, ($page - 1) * $limit);
    //
    //            // Obtenha todos os clientes
    //            $clients = $this->db->get(db_prefix() . 'clients')->result_array();
    //
    //            // Contar o total de clientes (considerando a busca)
    //            $this->db->reset_query(); // Resetar consulta para evitar contagem duplicada
    //
    //            if (!empty($search)) {
    //                // Condições de busca para contar os resultados
    //                $this->db->group_start(); // Começa um agrupamento de condição
    //                $this->db->like('company', $search);
    //                $this->db->or_like('billing_city', $search);
    //                $this->db->or_like('billing_state', $search);
    //                $this->db->or_like('vat', $search);
    //                $this->db->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%" . $this->db->escape_like_str($search) . "%'");
    //                $this->db->group_end(); // Fecha o agrupamento de condição
    //            }
    //
    //            // Seleciona o total de clientes
    //            $this->db->select('COUNT(*) as total');
    //            $total = $this->db->get(db_prefix() . 'clients')->row()->total;
    //
    //            return ['data' => $clients, 'total' => $total]; // Retorne os clientes e o total
    //        } else {
    //
    //            $client = $this->get($id);
    //            $total = 0;
    //            if ($client) {
    //                $total = 1;
    //            }
    //
    //            return ['data' => (array) $client, 'total' => $total];
    //        }
    //
    //        // (O resto do código existente para quando $id é válido)
    //


    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC', $status = [], $startDate = '', $endDate = '', $warehouse_id = 0)
    {
        $allowedSortFields = [
            'userid',
            'company',
            'vat',
            'city',
            'state',
            'payment_terms',
            'active',
            'datecreated'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'userid';
        }

        $this->db->select('*');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where('is_supplier', 1);

        if ($warehouse_id > 0) {
            $this->db->where('warehouse_id', $warehouse_id);
        }

        if (!empty($status)) {
            $statusValues = array_map(function ($s) {
                return $s === 'active' ? 1 : 0;
            }, $status);
            $this->db->where_in('active', $statusValues);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $this->db->where('datecreated >=', $startDate . ' 00:00:00');
            $this->db->where('datecreated <=', $endDate . ' 23:59:59');
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like(db_prefix() . 'clients.company', $search);
            $this->db->or_like(db_prefix() . 'clients.vat', $search);
            $this->db->or_like(db_prefix() . 'clients.city', $search);
            $this->db->or_like(db_prefix() . 'clients.state', $search);
            $this->db->or_like(db_prefix() . 'clients.address', $search);
            $this->db->or_like(db_prefix() . 'clients.cep', $search);
            $this->db->or_like(db_prefix() . 'clients.nome_fantasia', $search);
            $this->db->or_like(db_prefix() . 'clients.inscricao_estadual', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', FALSE);

        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $suppliers = $this->db->get()->result_array();

        $formattedData = [];
        foreach ($suppliers as $supplier) {
            $formattedData[] = [
                'userid' => $supplier['userid'],
                'company' => $supplier['company'],
                'vat' => $supplier['vat'],
                'city' => $supplier['city'] ?: '-',
                'state' => $supplier['state'] ?: '-',
                'payment_terms' => $supplier['payment_terms'] ?: '-',
                'status' => $supplier['active'] ? 'active' : 'inactive',
                'created_at' => $supplier['datecreated'],
                'email_default' => $supplier['email_default'] ?? null,
                'documents' => [
                    [
                        'type' => 'cnpj',
                        'number' => $supplier['vat']
                    ]
                ],
                'contacts_count' => 0
            ];
        }

        return ['data' => $formattedData, 'total' => $total];
    }



    // Busca por cidade
    public function get_api_by_city($page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC')
    {
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('billing_city', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $clients = $this->db->get(db_prefix() . 'clients')->result_array();

        $this->db->reset_query();
        if (!empty($search)) {
            $this->db->like('billing_city', $search);
        }
        $total = $this->db->count_all_results(db_prefix() . 'clients');

        return ['data' => $clients, 'total' => $total];
    }

    // Busca por estado
    public function get_api_by_state($page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC')
    {
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('billing_state', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $clients = $this->db->get(db_prefix() . 'clients')->result_array();

        // Contar total
        $this->db->reset_query();
        if (!empty($search)) {
            $this->db->like('billing_state', $search);
        }
        $total = $this->db->count_all_results(db_prefix() . 'clients');

        return ['data' => $clients, 'total' => $total];
    }

    //Busca por CNPJ/VAT
    public function get_api_by_vat($page = 1, $limit = 10, $search = '', $sortField = 'userid', $sortOrder = 'ASC')
    {
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('vat', $search);
            // Pesquisa o CNPJ sem formatação
            $this->db->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%" .
                $this->db->escape_like_str($search) . "%'");
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $clients = $this->db->get(db_prefix() . 'clients')->result_array();

        // Contar total
        $this->db->reset_query();
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('vat', $search);
            $this->db->or_where("REPLACE(REPLACE(REPLACE(REPLACE(vat, '.', ''), '/', ''), '-', ''), ' ', '') LIKE '%" .
                $this->db->escape_like_str($search) . "%'");
            $this->db->group_end();
        }
        $total = $this->db->count_all_results(db_prefix() . 'clients');

        return ['data' => $clients, 'total' => $total];
    }


    /**
     * Get customers contacts
     * @param  mixed $customer_id
     * @param  array $where       perform where query
     * @param  array $whereIn     perform whereIn query
     * @return array
     */
    public function get_contacts($customer_id = '', $where = ['active' => 1], $whereIn = [])
    {


        $this->db->select('userid, id, firstname,lastname, email,phonenumber, is_primary');

        $this->db->where($where);
        if ($customer_id != '') {
            $this->db->where('userid', $customer_id);
        }

        foreach ($whereIn as $key => $values) {
            if (is_string($key) && is_array($values)) {
                $this->db->where_in($key, $values);
            }
        }

        $this->db->order_by('is_primary', 'DESC');

        return $this->db->get(db_prefix() . 'contacts')->result_array();
    }

    /**
     * Get single contacts
     * @param  mixed $id contact id
     * @return object
     */
    public function get_contact($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'contacts')->row();
    }

    /**
     * Get contact by given email
     *
     * @since 2.8.0
     *
     * @param  string $email
     *
     * @return \strClass|null
     */
    public function get_contact_by_email($email)
    {
        $this->db->where('email', $email);
        $this->db->limit(1);

        return $this->db->get('contacts')->row();
    }

    /**
     * @param array $_POST data
     * @param withContact
     *
     * @return integer Insert ID
     *
     * Add new client to database
     */
    public function add($data, $withContact = false)
    {
        $contact_data = [];
        // From Lead Convert to client
        if (isset($data['send_set_password_email'])) {
            $contact_data['send_set_password_email'] = true;
        }

        if (isset($data['donotsendwelcomeemail'])) {
            $contact_data['donotsendwelcomeemail'] = true;
        }

        $data = $this->check_zero_columns($data);

        $data = hooks()->apply_filters('before_client_added', $data);

        foreach ($this->contact_columns as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $contact_data[$field] = $data[$field];

            // Phonenumber is also used for the company profile
            if ($field != 'phonenumber') {
                unset($data[$field]);
            }
        }

        $groups_in = Arr::pull($data, 'groups_in') ?? [];
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        // From customer profile register
        if (isset($data['contact_phonenumber'])) {
            $contact_data['phonenumber'] = $data['contact_phonenumber'];
            unset($data['contact_phonenumber']);
        }

        $client = $this->db->insert(db_prefix() . 'clients', array_merge($data, [
            'datecreated' => date('Y-m-d H:i:s'),
            'addedfrom' => is_staff_logged_in() ? get_staff_user_id() : 0,
        ]));

        $client_id = $this->db->insert_id();

        if ($client_id) {
            if (count($custom_fields) > 0) {
                $_custom_fields = $custom_fields;
                // Possible request from the register area with 2 types of custom fields for contact and for comapny/customer
                if (count($custom_fields) == 2) {
                    unset($custom_fields);
                    $custom_fields['customers'] = $_custom_fields['customers'];
                    $contact_data['custom_fields']['contacts'] = $_custom_fields['contacts'];
                } elseif (count($custom_fields) == 1) {
                    if (isset($_custom_fields['contacts'])) {
                        $contact_data['custom_fields']['contacts'] = $_custom_fields['contacts'];
                        unset($custom_fields);
                    }
                }

                handle_custom_fields_post($client_id, $custom_fields);
            }

            /**
             * Used in Import, Lead Convert, Register
             */
            if ($withContact == true) {
                $contact_id = $this->add_contact($contact_data, $client_id, $withContact);
            }

            foreach ($groups_in as $group) {
                $this->db->insert('customer_groups', [
                    'customer_id' => $client_id,
                    'groupid' => $group,
                ]);
            }

            $log = 'ID: ' . $client_id;

            if ($log == '' && isset($contact_id)) {
                $log = get_contact_full_name($contact_id);
            }

            $isStaff = null;

            if (!is_client_logged_in() && is_staff_logged_in()) {
                $log .= ', From Staff: ' . get_staff_user_id();
                $isStaff = get_staff_user_id();
            }

            do_action_deprecated('after_client_added', [$client_id], '2.9.4', 'after_client_created');

            hooks()->do_action('after_client_created', [
                'id' => $client_id,
                'data' => $data,
                'contact_data' => $contact_data,
                'custom_fields' => $custom_fields,
                'groups_in' => $groups_in,
                'with_contact' => $withContact,
            ]);

            log_activity('New Client Created [' . $log . ']', $isStaff);
        }

        return $client_id;
    }

    public function add_import_items()
    {
        $this->load->database();
        // Caminho do seu arquivo CSV
        $csvFile = '/home/api_arvis/www/uploads/import/produtos_rows.csv';

        // Abrir o arquivo CSV
        if (($handle = fopen($csvFile, 'r')) !== false) {
            // Ler o cabeçalho do arquivo CSV (apenas para pular)
            $header = fgetcsv($handle, 1000, ',');

            // Contador de registros inseridos com sucesso
            $insertedRecords = 0;

            // Percorrer cada linha do arquivo CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Verificar se a linha contém dados válidos
                if (count($data) < count($header)) { // Verifica se a linha tem a quantidade mínima de colunas
                    continue; // Pular linhas em branco ou inválidas
                }

                // Mapeia os dados do CSV para os campos da tabela
                $itemData = array(
                    'description' => $data[3], // Nome do produto / descrição (tpr_produto)
                    'long_description' => $data[4], // Descrição longa se necessário (tpr_descricao)
                    'rate' => $data[13], // Preço (tpr_precolista1)
                    'tax' => null, // ID do imposto - ajustável conforme sua aplicação
                    'tax2' => null, // ID do segundo imposto - ajustável conforme sua aplicação
                    'unit' => $data[6], // Unidade (tpr_unidade)
                    'group_id' => $data[5], // ID do grupo (tpr_codgrp)
                    'perfex_saas_tenant_id' => 'master'         // ID do inquilino - ajustável conforme sua aplicação
                );

                // Inserir dados na tabela
                $this->db->insert('tblitems', $itemData);

                // Incrementar contador de registros inseridos
                $insertedRecords++;
            }

            // Fechar o arquivo CSV
            fclose($handle);

            echo "$insertedRecords registros inseridos com sucesso!";
        } else {
            echo "Erro ao abrir o arquivo CSV.";
        }
    }

    /**
     * @param array $_POST data
     * @param withContact
     *
     * @return integer Insert ID
     *
     * Add new client to database
     */
    public function add_import()
    {
        // Conectar ao banco de dados (substitua pelos seus dados de conexão)
        $this->load->database();

        // Caminho do seu arquivo CSV
        $csvFile = '/home/api_arvis/www/uploads/import/clients.csv';
        // Abrir o arquivo CSV
        if (($handle = fopen($csvFile, 'r')) !== false) {
            // Ler o cabeçalho do arquivo CSV (apenas para pular)
            $header = fgetcsv($handle, 1000, ',');

            // Contador de registros inseridos com sucesso
            $insertedRecords = 0;

            // Percorrer cada linha do arquivo CSV
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Verificar se a linha contém dados válidos
                if (count($data) < count($header)) { // Verifica se a linha tem a quantidade mínima de colunas
                    continue; // Pular linhas em branco ou inválidas
                }

                // Mapeia os dados do CSV para os campos da tabela
                $clientData = array(
                    'company' => $data[1], // Nome da empresa (tcl_razao)
                    'vat' => $data[9], // CNPJ (tcl_cnpj)
                    'country' => 282, // CNPJ (tcl_cnpj)
                    'phonenumber' => $data[14], // Telefone (tcl_fone1)
                    'city' => $data[5], // Cidade (tcl_cidade)
                    'zip' => $data[7], // CEP (tcl_cep)
                    'state' => $data[6], // Estado (tcl_estado)
                    'address' => $data[3], // Endereço (tcl_endereco)
                    'website' => $data[18], // Site (tcl_site)
                    'datecreated' => date('Y-m-d H:i:s'), // Data atual
                    'active' => 1, // Ativo
                    'billing_street' => $data[3], // Rua de cobrança (tcl_endereco)
                    'billing_city' => $data[5], // Cidade de cobrança (tcl_cidade)
                    'billing_state' => $data[6], // Estado de cobrança (tcl_estado)
                    'billing_zip' => $data[7], // CEP de cobrança (tcl_cep)
                    'billing_country' => 282, // País de cobrança - ajustável conforme sua necessidade
                    'shipping_street' => $data[3], // Rua de entrega
                    'shipping_city' => $data[5], // Cidade de entrega
                    'shipping_state' => $data[6], // Estado de entrega
                    'shipping_zip' => $data[7], // CEP de entrega
                    'shipping_country' => 282, // País de entrega - ajustável conforme sua necessidade
                    'longitude' => null, // Longitude - não presente no CSV
                    'latitude' => null, // Latitude - não presente no CSV
                    'default_language' => 'pt', // Idioma padrão - ajustável
                    'default_currency' => 0, // Moeda padrão - ajustável
                    'show_primary_contact' => 1, // Mostrar contato primário
                    'stripe_id' => null, // Stripe ID - não presente no CSV
                    'registration_confirmed' => 1, // Registro confirmado
                    'addedfrom' => 0, // Quem adicionou - ajustável conforme sua aplicação
                    'perfex_saas_tenant_id' => 'master', // ID do inquilino - ajustável
                    'inscricao_estadual' => $data[10], // Inscrição Estadual - tcl_ie
                    'nome_fantasia' => $data[2]                   // Nome Fantasia - tcl_fantasia
                );

                // Inserir dados na tabela
                $this->db->insert('tblclients', $clientData);

                // Obter o ID do cliente recém-inserido
                $client_id = $this->db->insert_id();

                // Preparar dados do contato
                $contact_data = array(
                    'firstname' => $data[13], // Nome do contato - tcl_contato
                    'lastname' => '', // Sobrenome - ajuste conforme sua necessidade
                    'email' => $data[18], // Email - tcl_email
                    'phonenumber' => $data[14], // Telefone do contato - tcl_fone1
                    'userid' => $client_id,
                    'is_primary' => 1,
                    'password' => app_hash_password(preg_replace('/\D/', '', $data[9]))
                );

                // Adicionar contato
                $contact_id = $this->add_contact($contact_data, $client_id, true);
            }
        }
    }

    /**
     * @param  array $_POST data
     * @param  integer ID
     * @return boolean
     * Update client informations
     */
    public function update($data, $id, $client_request = false)
    {
        $updated = false;
        $data = $this->check_zero_columns($data);

        $data = hooks()->apply_filters('before_client_updated', $data, $id);

        $update_all_other_transactions = (bool) Arr::pull($data, 'update_all_other_transactions');
        $update_credit_notes = (bool) Arr::pull($data, 'update_credit_notes');
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];
        $groups_in = Arr::pull($data, 'groups_in') ?? false;

        if (handle_custom_fields_post($id, $custom_fields)) {
            $updated = true;
        }

        $this->db->where('userid', $id);
        $this->db->update(db_prefix() . 'clients', $data);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }

        if ($update_all_other_transactions || $update_credit_notes) {
            $transactions_update = [
                'billing_street' => $data['billing_street'],
                'billing_city' => $data['billing_city'],
                'billing_state' => $data['billing_state'],
                'billing_zip' => $data['billing_zip'],
                'billing_country' => $data['billing_country'],
                'shipping_street' => $data['shipping_street'],
                'shipping_city' => $data['shipping_city'],
                'shipping_state' => $data['shipping_state'],
                'shipping_zip' => $data['shipping_zip'],
                'shipping_country' => $data['shipping_country'],
            ];

            if ($update_all_other_transactions) {
                // Update all invoices except paid ones.
                $this->db->where('clientid', $id)
                    ->where('status !=', 2)
                    ->update('invoices', $transactions_update);

                if ($this->db->affected_rows() > 0) {
                    $updated = true;
                }

                // Update all estimates
                $this->db->where('clientid', $id)
                    ->update('estimates', $transactions_update);
                if ($this->db->affected_rows() > 0) {
                    $updated = true;
                }
            }

            if ($update_credit_notes) {
                $this->db->where('clientid', $id)
                    ->where('status !=', 2)
                    ->update('creditnotes', $transactions_update);

                if ($this->db->affected_rows() > 0) {
                    $updated = true;
                }
            }
        }

        if ($this->client_groups_model->sync_customer_groups($id, $groups_in)) {
            $updated = true;
        }

        do_action_deprecated('after_client_updated', [$id], '2.9.4', 'client_updated');

        hooks()->do_action('client_updated', [
            'id' => $id,
            'data' => $data,
            'update_all_other_transactions' => $update_all_other_transactions,
            'update_credit_notes' => $update_credit_notes,
            'custom_fields' => $custom_fields,
            'groups_in' => $groups_in,
            'updated' => &$updated,
        ]);

        if ($updated) {
            log_activity('Customer Info Updated [ID: ' . $id . ']');
        }

        return $updated;
    }

    /**
     * Update contact data
     * @param  array  $data           $_POST data
     * @param  mixed  $id             contact id
     * @param  boolean $client_request is request from customers area
     * @return mixed
     */
    public function update_contact($data, $id, $client_request = false)
    {
        $affectedRows = 0;
        $contact = $this->get_contact($id);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = app_hash_password($data['password']);
            $data['last_password_change'] = date('Y-m-d H:i:s');
        }

        $send_set_password_email = isset($data['send_set_password_email']) ? true : false;
        $set_password_email_sent = false;

        $permissions = isset($data['permissions']) ? $data['permissions'] : [];
        $data['is_primary'] = isset($data['is_primary']) ? 1 : 0;

        // Contact cant change if is primary or not
        if ($client_request == true) {
            unset($data['is_primary']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if ($client_request == false) {
            $data['invoice_emails'] = isset($data['invoice_emails']) ? 1 : 0;
            $data['estimate_emails'] = isset($data['estimate_emails']) ? 1 : 0;
            $data['credit_note_emails'] = isset($data['credit_note_emails']) ? 1 : 0;
            $data['contract_emails'] = isset($data['contract_emails']) ? 1 : 0;
            $data['task_emails'] = isset($data['task_emails']) ? 1 : 0;
            $data['project_emails'] = isset($data['project_emails']) ? 1 : 0;
            $data['ticket_emails'] = isset($data['ticket_emails']) ? 1 : 0;
        }

        $data = hooks()->apply_filters('before_update_contact', $data, $id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', $data);

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if (isset($data['is_primary']) && $data['is_primary'] == 1) {
                $this->db->where('userid', $contact->userid);
                $this->db->where('id !=', $id);
                $this->db->update(db_prefix() . 'contacts', [
                    'is_primary' => 0,
                ]);
            }
        }

        if ($client_request == false) {
            $customer_permissions = $this->roles_model->get_contact_permissions($id);
            if (sizeof($customer_permissions) > 0) {
                foreach ($customer_permissions as $customer_permission) {
                    if (!in_array($customer_permission['permission_id'], $permissions)) {
                        $this->db->where('userid', $id);
                        $this->db->where('permission_id', $customer_permission['permission_id']);
                        $this->db->delete(db_prefix() . 'contact_permissions');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
                foreach ($permissions as $permission) {
                    $this->db->where('userid', $id);
                    $this->db->where('permission_id', $permission);
                    $_exists = $this->db->get(db_prefix() . 'contact_permissions')->row();
                    if (!$_exists) {
                        $this->db->insert(db_prefix() . 'contact_permissions', [
                            'userid' => $id,
                            'permission_id' => $permission,
                        ]);
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                }
            } else {
                foreach ($permissions as $permission) {
                    $this->db->insert(db_prefix() . 'contact_permissions', [
                        'userid' => $id,
                        'permission_id' => $permission,
                    ]);
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
            if ($send_set_password_email) {
                $set_password_email_sent = $this->authentication_model->set_password_email($data['email'], 0);
            }
        }

        if (($client_request == true) && $send_set_password_email) {
            $set_password_email_sent = $this->authentication_model->set_password_email($data['email'], 0);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('contact_updated', $id, $data);
        }

        if ($affectedRows > 0 && !$set_password_email_sent) {
            log_activity('Contact Updated [ID: ' . $id . ']');

            return true;
        } elseif ($affectedRows > 0 && $set_password_email_sent) {
            return [
                'set_password_email_sent_and_profile_updated' => true,
            ];
        } elseif ($affectedRows == 0 && $set_password_email_sent) {
            return [
                'set_password_email_sent' => true,
            ];
        }

        return false;
    }

    /**
     * Add new contact
     * @param array  $data               $_POST data
     * @param mixed  $customer_id        customer id
     * @param boolean $not_manual_request is manual from admin area customer profile or register, convert to lead
     */
    public function add_contact($data, $customer_id, $not_manual_request = false)
    {
        $send_set_password_email = isset($data['send_set_password_email']) ? true : false;

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['permissions'])) {
            $permissions = $data['permissions'];
            unset($data['permissions']);
        }

        $data['email_verified_at'] = date('Y-m-d H:i:s');

        $send_welcome_email = true;

        if (isset($data['donotsendwelcomeemail'])) {
            $send_welcome_email = false;
        }

        if (defined('CONTACT_REGISTERING')) {
            $send_welcome_email = true;

            // Do not send welcome email if confirmation for registration is enabled
            if (get_option('customers_register_require_confirmation') == '1') {
                $send_welcome_email = false;
            }


            if (is_email_verification_enabled() && !empty($data['email'])) {
                // Verification is required on register
                $data['email_verified_at'] = null;
                $data['email_verification_key'] = app_generate_hash();
            }
        }

        if (isset($data['is_primary'])) {

            if ($data['is_primary'] == 1) {
                $data['is_primary'] = 1;
            } else {
                $data['is_primary'] = 0;
            }
            $this->db->where('userid', $customer_id);
            $this->db->update(db_prefix() . 'contacts', [
                'is_primary' => 0,
            ]);
        } else {
            $data['is_primary'] = 0;
        }

        $password_before_hash = '';
        $data['userid'] = $customer_id;
        if (isset($data['password'])) {
            $password_before_hash = $data['password'];
            $data['password'] = app_hash_password($data['password']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');

        if (!$not_manual_request) {
            $data['invoice_emails'] = isset($data['invoice_emails']) ? 1 : 0;
            $data['estimate_emails'] = isset($data['estimate_emails']) ? 1 : 0;
            $data['credit_note_emails'] = isset($data['credit_note_emails']) ? 1 : 0;
            $data['contract_emails'] = isset($data['contract_emails']) ? 1 : 0;
            $data['task_emails'] = isset($data['task_emails']) ? 1 : 0;
            $data['project_emails'] = isset($data['project_emails']) ? 1 : 0;
            $data['ticket_emails'] = isset($data['ticket_emails']) ? 1 : 0;
        }

        $data['email'] = trim($data['email']);

        $data = hooks()->apply_filters('before_create_contact', $data);

        $this->db->insert(db_prefix() . 'contacts', $data);
        $contact_id = $this->db->insert_id();

        if ($contact_id) {
            if (isset($custom_fields)) {
                handle_custom_fields_post($contact_id, $custom_fields);
            }
            // request from admin area
            if (!isset($permissions) && $not_manual_request == false) {
                $permissions = [];
            } elseif ($not_manual_request == true) {
                $permissions = [];
                $_permissions = get_contact_permissions();
                $default_permissions = @unserialize(get_option('default_contact_permissions'));
                if (is_array($default_permissions)) {
                    foreach ($_permissions as $permission) {
                        if (in_array($permission['id'], $default_permissions)) {
                            array_push($permissions, $permission['id']);
                        }
                    }
                }
            }

            if ($not_manual_request == true) {
                // update all email notifications to 0
                $this->db->where('id', $contact_id);
                $this->db->update(db_prefix() . 'contacts', [
                    'invoice_emails' => 0,
                    'estimate_emails' => 0,
                    'credit_note_emails' => 0,
                    'contract_emails' => 0,
                    'task_emails' => 0,
                    'project_emails' => 0,
                    'ticket_emails' => 0,
                ]);
            }
            foreach ($permissions as $permission) {
                $this->db->insert(db_prefix() . 'contact_permissions', [
                    'userid' => $contact_id,
                    'permission_id' => $permission,
                ]);

                // Auto set email notifications based on permissions
                if ($not_manual_request == true) {
                    if ($permission == 6) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['project_emails' => 1, 'task_emails' => 1]);
                    } elseif ($permission == 3) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['contract_emails' => 1]);
                    } elseif ($permission == 2) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['estimate_emails' => 1]);
                    } elseif ($permission == 1) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['invoice_emails' => 1, 'credit_note_emails' => 1]);
                    } elseif ($permission == 5) {
                        $this->db->where('id', $contact_id);
                        $this->db->update(db_prefix() . 'contacts', ['ticket_emails' => 1]);
                    }
                }
            }

            /*
              if ($send_welcome_email == true && !empty($data['email'])) {
              send_mail_template(
              'customer_created_welcome_mail',
              $data['email'],
              $data['userid'],
              $contact_id,
              $password_before_hash
              );
              }
             *
             */

            if ($send_set_password_email) {
                $this->authentication_model->set_password_email($data['email'], 0);
            }

            if (defined('CONTACT_REGISTERING')) {
                $this->send_verification_email($contact_id);
            } else {
                // User already verified because is added from admin area, try to transfer any tickets
                $this->load->model('tickets_model');
                $this->tickets_model->transfer_email_tickets_to_contact($data['email'], $contact_id);
            }

            log_activity('Contact Created [ID: ' . $contact_id . ']');

            hooks()->do_action('contact_created', $contact_id);

            return $contact_id;
        }

        return false;
    }

    /**
     * Add new contact via customers area
     *
     * @param array  $data
     * @param mixed  $customer_id
     */
    public function add_contact_via_customers_area($data, $customer_id)
    {
        $send_welcome_email = isset($data['donotsendwelcomeemail']) && $data['donotsendwelcomeemail'] ? false : true;
        $send_set_password_email = isset($data['send_set_password_email']) && $data['send_set_password_email'] ? true : false;
        $custom_fields = $data['custom_fields'];
        unset($data['custom_fields']);

        if (!is_email_verification_enabled()) {
            $data['email_verified_at'] = date('Y-m-d H:i:s');
        } elseif (is_email_verification_enabled() && !empty($data['email'])) {
            // Verification is required on register
            $data['email_verified_at'] = null;
            $data['email_verification_key'] = app_generate_hash();
        }

        $password_before_hash = $data['password'];

        $data = array_merge($data, [
            'datecreated' => date('Y-m-d H:i:s'),
            'userid' => $customer_id,
            'password' => app_hash_password(isset($data['password']) ? $data['password'] : time()),
        ]);

        $data = hooks()->apply_filters('before_create_contact', $data);
        $this->db->insert(db_prefix() . 'contacts', $data);

        $contact_id = $this->db->insert_id();

        if ($contact_id) {
            handle_custom_fields_post($contact_id, $custom_fields);

            // Apply default permissions
            $default_permissions = @unserialize(get_option('default_contact_permissions'));

            if (is_array($default_permissions)) {
                foreach (get_contact_permissions() as $permission) {
                    if (in_array($permission['id'], $default_permissions)) {
                        $this->db->insert(db_prefix() . 'contact_permissions', [
                            'userid' => $contact_id,
                            'permission_id' => $permission['id'],
                        ]);
                    }
                }
            }

            if ($send_welcome_email === true) {
                send_mail_template(
                    'customer_created_welcome_mail',
                    $data['email'],
                    $customer_id,
                    $contact_id,
                    $password_before_hash
                );
            }

            if ($send_set_password_email === true) {
                $this->authentication_model->set_password_email($data['email'], 0);
            }

            log_activity('Contact Created [ID: ' . $contact_id . ']');
            hooks()->do_action('contact_created', $contact_id);

            return $contact_id;
        }

        return false;
    }

    /**
     * Used to update company details from customers area
     * @param  array $data $_POST data
     * @param  mixed $id
     * @return boolean
     */
    public function update_company_details($data, $id)
    {
        $affectedRows = 0;
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }
        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }
        if (isset($data['billing_country']) && $data['billing_country'] == '') {
            $data['billing_country'] = 0;
        }
        if (isset($data['shipping_country']) && $data['shipping_country'] == '') {
            $data['shipping_country'] = 0;
        }

        // From v.1.9.4 these fields are textareas
        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);
        if (isset($data['billing_street'])) {
            $data['billing_street'] = trim($data['billing_street']);
            $data['billing_street'] = nl2br($data['billing_street']);
        }
        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        $data = hooks()->apply_filters('customer_update_company_info', $data, $id);

        $this->db->where('userid', $id);
        $this->db->update(db_prefix() . 'clients', $data);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            hooks()->do_action('customer_updated_company_info', $id);
            log_activity('Customer Info Updated From Clients Area [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get customer staff members that are added as customer admins
     * @param  mixed $id customer id
     * @return array
     */
    public function get_admins($id)
    {
        $this->db->where('customer_id', $id);

        return $this->db->get(db_prefix() . 'customer_admins')->result_array();
    }

    /**
     * Get unique staff id's of customer admins
     * @return array
     */
    public function get_customers_admin_unique_ids()
    {
        return $this->db->query('SELECT DISTINCT(staff_id) FROM ' . db_prefix() . 'customer_admins')->result_array();
    }

    /**
     * Assign staff members as admin to customers
     * @param  array $data $_POST data
     * @param  mixed $id   customer id
     * @return boolean
     */
    public function assign_admins($data, $id)
    {
        $affectedRows = 0;

        if (count($data) == 0) {
            $this->db->where('customer_id', $id);
            $this->db->delete(db_prefix() . 'customer_admins');
            if ($this->db->affected_rows() > 0) {
                $affectedRows++;
            }
        } else {
            $current_admins = $this->get_admins($id);
            $current_admins_ids = [];
            foreach ($current_admins as $c_admin) {
                array_push($current_admins_ids, $c_admin['staff_id']);
            }
            foreach ($current_admins_ids as $c_admin_id) {
                if (!in_array($c_admin_id, $data['customer_admins'])) {
                    $this->db->where('staff_id', $c_admin_id);
                    $this->db->where('customer_id', $id);
                    $this->db->delete(db_prefix() . 'customer_admins');
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
            foreach ($data['customer_admins'] as $n_admin_id) {
                if (
                    total_rows(db_prefix() . 'customer_admins', [
                        'customer_id' => $id,
                        'staff_id' => $n_admin_id,
                    ]) == 0
                ) {
                    $this->db->insert(db_prefix() . 'customer_admins', [
                        'customer_id' => $id,
                        'staff_id' => $n_admin_id,
                        'date_assigned' => date('Y-m-d H:i:s'),
                    ]);
                    if ($this->db->affected_rows() > 0) {
                        $affectedRows++;
                    }
                }
            }
        }
        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @return boolean
     * Delete client, also deleting rows from, dismissed client announcements, ticket replies, tickets, autologin, user notes
     */
    public function delete($id)
    {
        $affectedRows = 0;

        if (!is_gdpr() && is_reference_in_table('clientid', db_prefix() . 'invoices', $id)) {
            return [
                'referenced' => true,
            ];
        }

        if (!is_gdpr() && is_reference_in_table('clientid', db_prefix() . 'estimates', $id)) {
            return [
                'referenced' => true,
            ];
        }

        if (!is_gdpr() && is_reference_in_table('clientid', db_prefix() . 'creditnotes', $id)) {
            return [
                'referenced' => true,
            ];
        }

        hooks()->do_action('before_client_deleted', $id);

        $last_activity = get_last_system_activity_id();
        $company = get_company_name($id);

        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'clients');
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            // Delete all user contacts
            $this->db->where('userid', $id);
            $contacts = $this->db->get(db_prefix() . 'contacts')->result_array();
            foreach ($contacts as $contact) {
                $this->delete_contact($contact['id']);
            }

            // Delete all tickets start here
            $this->db->where('userid', $id);
            $tickets = $this->db->get(db_prefix() . 'tickets')->result_array();
            $this->load->model('tickets_model');
            foreach ($tickets as $ticket) {
                $this->tickets_model->delete($ticket['ticketid']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            $this->db->delete(db_prefix() . 'notes');

            if (is_gdpr() && get_option('gdpr_on_forgotten_remove_invoices_credit_notes') == '1') {
                $this->load->model('invoices_model');
                $this->db->where('clientid', $id);
                $invoices = $this->db->get(db_prefix() . 'invoices')->result_array();
                foreach ($invoices as $invoice) {
                    $this->invoices_model->delete($invoice['id'], true);
                }

                $this->load->model('credit_notes_model');
                $this->db->where('clientid', $id);
                $credit_notes = $this->db->get(db_prefix() . 'creditnotes')->result_array();
                foreach ($credit_notes as $credit_note) {
                    $this->credit_notes_model->delete($credit_note['id'], true);
                }
            } elseif (is_gdpr()) {
                $this->db->where('clientid', $id);
                $this->db->update(db_prefix() . 'invoices', ['deleted_customer_name' => $company]);

                $this->db->where('clientid', $id);
                $this->db->update(db_prefix() . 'creditnotes', ['deleted_customer_name' => $company]);
            }

            $this->db->where('clientid', $id);
            $this->db->update(db_prefix() . 'creditnotes', [
                'clientid' => 0,
                'project_id' => 0,
            ]);

            $this->db->where('clientid', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'clientid' => 0,
                'recurring' => 0,
                'recurring_type' => null,
                'custom_recurring' => 0,
                'cycles' => 0,
                'last_recurring_date' => null,
                'project_id' => 0,
                'subscription_id' => 0,
                'cancel_overdue_reminders' => 1,
                'last_overdue_reminder' => null,
                'last_due_reminder' => null,
            ]);

            if (is_gdpr() && get_option('gdpr_on_forgotten_remove_estimates') == '1') {
                $this->load->model('estimates_model');
                $this->db->where('clientid', $id);
                $estimates = $this->db->get(db_prefix() . 'estimates')->result_array();
                foreach ($estimates as $estimate) {
                    $this->estimates_model->delete($estimate['id'], true);
                }
            } elseif (is_gdpr()) {
                $this->db->where('clientid', $id);
                $this->db->update(db_prefix() . 'estimates', ['deleted_customer_name' => $company]);
            }

            $this->db->where('clientid', $id);
            $this->db->update(db_prefix() . 'estimates', [
                'clientid' => 0,
                'project_id' => 0,
                'is_expiry_notified' => 1,
            ]);

            $this->load->model('subscriptions_model');
            $this->db->where('clientid', $id);
            $subscriptions = $this->db->get(db_prefix() . 'subscriptions')->result_array();
            foreach ($subscriptions as $subscription) {
                $this->subscriptions_model->delete($subscription['id'], true);
            }
            // Get all client contracts
            $this->load->model('contracts_model');
            $this->db->where('client', $id);
            $contracts = $this->db->get(db_prefix() . 'contracts')->result_array();
            foreach ($contracts as $contract) {
                $this->contracts_model->delete($contract['id']);
            }
            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'customers');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            // Get customer related tasks
            $this->db->where('rel_type', 'customer');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id'], false);
            }

            $this->db->where('rel_type', 'customer');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('customer_id', $id);
            $this->db->delete(db_prefix() . 'customer_admins');

            $this->db->where('customer_id', $id);
            $this->db->delete(db_prefix() . 'vault');

            $this->db->where('customer_id', $id);
            $this->db->delete(db_prefix() . 'customer_groups');

            $this->load->model('proposals_model');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            $proposals = $this->db->get(db_prefix() . 'proposals')->result_array();
            foreach ($proposals as $proposal) {
                $this->proposals_model->delete($proposal['id']);
            }
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'customer');
            $attachments = $this->db->get(db_prefix() . 'files')->result_array();
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('clientid', $id);
            $expenses = $this->db->get(db_prefix() . 'expenses')->result_array();

            $this->load->model('expenses_model');
            foreach ($expenses as $expense) {
                $this->expenses_model->delete($expense['id'], true);
            }

            $this->db->where('client_id', $id);
            $this->db->delete(db_prefix() . 'user_meta');

            $this->db->where('client_id', $id);
            $this->db->update(db_prefix() . 'leads', ['client_id' => 0]);

            // Delete all projects
            $this->load->model('projects_model');
            $this->db->where('clientid', $id);
            $projects = $this->db->get(db_prefix() . 'projects')->result_array();
            foreach ($projects as $project) {
                $this->projects_model->delete($project['id']);
            }
        }
        if ($affectedRows > 0) {
            hooks()->do_action('after_client_deleted', $id);

            // Delete activity log caused by delete customer function
            if ($last_activity) {
                $this->db->where('id >', $last_activity->id);
                $this->db->delete(db_prefix() . 'activity_log');
            }

            log_activity('Client Deleted [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete customer contact
     * @param  mixed $id contact id
     * @return boolean
     */
    public function delete_contact($id)
    {
        hooks()->do_action('before_delete_contact', $id);

        $this->db->where('id', $id);
        $result = $this->db->get(db_prefix() . 'contacts')->row();
        $customer_id = $result->userid;

        $last_activity = get_last_system_activity_id();

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'contacts');

        if ($this->db->affected_rows() > 0) {
            if (is_dir(get_upload_path_by_type('contact_profile_images') . $id)) {
                delete_dir(get_upload_path_by_type('contact_profile_images') . $id);
            }

            $this->db->where('contact_id', $id);
            $this->db->delete(db_prefix() . 'consents');

            $this->db->where('contact_id', $id);
            $this->db->delete(db_prefix() . 'shared_customer_files');

            $this->db->where('userid', $id);
            $this->db->where('staff', 0);
            $this->db->delete(db_prefix() . 'dismissed_announcements');

            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'contacts');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('userid', $id);
            $this->db->delete(db_prefix() . 'contact_permissions');

            $this->db->where('user_id', $id);
            $this->db->where('staff', 0);
            $this->db->delete(db_prefix() . 'user_auto_login');

            $this->db->select('ticketid');
            $this->db->where('contactid', $id);
            $this->db->where('userid', $customer_id);
            $tickets = $this->db->get(db_prefix() . 'tickets')->result_array();

            $this->load->model('tickets_model');
            foreach ($tickets as $ticket) {
                $this->tickets_model->delete($ticket['ticketid']);
            }

            $this->load->model('tasks_model');

            $this->db->where('addedfrom', $id);
            $this->db->where('is_added_from_contact', 1);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();

            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id'], false);
            }

            // Added from contact in customer profile
            $this->db->where('contact_id', $id);
            $this->db->where('rel_type', 'customer');
            $attachments = $this->db->get(db_prefix() . 'files')->result_array();

            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            // Remove contact files uploaded to tasks
            $this->db->where('rel_type', 'task');
            $this->db->where('contact_id', $id);
            $filesUploadedFromContactToTasks = $this->db->get(db_prefix() . 'files')->result_array();

            foreach ($filesUploadedFromContactToTasks as $file) {
                $this->tasks_model->remove_task_attachment($file['id']);
            }

            $this->db->where('contact_id', $id);
            $tasksComments = $this->db->get(db_prefix() . 'task_comments')->result_array();
            foreach ($tasksComments as $comment) {
                $this->tasks_model->remove_comment($comment['id'], true);
            }

            $this->load->model('projects_model');

            $this->db->where('contact_id', $id);
            $files = $this->db->get(db_prefix() . 'project_files')->result_array();
            foreach ($files as $file) {
                $this->projects_model->remove_file($file['id'], false);
            }

            $this->db->where('contact_id', $id);
            $discussions = $this->db->get(db_prefix() . 'projectdiscussions')->result_array();
            foreach ($discussions as $discussion) {
                $this->projects_model->delete_discussion($discussion['id'], false);
            }

            $this->db->where('contact_id', $id);
            $discussionsComments = $this->db->get(db_prefix() . 'projectdiscussioncomments')->result_array();
            foreach ($discussionsComments as $comment) {
                $this->projects_model->delete_discussion_comment($comment['id'], false);
            }

            $this->db->where('contact_id', $id);
            $this->db->delete(db_prefix() . 'user_meta');

            $this->db->where('(email="' . $result->email . '" OR bcc LIKE "%' . $result->email . '%" OR cc LIKE "%' . $result->email . '%")');
            $this->db->delete(db_prefix() . 'mail_queue');

            if (is_gdpr()) {
                $this->db->where('email', $result->email);
                $this->db->delete(db_prefix() . 'listemails');

                if (!empty($result->last_ip)) {
                    $this->db->where('ip', $result->last_ip);
                    $this->db->delete(db_prefix() . 'knowedge_base_article_feedback');
                }

                $this->db->where('email', $result->email);
                $this->db->delete(db_prefix() . 'tickets_pipe_log');

                $this->db->where('email', $result->email);
                $this->db->delete(db_prefix() . 'tracked_mails');

                $this->db->where('contact_id', $id);
                $this->db->delete(db_prefix() . 'project_activity');

                $this->db->where('(additional_data LIKE "%' . $result->email . '%" OR full_name LIKE "%' . $result->firstname . ' ' . $result->lastname . '%")');
                $this->db->where('additional_data != "" AND additional_data IS NOT NULL');
                $this->db->delete(db_prefix() . 'sales_activity');

                $contactActivityQuery = false;
                if (!empty($result->email)) {
                    $this->db->or_like('description', $result->email);
                    $contactActivityQuery = true;
                }
                if (!empty($result->firstname)) {
                    $this->db->or_like('description', $result->firstname);
                    $contactActivityQuery = true;
                }
                if (!empty($result->lastname)) {
                    $this->db->or_like('description', $result->lastname);
                    $contactActivityQuery = true;
                }

                if (!empty($result->phonenumber)) {
                    $this->db->or_like('description', $result->phonenumber);
                    $contactActivityQuery = true;
                }

                if (!empty($result->last_ip)) {
                    $this->db->or_like('description', $result->last_ip);
                    $contactActivityQuery = true;
                }

                if ($contactActivityQuery) {
                    $this->db->delete(db_prefix() . 'activity_log');
                }
            }

            // Delete activity log caused by delete contact function
            if ($last_activity) {
                $this->db->where('id >', $last_activity->id);
                $this->db->delete(db_prefix() . 'activity_log');
            }

            hooks()->do_action('contact_deleted', $id, $result);

            return true;
        }

        return false;
    }

    /**
     * Get customer default currency
     * @param  mixed $id customer id
     * @return mixed
     */
    public function get_customer_default_currency($id)
    {
        $this->db->select('default_currency');
        $this->db->where('userid', $id);
        $result = $this->db->get(db_prefix() . 'clients')->row();
        if ($result) {
            return $result->default_currency;
        }

        return false;
    }

    /**
     *  Get customer billing details
     * @param   mixed $id   customer id
     * @return  array
     */
    public function get_customer_billing_and_shipping_details($id)
    {
        $this->db->select('billing_street,billing_city,billing_state,billing_zip,billing_country,shipping_street,shipping_city,shipping_state,shipping_zip,shipping_country');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where('userid', $id);

        $result = $this->db->get()->result_array();
        if (count($result) > 0) {
            $result[0]['billing_street'] = clear_textarea_breaks($result[0]['billing_street']);
            $result[0]['shipping_street'] = clear_textarea_breaks($result[0]['shipping_street']);
        }

        return $result;
    }

    /**
     * Get customer files uploaded in the customer profile
     * @param  mixed $id    customer id
     * @param  array  $where perform where
     * @return array
     */
    public function get_customer_files($id, $where = [])
    {
        $this->db->where($where);
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'customer');
        $this->db->order_by('dateadded', 'desc');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }

    /**
     * Delete customer attachment uploaded from the customer profile
     * @param  mixed $id attachment id
     * @return boolean
     */
    public function delete_attachment($id)
    {
        $this->db->where('id', $id);
        $attachment = $this->db->get(db_prefix() . 'files')->row();
        $deleted = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                $relPath = get_upload_path_by_type('customer') . $attachment->rel_id . '/';
                $fullPath = $relPath . $attachment->file_name;
                unlink($fullPath);
                $fname = pathinfo($fullPath, PATHINFO_FILENAME);
                $fext = pathinfo($fullPath, PATHINFO_EXTENSION);
                $thumbPath = $relPath . $fname . '_thumb.' . $fext;
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                $this->db->where('file_id', $id);
                $this->db->delete(db_prefix() . 'shared_customer_files');
                log_activity('Customer Attachment Deleted [ID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('customer') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('customer') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    delete_dir(get_upload_path_by_type('customer') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update contact status Active/Inactive
     */
    public function change_contact_status($id, $status)
    {
        $status = hooks()->apply_filters('change_contact_status', $status, $id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', [
            'active' => $status,
        ]);
        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('contact_status_changed', [
                'id' => $id,
                'status' => $status,
            ]);

            log_activity('Contact Status Changed [ContactID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @param  integer Status ID
     * @return boolean
     * Update client status Active/Inactive
     */
    public function change_client_status($id, $status)
    {
        $this->db->where('userid', $id);
        $this->db->update('clients', [
            'active' => $status,
        ]);

        if ($this->db->affected_rows() > 0) {
            hooks()->do_action('client_status_changed', [
                'id' => $id,
                'status' => $status,
            ]);

            log_activity('Customer Status Changed [ID: ' . $id . ' Status(Active/Inactive): ' . $status . ']');

            return true;
        }

        return false;
    }

    /**
     * Change contact password, used from client area
     * @param  mixed $id          contact id to change password
     * @param  string $oldPassword old password to verify
     * @param  string $newPassword new password
     * @return boolean
     */
    public function change_contact_password($id, $oldPassword, $newPassword)
    {
        // Get current password
        $this->db->where('id', $id);
        $client = $this->db->get(db_prefix() . 'contacts')->row();

        if (!app_hasher()->CheckPassword($oldPassword, $client->password)) {
            return [
                'old_password_not_match' => true,
            ];
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', [
            'last_password_change' => date('Y-m-d H:i:s'),
            'password' => app_hash_password($newPassword),
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Contact Password Changed [ContactID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Get customer groups where customer belongs
     * @param  mixed $id customer id
     * @return array
     */
    public function get_customer_groups($id)
    {
        return $this->client_groups_model->get_customer_groups($id);
    }

    /**
     * Get all customer groups
     * @param  string $id
     * @return mixed
     */
    public function get_groups($id = '')
    {
        return $this->client_groups_model->get_groups($id);
    }

    /**
     * Delete customer groups
     * @param  mixed $id group id
     * @return boolean
     */
    public function delete_group($id)
    {
        return $this->client_groups_model->delete($id);
    }

    /**
     * Add new customer groups
     * @param array $data $_POST data
     */
    public function add_group($data)
    {
        return $this->client_groups_model->add($data);
    }

    /**
     * Edit customer group
     * @param  array $data $_POST data
     * @return boolean
     */
    public function edit_group($data)
    {
        return $this->client_groups_model->edit($data);
    }

    /**
     * Create new vault entry
     * @param  array $data        $_POST data
     * @param  mixed $customer_id customer id
     * @return boolean
     */
    public function vault_entry_create($data, $customer_id)
    {
        return $this->client_vault_entries_model->create($data, $customer_id);
    }

    /**
     * Update vault entry
     * @param  mixed $id   vault entry id
     * @param  array $data $_POST data
     * @return boolean
     */
    public function vault_entry_update($id, $data)
    {
        return $this->client_vault_entries_model->update($id, $data);
    }

    /**
     * Delete vault entry
     * @param  mixed $id entry id
     * @return boolean
     */
    public function vault_entry_delete($id)
    {
        return $this->client_vault_entries_model->delete($id);
    }

    /**
     * Get customer vault entries
     * @param  mixed $customer_id
     * @param  array  $where       additional wher
     * @return array
     */
    public function get_vault_entries($customer_id, $where = [])
    {
        return $this->client_vault_entries_model->get_by_customer_id($customer_id, $where);
    }

    /**
     * Get single vault entry
     * @param  mixed $id vault entry id
     * @return object
     */
    public function get_vault_entry($id)
    {
        return $this->client_vault_entries_model->get($id);
    }

    /**
     * Get customer statement formatted
     * @param  mixed $customer_id customer id
     * @param  string $from        date from
     * @param  string $to          date to
     * @return array
     */
    public function get_statement($customer_id, $from, $to)
    {
        return $this->statement_model->get_statement($customer_id, $from, $to);
    }

    /**
     * Send customer statement to email
     * @param  mixed $customer_id customer id
     * @param  array $send_to     array of contact emails to send
     * @param  string $from        date from
     * @param  string $to          date to
     * @param  string $cc          email CC
     * @return boolean
     */
    public function send_statement_to_email($customer_id, $send_to, $from, $to, $cc = '')
    {
        return $this->statement_model->send_statement_to_email($customer_id, $send_to, $from, $to, $cc);
    }

    /**
     * When customer register, mark the contact and the customer as inactive and set the registration_confirmed field to 0
     * @param  mixed $client_id  the customer id
     * @return boolean
     */
    public function require_confirmation($client_id)
    {
        $contact_id = get_primary_contact_user_id($client_id);
        $this->db->where('userid', $client_id);
        $this->db->update(db_prefix() . 'clients', ['active' => 0, 'registration_confirmed' => 0]);

        $this->db->where('id', $contact_id);
        $this->db->update(db_prefix() . 'contacts', ['active' => 0]);

        return true;
    }

    public function confirm_registration($client_id)
    {
        $contact_id = get_primary_contact_user_id($client_id);
        $this->db->where('userid', $client_id);
        $this->db->update(db_prefix() . 'clients', ['active' => 1, 'registration_confirmed' => 1]);

        $this->db->where('id', $contact_id);
        $this->db->update(db_prefix() . 'contacts', ['active' => 1]);

        $contact = $this->get_contact($contact_id);

        if ($contact) {
            send_mail_template('customer_registration_confirmed', $contact);

            return true;
        }

        return false;
    }

    public function send_verification_email($id)
    {
        $contact = $this->get_contact($id);

        if (empty($contact->email)) {
            return false;
        }

        $success = send_mail_template('customer_contact_verification', $contact);

        if ($success) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'contacts', ['email_verification_sent_at' => date('Y-m-d H:i:s')]);
        }

        return $success;
    }

    public function mark_email_as_verified($id)
    {
        $contact = $this->get_contact($id);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'email_verification_key' => null,
            'email_verification_sent_at' => null,
        ]);

        if ($this->db->affected_rows() > 0) {

            // Check for previous tickets opened by this email/contact and link to the contact
            $this->load->model('tickets_model');
            $this->tickets_model->transfer_email_tickets_to_contact($contact->email, $contact->id);

            return true;
        }

        return false;
    }

    public function get_clients_distinct_countries()
    {
        return $this->db->query('SELECT DISTINCT(country_id), short_name FROM ' . db_prefix() . 'clients JOIN ' . db_prefix() . 'countries ON ' . db_prefix() . 'countries.country_id=' . db_prefix() . 'clients.country')->result_array();
    }

    public function send_notification_customer_profile_file_uploaded_to_responsible_staff($contact_id, $customer_id)
    {
        $staff = $this->get_staff_members_that_can_access_customer($customer_id);
        $merge_fields = $this->app_merge_fields->format_feature('client_merge_fields', $customer_id, $contact_id);
        $notifiedUsers = [];

        foreach ($staff as $member) {
            mail_template('customer_profile_uploaded_file_to_staff', $member['email'], $member['staffid'])
                ->set_merge_fields($merge_fields)
                ->send();

            if (
                add_notification([
                    'touserid' => $member['staffid'],
                    'description' => 'not_customer_uploaded_file',
                    'link' => 'clients/client/' . $customer_id . '?group=attachments',
                ])
            ) {
                array_push($notifiedUsers, $member['staffid']);
            }
        }
        pusher_trigger_notification($notifiedUsers);
    }

    public function get_staff_members_that_can_access_customer($id)
    {
        $id = $this->db->escape_str($id);

        return $this->db->query('SELECT * FROM ' . db_prefix() . 'staff
            WHERE (
                    admin=1
                    OR staffid IN (SELECT staff_id FROM ' . db_prefix() . "customer_admins WHERE customer_id='.$id.')
                    OR staffid IN(SELECT staff_id FROM " . db_prefix() . 'staff_permissions WHERE feature = "customers" AND capability="view")
                )
            AND active=1')->result_array();
    }

    private function check_zero_columns($data)
    {
        if (!isset($data['show_primary_contact'])) {
            $data['show_primary_contact'] = 0;
        }

        if (isset($data['default_currency']) && $data['default_currency'] == '' || !isset($data['default_currency'])) {
            $data['default_currency'] = 0;
        }

        if (isset($data['country']) && $data['country'] == '' || !isset($data['country'])) {
            $data['country'] = 0;
        }

        if (isset($data['billing_country']) && $data['billing_country'] == '' || !isset($data['billing_country'])) {
            $data['billing_country'] = 0;
        }

        if (isset($data['shipping_country']) && $data['shipping_country'] == '' || !isset($data['shipping_country'])) {
            $data['shipping_country'] = 0;
        }

        return $data;
    }

    public function delete_contact_profile_image($id)
    {
        hooks()->do_action('before_remove_contact_profile_image');
        if (file_exists(get_upload_path_by_type('contact_profile_images') . $id)) {
            delete_dir(get_upload_path_by_type('contact_profile_images') . $id);
        }
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'contacts', [
            'profile_image' => null,
        ]);
    }

    /**
     * @param $projectId
     * @param  string  $tasks_email
     *
     * @return array[]
     */
    public function get_contacts_for_project_notifications($projectId, $type)
    {
        $this->db->select('clientid,contact_notification,notify_contacts');
        $this->db->from(db_prefix() . 'projects');
        $this->db->where('id', $projectId);
        $project = $this->db->get()->row();

        if (!in_array($project->contact_notification, [1, 2])) {
            return [];
        }

        $this->db
            ->where('userid', $project->clientid)
            ->where('active', 1)
            ->where($type, 1);

        if ($project->contact_notification == 2) {
            $projectContacts = unserialize($project->notify_contacts);
            $this->db->where_in('id', $projectContacts);
        }

        return $this->db->get(db_prefix() . 'contacts')->result_array();
    }
}
