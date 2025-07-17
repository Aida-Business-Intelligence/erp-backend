<?php
if (!headers_sent()) {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/../REST_Controller.php';

class Receivables extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Receivables_model');
    }

    public function warehouselist_get()
    {
        try {
            $warehouses = $this->Receivables_model->get_warehouses();

            $this->response([
                'success' => true,
                'data' => $warehouses
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_get($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $receivable = $this->Receivables_model->get_receivable_by_id($id);
            
            if ($receivable) {
                return $this->response([
                    'status' => true,
                    'data' => $receivable
                ], REST_Controller::HTTP_OK);
            } else {
                return $this->response([
                    'status' => false,
                    'message' => 'Receita não encontrada'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function validateduplicates_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $input = $this->post();

        if (
            empty($input['warehouse_id']) ||
            empty($input['data']) ||
            empty($input['mappedColumns'])
        ) {
            return $this->response([
                'status' => false,
                'message' => 'Parâmetros obrigatórios ausentes'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $duplicates = $this->Receivables_model->validate_duplicates(
            $input['warehouse_id'],
            $input['data'],
            $input['mappedColumns']
        );

        return $this->response([
            'status' => true,
            'duplicates' => $duplicates,
        ], REST_Controller::HTTP_OK);
    }

    public function summary_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $summary = $this->Receivables_model->get_receivables_summary($warehouse_id);

        $total = $summary['received'] + $summary['to_receive'] + $summary['overdue'];
        $received_percent = $total > 0 ? round(($summary['received'] / $total) * 100, 1) : 0;

        return $this->response([
            'status' => true,
            'data' => [
                'received' => $summary['received'],
                'received_count' => $summary['received_count'],
                'received_today' => $summary['received_today'],
                'received_today_count' => $summary['received_today_count'],
                'to_receive' => $summary['to_receive'],
                'to_receive_count' => $summary['to_receive_count'],
                'to_receive_month' => $summary['to_receive_month'],
                'to_receive_month_count' => $summary['to_receive_month_count'],
                'overdue' => $summary['overdue'],
                'overdue_count' => $summary['overdue_count'],
                'received_percent' => $received_percent
            ],
        ], REST_Controller::HTTP_OK);
    }

    public function list_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $filters = [
            'warehouse_id' => $this->post('warehouse_id'),
            'search'       => $this->post('search'),
            'category'     => $this->post('category'),
            'status'       => $this->post('status'),
            'startDate'    => $this->post('startDate'),
            'endDate'      => $this->post('endDate'),
        ];

        if (empty($filters['warehouse_id'])) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $page      = (int) ($this->post('page') ?? 0);
        $pageSize  = (int) ($this->post('pageSize') ?? 10);
        $sortField = $this->post('sortField') ?? 'id';
        $sortOrder = strtolower($this->post('sortOrder')) === 'desc' ? 'DESC' : 'ASC';

        $data = $this->Receivables_model->get_receivables($filters, $page, $pageSize, $sortField, $sortOrder);
        $total = $this->Receivables_model->count_receivables($filters);

        return $this->response([
            'status'      => true,
            'data'        => $data,
            'total'       => $total,
            'page'        => $page + 1,
            'limit'       => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ], REST_Controller::HTTP_OK);
    }

    public function payment_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);

        $id = $input['id'] ?? null;
        $status = $input['status'] ?? null;

        if (empty($id) || !in_array($status, ['pending', 'received'])) {
            return $this->response([
                'status' => false,
                'message' => 'ID e status são obrigatórios (pending ou received)'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Novos campos do payload
        $fields = [
            'juros', 'desconto', 'multa', 'valorPago', 'comprovante', 'descricao_recebimento', 'bank_account_id', 'category_id', 'payment_date'
        ];
        $data = [
            'status' => $status,
        ];

        // Buscar registro atual para pegar o caminho do comprovante antigo
        $this->load->model('Receivables_model');
        $current = $this->Receivables_model->get_receivable_by_id($id);
        $old_voucher = $current && isset($current->comprovante) ? $current->comprovante : null;
        $voucher_path = $old_voucher;
        $voucher_was_updated = false;

        foreach ($fields as $field) {
            $value = $input[$field] ?? null;
            if ($field === 'comprovante') {
                $voucher_path = $old_voucher;
                if (!empty($value) && preg_match('/^data:(.+);base64,/', $value, $matches)) {
                    $mime_type = $matches[1];
                    $document_data = substr($value, strpos($value, ',') + 1);
                    $allowed_types = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/jpg',
                        'image/png'
                    ];
                    if (!in_array($mime_type, $allowed_types)) {
                        return $this->response([
                            'status' => false,
                            'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                    $document_data = base64_decode($document_data);
                    if ($document_data === false) {
                        return $this->response([
                            'status' => false,
                            'message' => 'Falha ao decodificar o comprovante'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                    if (strlen($document_data) > 5 * 1024 * 1024) {
                        return $this->response([
                            'status' => false,
                            'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                    }
                    // Apagar o arquivo antigo, se existir e não for base64
                    if ($old_voucher && strpos($old_voucher, 'data:') !== 0) {
                        $old_path = FCPATH . ltrim($old_voucher, '/');
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                    // Corrigir caminho absoluto para garantir barra
                    $upload_path = rtrim(FCPATH, '/\\') . '/uploads/receivables/vouchers/';
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0755, true);
                    }
                    $extension_map = [
                        'application/pdf' => 'pdf',
                        'application/msword' => 'doc',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                        'image/jpeg' => 'jpg',
                        'image/jpg' => 'jpg',
                        'image/png' => 'png'
                    ];
                    $extension = $extension_map[$mime_type] ?? 'bin';
                    $filename = 'voucher_' . $id . '_' . time() . '_' . uniqid() . '.' . $extension;
                    $file_path = $upload_path . $filename;
                    if (file_put_contents($file_path, $document_data)) {
                        $voucher_path = 'uploads/receivables/vouchers/' . $filename;
                    } else {
                        return $this->response([
                            'status' => false,
                            'message' => 'Falha ao salvar o comprovante no servidor'
                        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                    }
                } else if ($value === '' || $value === null) {
                    // Se veio vazio, apagar arquivo antigo
                    if ($old_voucher && strpos($old_voucher, 'data:') !== 0) {
                        $old_path = FCPATH . ltrim($old_voucher, '/');
                        if (file_exists($old_path)) {
                            @unlink($old_path);
                        }
                    }
                    $voucher_path = null;
                } else if (is_string($value) && strpos($value, 'uploads/') === 0) {
                    $voucher_path = $value;
                }
            } else if ($value !== null) {
                if ($field === 'valorPago') $data['valor_recebido'] = $value;
                elseif ($field === 'payment_date') $data['data_pagamento'] = $value;
                elseif ($field === 'category_id') $data['category'] = $value;
                else $data[$field] = $value;
            }
        }
        $data['comprovante'] = $voucher_path;

        $this->db->where('id', $id);
        $success = $this->db->update(db_prefix() . 'receivables', $data);

        if ($success || $voucher_was_updated) {
            return $this->response([
                'status' => true,
                'message' => 'Status e dados atualizados com sucesso'
            ], REST_Controller::HTTP_OK);
        }

        return $this->response([
            'status' => false,
            'message' => 'Falha ao atualizar status/dados'
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function pay_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        $id = $this->post('id');
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $fields = [
            'juros', 'desconto', 'multa', 'valor_recebido', 'comprovante', 'data_pagamento', 'descricao_recebimento', 'bank_account_id', 'category_id'
        ];
        $data = [];
        foreach ($fields as $field) {
            $value = $this->post($field);
            if ($value !== null) {
                $data[$field] = $value;
            }
        }
        $data['status'] = 'received';
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $success = $this->db->update(db_prefix() . 'receivables', $data);
        if ($success) {
            return $this->response([
                'status' => true,
                'message' => 'Recebimento baixado com sucesso'
            ], REST_Controller::HTTP_OK);
        }
        return $this->response([
            'status' => false,
            'message' => 'Falha ao baixar recebimento'
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function payment_modes_get()
    {
        try {
            $paymentModes = $this->Receivables_model->get_payment_modes();

            $this->response([
                'success' => true,
                'data' => $paymentModes
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function clients_get()
    {
        try {
            $warehouse_id = $this->input->get('warehouse_id') ?: 0;
            $search = $this->input->get('search') ?: '';
            $page = $this->input->get('page') ?: 0;
            $limit = $this->input->get('pageSize') ?: 5;

            $clients = $this->Receivables_model->get_clients($warehouse_id, $search, $limit, $page);

            $this->response([
                'success' => true,
                'data' => $clients
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Métodos CRUD básicos para Receitas
    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        // Logar Content-Type
        $log_content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);
        $raw_input = file_get_contents('php://input');

        // Detectar tipo de conteúdo
        $content_type = $log_content_type;
        $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            $input = $this->input->post();
        } else {
            $input = json_decode($raw_input, true);
        }

        // Validação robusta dos campos obrigatórios
        $required = [];
        if (!isset($input['category']) || $input['category'] === '' || $input['category'] === null) $required[] = 'category';
        if (!isset($input['amount']) || $input['amount'] === '' || $input['amount'] === null) $required[] = 'amount';
        if (!isset($input['date']) || $input['date'] === '' || $input['date'] === null) $required[] = 'date';
        if (!isset($input['warehouse_id']) || $input['warehouse_id'] === '' || $input['warehouse_id'] === null) $required[] = 'warehouse_id';
        if (!isset($input['origin_id']) || $input['origin_id'] === '' || $input['origin_id'] === null) $required[] = 'origin_id';
        if (!isset($input['receivable_identifier']) || $input['receivable_identifier'] === '' || $input['receivable_identifier'] === null) $required[] = 'receivable_identifier';
        if (!empty($required)) {
            return $this->response([
                'status' => false,
                'message' => 'Campos obrigatórios: ' . implode(', ', $required),
                'debug_input' => $input,
                'debug_files' => $_FILES,
                'debug__POST' => $_POST,
                'debug_raw_input' => $raw_input,
                'debug_content_type' => $log_content_type
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $receivables_document = null;
        // Salvar documento apenas se vier base64 válido (igual despesas)
        if (!empty($input['receivables_document']) && preg_match('/^data:(.+);base64,/', $input['receivables_document'], $matches)) {
            $mime_type = $matches[1];
            $document_data = substr($input['receivables_document'], strpos($input['receivables_document'], ',') + 1);
            $allowed_types = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/jpg',
                'image/png'
            ];
            if (!in_array($mime_type, $allowed_types)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $document_data = base64_decode($document_data);
            if ($document_data === false) {
                return $this->response([
                    'status' => false,
                    'message' => 'Falha ao decodificar o documento'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            if (strlen($document_data) > 5 * 1024 * 1024) {
                return $this->response([
                    'status' => false,
                    'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $upload_path = FCPATH . 'uploads/receivables/documents/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            $extension_map = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png'
            ];
            $extension = $extension_map[$mime_type] ?? 'bin';
            $filename = 'receivable_' . time() . '_' . uniqid() . '.' . $extension;
            $file_path = $upload_path . $filename;
            if (file_put_contents($file_path, $document_data)) {
                $receivables_document = 'uploads/receivables/documents/' . $filename;
            } else {
                return $this->response([
                    'status' => false,
                    'message' => 'Falha ao salvar o documento no servidor'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        $data = [
            'category' => $input['category'],
            'currency' => $input['currency'] ?? 1,
            'amount' => $input['amount'],
            'tax' => $input['tax'] ?? null,
            'tax2' => $input['tax2'] ?? 0,
            'reference_no' => $input['reference_no'] ?? null,
            'note' => $input['note'] ?? null,
            'expense_name' => $input['expense_name'] ?? null,
            'receivable_identifier' => $input['receivable_identifier'],
            'clientid' => $input['clientid'] ?? null,
            'project_id' => $input['project_id'] ?? 0,
            'billable' => isset($input['billable']) ? ($input['billable'] ? 1 : 0) : 0,
            'invoiceid' => $input['invoiceid'] ?? null,
            'paymentmode' => $input['paymentmode'] ?? 0,
            'date' => $input['date'],
            'due_date' => $input['due_date'] ?? null,
            'reference_date' => $input['reference_date'] ?? null,
            'order_number' => $input['order_number'] ?? null,
            'installment_number' => $input['installment_number'] ?? null,
            'nfe_key' => $input['nfe_key'] ?? null,
            'barcode' => $input['barcode'] ?? null,
            'origin_id' => $input['origin_id'],
            'recurring_type' => $input['recurring_type'] ?? null,
            'repeat_every' => $input['repeat_every'] ?? null,
            'recurring' => isset($input['recurring']) ? ($input['recurring'] ? 1 : 0) : 0,
            'cycles' => $input['cycles'] ?? 0,
            'total_cycles' => $input['total_cycles'] ?? 0,
            'custom_recurring' => isset($input['custom_recurring']) ? ($input['custom_recurring'] ? 1 : 0) : 0,
            'last_recurring_date' => $input['last_recurring_date'] ?? null,
            'create_invoice_billable' => isset($input['create_invoice_billable']) ? ($input['create_invoice_billable'] ? 1 : 0) : 0,
            'send_invoice_to_customer' => isset($input['send_invoice_to_customer']) ? ($input['send_invoice_to_customer'] ? 1 : 0) : 0,
            'recurring_from' => $input['recurring_from'] ?? null,
            'dateadded' => date('Y-m-d H:i:s'),
            'addedfrom' => get_staff_user_id() ?? 1,
            'perfex_saas_tenant_id' => 'master',
            'status' => $input['status'] ?? 'pending',
            'warehouse_id' => $input['warehouse_id'],
            'receivables_document' => $receivables_document,
            'registration_date' => $input['registration_date'] ?? null,
        ];
        $data = array_filter($data, function ($v) { return $v !== null; });
        $this->db->insert(db_prefix() . 'receivables', $data);
        $id = $this->db->insert_id();
        if ($id) {
            return $this->response([
                'status' => true,
                'message' => 'Receita criada com sucesso',
                'data' => ['id' => $id]
            ], REST_Controller::HTTP_CREATED);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao criar receita'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_post($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        $raw_input = file_get_contents('php://input');
        $input = json_decode($raw_input, true);
        if (empty($input)) {
            return $this->response([
                'status' => false,
                'message' => 'Dados inválidos'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        // Validação robusta dos campos obrigatórios
        $required = [];
        if (!isset($input['category']) || $input['category'] === '' || $input['category'] === null) $required[] = 'category';
        if (!isset($input['amount']) || $input['amount'] === '' || $input['amount'] === null) $required[] = 'amount';
        if (!isset($input['date']) || $input['date'] === '' || $input['date'] === null) $required[] = 'date';
        if (!isset($input['warehouse_id']) || $input['warehouse_id'] === '' || $input['warehouse_id'] === null) $required[] = 'warehouse_id';
        if (!isset($input['origin_id']) || $input['origin_id'] === '' || $input['origin_id'] === null) $required[] = 'origin_id';
        if (!isset($input['receivable_identifier']) || $input['receivable_identifier'] === '' || $input['receivable_identifier'] === null) $required[] = 'receivable_identifier';
        if (!empty($required)) {
            return $this->response([
                'status' => false,
                'message' => 'Campos obrigatórios: ' . implode(', ', $required),
                'debug_input' => $input
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        // Buscar registro atual para pegar o caminho do arquivo antigo
        $this->load->model('Receivables_model');
        $current = $this->Receivables_model->get_receivable_by_id($id);
        $old_document = $current && isset($current->receivables_document) ? $current->receivables_document : null;
        $receivables_document = $old_document;
        $document_was_updated = false;
        // Processar documento
        if (array_key_exists('receivables_document', $input)) {
            $document_data = $input['receivables_document'];
            if (empty($document_data)) {
                // Se veio vazio, apagar arquivo antigo
                if ($old_document && strpos($old_document, 'data:') !== 0) {
                    $old_path = FCPATH . ltrim($old_document, '/');
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                }
                $receivables_document = null;
                $document_was_updated = true;
            } else if (preg_match('/^data:(.+);base64,/', $document_data, $matches)) {
                $mime_type = $matches[1];
                $document_data = substr($document_data, strpos($document_data, ',') + 1);
                $allowed_types = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/jpg',
                    'image/png'
                ];
                if (!in_array($mime_type, $allowed_types)) {
                    return $this->response([
                        'status' => false,
                        'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                $document_data = base64_decode($document_data);
                if ($document_data === false) {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao decodificar o documento'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                if (strlen($document_data) > 5 * 1024 * 1024) {
                    return $this->response([
                        'status' => false,
                        'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                // Apagar o arquivo antigo, se existir e não for base64
                if ($old_document && strpos($old_document, 'data:') !== 0) {
                    $old_path = FCPATH . ltrim($old_document, '/');
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                }
                $upload_path = FCPATH . 'uploads/receivables/documents/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }
                $extension_map = [
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png'
                ];
                $extension = $extension_map[$mime_type] ?? 'bin';
                $filename = 'receivable_' . time() . '_' . uniqid() . '.' . $extension;
                $file_path = $upload_path . $filename;
                if (file_put_contents($file_path, $document_data)) {
                    $receivables_document = 'uploads/receivables/documents/' . $filename;
                    $document_was_updated = true;
                } else {
                    return $this->response([
                        'status' => false,
                        'message' => 'Falha ao salvar o documento no servidor'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else if (is_string($document_data) && strpos($document_data, 'uploads/') === 0) {
                // Se for caminho relativo, apenas atualiza o campo
                $receivables_document = $document_data;
                $document_was_updated = true;
            }
        }
        $data = [
            'category' => $input['category'],
            'currency' => $input['currency'] ?? 1,
            'amount' => $input['amount'],
            'tax' => $input['tax'] ?? null,
            'tax2' => $input['tax2'] ?? 0,
            'reference_no' => $input['reference_no'] ?? null,
            'note' => $input['note'] ?? null,
            'expense_name' => $input['expense_name'] ?? null,
            'receivable_identifier' => $input['receivable_identifier'],
            'clientid' => $input['clientid'] ?? null,
            'project_id' => $input['project_id'] ?? 0,
            'billable' => isset($input['billable']) ? ($input['billable'] ? 1 : 0) : 0,
            'invoiceid' => $input['invoiceid'] ?? null,
            'paymentmode' => $input['paymentmode'] ?? 0,
            'date' => $input['date'],
            'due_date' => $input['due_date'] ?? null,
            'reference_date' => $input['reference_date'] ?? null,
            'order_number' => $input['order_number'] ?? null,
            'installment_number' => $input['installment_number'] ?? null,
            'nfe_key' => $input['nfe_key'] ?? null,
            'barcode' => $input['barcode'] ?? null,
            'origin_id' => $input['origin_id'],
            'recurring_type' => $input['recurring_type'] ?? null,
            'repeat_every' => $input['repeat_every'] ?? null,
            'recurring' => isset($input['recurring']) ? ($input['recurring'] ? 1 : 0) : 0,
            'cycles' => $input['cycles'] ?? 0,
            'total_cycles' => $input['total_cycles'] ?? 0,
            'custom_recurring' => isset($input['custom_recurring']) ? ($input['custom_recurring'] ? 1 : 0) : 0,
            'last_recurring_date' => $input['last_recurring_date'] ?? null,
            'create_invoice_billable' => isset($input['create_invoice_billable']) ? ($input['create_invoice_billable'] ? 1 : 0) : 0,
            'send_invoice_to_customer' => isset($input['send_invoice_to_customer']) ? ($input['send_invoice_to_customer'] ? 1 : 0) : 0,
            'recurring_from' => $input['recurring_from'] ?? null,
            'warehouse_id' => $input['warehouse_id'],
            'receivables_document' => $receivables_document,
            'due_day' => $input['due_day'] ?? null,
            'due_day_2' => $input['due_day_2'] ?? null,
            'installments' => $input['installments'] ?? null,
            'consider_business_days' => $input['consider_business_days'] ?? null,
            'week_day' => $input['week_day'] ?? null,
            'end_date' => $input['end_date'] ?? null,
            'boleto_number' => $input['boleto_number'] ?? null,
            'nfe_number' => $input['nfe_number'] ?? null,
            'bank_account_id' => $input['bank_account_id'] ?? null,
            'registration_date' => $input['registration_date'] ?? null,
        ];
        $data = array_filter($data, function ($v) { return $v !== null; });
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'receivables', $data);
        if ($this->db->affected_rows() > 0 || $document_was_updated) {
            return $this->response([
                'status' => true,
                'message' => 'Receita atualizada com sucesso'
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao atualizar receita ou nenhum dado alterado'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        $id = $this->post('id');
        if (empty($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID é obrigatório para deletar'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        // Buscar o documento associado
        $this->load->model('Receivables_model');
        $receivable = $this->Receivables_model->get_receivable_by_id($id);
        if ($receivable && !empty($receivable->receivables_document)) {
            $file_path = FCPATH . $receivable->receivables_document;
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'receivables');
        if ($this->db->affected_rows() > 0) {
            return $this->response([
                'status' => true,
                'message' => 'Receita deletada com sucesso'
            ], REST_Controller::HTTP_OK);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Falha ao deletar receita'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Listar origens
    public function origins_list_get()
    {
        try {
            $warehouse_id = $this->input->get('warehouse_id');
            $search = $this->input->get('search') ?: '';
            $page = (int)($this->input->get('page') ?? 1);
            $pageSize = (int)($this->input->get('pageSize') ?? 5);
            $offset = ($page - 1) * $pageSize;

            if (empty($warehouse_id)) {
                return $this->response([
                    'success' => false,
                    'message' => 'warehouse_id é obrigatório'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->db->select('id, name, description');
            $this->db->from(db_prefix() . 'origins');
            $this->db->where('warehouse_id', $warehouse_id);
            if ($search) {
                $this->db->like('name', $search);
            }
            $total = $this->db->count_all_results('', false);
            $this->db->order_by('name', 'ASC');
            $this->db->limit($pageSize, $offset);
            $origins = $this->db->get()->result_array();

            return $this->response([
                'success' => true,
                'data' => $origins,
                'total' => $total,
                'page' => $page,
                'pageSize' => $pageSize
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Criar origem
    public function origins_create_post()
    {
        try {
            $input = $this->input->post();
            if (empty($input)) {
                $input = json_decode(file_get_contents('php://input'), true);
            }
            if (empty($input['name']) || empty($input['warehouse_id'])) {
                return $this->response([
                    'success' => false,
                    'message' => 'Nome e warehouse_id são obrigatórios'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $data = [
                'name' => $input['name'],
                'description' => $input['description'] ?? null,
                'warehouse_id' => $input['warehouse_id'],
            ];
            $this->db->insert(db_prefix() . 'origins', $data);
            $id = $this->db->insert_id();

            return $this->response([
                'success' => true,
                'data' => ['id' => $id]
            ], REST_Controller::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Excluir origem
    public function origins_delete_post()
    {
        try {
            $id = $this->input->post('id');
            if (empty($id)) {
                // Tenta pegar do JSON puro
                $input = json_decode(file_get_contents('php://input'), true);
                $id = $input['id'] ?? null;
            }
            if (empty($id)) {
                return $this->response([
                    'success' => false,
                    'message' => 'ID é obrigatório'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            $this->db->where('id', $id);
            try {
                $this->db->delete(db_prefix() . 'origins');
                if ($this->db->affected_rows() > 0) {
                    return $this->response([
                        'success' => true,
                        'message' => 'Origem excluída com sucesso'
                    ], REST_Controller::HTTP_OK);
                } else {
                    return $this->response([
                        'success' => false,
                        'message' => 'Falha ao excluir origem'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            } catch (Exception $e) {
                // Verifica se é erro de restrição de chave estrangeira
                if (strpos($e->getMessage(), 'a foreign key constraint fails') !== false) {
                    return $this->response([
                        'success' => false,
                        'message' => 'Não é possível excluir esta origem pois ela está associada a uma ou mais receitas.'
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                return $this->response([
                    'success' => false,
                    'message' => $e->getMessage()
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            return $this->response([
                'success' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Listar recebíveis por dia (por data de vencimento)
    public function list_by_day_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $warehouse_id = $this->post('warehouse_id');
        $date = $this->post('date');
        $page = (int)($this->post('page') ?? 1);
        $pageSize = (int)($this->post('pageSize') ?? 10);

        if (empty($warehouse_id) || empty($date)) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id e date são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $params = [
            'warehouse_id' => $warehouse_id,
            'date' => $date,
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        $result = $this->Receivables_model->get_receivables_by_day($params);

        return $this->response([
            'status' => true,
            'total' => $result['total'],
            'page' => $page,
            'limit' => $pageSize,
            'total_pages' => ceil($result['total'] / $pageSize),
            'data' => $result['data']
        ], REST_Controller::HTTP_OK);
    }


}