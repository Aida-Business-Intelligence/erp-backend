<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Invoices extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Invoices_model');

        /*
        $decodedToken = $this->authservice->decodeToken($this->token_jwt);
        if (!$decodedToken['status']) {
            $this->response([
                'status' => FALSE,
                'message' => 'Usuario nao autenticado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
            */
    }

    public function create_purchase_order_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['items']) || empty($_POST['items'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No items provided for purchase order'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['warehouse_id']) || empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->trans_start();

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                throw new Exception('User not authenticated');
            }

            $user_id = $decodedToken['data']->user->staffid;
            $warehouse_id = $_POST['warehouse_id'];

            if (empty($user_id)) {
                throw new Exception('User ID not found in token');
            }

            $newitems = [];
            $total = 0;

            foreach ($_POST['items'] as $item) {
                if (!isset($item['quantity']) || empty($item['quantity'])) {
                    throw new Exception('Quantity is required for each item');
                }

                $this->db->where('id', $item['id']);
                $this->db->where('warehouse_id', $warehouse_id);
                $purchase_need = $this->db->get(db_prefix() . 'purchase_needs')->row();

                if (!$purchase_need) {
                    throw new Exception('Purchase need not found with ID: ' . $item['id']);
                }

                $this->db->where('id', $purchase_need->item_id);
                $this->db->where('warehouse_id', $warehouse_id);
                $product = $this->db->get(db_prefix() . 'items')->row();

                if (!$product) {
                    throw new Exception('Product not found for purchase need ID: ' . $item['id']);
                }

                if ($item['quantity'] < $product->defaultPurchaseQuantity) {
                    throw new Exception(sprintf(
                        'Purchase quantity (%d) cannot be less than default purchase quantity (%d) for product: %s',
                        $item['quantity'],
                        $product->defaultPurchaseQuantity,
                        $product->description
                    ));
                }

                $this->db->where('id', $purchase_need->id);
                $this->db->update(db_prefix() . 'purchase_needs', [
                    'status' => 1,
                    'user_id' => $user_id,
                    'date' => date('Y-m-d H:i:s'),
                    'qtde' => $item['quantity']
                ]);

                if ($this->db->affected_rows() == 0) {
                    throw new Exception('Failed to update purchase need');
                }

                $item_total = $product->cost * $item['quantity'];
                $total += $item_total;

                $newitems[] = [
                    'description' => $product->description,
                    'long_description' => $product->long_description,
                    'qty' => $item['quantity'],
                    'rate' => $product->cost,
                    'unit' => $product->unit,
                    'item_id' => $product->id,
                    'order' => count($newitems) + 1
                ];
            }

            $invoice_data = [
                'clientid' => $user_id,
                'number' => get_option('next_invoice_number'),
                'date' => date('Y-m-d'),
                'duedate' => date('Y-m-d', strtotime('+30 days')),
                'subtotal' => $total,
                'total' => $total,
                'status' => 1,
                'clientnote' => '',
                'adminnote' => '',
                'currency' => get_base_currency()->id,
                'billing_street' => '',
                'billing_city' => '',
                'billing_state' => '',
                'billing_zip' => '',
                'billing_country' => '',
                'shipping_street' => '',
                'shipping_city' => '',
                'shipping_state' => '',
                'shipping_zip' => '',
                'shipping_country' => '',
                'include_shipping' => 0,
                'show_shipping_on_invoice' => 0,
                'show_quantity_as' => 1,
                'newitems' => $newitems,
                'cancel_overdue_reminders' => 1,
                'allowed_payment_modes' => serialize([]),
                'prefix' => get_option('invoice_prefix'),
                'number_format' => get_option('invoice_number_format'),
                'datecreated' => date('Y-m-d H:i:s'),
                'addedfrom' => $user_id,
                'warehouse_id' => $warehouse_id
            ];

            $invoice_id = $this->Invoices_model->add($invoice_data);

            if (!$invoice_id) {
                throw new Exception('Failed to create invoice');
            }

            foreach ($_POST['items'] as $item) {
                $this->db->where('id', $item['id']);
                $this->db->update(db_prefix() . 'purchase_needs', ['invoice_id' => $invoice_id]);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->response([
                'status' => TRUE,
                'message' => 'Purchase order and invoice created successfully',
                'invoice_id' => $invoice_id
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->response([
                'status' => FALSE,
                'message' => 'Error: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create_bulk_purchase_orders_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['supplier_ids']) || empty($_POST['supplier_ids'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No supplier IDs provided'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['warehouse_id']) || empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->trans_start();

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                throw new Exception('User not authenticated');
            }

            $user_id = $decodedToken['data']->user->staffid;
            $warehouse_id = $_POST['warehouse_id'];
            $supplier_ids = $_POST['supplier_ids'];

            if (empty($user_id)) {
                throw new Exception('User ID not found in token');
            }

            $results = [];
            $errors = [];

            foreach ($supplier_ids as $supplier_id) {
                $this->db->select('pn.*, i.cost, i.description, i.unit');
                $this->db->from(db_prefix() . 'purchase_needs pn');
                $this->db->join(db_prefix() . 'items i', 'i.id = pn.item_id');
                $this->db->where('pn.status', 0);
                $this->db->where('i.userid', $supplier_id);
                $this->db->where('i.warehouse_id', $warehouse_id);
                $purchase_needs = $this->db->get()->result_array();

                if (empty($purchase_needs)) {
                    continue;
                }

                $newitems = [];
                $total = 0;

                foreach ($purchase_needs as $purchase_need) {
                    $item_total = $purchase_need['cost'] * $purchase_need['qtde'];
                    $total += $item_total;

                    $newitems[] = [
                        'description' => $purchase_need['description'],
                        'long_description' => '',
                        'qty' => $purchase_need['qtde'],
                        'rate' => $purchase_need['cost'],
                        'unit' => $purchase_need['unit'],
                        'item_id' => $purchase_need['item_id'],
                        'order' => count($newitems) + 1
                    ];
                }

                $invoice_data = [
                    'clientid' => $supplier_id,
                    'number' => get_option('next_invoice_number'),
                    'date' => date('Y-m-d'),
                    'duedate' => date('Y-m-d', strtotime('+30 days')),
                    'subtotal' => $total,
                    'total' => $total,
                    'status' => 1,
                    'clientnote' => '',
                    'adminnote' => '',
                    'currency' => get_base_currency()->id,
                    'billing_street' => '',
                    'billing_city' => '',
                    'billing_state' => '',
                    'billing_zip' => '',
                    'billing_country' => '',
                    'shipping_street' => '',
                    'shipping_city' => '',
                    'shipping_state' => '',
                    'shipping_zip' => '',
                    'shipping_country' => '',
                    'include_shipping' => 0,
                    'show_shipping_on_invoice' => 0,
                    'show_quantity_as' => 1,
                    'newitems' => $newitems,
                    'cancel_overdue_reminders' => 1,
                    'allowed_payment_modes' => serialize([]),
                    'prefix' => get_option('invoice_prefix'),
                    'number_format' => get_option('invoice_number_format'),
                    'datecreated' => date('Y-m-d H:i:s'),
                    'addedfrom' => $user_id,
                    'warehouse_id' => $warehouse_id
                ];

                $invoice_id = $this->Invoices_model->add($invoice_data);

                if (!$invoice_id) {
                    $errors[] = "Failed to create invoice for supplier ID: {$supplier_id}";
                    continue;
                }

                foreach ($purchase_needs as $purchase_need) {
                    $this->db->where('id', $purchase_need['id']);
                    $this->db->update(db_prefix() . 'purchase_needs', [
                        'status' => 1,
                        'user_id' => $user_id,
                        'date' => date('Y-m-d H:i:s'),
                        'invoice_id' => $invoice_id
                    ]);
                }

                $results[] = [
                    'supplier_id' => $supplier_id,
                    'invoice_id' => $invoice_id,
                    'total_items' => count($purchase_needs),
                    'total_amount' => $total
                ];
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->response([
                'status' => TRUE,
                'message' => 'Bulk purchase orders created successfully',
                'results' => $results,
                'errors' => $errors
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->response([
                'status' => FALSE,
                'message' => 'Error: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create_ecommerce_purchase_order_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['items']) || empty($_POST['items'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No items provided'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['warehouse_id']) || empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->trans_start();

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                throw new Exception('User not authenticated');
            }

            $user_id = $decodedToken['data']->user->staffid;
            $warehouse_id = $_POST['warehouse_id'];
            $clientid = $_POST['clientid'] ?? 0;
            $supplier_id = $_POST['supplier_id'] ?? 0;

            if (empty($user_id)) {
                throw new Exception('User ID not found in token');
            }

            $newitems = [];
            $total = 0;
            $purchase_need_ids = [];

            foreach ($_POST['items'] as $item) {
                // Verificar se o produto existe
                $this->db->where('id', $item['id']);
                $product = $this->db->get(db_prefix() . 'items')->row();

                if (!$product) {
                    throw new Exception('Product not found with ID: ' . $item['id']);
                }

                // Usar o preço enviado ou o preço padrão do produto
                $item_price = $item['price'] ?? $product->rate;

                // Criar purchase need
                $this->db->insert(db_prefix() . 'purchase_needs', [
                    'item_id' => $product->id,
                    'warehouse_id' => $warehouse_id,
                    'qtde' => $item['quantity'],
                    'status' => 0,
                    'date' => date('Y-m-d H:i:s'),
                    'user_id' => $user_id,
                    // 'unit_price' => $item_price
                ]);

                $purchase_need_id = $this->db->insert_id();
                $purchase_need_ids[] = $purchase_need_id;

                // Preparar items para a invoice
                $item_total = $item_price * $item['quantity']; // Usar o preço recebido
                $total += $item_total;

                $newitems[] = [
                    'description' => $product->description,
                    'long_description' => $product->long_description,
                    'qty' => $item['quantity'],
                    'rate' => $item_price, // Usar o preço recebido
                    'unit' => $product->unit,
                    'item_id' => $product->id,
                    'order' => count($newitems) + 1
                ];
            }

            // 2. Criar a invoice
            $invoice_data = [
                'clientid' => $clientid,
                'supplier_id' => $supplier_id,
                'number' => get_option('next_invoice_number'),
                'date' => date('Y-m-d'),
                'duedate' => date('Y-m-d', strtotime('+30 days')),
                'subtotal' => $total,
                'total' => $total,
                'status' => 1,
                'clientnote' => '',
                'adminnote' => '',
                'currency' => get_base_currency()->id,
                'billing_street' => '',
                'billing_city' => '',
                'billing_state' => '',
                'billing_zip' => '',
                'billing_country' => '',
                'shipping_street' => '',
                'shipping_city' => '',
                'shipping_state' => '',
                'shipping_zip' => '',
                'shipping_country' => '',
                'include_shipping' => 0,
                'show_shipping_on_invoice' => 0,
                'show_quantity_as' => 1,
                'newitems' => $newitems,
                'cancel_overdue_reminders' => 1,
                'allowed_payment_modes' => serialize([]),
                'prefix' => get_option('invoice_prefix'),
                'number_format' => get_option('invoice_number_format'),
                'datecreated' => date('Y-m-d H:i:s'),
                'addedfrom' => $user_id,
                'warehouse_id' => $warehouse_id
            ];

            $invoice_id = $this->Invoices_model->add($invoice_data);

            if (!$invoice_id) {
                throw new Exception('Failed to create invoice');
            }

            // 3. Atualizar purchase needs com o invoice_id
            foreach ($purchase_need_ids as $need_id) {
                $this->db->where('id', $need_id);
                $this->db->update(db_prefix() . 'purchase_needs', [
                    'invoice_id' => $invoice_id,
                    'status' => 1
                ]);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->response([
                'status' => TRUE,
                'message' => 'Purchase order created successfully',
                'invoice_id' => $invoice_id,
                'purchase_need_ids' => $purchase_need_ids
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Error: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function create_bulk_ecommerce_orders_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['orders']) || empty($_POST['orders'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No orders provided'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['warehouse_id']) || empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->trans_start();

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                throw new Exception('User not authenticated');
            }

            $user_id = $decodedToken['data']->user->staffid;
            $warehouse_id = $_POST['warehouse_id'];

            if (empty($user_id)) {
                throw new Exception('User ID not found in token');
            }

            $results = [];
            $errors = [];

            foreach ($_POST['orders'] as $order) {
                try {
                    $newitems = [];
                    $total = 0;
                    $purchase_need_ids = [];
                    $clientid = $order['clientid'] ?? 0; // Corrigido: estava usando $order fora do loop
                    $supplier_id = $order['supplier_id'] ?? 0; // Corrigido: estava usando $order fora do loop

                    // 1. Criar purchase needs para cada item
                    foreach ($order['items'] as $item) {
                        $this->db->where('id', $item['id']);
                        $product = $this->db->get(db_prefix() . 'items')->row();

                        if (!$product) {
                            throw new Exception('Product not found with ID: ' . $item['id']);
                        }

                        // Usar o preço enviado ou o preço padrão do produto
                        $item_price = $item['price'] ?? $product->rate; // Alterado de cost para rate

                        // Criar purchase need
                        $this->db->insert(db_prefix() . 'purchase_needs', [
                            'item_id' => $product->id,
                            'warehouse_id' => $warehouse_id,
                            'qtde' => $item['quantity'],
                            'status' => 0,
                            'date' => date('Y-m-d H:i:s'),
                            'user_id' => $user_id,
                            // 'unit_price' => $item_price
                        ]);

                        $purchase_need_id = $this->db->insert_id();
                        $purchase_need_ids[] = $purchase_need_id;

                        // Preparar items para a invoice
                        $item_total = $item_price * $item['quantity']; // Usar o preço recebido
                        $total += $item_total;

                        $newitems[] = [
                            'description' => $product->description,
                            'long_description' => $product->long_description,
                            'qty' => $item['quantity'],
                            'rate' => $item_price, // Usar o preço recebido
                            'unit' => $product->unit,
                            'item_id' => $product->id,
                            'order' => count($newitems) + 1
                        ];
                    }

                    // 2. Criar a invoice
                    $invoice_data = [
                        'clientid' => $clientid,
                        'supplier_id' => $supplier_id,
                        'number' => get_option('next_invoice_number'),
                        'date' => date('Y-m-d'),
                        'duedate' => date('Y-m-d', strtotime('+30 days')),
                        'subtotal' => $total,
                        'total' => $total,
                        'status' => 1,
                        'clientnote' => '',
                        'adminnote' => '',
                        'currency' => get_base_currency()->id,
                        'billing_street' => '',
                        'billing_city' => '',
                        'billing_state' => '',
                        'billing_zip' => '',
                        'billing_country' => '',
                        'shipping_street' => '',
                        'shipping_city' => '',
                        'shipping_state' => '',
                        'shipping_zip' => '',
                        'shipping_country' => '',
                        'include_shipping' => 0,
                        'show_shipping_on_invoice' => 0,
                        'show_quantity_as' => 1,
                        'newitems' => $newitems,
                        'cancel_overdue_reminders' => 1,
                        'allowed_payment_modes' => serialize([]),
                        'prefix' => get_option('invoice_prefix'),
                        'number_format' => get_option('invoice_number_format'),
                        'datecreated' => date('Y-m-d H:i:s'),
                        'addedfrom' => $user_id,
                        'warehouse_id' => $warehouse_id
                    ];

                    $invoice_id = $this->Invoices_model->add($invoice_data);

                    if (!$invoice_id) {
                        throw new Exception('Failed to create invoice');
                    }

                    // 3. Atualizar purchase needs com o invoice_id
                    foreach ($purchase_need_ids as $need_id) {
                        $this->db->where('id', $need_id);
                        $this->db->update(db_prefix() . 'purchase_needs', [
                            'invoice_id' => $invoice_id,
                            'status' => 1
                        ]);
                    }

                    $results[] = [
                        'order_index' => $order['index'],
                        'invoice_id' => $invoice_id,
                        'purchase_need_ids' => $purchase_need_ids
                    ];

                } catch (Exception $e) {
                    $errors[] = [
                        'order_index' => $order['index'],
                        'message' => $e->getMessage()
                    ];
                }
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->response([
                'status' => TRUE,
                'message' => 'Bulk orders processed',
                'results' => $results,
                'errors' => $errors
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Error: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function list_post()
    {
        $warehouse_id = $this->post('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('limit') ? (int) $this->post('limit') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'datecreated';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';
        $start_date = $this->post('start_date');
        $end_date = $this->post('end_date');
        $status = $this->post('status');
        $supplier_id = $this->post('supplier_id');

        $select = '
        i.id,
        i.total,
        i.status as invoice_status,
        i.datecreated,
        i.warehouse_id,
        IF(i.status = 12, i.clientnote, NULL) as dispute_message,
        IF(i.status = 12, i.dispute_type, NULL) as dispute_type,
        c.company as supplier_name,
        c.vat as supplier_document,
        c.phonenumber as supplier_phone,
        GROUP_CONCAT(DISTINCT itm.description) as products,
        COUNT(DISTINCT pn.id) as total_items,
        SUM(pn.qtde) as total_quantity
    ';


        $this->db->select($select);
        $this->db->from(db_prefix() . 'invoices i');
        $this->db->join(db_prefix() . 'purchase_needs pn', 'pn.invoice_id = i.id', 'left');
        $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = i.clientid', 'left');

        $this->db->where('pn.id IS NOT NULL');
        $this->db->where('i.warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('i.id', $search);
            $this->db->or_like('c.company', $search);
            $this->db->or_like('c.vat', $search);
            $this->db->or_like('itm.description', $search);
            $this->db->group_end();
        }

        if (!empty($start_date)) {
            $this->db->where('DATE(i.datecreated) >=', date('Y-m-d', strtotime($start_date)));
        }

        if (!empty($end_date)) {
            $this->db->where('DATE(i.datecreated) <=', date('Y-m-d', strtotime($end_date)));
        }

        if (!empty($status)) {
            if (is_array($status)) {
                $this->db->where_in('i.status', $status);
            } else {
                $this->db->where('i.status', $status);
            }
        }

        if (!empty($supplier_id)) {
            $this->db->where('i.clientid', $supplier_id);
        }

        $this->db->group_by('i.id');

        $countQuery = clone $this->db;
        $total = $countQuery->get()->num_rows();

        if ($sortField === 'datecreated') {
            $this->db->order_by('i.' . $sortField, $sortOrder);
        } else {
            $this->db->order_by($sortField, $sortOrder);
        }

        $this->db->limit($limit, ($page - 1) * $limit);

        $invoices = $this->db->get()->result_array();

        foreach ($invoices as &$invoice) {
            $this->db->select('
            pn.id as purchase_need_id,
            pn.qtde,
            pn.status as purchase_status,
            itm.description as product_name,
            itm.sku_code,
            itm.image,
            itm.rate as unit_price,
            (pn.qtde * itm.rate) as total_price
        ');
            $this->db->from(db_prefix() . 'purchase_needs pn');
            $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
            $this->db->where('pn.invoice_id', $invoice['id']);
            $this->db->where('pn.warehouse_id', $warehouse_id);

            $invoice['items'] = $this->db->get()->result_array();
        }

        $this->response([
            'status' => TRUE,
            'total' => (int) $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
            'data' => $invoices
        ], REST_Controller::HTTP_OK);
    }

    public function list_transmitidos_post()
    {
        $warehouse_id = $this->post('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('limit') ? (int) $this->post('limit') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'datecreated';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';
        $start_date = $this->post('start_date');
        $end_date = $this->post('end_date');
        $status = $this->post('status');
        $supplier_id = $this->post('supplier_id');

        $select = '
        i.id,
        i.total,
        i.status as invoice_status,
        i.datecreated,
        i.warehouse_id,
        IF(i.status = 12, i.clientnote, NULL) as dispute_message,
        IF(i.status = 12, i.dispute_type, NULL) as dispute_type,
        c.company as supplier_name,
        c.vat as supplier_document,
        c.phonenumber as supplier_phone,
        COUNT(DISTINCT pn.id) as total_items,
        SUM(pn.qtde) as total_quantity
    ';

        $this->db->select($select);
        $this->db->from(db_prefix() . 'invoices i');
        $this->db->join(db_prefix() . 'purchase_needs pn', 'pn.invoice_id = i.id', 'left');
        $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = i.clientid', 'left');

        $this->db->where('pn.id IS NOT NULL');
        $this->db->where('i.warehouse_id', $warehouse_id);
        $this->db->where_in('i.status', [2, 3, 4]);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('i.id', $search);
            $this->db->or_like('c.company', $search);
            $this->db->or_like('c.vat', $search);
            $this->db->or_like('itm.description', $search);
            $this->db->group_end();
        }

        if (!empty($start_date)) {
            $this->db->where('DATE(i.datecreated) >=', date('Y-m-d', strtotime($start_date)));
        }

        if (!empty($end_date)) {
            $this->db->where('DATE(i.datecreated) <=', date('Y-m-d', strtotime($end_date)));
        }

        if (!empty($status)) {
            if (is_array($status)) {
                $this->db->where_in('i.status', $status);
            } else {
                $this->db->where('i.status', $status);
            }
        }

        if (!empty($supplier_id)) {
            $this->db->where('i.clientid', $supplier_id);
        }

        $this->db->group_by('i.id');

        $countQuery = clone $this->db;
        $total = $countQuery->get()->num_rows();

        if ($sortField === 'datecreated') {
            $this->db->order_by('i.' . $sortField, $sortOrder);
        } else {
            $this->db->order_by($sortField, $sortOrder);
        }

        $this->db->limit($limit, ($page - 1) * $limit);

        $invoices = $this->db->get()->result_array();


        foreach ($invoices as &$invoice) {
            $this->db->select('
            pn.id as purchase_need_id,
            pn.qtde,
            pn.status as purchase_status,
            itm.description as product_name,
            itm.sku_code,
            itm.image,
            itm.rate as unit_price, 
            (pn.qtde * itm.rate) as total_price
        ');
            $this->db->from(db_prefix() . 'purchase_needs pn');
            $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
            $this->db->where('pn.invoice_id', $invoice['id']);
            $this->db->where('pn.warehouse_id', $warehouse_id);

            $invoice['items'] = $this->db->get()->result_array();
        }

        $this->response([
            'status' => TRUE,
            'total' => (int) $total,
            'page' => (int) $page,
            'limit' => (int) $limit,
            'data' => $invoices
        ], REST_Controller::HTTP_OK);
    }


    public function dispute_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invoice ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['dispute_type']) || empty($_POST['dispute_type'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Dispute type is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['clientnote']) || empty($_POST['clientnote'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Message is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $allowed_dispute_types = ['broken', 'malfunction', 'wrong_item', 'quality_issues', 'other'];
        if (!in_array($_POST['dispute_type'], $allowed_dispute_types)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid dispute type. Allowed values: ' . implode(', ', $allowed_dispute_types)
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            $this->db->trans_start();

            $this->db->where('id', $_POST['invoice_id']);
            $this->db->update(db_prefix() . 'invoices', [
                'status' => 12,
                'clientnote' => $_POST['clientnote'],
                'dispute_type' => $_POST['dispute_type']
            ]);

            if ($this->db->affected_rows() == 0) {
                throw new Exception('Invoice not found or no changes made');
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $this->response([
                'status' => TRUE,
                'message' => 'Invoice disputed successfully'
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->response([
                'status' => FALSE,
                'message' => 'Error: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

    // Rejeita o/os pedidos
    public function put_rejeitar_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $status = "0"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Invoices_model->update_rejeita($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }


    // Transmite o/os pedidos
    public function put_transmitir_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $status = "2"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Invoices_model->update_transmite($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    // Envia/Exporta o/os pedidos
    public function put_enviar_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $status = "3"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Invoices_model->update_envia($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    // Fatura o/os pedidos
    public function put_faturar_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $status = "4"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Invoices_model->update_fatura($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    // Cancela o/os pedidos
    public function put_cancelar_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $status = "5"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Invoices_model->update_cancela($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);

        }
    }


    public function put_entregue_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $status = "11";

        $output = $this->Invoices_model->update_entregue($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function get_details_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['invoice_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invoice ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $invoice_id = $_POST['invoice_id'];

        $this->db->select('
            i.id,
            i.total,
            i.status as invoice_status,
            i.datecreated,
            i.warehouse_id,
            IF(i.status = 12, i.clientnote, NULL) as dispute_message,
            IF(i.status = 12, i.dispute_type, NULL) as dispute_type,
            c.company as supplier_name,
            c.vat as supplier_document,
            c.phonenumber as supplier_phone
        ');
        $this->db->from(db_prefix() . 'invoices i');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = i.clientid', 'left');
        $this->db->where('i.id', $invoice_id);

        $invoice = $this->db->get()->row_array();

        if (!$invoice) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invoice not found'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Busca os itens da fatura
        $this->db->select('
            pn.id as purchase_need_id,
            pn.qtde,
            pn.status as purchase_status,
            itm.description as product_name,
            itm.sku_code,
            itm.image,
            itm.cost as unit_cost,
            (itm.cost * pn.qtde) as total_cost
        ');
        $this->db->from(db_prefix() . 'purchase_needs pn');
        $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
        $this->db->where('pn.invoice_id', $invoice_id);

        $items = $this->db->get()->result_array();

        $invoice['formatted_date'] = date('d/m/Y H:i', strtotime($invoice['datecreated']));

        $status_map = [
            '0' => 'Rejeitado',
            '1' => 'Pendente',
            '2' => 'Transmitido',
            '3' => 'Enviado',
            '4' => 'Faturado',
            '5' => 'Cancelado',
            '11' => 'Entregue',
            '12' => 'Em contestação'
        ];

        $invoice['status_text'] = isset($status_map[$invoice['invoice_status']]) ? $status_map[$invoice['invoice_status']] : 'Desconhecido';

        $dispute_type_map = [
            'broken' => 'Quebrado',
            'malfunction' => 'Mal funcionamento',
            'wrong_item' => 'Item errado',
            'quality_issues' => 'Problemas de qualidade',
            'other' => 'Outro'
        ];

        if ($invoice['dispute_type']) {
            $invoice['dispute_type_text'] = isset($dispute_type_map[$invoice['dispute_type']]) ? $dispute_type_map[$invoice['dispute_type']] : 'Desconhecido';
        }

        $invoice['formatted_total'] = 'R$ ' . number_format($invoice['total'], 2, ',', '.');

        $invoice['items'] = $items;

        $this->response([
            'status' => TRUE,
            'data' => $invoice
        ], REST_Controller::HTTP_OK);
    }

}
