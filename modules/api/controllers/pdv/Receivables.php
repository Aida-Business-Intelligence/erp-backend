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

    /**
     * Obter parcelas de uma receita
     * @param int $id ID da receita
     */
    public function installments_get($id = null)
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        try {
            if (!$id) {
                throw new Exception('ID da receita é obrigatório');
            }

            $this->load->model('Receivables_installments_model');
            
            $installments = $this->Receivables_installments_model->get_installments_by_receivable($id);
            $summary = $this->Receivables_installments_model->get_installments_summary($id);

            return $this->response([
                'status' => true,
                'data' => [
                    'installments' => $installments,
                    'summary' => $summary
                ]
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
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
        $installment_id = $input['installment_id'] ?? null;

        if (empty($id) || !in_array($status, ['pending', 'received'])) {
            return $this->response([
                'status' => false,
                'message' => 'ID e status são obrigatórios (pending ou received)'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Se não há installment_id, buscar a primeira parcela disponível
        if (!$installment_id) {
            $this->load->model('Receivables_installments_model');
            $installments = $this->Receivables_installments_model->get_installments_by_receivable($id);
            
            if (empty($installments)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Nenhuma parcela encontrada para esta receita'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
            
            // Usar a primeira parcela não recebida, ou a primeira se todas estiverem recebidas
            $first_installment = null;
            foreach ($installments as $installment) {
                if ($installment['status'] !== 'Pago') {
                    $first_installment = $installment;
                    break;
                }
            }
            
            if (!$first_installment) {
                $first_installment = $installments[0]; // Usar a primeira se todas estiverem pagas
            }
            
            $installment_id = $first_installment['id'];
        }

        // Sempre processar através do sistema de parcelas
        return $this->receive_installment($id, $installment_id, $input);
    }

    /**
     * Receber uma parcela específica
     * @param int $receivable_id ID da receita
     * @param int $installment_id ID da parcela
     * @param array $data Dados do recebimento
     * @return mixed
     */
    private function receive_installment($receivable_id, $installment_id, $data)
    {
        try {
            $this->load->model('Receivables_installments_model');
            
            // Verificar se a parcela existe
            $installment = $this->Receivables_installments_model->get_installment($installment_id);
            if (!$installment) {
                throw new Exception('Parcela não encontrada');
            }
            
            // Verificar se a parcela pertence à receita
            if ($installment->receivables_id != $receivable_id) {
                throw new Exception('Parcela não pertence à receita informada');
            }
            
            // Preparar dados do recebimento
            $payment_data = [
                'data_pagamento' => $data['payment_date'] ?? date('Y-m-d'),
                'valor_pago' => $data['valorPago'] ?? $installment->valor_com_juros,
                'banco_id' => $data['bank_account_id'] ?? null,
                'observacoes' => $data['descricao_recebimento'] ?? null,
                'juros_adicional' => $data['juros'] ?? 0,
                'desconto' => $data['desconto'] ?? 0,
                'multa' => $data['multa'] ?? 0,
                'id_cheque' => $data['check_identifier'] ?? null,
                'id_boleto' => $data['boleto_identifier'] ?? null,
            ];
            
            // Upload do comprovante (voucher)
            $voucher_path = null;
            if (!empty($data['comprovante'])) {
                $voucher_path = $this->upload_voucher($receivable_id, $data['comprovante']);
            }
            
            // Adicionar o caminho do comprovante aos dados de pagamento
            $payment_data['comprovante'] = $voucher_path;
            
            // Realizar o recebimento
            $success = $this->Receivables_installments_model->receive_installment($installment_id, $payment_data);
            if (!$success) {
                throw new Exception('Falha ao processar recebimento da parcela');
            }
            
            // Verificar se todas as parcelas foram recebidas
            $summary = $this->Receivables_installments_model->get_installments_summary($receivable_id);
            
            // Log para debug
            log_message('debug', 'Receivable ID: ' . $receivable_id . ' - Parcelas pendentes: ' . $summary['parcelas_pendentes'] . ' - Total parcelas: ' . $summary['total_parcelas']);
            
            if ($summary['parcelas_pendentes'] == 0) {
                // Marcar receita como recebida apenas quando todas as parcelas forem pagas
                log_message('debug', 'Todas as parcelas foram recebidas. Marcando receita como received.');
                $this->db->where('id', $receivable_id);
                $this->db->update(db_prefix() . 'receivables', [
                    'status' => 'received'
                ]);
            } else {
                // Se ainda há parcelas pendentes, manter como pending e atualizar apenas o due_date
                log_message('debug', 'Ainda há ' . $summary['parcelas_pendentes'] . ' parcelas pendentes. Mantendo receita como pending.');
                // O due_date será atualizado automaticamente pelo método update_receivable_due_date
                // que é chamado dentro do receive_installment do Receivables_installments_model
            }
            
            return $this->response([
                'status' => true,
                'message' => 'Parcela recebida com sucesso',
                'voucher_url' => $voucher_path ? base_url($voucher_path) : null,
                'installment_summary' => $summary
            ], REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            return $this->response([
                'status' => false,
                'message' => 'Erro: ' . $e->getMessage(),
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            $type = $this->input->get('type') ?: 'clients'; // 'clients' ou 'franchisees'

            if ($type === 'franchisees') {
                $clients = $this->Receivables_model->get_franchisees($warehouse_id, $search, $limit, $page);
            } else {
                $clients = $this->Receivables_model->get_clients($warehouse_id, $search, $limit, $page);
            }

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
            'nfe_number' => $input['nfe_number'] ?? null,
            'boleto_number' => $input['boleto_number'] ?? null,
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
            'is_staff' => isset($input['is_staff']) ? ($input['is_staff'] ? 1 : 0) : 0,
            // Campos de parcelamento de cartão de crédito, cheque e boleto
            'num_parcelas' => $input['num_parcelas'] ?? 1,
            'juros' => $input['juros'] ?? 0,
            'juros_apartir' => $input['juros_apartir'] ?? 1,
            'total_parcelado' => $input['total_parcelado'] ?? $input['amount'],
        ];
        $data = array_filter($data, function ($v) { return $v !== null; });
        
        // Processar parcelas se fornecidas
        $installments = null;
        if (isset($data['num_parcelas']) && $data['num_parcelas'] > 1) {
            // Criar array temporário com todos os campos necessários para processar parcelas
            $installment_data = array_merge($data, [
                'tipo_juros' => $input['tipo_juros'] ?? 'simples',
            ]);
            $installments = $this->process_installments($installment_data);
            $data['installments'] = $installments;
        }

        $this->load->model('Receivables_model');
        $id = $this->Receivables_model->add($data);
        
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
            'is_staff' => isset($input['is_staff']) ? ($input['is_staff'] ? 1 : 0) : 0,
            // Campos de parcelamento de cartão de crédito, cheque e boleto
            'num_parcelas' => $input['num_parcelas'] ?? 1,
            'juros' => $input['juros'] ?? 0,
            'juros_apartir' => $input['juros_apartir'] ?? 1,
            'total_parcelado' => $input['total_parcelado'] ?? $input['amount'],
        ];
        $data = array_filter($data, function ($v) { return $v !== null; });
        
        // Processar parcelas se fornecidas
        $installments = null;
        if (isset($data['num_parcelas']) && $data['num_parcelas'] > 1) {
            // Criar array temporário com todos os campos necessários para processar parcelas
            $installment_data = array_merge($data, [
                'tipo_juros' => $input['tipo_juros'] ?? 'simples',
            ]);
            $installments = $this->process_installments($installment_data);
            $data['installments'] = $installments;
        }

        $this->load->model('Receivables_model');
        $success = $this->Receivables_model->update($data, $id);
        
        if ($success || $document_was_updated) {
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
        
        $success = $this->Receivables_model->delete($id);
        
        if ($success) {
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

    /**
     * Processar parcelas para uma receita
     * @param array $data Dados da receita
     * @return array Array de parcelas
     */
    private function process_installments($data)
    {
        $num_parcelas = $data['num_parcelas'] ?? 1;
        $valor_original = $data['amount'] ?? 0;
        $juros = $data['juros'] ?? 0;
        $juros_apartir = $data['juros_apartir'] ?? 1;
        $tipo_juros = $data['tipo_juros'] ?? 'simples';
        $data_vencimento = $data['due_date'] ?? date('Y-m-d');
        $paymentmode_id = $data['paymentmode'] ?? 0;

        $installments = [];
        $valor_parcela = $valor_original / $num_parcelas;

        if ($tipo_juros === 'composto') {
            // Juros compostos: aplicado sobre o valor acumulado
            $valor_acumulado = $valor_parcela;
            for ($i = 1; $i <= $num_parcelas; $i++) {
                $tem_juros = $i >= $juros_apartir;
                $juros_parcela = 0;
                $valor_com_juros = $valor_parcela;
                
                if ($tem_juros) {
                    $juros_parcela = $valor_acumulado * ($juros / 100);
                    $valor_com_juros = $valor_parcela + $juros_parcela;
                    $valor_acumulado += $juros_parcela;
                }

                // Calcular data de vencimento da parcela
                $data_vencimento_parcela = date('Y-m-d', strtotime($data_vencimento . ' + ' . ($i - 1) . ' months'));

                $installments[] = [
                    'numero_parcela' => $i,
                    'data_vencimento' => $data_vencimento_parcela,
                    'valor_parcela' => $valor_parcela,
                    'valor_com_juros' => $valor_com_juros,
                    'juros' => $juros_parcela,
                    'juros_adicional' => 0, // Será preenchido no momento do recebimento
                    'desconto' => 0, // Será preenchido no momento do recebimento
                    'multa' => 0, // Será preenchido no momento do recebimento
                    'percentual_juros' => $tem_juros ? $juros : 0,
                    'tipo_juros' => $tipo_juros,
                    'paymentmode_id' => $paymentmode_id,
                    'documento_parcela' => $data['receivable_identifier'] ?? null,
                    'observacoes' => $data['note'] ?? null,
                ];
            }
        } else {
            // Juros simples: aplicado sobre o valor original da parcela
            for ($i = 1; $i <= $num_parcelas; $i++) {
                $tem_juros = $i >= $juros_apartir;
                $juros_parcela = $tem_juros ? $valor_parcela * ($juros / 100) : 0;
                $valor_com_juros = $valor_parcela + $juros_parcela;

                // Calcular data de vencimento da parcela
                $data_vencimento_parcela = date('Y-m-d', strtotime($data_vencimento . ' + ' . ($i - 1) . ' months'));

                $installments[] = [
                    'numero_parcela' => $i,
                    'data_vencimento' => $data_vencimento_parcela,
                    'valor_parcela' => $valor_parcela,
                    'valor_com_juros' => $valor_com_juros,
                    'juros' => $juros_parcela,
                    'juros_adicional' => 0, // Será preenchido no momento do recebimento
                    'desconto' => 0, // Será preenchido no momento do recebimento
                    'multa' => 0, // Será preenchido no momento do recebimento
                    'percentual_juros' => $tem_juros ? $juros : 0,
                    'tipo_juros' => $tipo_juros,
                    'paymentmode_id' => $paymentmode_id,
                    'documento_parcela' => $data['receivable_identifier'] ?? null,
                    'observacoes' => $data['note'] ?? null,
                ];
            }
        }

        return $installments;
    }

    /**
     * Upload de voucher
     * @param int $receivable_id ID da receita
     * @param string $document_data Dados do documento em base64
     * @return string|null Caminho do arquivo ou null
     */
    private function upload_voucher($receivable_id, $document_data)
    {
        if (preg_match('/^data:(.+);base64,/', $document_data, $matches)) {
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
                throw new Exception('Tipo de arquivo não permitido. Tipos permitidos: PDF, DOC, DOCX, JPG, PNG');
            }
            $document_data = base64_decode($document_data);
            if ($document_data === false) {
                throw new Exception('Falha ao decodificar o comprovante');
            }
            if (strlen($document_data) > 5 * 1024 * 1024) {
                throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
            }
            $upload_path = FCPATH . 'uploads/receivables/voucher/';
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
            $filename = 'voucher_' . $receivable_id . '_' . time() . '_' . uniqid() . '.' . $extension;
            $file_path = $upload_path . $filename;
            if (file_put_contents($file_path, $document_data)) {
                return 'uploads/receivables/voucher/' . $filename;
            } else {
                throw new Exception('Falha ao salvar o comprovante no servidor');
            }
        }
        return null;
    }
}