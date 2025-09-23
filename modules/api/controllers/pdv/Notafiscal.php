<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Notafiscal extends REST_Controller
{
    protected $user_id = 0;

    function __construct()
    {
        parent::__construct();
        $this->load->model('Notafiscal_model');

        // pegue do JWT se você já tem isso; caso contrário, usa sessão
        $this->user_id = (int) ($this->session->userdata('staff_user_id') ?? 0);
    }

    public function nfe_get($id = '')
    {
        if (!empty($id)) {
            if (!is_numeric($id)) {
                return $this->response(['status' => false, 'message' => 'ID inválido'], REST_Controller::HTTP_BAD_REQUEST);
            }
            $row = $this->Notafiscal_model->nfe_get((int) $id);
            if (!$row) {
                return $this->response(['status' => false, 'message' => 'NFe não encontrada'], REST_Controller::HTTP_NOT_FOUND);
            }
            return $this->response(['status' => true, 'data' => $row], REST_Controller::HTTP_OK);
        }

        // LISTAGEM (query string)
        $page = (int) ($this->get('page') ?? 0);
        $page = $page + 1;
        $limit        = (int) ($this->get('limit') ?: ($this->get('pageSize') ?: 10));
        $search       = trim($this->get('search') ?: '');
        $sortField    = $this->get('sortField') ?: 'created_at';
        $sortOrder    = strtoupper($this->get('sortOrder')) === 'ASC' ? 'ASC' : 'DESC';
        $warehouse_id = (int) ($this->get('warehouse_id') ?: 0);
        $status       = $this->get('status') ?: $this->get('invoice_status'); // aceita ambos
        $start_date   = $this->get('start_date') ?: null;
        $end_date     = $this->get('end_date') ?: null;
        $invoice_id   = $this->get('invoice_id') ?: null;

        if ($warehouse_id <= 0) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $params = compact('page','limit','search','sortField','sortOrder','warehouse_id','status','start_date','end_date','invoice_id');
        $data = $this->Notafiscal_model->nfe_list($params);

        return $this->response([
            'status' => true,
            'total'  => (int) $data['total'],
            'data'   => $data['rows'],
        ], REST_Controller::HTTP_OK);
    }

      public function nfe_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true) ?: $this->post();

        // page vindo do frontend costuma ser 0-based
        $page  = isset($_POST['page']) ? (int) $_POST['page'] : 0;
        $page  = $page + 1; // agora 1-based
        $limit = (int) ($_POST['limit'] ?? ($_POST['pageSize'] ?? 10));
        $search       = trim($_POST['search'] ?? '');
        $sortField    = $_POST['sortField'] ?? 'created_at';
        $sortOrder    = strtoupper($_POST['sortOrder'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $warehouse_id = (int) ($_POST['warehouse_id'] ?? 0);
        $status       = $_POST['status'] ?? ($_POST['invoice_status'] ?? null);
        $start_date   = $_POST['start_date'] ?? null;
        $end_date     = $_POST['end_date'] ?? null;
        $invoice_id   = $_POST['invoice_id'] ?? null;


        if ($warehouse_id <= 0) {
            return $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $params = compact('page','limit','search','sortField','sortOrder','warehouse_id','status','start_date','end_date','invoice_id');
        $data = $this->Notafiscal_model->nfe_list($params);

        return $this->response([
            'status' => true,
            'total'  => (int) $data['total'],
            'data'   => $data['rows'],
        ], REST_Controller::HTTP_OK);
    }

    public function nfe_validate_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true) ?: $this->post();

        if (empty($_POST['invoice_id']) || empty($_POST['warehouse_id'])) {
            return $this->response([
                'status' => false,
                'message' => 'invoice_id e warehouse_id são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        try {
            $this->db->trans_begin();

            $result = $this->Notafiscal_model->nfe_validate_invoice(
                (int) $_POST['invoice_id'],
                (int) $_POST['warehouse_id'],
                $this->user_id
            );

            if (!$result['status']) {
                $this->db->trans_rollback();
                return $this->response($result, REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->db->trans_commit();
            return $this->response([
                'status'  => true,
                'message' => 'Validação realizada',
                'nfe_id'  => $result['nfe_id']
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->response([
                'status' => false,
                'message' => 'Erro na validação: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function nfe_generate_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true) ?: $this->post();

        try {
            $this->db->trans_begin();

            $out = $this->Notafiscal_model->nfe_generate_nfe($_POST, $this->user_id);

            if (!$out['status']) {
                $this->db->trans_rollback();
                return $this->response($out, REST_Controller::HTTP_BAD_REQUEST);
            }

            $this->db->trans_commit();
            return $this->response([
                'status'  => true,
                'message' => 'NFe gerada',
                'data'    => [
                    'nfe_id' => $out['nfe_id'],
                    'chave'  => $out['chave'],
                    'xml'    => $out['xml_preview'],
                ],
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->response([
                'status' => false,
                'message' => 'Erro ao gerar NFe: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function nfe_pdf_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->response([
                'status' => false,
                'message' => 'ID inválido'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $row = $this->Notafiscal_model->nfe_get((int) $id);
        if (!$row) {
            return $this->response([
                'status' => false,
                'message' => 'NFe não encontrada'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        // AJUSTE: usar pdf_url (coluna do banco)
        if (empty($row['pdf_url'])) {
            return $this->response([
                'status' => false,
                'message' => 'PDF ainda não gerado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response([
            'status'   => true,
            'pdf_path' => $row['pdf_url'],
        ], REST_Controller::HTTP_OK);
    }

    public function nfe_remove_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents('php://input')), true) ?: $this->post();

        if (empty($_POST['rows']) || !is_array($_POST['rows'])) {
            return $this->response([
                'status' => false,
                'message' => 'rows deve ser um array de IDs'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $ids = array_values(array_filter($_POST['rows'], 'is_numeric'));
        if (empty($ids)) {
            return $this->response([
                'status' => false,
                'message' => 'Nenhum ID válido informado'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $out = $this->Notafiscal_model->nfe_remove_many($ids);
        return $this->response($out, $out['status'] ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST);
    }

    public function nfce_post()
    {
        
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        $warehouse_id = $this->post('warehouse_id') ? (int) $this->post('warehouse_id') : 0;
        $status = $this->post('invoice_status') ?: null;
        $start_date = $this->post('start_date') ?: null;
        $end_date = $this->post('end_date') ?: null;
        $invoice_id = $this->post('invoice_id') ?: '';

        // Validar warehouse_id
        if ($warehouse_id <= 0) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = $this->Notafiscal_model->get_api_nfce(
            $this->post('id') ?: '',
            $page,
            $limit,
            $search,
            $sortField,
            $sortOrder,
            $warehouse_id, // Passando o warehouse_id para o model
            $status,
            $start_date,
            $end_date,
            $invoice_id,
        );

        if (empty($data['data'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Nenhuma nota fiscal encontrada para esta Fatura'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => TRUE,
                'total' => $data['total'],
                'data' => $data['data']
            ], REST_Controller::HTTP_OK);
        }
    }

    public function list_post()
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        $warehouse_id = $this->post('warehouse_id') ? (int) $this->post('warehouse_id') : 0;
        $status = $this->post('invoice_status') ?: null;
        $start_date = $this->post('start_date') ?: null;
        $end_date = $this->post('end_date') ?: null;
        $invoice_id = $this->post('invoice_id') ?: '';

        // Validar warehouse_id
        if ($warehouse_id <= 0) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = $this->Notafiscal_model->get_api(
            $this->post('id') ?: '',
            $page,
            $limit,
            $search,
            $sortField,
            $sortOrder,
            $warehouse_id, // Passando o warehouse_id para o model
            $status,
            $start_date,
            $end_date,
            $invoice_id,
        );

        if (empty($data['data'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Nenhuma nota fiscal encontrada para esta Fatura'
            ], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response([
                'status' => TRUE,
                'total' => $data['total'],
                'data' => $data['data']
            ], REST_Controller::HTTP_OK);
        }
    }

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid NF ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $list_nf = $this->Notafiscal_model->get($id);

        if ($list_nf) {
            $this->response([
                'status' => TRUE,
                'data' => $list_nf
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function nfce_get($id = '')
    {
        // var_dump($id);
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid NF ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $list_nf = $this->Notafiscal_model->get_nfce($id);

        if ($list_nf) {
            $this->response([
                'status' => TRUE,
                'data' => $list_nf
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        // Verificar Content-Type e processar payload
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        if (strpos($content_type, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->response(['status' => false, 'message' => 'Invalid JSON'], 400);
                return;
            }
            $_POST = $input;
        }

        // Validação mínima
        if (empty($_POST)) {
            $this->response(['status' => false, 'message' => 'Empty payload'], 400);
            return;
        }

        // Converter datas para formato MySQL
        $date_fields = ['invoice_date', 'invoice_issuance_date', 'invoice_departure_date', 'due_date', 'linked_at', 'xml_imported_at'];
        foreach ($date_fields as $field) {
            if (!empty($_POST[$field])) {
                $_POST[$field] = date('Y-m-d H:i:s', strtotime($_POST[$field]));
            }
        }

        // Preparar dados para inserção
        $insert_data = [
            'warehouse_id' => $_POST['warehouse_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_key' => $_POST['invoice_key'] ?? null,
            'invoice_type' => $_POST['invoice_type'] ?? 'entrada', // Definir padrão
            'invoice_status' => $_POST['invoice_status'] ?? '0',
            'invoice_date' => $_POST['invoice_date'],
            'invoice_operation' => $_POST['invoice_operation'] ?? null,
            'invoice_series' => $_POST['invoice_series'] ?? null,
            'invoice_issuance_date' => $_POST['invoice_issuance_date'] ?? null,
            'invoice_departure_date' => $_POST['invoice_departure_date'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,

            // Dados do fornecedor (removido supplier_fantasy_name)
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'supplier_name' => $_POST['supplier_name'] ?? null,
            'supplier_document' => $_POST['supplier_document'] ?? null,
            'supplier_ie' => $_POST['supplier_ie'] ?? null,
            'supplier_address' => $_POST['supplier_address'] ?? null,
            'supplier_address_number' => $_POST['supplier_address_number'] ?? null,
            'supplier_district' => $_POST['supplier_district'] ?? null,
            'supplier_city' => $_POST['supplier_city'] ?? null,
            'supplier_state' => $_POST['supplier_state'] ?? null,
            'supplier_zip_code' => $_POST['supplier_zip_code'] ?? null,
            'supplier_phone' => $_POST['supplier_phone'] ?? null,
            'supplier_email' => $_POST['supplier_email'] ?? null,

            // Dados do cliente
            'client_id' => $_POST['client_id'] ?? null,
            'client_name' => $_POST['client_name'] ?? null,
            'client_document' => $_POST['client_document'] ?? null,
            'client_ie' => $_POST['client_ie'] ?? null,
            'client_address' => $_POST['client_address'] ?? null,
            'client_address_number' => $_POST['client_address_number'] ?? null,
            'client_district' => $_POST['client_district'] ?? null,
            'client_city' => $_POST['client_city'] ?? null,
            'client_state' => $_POST['client_state'] ?? null,
            'client_zip_code' => $_POST['client_zip_code'] ?? null,
            'client_phone' => $_POST['client_phone'] ?? null,
            'client_email' => $_POST['client_email'] ?? null,

            // Valores
            'subtotal' => $_POST['subtotal'] ?? 0,
            'taxes' => $_POST['taxes'] ?? 0,
            'total_value' => $_POST['total_value'] ?? 0,
            'payment_type' => $_POST['payment_type'] ?? null,

            // Impostos
            'icms_base' => $_POST['icms_base'] ?? 0,
            'icms_value' => $_POST['icms_value'] ?? 0,
            'icms_st_base' => $_POST['icms_st_base'] ?? 0,
            'icms_st_value' => $_POST['icms_st_value'] ?? 0,
            'ipi_value' => $_POST['ipi_value'] ?? 0,
            'pis_value' => $_POST['pis_value'] ?? 0,
            'cofins_value' => $_POST['cofins_value'] ?? 0,

            // Pedidos e itens (verificar se já são strings JSON)
            'orders_id' => is_array($_POST['orders'] ?? null) ? json_encode($_POST['orders']) : json_encode([]),
            'items' => is_string($_POST['items'] ?? null) ? $_POST['items'] : json_encode($_POST['items'] ?? []),
            'installments' => is_string($_POST['installments'] ?? null) ? $_POST['installments'] : json_encode($_POST['installments'] ?? []),

            // XML
            'xml_content' => $_POST['xml_content'] ?? null,
            'xml_source' => $_POST['xml_source'] ?? null,
            'xml_imported_at' => $_POST['xml_imported_at'] ?? null,

            // Usuário
            'created_by' => $_POST['created_by'] ?? ($this->session->userdata('staff_user_id') ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
            'linked_by' => $_POST['linked_by'] ?? null,
            'linked_at' => $_POST['linked_at'] ?? null
        ];

        try {
            $this->db->insert(db_prefix() . 'nota_fiscal', $insert_data);
            $invoice_id = $this->db->insert_id();

            if ($invoice_id) {
                $this->response([
                    'status' => true,
                    'message' => 'Nota fiscal criada com sucesso',
                    'data' => ['id' => $invoice_id]
                ], 200);
            } else {
                throw new Exception('Falha ao inserir no banco de dados');
            }
        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => 'Erro ao criar nota fiscal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function manualcreate_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        // Verificar Content-Type e processar payload
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        if (strpos($content_type, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->response(['status' => false, 'message' => 'Invalid JSON'], 400);
                return;
            }
            $_POST = $input;
        }

        // Validação mínima
        $required_fields = [
            'warehouse_id',
            'invoice_number',
            'invoice_type',
            'invoice_date',
            'total_value',
            'items'
        ];

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $this->response(['status' => false, 'message' => "Campo obrigatório faltando: $field"], 400);
                return;
            }
        }

        // Converter datas para formato MySQL
        $date_fields = ['invoice_date', 'invoice_issuance_date', 'invoice_departure_date', 'due_date'];
        foreach ($date_fields as $field) {
            if (!empty($_POST[$field])) {
                $_POST[$field] = date('Y-m-d H:i:s', strtotime($_POST[$field]));
            }
        }

        // Validar e formatar itens
        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            $_POST['items'] = json_encode($_POST['items']);
        } else {
            $_POST['items'] = json_encode([]);
        }

        // Validar e formatar parcelas
        if (!empty($_POST['installments']) && is_array($_POST['installments'])) {
            $_POST['installments'] = json_encode($_POST['installments']);
        } else {
            $_POST['installments'] = json_encode([]);
        }

        // Preparar dados para inserção
        $insert_data = [
            'warehouse_id' => $_POST['warehouse_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_key' => $_POST['invoice_key'] ?? null,
            'invoice_type' => $_POST['invoice_type'] ?? 'entrada', // Definir padrão
            'invoice_status' => $_POST['invoice_status'] ?? '0',
            'invoice_date' => $_POST['invoice_date'],
            'invoice_operation' => $_POST['invoice_operation'] ?? null,
            'invoice_series' => $_POST['invoice_series'] ?? null,
            'invoice_issuance_date' => $_POST['invoice_issuance_date'] ?? null,
            'invoice_departure_date' => $_POST['invoice_departure_date'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,

            // Dados do fornecedor (removido supplier_fantasy_name)
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'supplier_name' => $_POST['supplier_name'] ?? null,
            'supplier_document' => $_POST['supplier_document'] ?? null,
            'supplier_ie' => $_POST['supplier_ie'] ?? null,
            'supplier_address' => $_POST['supplier_address'] ?? null,
            'supplier_address_number' => $_POST['supplier_address_number'] ?? null,
            'supplier_district' => $_POST['supplier_district'] ?? null,
            'supplier_city' => $_POST['supplier_city'] ?? null,
            'supplier_state' => $_POST['supplier_state'] ?? null,
            'supplier_zip_code' => $_POST['supplier_zip_code'] ?? null,
            'supplier_phone' => $_POST['supplier_phone'] ?? null,
            'supplier_email' => $_POST['supplier_email'] ?? null,

            // Dados do cliente
            'client_id' => $_POST['client_id'] ?? null,
            'client_name' => $_POST['client_name'] ?? null,
            'client_document' => $_POST['client_document'] ?? null,
            'client_ie' => $_POST['client_ie'] ?? null,
            'client_address' => $_POST['client_address'] ?? null,
            'client_address_number' => $_POST['client_address_number'] ?? null,
            'client_district' => $_POST['client_district'] ?? null,
            'client_city' => $_POST['client_city'] ?? null,
            'client_state' => $_POST['client_state'] ?? null,
            'client_zip_code' => $_POST['client_zip_code'] ?? null,
            'client_phone' => $_POST['client_phone'] ?? null,
            'client_email' => $_POST['client_email'] ?? null,

            // Valores
            'subtotal' => $_POST['subtotal'] ?? 0,
            'taxes' => $_POST['taxes'] ?? 0,
            'total_value' => $_POST['total_value'] ?? 0,
            'payment_type' => $_POST['payment_type'] ?? null,

            // Impostos
            'icms_base' => $_POST['icms_base'] ?? 0,
            'icms_value' => $_POST['icms_value'] ?? 0,
            'icms_st_base' => $_POST['icms_st_base'] ?? 0,
            'icms_st_value' => $_POST['icms_st_value'] ?? 0,
            'ipi_value' => $_POST['ipi_value'] ?? 0,
            'pis_value' => $_POST['pis_value'] ?? 0,
            'cofins_value' => $_POST['cofins_value'] ?? 0,

            // Pedidos e itens (verificar se já são strings JSON)
            'orders_id' => is_string($_POST['orders_id'] ?? null) ? $_POST['orders_id'] : json_encode($_POST['orders_id'] ?? []),
            'items' => is_string($_POST['items'] ?? null) ? $_POST['items'] : json_encode($_POST['items'] ?? []),
            'installments' => is_string($_POST['installments'] ?? null) ? $_POST['installments'] : json_encode($_POST['installments'] ?? []),

            // XML
            'xml_content' => $_POST['xml_content'] ?? null,
            'xml_source' => $_POST['xml_source'] ?? null,
            'xml_imported_at' => $_POST['xml_imported_at'] ?? null,

            // Usuário
            'created_by' => $_POST['created_by'] ?? ($this->session->userdata('staff_user_id') ?? 0),
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $this->db->insert(db_prefix() . 'nota_fiscal', $insert_data);
            $invoice_id = $this->db->insert_id();

            if ($invoice_id) {
                $this->response([
                    'status' => true,
                    'message' => 'Nota fiscal criada com sucesso',
                    'data' => ['id' => $invoice_id]
                ], 200);
            } else {
                throw new Exception('Falha ao inserir no banco de dados');
            }
        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => 'Erro ao criar nota fiscal: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update_post($id = '')
    {
        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            $_POST = $this->input->post();
        } else {
            $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        }

        if (empty($_POST) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid Invoice ID or Data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Get current invoice data
        $current_invoice = $this->Notafiscal_model->get($id);
        if (!$current_invoice) {
            $this->response(['status' => FALSE, 'message' => 'Invoice not found'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Preparar dados para atualização
        $update_data = [
            'invoice_number' => $_POST['invoice_number'] ?? $current_invoice->invoice_number,
            'invoice_key' => $_POST['invoice_key'] ?? $current_invoice->invoice_key,
            'invoice_type' => $_POST['invoice_type'] ?? $current_invoice->invoice_type,
            'invoice_status' => $_POST['invoice_status'] ?? $current_invoice->invoice_status,
            'invoice_date' => $_POST['invoice_date'] ?? $current_invoice->invoice_date,
            'invoice_operation' => $_POST['invoice_operation'] ?? $current_invoice->invoice_operation,
            'invoice_series' => $_POST['invoice_series'] ?? $current_invoice->invoice_series,
            'invoice_issuance_date' => $_POST['invoice_issuance_date'] ?? $current_invoice->invoice_issuance_date,
            'invoice_departure_date' => $_POST['invoice_departure_date'] ?? $current_invoice->invoice_departure_date,
            'due_date' => $_POST['due_date'] ?? $current_invoice->due_date,

            // Dados do fornecedor
            'supplier_name' => $_POST['supplier_name'] ?? $current_invoice->supplier_name,
            'supplier_document' => $_POST['supplier_document'] ?? $current_invoice->supplier_document,
            'supplier_ie' => $_POST['supplier_ie'] ?? $current_invoice->supplier_ie,
            'supplier_address' => $_POST['supplier_address'] ?? $current_invoice->supplier_address,
            'supplier_address_number' => $_POST['supplier_address_number'] ?? $current_invoice->supplier_address_number,
            'supplier_district' => $_POST['supplier_district'] ?? $current_invoice->supplier_district,
            'supplier_city' => $_POST['supplier_city'] ?? $current_invoice->supplier_city,
            'supplier_state' => $_POST['supplier_state'] ?? $current_invoice->supplier_state,
            'supplier_zip_code' => $_POST['supplier_zip_code'] ?? $current_invoice->supplier_zip_code,
            'supplier_phone' => $_POST['supplier_phone'] ?? $current_invoice->supplier_phone,
            'supplier_email' => $_POST['supplier_email'] ?? $current_invoice->supplier_email,

            // Dados do cliente
            'client_name' => $_POST['client_name'] ?? $current_invoice->client_name,
            'client_document' => $_POST['client_document'] ?? $current_invoice->client_document,
            'client_ie' => $_POST['client_ie'] ?? $current_invoice->client_ie,
            'client_address' => $_POST['client_address'] ?? $current_invoice->client_address,
            'client_address_number' => $_POST['client_address_number'] ?? $current_invoice->client_address_number,
            'client_district' => $_POST['client_district'] ?? $current_invoice->client_district,
            'client_city' => $_POST['client_city'] ?? $current_invoice->client_city,
            'client_state' => $_POST['client_state'] ?? $current_invoice->client_state,
            'client_zip_code' => $_POST['client_zip_code'] ?? $current_invoice->client_zip_code,
            'client_email' => $_POST['client_email'] ?? $current_invoice->client_email,
            'client_phone' => $_POST['client_phone'] ?? $current_invoice->client_phone,

            // Valores
            'subtotal' => $_POST['subtotal'] ?? $current_invoice->subtotal,
            'taxes' => $_POST['taxes'] ?? $current_invoice->taxes,
            'total_value' => $_POST['total_value'] ?? $current_invoice->total_value,
            'payment_type' => $_POST['payment_type'] ?? $current_invoice->payment_type,

            // Impostos
            'icms_base' => $_POST['icms_base'] ?? $current_invoice->icms_base,
            'icms_value' => $_POST['icms_value'] ?? $current_invoice->icms_value,
            'icms_st_base' => $_POST['icms_st_base'] ?? $current_invoice->icms_st_base,
            'icms_st_value' => $_POST['icms_st_value'] ?? $current_invoice->icms_st_value,
            'icms_uf_remet_value' => $_POST['icms_uf_remet_value'] ?? $current_invoice->icms_uf_remet_value,
            'icms_uf_dest_value' => $_POST['icms_uf_dest_value'] ?? $current_invoice->icms_uf_dest_value,
            'ipi_value' => $_POST['ipi_value'] ?? $current_invoice->ipi_value,
            'pis_value' => $_POST['pis_value'] ?? $current_invoice->pis_value,
            'cofins_value' => $_POST['cofins_value'] ?? $current_invoice->cofins_value,
            'ii_value' => $_POST['ii_value'] ?? $current_invoice->ii_value,
            'total_taxes_value' => $_POST['total_taxes_value'] ?? $current_invoice->total_taxes_value,

            // Outros valores
            'freight_value' => $_POST['freight_value'] ?? $current_invoice->freight_value,
            'insurance_value' => $_POST['insurance_value'] ?? $current_invoice->insurance_value,
            'discount_value' => $_POST['discount_value'] ?? $current_invoice->discount_value,
            'other_expenses' => $_POST['other_expenses'] ?? $current_invoice->other_expenses,

            // Pedidos e itens
            'orders_id' => isset($_POST['orders_id']) ? json_encode($_POST['orders_id']) : $current_invoice->orders_id,
            'items' => isset($_POST['items']) ? json_encode($_POST['items']) : $current_invoice->items,
            'installments' => isset($_POST['installments']) ? json_encode($_POST['installments']) : $current_invoice->installments,

            // XML
            'xml_content' => $_POST['xml_content'] ?? $current_invoice->xml_content,
            'xml_source' => $_POST['xml_source'] ?? $current_invoice->xml_source,
            'xml_imported_at' => $_POST['xml_imported_at'] ?? $current_invoice->xml_imported_at,

            // Atualização
            'updated_at' => date('Y-m-d H:i:s'),
            'linked_by' => $_POST['linked_by'] ?? $current_invoice->linked_by,
            'linked_at' => $_POST['linked_at'] ?? $current_invoice->linked_at
        ];

        // Processar upload de arquivo XML se existir
        if ($is_multipart && isset($_FILES['xml_file']) && $_FILES['xml_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['xml_file'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if ($file['size'] <= $max_size) {
                $xml_content = file_get_contents($file['tmp_name']);
                $update_data['xml_content'] = $xml_content;
                $update_data['xml_source'] = 'uploaded';
                $update_data['xml_imported_at'] = date('Y-m-d H:i:s');
            }
        }

        $output = $this->Notafiscal_model->update($update_data, $id);

        if (!$output) {
            $this->response(['status' => FALSE, 'message' => 'Failed to update invoice'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Invoice updated successfully',
            'data' => $this->Notafiscal_model->get($id)
        ], REST_Controller::HTTP_OK);
    }

    public function remove_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['rows']) || !is_array($_POST['rows'])) {
            $this->response(['status' => FALSE, 'message' => 'Invalid request: rows array is required'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = array_filter($_POST['rows'], 'is_numeric');
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            if ($this->Notafiscal_model->delete($id)) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        $this->response([
            'status' => $success_count > 0,
            'message' => $success_count . ' invoice(s) deleted successfully',
            'failed_ids' => $failed_ids
        ], $success_count > 0 ? REST_Controller::HTTP_OK : REST_Controller::HTTP_NOT_FOUND);
    }

    public function distribute_products_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        // Verificar Content-Type e processar payload
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

        if (strpos($content_type, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->response(['status' => false, 'message' => 'Invalid JSON'], 400);
                return;
            }
            $_POST = $input;
        }

        // Log para debug
        log_message('debug', 'Payload recebido: ' . json_encode($_POST));

        // Validação mínima
        $required_fields = [
            'warehouse_id',
            'invoice_number',
            'invoice_type',
            'invoice_date',
            'due_date',
            'supplier_name',
            'supplier_document',
            'supplier_ie',
            'supplier_address',
            'supplier_address_number',
            'supplier_district',
            'supplier_city',
            'supplier_state',
            'supplier_zip_code',
            'client_name',
            'subtotal',
            'taxes',
            'total_value',
            'payment_type',
            'items',
            'original_invoice_id',
            'distributed_items'
        ];

        foreach ($required_fields as $field) {
            if (!isset($_POST[$field])) {
                $this->response(['status' => false, 'message' => "Campo obrigatório ausente: {$field}"], 400);
                return;
            }
        }

        // Formatar campos de data
        $invoice_date = date('Y-m-d H:i:s', strtotime($_POST['invoice_date']));
        $due_date = date('Y-m-d H:i:s', strtotime($_POST['due_date']));

        // Preparar dados para inserção
        $insert_data = [
            'warehouse_id' => $_POST['warehouse_id'],
            'invoice_number' => $_POST['invoice_number'],
            'invoice_type' => $_POST['invoice_type'],
            'invoice_status' => $_POST['invoice_status'] ?? '0',
            'invoice_date' => $invoice_date,
            'due_date' => $due_date,
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'supplier_name' => $_POST['supplier_name'],
            'supplier_document' => $_POST['supplier_document'],
            'supplier_ie' => $_POST['supplier_ie'],
            'supplier_address' => $_POST['supplier_address'],
            'supplier_address_number' => $_POST['supplier_address_number'],
            'supplier_district' => $_POST['supplier_district'],
            'supplier_city' => $_POST['supplier_city'],
            'supplier_state' => $_POST['supplier_state'],
            'supplier_zip_code' => $_POST['supplier_zip_code'],
            'supplier_phone' => $_POST['supplier_phone'] ?? '',
            'supplier_email' => $_POST['supplier_email'] ?? '',
            'client_id' => $_POST['client_id'] ?? null,
            'client_name' => $_POST['client_name'],
            'client_email' => $_POST['client_email'] ?? '',
            'client_phone' => $_POST['client_phone'] ?? '',
            'subtotal' => $_POST['subtotal'],
            'taxes' => $_POST['taxes'],
            'total_value' => $_POST['total_value'],
            'payment_type' => $_POST['payment_type'],
            'items' => is_string($_POST['items']) ? $_POST['items'] : json_encode($_POST['items']),
            'installments' => isset($_POST['installments']) ? (is_string($_POST['installments']) ? $_POST['installments'] : json_encode($_POST['installments'])) : '[]',
            'orders_id' => isset($_POST['orders_id']) ? (is_string($_POST['orders_id']) ? $_POST['orders_id'] : json_encode($_POST['orders_id'])) : '[]',
            'created_by' => $this->session->userdata('staff_user_id') ?? 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Log para debug
        log_message('debug', 'Dados para inserção: ' . json_encode($insert_data));

        // Iniciar transação
        $this->db->trans_begin();

        try {
            // Inserir a nova nota fiscal
            $this->db->insert(db_prefix() . 'nota_fiscal', $insert_data);
            $new_invoice_id = $this->db->insert_id();

            if (!$new_invoice_id) {
                throw new Exception('Erro ao inserir nota fiscal: ' . $this->db->error()['message']);
            }

            // Atualizar o estoque dos itens distribuídos
            $original_invoice_id = $_POST['original_invoice_id'];
            $distributed_items = $_POST['distributed_items'];

            // Buscar a nota fiscal original
            $original_invoice = $this->db->get_where(db_prefix() . 'nota_fiscal', ['id' => $original_invoice_id])->row();
            if (!$original_invoice) {
                throw new Exception('Nota fiscal original não encontrada');
            }

            // Decodificar os itens da nota original
            $original_items = json_decode($original_invoice->items, true);
            if (!$original_items) {
                throw new Exception('Erro ao decodificar itens da nota original');
            }

            // Log para debug
            log_message('debug', 'Itens originais: ' . json_encode($original_items));
            log_message('debug', 'Itens distribuídos: ' . json_encode($distributed_items));

            // Atualizar as quantidades dos itens
            foreach ($distributed_items as $distributed_item) {
                foreach ($original_items as &$original_item) {
                    if ($original_item['id'] == $distributed_item['id']) {
                        $original_item['quantity'] -= $distributed_item['quantity'];
                        break;
                    }
                }
            }

            // Atualizar a nota fiscal original com as novas quantidades
            $update_data = [
                'items' => json_encode($original_items),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Log para debug
            log_message('debug', 'Dados para atualização: ' . json_encode($update_data));

            $this->db->where('id', $original_invoice_id);
            $updated = $this->db->update(db_prefix() . 'nota_fiscal', $update_data);

            if (!$updated) {
                throw new Exception('Erro ao atualizar estoque da nota original: ' . $this->db->error()['message']);
            }

            // Confirmar transação
            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Erro na transação: ' . $this->db->error()['message']);
            }

            $this->response([
                'status' => true,
                'message' => 'Nota fiscal criada e estoque atualizado com sucesso',
                'data' => ['id' => $new_invoice_id]
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            // Reverter transação em caso de erro
            $this->db->trans_rollback();

            // Log do erro
            log_message('error', 'Erro na distribuição: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());

            $this->response([
                'status' => false,
                'message' => 'Erro ao processar distribuição: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}