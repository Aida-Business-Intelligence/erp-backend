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
        $this->load->library('storage_s3');
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
        
        try {
            // Verificar se é multipart/form-data ou JSON
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);

            if ($is_multipart) {
                // Processar dados do FormData - usar $_POST diretamente como no Produto.php e reatribuir $_POST
                if (isset($_POST['data'])) {
                    $input = json_decode($_POST['data'], true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('JSON inválido: ' . json_last_error_msg());
                    }
                    $_POST = $input; // Reatribuir $_POST
                } else {
                    throw new Exception('Campo "data" não encontrado na requisição multipart');
                }
            } else {
                // Processar dados JSON
                $raw_input = file_get_contents('php://input');
                $input = json_decode($raw_input, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('JSON inválido: ' . json_last_error_msg());
                }
                $_POST = $input; // Reatribuir $_POST
            }

            $id = $input['id'] ?? null;
            $status = $input['status'] ?? null;
            $installment_id = $input['installment_id'] ?? null;
            $installment_numbers = $input['installment_numbers'] ?? null;

        if (empty($id) || !in_array($status, ['pending', 'received'])) {
            return $this->response([
                'status' => false,
                'message' => 'ID e status são obrigatórios (pending ou received)'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Se há installment_numbers, é recebimento de múltiplas parcelas
        if ($installment_numbers && is_array($installment_numbers)) {
            return $this->receive_multiple_installments($id, $installment_numbers, $input);
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
        
    } catch (Exception $e) {
        return $this->response([
            'status' => false,
            'message' => 'Erro: ' . $e->getMessage(),
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
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
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);
            
            if ($is_multipart && isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $voucher_path = $this->upload_voucher($receivable_id, $_FILES['comprovante']);
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

    /**
     * Receber múltiplas parcelas em sequência
     * @param int $receivable_id ID da receita
     * @param array $installment_numbers Array com números das parcelas
     * @param array $data Dados do recebimento
     * @return mixed
     */
    private function receive_multiple_installments($receivable_id, $installment_numbers, $data)
    {
        try {
            $this->load->model('Receivables_installments_model');
            
            // Verificar se as parcelas são sequenciais
            sort($installment_numbers);
            for ($i = 1; $i < count($installment_numbers); $i++) {
                if ($installment_numbers[$i] !== $installment_numbers[$i-1] + 1) {
                    throw new Exception('As parcelas devem ser sequenciais. Não é possível pular parcelas.');
                }
            }
            
            // Buscar todas as parcelas da receita
            $all_installments = $this->Receivables_installments_model->get_installments_by_receivable($receivable_id);
            
            // Filtrar apenas as parcelas selecionadas
            $selected_installments = array_filter($all_installments, function($installment) use ($installment_numbers) {
                return in_array($installment['numero_parcela'], $installment_numbers);
            });
            
            if (count($selected_installments) !== count($installment_numbers)) {
                throw new Exception('Algumas parcelas selecionadas não foram encontradas');
            }
            
            // Verificar se todas as parcelas estão pendentes
            foreach ($selected_installments as $installment) {
                if ($installment['status'] === 'Pago') {
                    throw new Exception("A parcela {$installment['numero_parcela']} já foi recebida");
                }
            }
            
            // Upload do comprovante (voucher) - será usado para todas as parcelas
            $voucher_path = null;
            $content_type = $this->input->request_headers()['Content-Type'] ?? '';
            $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);
            
            if ($is_multipart && isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $voucher_path = $this->upload_voucher($receivable_id, $_FILES['comprovante']);
            } elseif (!empty($data['comprovante'])) {
                // Fallback para dados base64 (se ainda existir)
                $voucher_path = $this->upload_voucher($receivable_id, $data['comprovante']);
            }
            
            $success_count = 0;
            $failed_count = 0;
            $errors = [];
            
            // Processar cada parcela
            foreach ($selected_installments as $installment) {
                try {
                    $payment_data = [
                        'data_pagamento' => $data['payment_date'] ?? date('Y-m-d'),
                        'valor_pago' => $data['valorPago'] ?? $installment['valor_com_juros'],
                        'banco_id' => $data['bank_account_id'] ?? null,
                        'observacoes' => $data['descricao_recebimento'] ?? null,
                        'juros_adicional' => $data['juros'] ?? 0,
                        'desconto' => $data['desconto'] ?? 0,
                        'multa' => $data['multa'] ?? 0,
                        'id_cheque' => $data['check_identifier'] ?? null,
                        'id_boleto' => $data['boleto_identifier'] ?? null,
                        'comprovante' => $voucher_path,
                    ];
                    
                    $success = $this->Receivables_installments_model->receive_installment($installment['id'], $payment_data);
                    if ($success) {
                        $success_count++;
                    } else {
                        $failed_count++;
                        $errors[] = "Falha ao processar parcela {$installment['numero_parcela']}";
                    }
                } catch (Exception $e) {
                    $failed_count++;
                    $errors[] = "Erro na parcela {$installment['numero_parcela']}: " . $e->getMessage();
                }
            }
            
            // Verificar se todas as parcelas foram recebidas
            $summary = $this->Receivables_installments_model->get_installments_summary($receivable_id);
            if ($summary['parcelas_pendentes'] == 0) {
                // Marcar receita como recebida
                $this->load->model('Receivables_model');
                $this->Receivables_model->update([
                    'status' => 'received',
                ], $receivable_id);
            }
            
            if ($failed_count > 0) {
                return $this->response([
                    'status' => false,
                    'message' => "Algumas parcelas não puderam ser processadas",
                    'success_count' => $success_count,
                    'failed_count' => $failed_count,
                    'errors' => $errors,
                    'voucher_url' => $voucher_path ? base_url($voucher_path) : null
                ], REST_Controller::HTTP_PARTIAL_CONTENT);
            }
            
            return $this->response([
                'status' => true,
                'message' => "Todas as parcelas foram recebidas com sucesso",
                'success_count' => $success_count,
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

            log_message('debug', 'Receivables clients_get - type: ' . $type . ', warehouse_id: ' . $warehouse_id . ', search: ' . $search);

            if ($type === 'franchisees') {
                $clients = $this->Receivables_model->get_franchisees($warehouse_id, $search, $limit, $page);
            } else {
                $clients = $this->Receivables_model->get_clients($warehouse_id, $search, $limit, $page);
            }

            log_message('debug', 'Receivables clients_get - result count: ' . count($clients));

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

        // Verificar se é multipart/form-data ou JSON
        $headers = $this->input->request_headers();
        $content_type = $headers['Content-Type'] ?? $headers['content-type'] ?? $_SERVER['CONTENT_TYPE'] ?? '';
        $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES) || isset($_POST['data']);
        
        // Log para debug
        log_message('debug', 'Receivables Content-Type: ' . $content_type);
        log_message('debug', 'Receivables Is multipart: ' . ($is_multipart ? 'true' : 'false'));
        log_message('debug', 'Receivables FILES: ' . json_encode($_FILES));
        log_message('debug', 'Receivables POST keys: ' . json_encode(array_keys($_POST)));
        log_message('debug', 'Receivables POST data exists: ' . (isset($_POST['data']) ? 'true' : 'false'));

        if ($is_multipart) {
            // Processar dados do FormData - usar $_POST diretamente como no Produto.php e reatribuir $_POST
            if (isset($_POST['data'])) {
                $input = json_decode($_POST['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->response([
                        'status' => false,
                        'message' => 'JSON inválido: ' . json_last_error_msg() . ' - Input: ' . $_POST['data'] . ' - Content-Type: ' . $content_type,
                        'debug' => [
                            'input' => $_POST['data'],
                            'decoded' => null
                        ]
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                $_POST = $input; // Reatribuir $_POST
            } else {
                return $this->response([
                    'status' => false,
                    'message' => 'Campo "data" não encontrado na requisição multipart',
                    'debug' => [
                        'input' => '',
                        'decoded' => null
                    ]
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            $input = json_decode($raw_input, true);
            $_POST = $input; // Reatribuir $_POST
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

        // Inicializar cliente S3
        $s3 = $this->storage_s3->getClient();
        $receivables_document = null;

        // Processar documento da receita se enviado via multipart
        if ($is_multipart && isset($_FILES['receivables_document']) && $_FILES['receivables_document']['error'] === UPLOAD_ERR_OK && $_FILES['receivables_document']['size'] > 0) {
            $file = $_FILES['receivables_document'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validar tipos de arquivo permitidos
            $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
            if (!in_array($file_extension, $allowed_extensions)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            // Verificar tamanho do arquivo (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                return $this->response([
                    'status' => false,
                    'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            $unique_filename = 'receivable_' . time() . '_' . uniqid() . '.' . $file_extension;
            $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $input['warehouse_id'] . '/receivables/documents/' . $unique_filename;

            try {
                // Upload para S3
                $s3->putObject([
                    'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                    'Key' => $blobName,
                    'SourceFile' => $file['tmp_name'],
                    'ACL' => 'public-read',
                ]);

                // Constrói a URL do arquivo
                $receivables_document = "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
            } catch (Exception $e) {
                return $this->response([
                    'status' => false,
                    'message' => 'Falha ao fazer upload do documento para S3: ' . $e->getMessage()
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        $data = [
            'category' => $input['category'],
            'currency' => $input['currency'] ?? 1,
            'amount' => $input['amount'],
            'amount_base' => $input['amount_base'] ?? $input['amount'],
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
            'recurring_type' => null,
            'repeat_every' => null,
            'recurring' => 0,
            'cycles' => 0,
            'total_cycles' => 0,
            'custom_recurring' => 0,
            'last_recurring_date' => null,
            'create_invoice_billable' => isset($input['create_invoice_billable']) ? ($input['create_invoice_billable'] ? 1 : 0) : 0,
            'send_invoice_to_customer' => isset($input['send_invoice_to_customer']) ? ($input['send_invoice_to_customer'] ? 1 : 0) : 0,
            'recurring_from' => null,
            'dateadded' => date('Y-m-d H:i:s'),
            'addedfrom' => get_staff_user_id() ?? 1,
            'perfex_saas_tenant_id' => 'master',
            'status' => $input['status'] ?? 'pending',
            'warehouse_id' => $input['warehouse_id'],
            'receivables_document' => $receivables_document,
            'registration_date' => $input['registration_date'] ?? null,
            'is_staff' => isset($input['is_staff']) ? ($input['is_staff'] ? 1 : 0) : 0,
            'is_client' => isset($input['is_client']) ? ($input['is_client'] ? 1 : 0) : 0,
            // Campos de parcelamento de cartão de crédito, cheque e boleto
            'num_parcelas' => $input['num_parcelas'] ?? 1,
            'juros' => $input['juros'] ?? 0,
            'juros_apartir' => $input['juros_apartir'] ?? 1,
            'total_parcelado' => $input['total_parcelado'] ?? $input['amount'],
            // 'tipo_juros' => $input['tipo_juros'] ?? 'simples', // continua fora do principal
        ];
        // Filtrar apenas valores null, mas manter campos opcionais como receivables_document
        $data = array_filter($data, function ($value, $key) {
            // Campos que podem ser null
            $nullable_fields = ['receivables_document', 'tax', 'tax2', 'reference_no', 'note', 'expense_name', 'clientid', 'invoiceid', 'order_number', 'installment_number', 'nfe_key', 'nfe_number', 'boleto_number', 'barcode', 'registration_date'];
            
            if (in_array($key, $nullable_fields)) {
                return true; // Manter o campo mesmo se for null
            }
            
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
        
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

        // Verificar se é multipart/form-data ou JSON
        $content_type = $this->input->request_headers()['Content-Type'] ?? '';
        $is_multipart = strpos(strtolower($content_type), 'multipart/form-data') !== false || !empty($_FILES);

        if ($is_multipart) {
            // Processar dados do FormData - usar $_POST diretamente como no Produto.php e reatribuir $_POST
            if (isset($_POST['data'])) {
                $input = json_decode($_POST['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->response([
                        'status' => false,
                        'message' => 'JSON inválido: ' . json_last_error_msg()
                    ], REST_Controller::HTTP_BAD_REQUEST);
                }
                $_POST = $input; // Reatribuir $_POST
            } else {
                return $this->response([
                    'status' => false,
                    'message' => 'Campo "data" não encontrado na requisição multipart'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            $raw_input = file_get_contents('php://input');
            $input = json_decode($raw_input, true);
            $_POST = $input; // Reatribuir $_POST
        }

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
        // Processar documento se existir via multipart
        if ($is_multipart && isset($_FILES['receivables_document']) && $_FILES['receivables_document']['error'] === UPLOAD_ERR_OK && $_FILES['receivables_document']['size'] > 0) {
            $file = $_FILES['receivables_document'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validar tipos de arquivo permitidos
            $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
            if (!in_array($file_extension, $allowed_extensions)) {
                return $this->response([
                    'status' => false,
                    'message' => 'Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            // Verificar tamanho do arquivo (5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                return $this->response([
                    'status' => false,
                    'message' => 'O arquivo é muito grande. Tamanho máximo: 5MB'
                ], REST_Controller::HTTP_BAD_REQUEST);
            }

            // Apagar o arquivo antigo do S3, se existir
            if ($old_document && strpos($old_document, 'https://') === 0) {
                try {
                    $s3 = $this->storage_s3->getClient();
                    $old_key = str_replace("https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/", "", $old_document);
                    $s3->deleteObject([
                        'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                        'Key' => $old_key
                    ]);
                } catch (Exception $e) {
                    // Log do erro, mas não falhar a operação
                    log_message('error', 'Erro ao deletar arquivo antigo do S3: ' . $e->getMessage());
                }
            }

            $warehouse_id = $current ? $current->warehouse_id : 0;
            $unique_filename = 'receivable_' . time() . '_' . uniqid() . '.' . $file_extension;
            $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $warehouse_id . '/receivables/documents/' . $unique_filename;

            try {
                // Inicializar cliente S3
                $s3 = $this->storage_s3->getClient();
                
                // Upload para S3
                $s3->putObject([
                    'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                    'Key' => $blobName,
                    'SourceFile' => $file['tmp_name'],
                    'ACL' => 'public-read',
                ]);

                // Constrói a URL do arquivo
                $receivables_document = "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
                $document_was_updated = true;
            } catch (Exception $e) {
                return $this->response([
                    'status' => false,
                    'message' => 'Falha ao fazer upload do documento para S3: ' . $e->getMessage()
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        $data = [
            'category' => $input['category'],
            'currency' => $input['currency'] ?? 1,
            'amount' => $input['amount'],
            'amount_base' => $input['amount_base'] ?? $input['amount'],
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
            'recurring_type' => null,
            'repeat_every' => null,
            'recurring' => 0,
            'cycles' => 0,
            'total_cycles' => 0,
            'custom_recurring' => 0,
            'last_recurring_date' => null,
            'create_invoice_billable' => isset($input['create_invoice_billable']) ? ($input['create_invoice_billable'] ? 1 : 0) : 0,
            'send_invoice_to_customer' => isset($input['send_invoice_to_customer']) ? ($input['send_invoice_to_customer'] ? 1 : 0) : 0,
            'recurring_from' => null,
            'warehouse_id' => $input['warehouse_id'],
            'receivables_document' => $receivables_document,
            'boleto_number' => $input['boleto_number'] ?? null,
            'nfe_number' => $input['nfe_number'] ?? null,
            'bank_account_id' => $input['bank_account_id'] ?? null,
            'registration_date' => $input['registration_date'] ?? null,
            'is_staff' => isset($input['is_staff']) ? ($input['is_staff'] ? 1 : 0) : 0,
            'is_client' => isset($input['is_client']) ? ($input['is_client'] ? 1 : 0) : 0,
            // Campos de parcelamento de cartão de crédito, cheque e boleto
            'num_parcelas' => $input['num_parcelas'] ?? 1,
            'juros' => $input['juros'] ?? 0,
            'juros_apartir' => $input['juros_apartir'] ?? 1,
            'total_parcelado' => $input['total_parcelado'] ?? $input['amount'],
            // 'tipo_juros' => $input['tipo_juros'] ?? 'simples', // continua fora do principal
        ];
        
        // Remover campos nulos, exceto installments
        $installments_backup = $data['installments'] ?? null;
        $data = array_filter($data, function ($value, $key) {
            // Campos que podem ser null
            $nullable_fields = ['receivables_document', 'tax', 'tax2', 'reference_no', 'note', 'expense_name', 'clientid', 'invoiceid', 'order_number', 'installment_number', 'nfe_key', 'nfe_number', 'boleto_number', 'barcode', 'registration_date'];
            
            if (in_array($key, $nullable_fields)) {
                return true; // Manter o campo mesmo se for null
            }
            
            return $value !== null;
        }, ARRAY_FILTER_USE_BOTH);
        if ($installments_backup !== null) {
            $data['installments'] = $installments_backup;
        }
        
        // Processar parcelas se fornecidas
        $installments = null;
        
        // Se já existem parcelas no input, usar elas
        if (isset($input['installments']) && is_array($input['installments']) && !empty($input['installments'])) {
            log_message('debug', 'Parcelas encontradas no input: ' . json_encode($input['installments']));
            $installments = $input['installments'];
            $data['installments'] = $installments;
        }
        // Se não existem parcelas mas num_parcelas > 1, criar novas parcelas
        else if (isset($data['num_parcelas']) && $data['num_parcelas'] > 1) {
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
        
        // Deletar arquivos S3 antes de deletar o registro
        $this->delete_receivable_files_from_s3($id);
        
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
        $valor_original = $data['valor_original'] ?? $data['amount'] ?? 0; // Usar valor_original se disponível, senão amount
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
    /**
     * Upload de voucher para S3
     * @param int $receivable_id ID da receita
     * @param array $file Dados do arquivo do $_FILES
     * @return string|null URL do arquivo ou null
     */
    private function upload_voucher($receivable_id, $file)
    {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validar tipos de arquivo permitidos
        $allowed_extensions = ['pdf', 'docx', 'jpeg', 'jpg', 'png'];
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Tipo de arquivo não permitido. Tipos permitidos: PDF, DOCX, JPG, PNG');
        }

        // Verificar tamanho do arquivo (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('O arquivo é muito grande. Tamanho máximo: 5MB');
        }

        // Buscar warehouse_id da receita
        $receivable = $this->Receivables_model->get_receivable_by_id($receivable_id);
        $warehouse_id = $receivable ? $receivable->warehouse_id : 0;

        $unique_filename = 'voucher_' . $receivable_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $blobName = 'uploads_erp/' . getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') . '/' . $warehouse_id . '/receivables/vouchers/' . $receivable_id . '/' . $unique_filename;

        try {
            // Inicializar cliente S3
            $s3 = $this->storage_s3->getClient();
            
            // Upload para S3
            $s3->putObject([
                'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                'Key' => $blobName,
                'SourceFile' => $file['tmp_name'],
                'ACL' => 'public-read',
            ]);

            // Constrói a URL do arquivo
            return "https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/" . $blobName;
        } catch (Exception $e) {
            throw new Exception('Falha ao fazer upload do comprovante para S3: ' . $e->getMessage());
        }
    }

    // Função auxiliar para deletar arquivo do S3
    private function delete_receivable_document_file($document) {
        if (strpos($document, 'data:') === 0) {
            // Documento em base64, nada a deletar
            return;
        }
        
        // Se for URL do S3, deletar do S3
        if (strpos($document, 'https://') === 0) {
            try {
                $s3 = $this->storage_s3->getClient();
                $key = str_replace("https://" . getenv('STORAGE_S3_NAME_SPACE') . ".sfo3.digitaloceanspaces.com/", "", $document);
                $s3->deleteObject([
                    'Bucket' => getenv('STORAGE_S3_NAME_SPACE'),
                    'Key' => $key
                ]);
            } catch (Exception $e) {
                log_message('error', 'Erro ao deletar arquivo do S3: ' . $e->getMessage());
            }
        } else {
            // Fallback para arquivo local (legado)
            $filePath = FCPATH . ltrim($document, '/');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
    }

    // Função para deletar todos os arquivos S3 relacionados a uma receita
    private function delete_receivable_files_from_s3($receivable_id) {
        // Buscar a receita para obter o documento principal
        $this->load->model('Receivables_model');
        $receivable = $this->Receivables_model->get_receivable_by_id($receivable_id);
        if ($receivable && !empty($receivable->receivables_document)) {
            $this->delete_receivable_document_file($receivable->receivables_document);
        }

        // Buscar e deletar todos os comprovantes das parcelas
        $this->load->model('Receivables_installments_model');
        $installments = $this->Receivables_installments_model->get_installments_by_receivable($receivable_id);
        
        if ($installments) {
            foreach ($installments as $installment) {
                if (!empty($installment['comprovante'])) {
                    $this->delete_receivable_document_file($installment['comprovante']);
                }
            }
        }
    }
}