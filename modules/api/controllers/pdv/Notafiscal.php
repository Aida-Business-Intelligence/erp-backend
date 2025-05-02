<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Notafiscal extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Notafiscal_model');
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
        $status = $this->post('status') ?: null;
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
                'message' => 'Nenhuma nota fiscal encontrada para este armazém'
            ], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response([
                'status' => TRUE,
                'total' => $data['total'],
                'data' => $data['data']
            ], REST_Controller::HTTP_OK);
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
}