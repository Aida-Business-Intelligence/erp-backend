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
                'ecommerce' => 0,
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
                    'ecommerce' => 0,
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
            $status = $_POST['status'] ?? 1;

            if (empty($user_id)) {
                throw new Exception('User ID not found in token');
            }

            $newitems = [];
            $total = 0;
            $purchase_need_ids = [];
            $item_ids_to_remove = [];

            foreach ($_POST['items'] as $index => $item) {
                $product = $this->db->get_where('tblitems', ['id' => $item['id']])->row();

                if (!$product) {
                    continue;
                }

                $purchase_need = [
                    'item_id' => $item['id'],
                    'warehouse_id' => $warehouse_id,
                    'qtde' => $item['quantity'],
                    'status' => 0,
                    'date' => date('Y-m-d H:i:s'),
                    'user_id' => $user_id
                ];

                $this->db->insert('tblpurchase_needs', $purchase_need);
                $purchase_need_id = $this->db->insert_id();
                $purchase_need_ids[] = $purchase_need_id;
                $item_ids_to_remove[] = $item['id'];

                $item_total = $product->cost * $item['quantity'];
                $total += $item_total;

                $newitems[] = [
                    'description' => $product->description,
                    'long_description' => $product->long_description,
                    'qty' => $item['quantity'],
                    'rate' => $item['price'],
                    'unit' => $product->unit,
                    'item_id' => $item['id'],
                    'order' => $index + 1
                ];
            }

            $invoice_data = [
                'clientid' => $clientid,
                'supplier_id' => $supplier_id,
                'number' => get_option('next_invoice_number'),
                'date' => date('Y-m-d'),
                'duedate' => date('Y-m-d', strtotime('+30 days')),
                'subtotal' => $_POST['total'],
                'total' => $_POST['total'],
                'expense_id' => $_POST['expense_id'],
                'status' => $status,
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
                'ecommerce' => 1,
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

            // Se o status foi especificamente definido pelo usuário, não deixar que seja alterado
            if (isset($_POST['status']) && $_POST['status'] != 1) {
                $this->db->where('id', $invoice_id);
                $this->db->update('tblinvoices', ['status' => $status]);
            }

            foreach ($purchase_need_ids as $index => $purchase_need_id) {
                $this->db->where('id', $purchase_need_id);
                $this->db->update('tblpurchase_needs', ['invoice_id' => $invoice_id]);
            }

            // Remover os itens do carrinho do usuário logado
            if (!empty($item_ids_to_remove)) {
                $this->db->where('user_id', $user_id);
                $this->db->where('warehouse_id', $warehouse_id);
                $this->db->where_in('item_id', $item_ids_to_remove);
                $this->db->delete('tblecommerce_cart');
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return $this->response([
                    'status' => false,
                    'message' => 'Error creating purchase order'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->response([
                'status' => true,
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
                    $clientid = $order['clientid'] ?? 0;
                    $supplier_id = $order['supplier_id'] ?? 0;
                    $item_ids_to_remove = [];

                    foreach ($order['items'] as $item) {
                        $this->db->where('id', $item['id']);
                        $product = $this->db->get(db_prefix() . 'items')->row();

                        if (!$product) {
                            throw new Exception('Product not found with ID: ' . $item['id']);
                        }

                        $item_price = $item['price'] ?? $product->rate;

                        $this->db->insert(db_prefix() . 'purchase_needs', [
                            'item_id' => $product->id,
                            'warehouse_id' => $warehouse_id,
                            'qtde' => $item['quantity'],
                            'status' => 0,
                            'date' => date('Y-m-d H:i:s'),
                            'user_id' => $user_id,
                        ]);

                        $purchase_need_id = $this->db->insert_id();
                        $purchase_need_ids[] = $purchase_need_id;
                        $item_ids_to_remove[] = $item['id'];

                        $item_total = $item_price * $item['quantity'];
                        $total += $item_total;

                        $newitems[] = [
                            'description' => $product->description,
                            'long_description' => $product->long_description,
                            'qty' => $item['quantity'],
                            'rate' => $item_price,
                            'unit' => $product->unit,
                            'item_id' => $product->id,
                            'order' => count($newitems) + 1
                        ];
                    }

                    $invoice_data = [
                        'clientid' => $clientid,
                        'supplier_id' => $supplier_id,
                        'number' => get_option('next_invoice_number'),
                        'date' => date('Y-m-d'),
                        'duedate' => date('Y-m-d', strtotime('+30 days')),
                        'subtotal' => $order['total'],
                        'total' => $order['total'],
                        'expense_id' => $_POST['expense_id'],
                        'status' => $order['status'],
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
                        'ecommerce' => 1,
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

                    // Se o status foi especificamente definido pelo usuário, não deixar que seja alterado
                    if (isset($order['status']) && $order['status'] != 1) {
                        $this->db->where('id', $invoice_id);
                        $this->db->update('tblinvoices', ['status' => $order['status']]);
                    }

                    foreach ($purchase_need_ids as $index => $need_id) {
                        $this->db->where('id', $need_id);
                        $this->db->update(db_prefix() . 'purchase_needs', [
                            'invoice_id' => $invoice_id,
                            'status' => 1
                        ]);
                    }

                    // Remover os itens do carrinho do usuário logado
                    if (!empty($item_ids_to_remove)) {
                        $this->db->where('user_id', $user_id);
                        $this->db->where('warehouse_id', $warehouse_id);
                        $this->db->where_in('item_id', $item_ids_to_remove);
                        $this->db->delete('tblecommerce_cart');
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
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'asc' : 'desc';
        $start_date = $this->post('start_date');
        $end_date = $this->post('end_date');
        $status = $this->post('status');
        $supplier_id = $this->post('supplier_id');

        $select = '
        i.id,
        i.total,
        i.status as invoice_status,
        i.ecommerce,
        i.expense_id,
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

    // Lista os pedidos transmitidos
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
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'asc' : 'desc';
        $start_date = $this->post('start_date');
        $end_date = $this->post('end_date');
        $status = $this->post('status');
        $supplier_id = $this->post('supplier_id');

        $select = '
        i.id,
        i.total,
        i.status as invoice_status,
        i.ecommerce,
        i.expense_id,
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
        $this->db->where_in('i.status', [2, 3]);

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

    // Lista os pedidos expedidos
    public function list_expedidos_post()
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
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'asc' : 'desc';
        $start_date = $this->post('start_date');
        $end_date = $this->post('end_date');
        $status = $this->post('status');
        $supplier_id = $this->post('supplier_id');

        $select = '
        i.id,
        i.total,
        i.status as invoice_status,
        i.ecommerce,
        i.expense_id,
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
        $this->db->where_in('i.status', [3, 4, 6]);

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

    // Lista os pedidos conferidos
    public function list_conferidos_post()
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
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'asc' : 'desc';
        $start_date = $this->post('start_date');
        $end_date = $this->post('end_date');
        $status = $this->post('status');
        $supplier_id = $this->post('supplier_id');

        $select = '
        i.id,
        i.total,
        i.status as invoice_status,
        i.ecommerce,
        i.expense_id,
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
        $this->db->where_in('i.status', [4, 6]);

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

        try {
            $this->db->trans_start();

            $ids = $_POST['ids'];
            $status = "0"; // Define o status como "0" (rejeitado)

            // Buscar os itens dos pedidos que serão rejeitados
            $this->db->select('pn.*, i.warehouse_id');
            $this->db->from(db_prefix() . 'purchase_needs pn');
            $this->db->join(db_prefix() . 'invoices i', 'i.id = pn.invoice_id');
            $this->db->where_in('pn.invoice_id', $ids);
            $purchase_needs = $this->db->get()->result_array();

            // Para cada item do pedido, vai restaurar o estoque
            foreach ($purchase_needs as $need) {
                try {
                    $item = [
                        'qty' => $need['qtde'],
                        'id' => $need['item_id']
                    ];

                    $data = [
                        'warehouse_id' => $need['warehouse_id'],
                        'user_id' => $need['user_id'],
                        'obs' => 'Restaurando estoque após rejeição de pedido',
                        'hash' => md5(uniqid(rand(), true))
                    ];

                    $transaction = [
                        'id' => $need['invoice_id'],
                        'cash' => 'restore'
                    ];

                    // Atualizar o estoque diretamente
                    $this->db->where('id', $item['id']);
                    $this->db->where('warehouse_id', $data['warehouse_id']);
                    $current_stock = $this->db->get(db_prefix() . 'items')->row();

                    if (!$current_stock) {
                        throw new Exception('Item não encontrado no estoque');
                    }

                    // Atualizar o estoque do item (adicionando a quantidade de volta)
                    $new_stock = $current_stock->stock + $item['qty'];

                    $this->db->where('id', $item['id']);
                    $this->db->where('warehouse_id', $data['warehouse_id']);
                    $this->db->update(db_prefix() . 'items', ['stock' => $new_stock]);

                    // Registrar o movimento no itemstocksmov
                    $data_itemstocksmov = [
                        'warehouse_id' => $data['warehouse_id'],
                        'transaction_id' => $transaction['id'],
                        'cash_id' => $transaction['id'],
                        'qtde' => $item['qty'],
                        'hash' => $data['hash'],
                        'user_id' => $data['user_id'],
                        'obs' => 'Restaurando estoque após rejeição de pedido',
                        'type_transaction' => 'restore'
                    ];

                    $this->db->insert(db_prefix() . 'itemstocksmov', $data_itemstocksmov);

                } catch (Exception $e) {
                    throw $e;
                }
            }

            // Atualiza o status dos pedidos
            $output = $this->Invoices_model->update_rejeita($ids, $status);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Falha na transação');
            }

            if ($output) {
                $message = array('status' => TRUE, 'message' => 'Pedidos rejeitados e estoque restaurado com sucesso.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Falha ao rejeitar pedidos.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $message = array('status' => FALSE, 'message' => 'Erro: ' . $e->getMessage());
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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

        $output = $this->Invoices_model->update_entrega($ids, $status);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Invoices Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update Invoices.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function update_order_nf_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['updates']) || !is_array($_POST['updates'])) {
            $message = array('status' => FALSE, 'message' => 'Dados não fornecidos ou inválidos');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }

        $updates = $_POST['updates'];

        // Valida os dados antes de processar
        foreach ($updates as $update) {
            if (!isset($update['order_id']) || !isset($update['item_id']) || !isset($update['quantity'])) {
                $message = array('status' => FALSE, 'message' => 'Dados inválidos para um dos itens');
                $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
                return;
            }
        }

        // Processa todas as atualizações em uma única transação
        $output = $this->Invoices_model->update_order_item_quantity($updates);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Quantidades atualizadas com sucesso');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Falha ao atualizar quantidades');
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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
            i.ecommerce,
            i.expense_id,
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
            '6' => 'Aguardando faturamento',
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

    private function format_quantity($value)
    {
        $value = (float) $value;
        return $value == floor($value) ? (int) $value : $value;
    }

    public function get_invoice_products_for_sale_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'ID da fatura é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $invoice_id = $_POST['invoice_id'];

        $this->db->where('id', $invoice_id);
        $this->db->where('status', 3); // Only transmitted orders (transmitido)
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();

        if (!$invoice) {
            $this->response([
                'status' => FALSE,
                'message' => 'Fatura não encontrada ou não está em status válido para venda'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->db->select('
            pn.id as purchase_need_id,
            pn.item_id,
            pn.qtde as order_quantity,
            pn.qtde as available_quantity, 
            itm.description as product_name,
            itm.sku_code,
            itm.rate as selling_price,
            itm.cost as purchase_price,
            itm.unit,
            itm.image as product_image,
            itm.commodity_barcode as barcode
        ');
        $this->db->from(db_prefix() . 'purchase_needs pn');
        $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
        $this->db->where('pn.invoice_id', $invoice_id);

        $products = $this->db->get()->result_array();

        $this->response([
            'status' => TRUE,
            'invoice' => [
                'id' => $invoice->id,
                'date' => $invoice->date,
                'status' => $invoice->status,
                'warehouse_id' => $invoice->warehouse_id
            ],
            'products' => $products
        ], REST_Controller::HTTP_OK);
    }

    public function process_sale_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'ID da fatura é obrigatório'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['products']) || empty($_POST['products'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Produtos são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $invoice_id = $_POST['invoice_id'];
        $products = $_POST['products'];
        $customer_id = isset($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
        $discount = isset($_POST['discount']) ? (float) $_POST['discount'] : 0;

        $this->db->trans_start();

        $this->db->where('id', $invoice_id);
        $this->db->where('status', 3); // Only transmitted orders (transmitido)
        $invoice = $this->db->get(db_prefix() . 'invoices')->row();

        if (!$invoice) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Fatura não encontrada ou não está em status válido para venda'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $warehouse_id = $invoice->warehouse_id;
        $total_amount = 0;
        $sale_items = [];

        foreach ($products as $product) {
            if (!isset($product['purchase_need_id']) || !isset($product['quantity'])) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Dados do produto inválidos. Cada produto deve ter purchase_need_id e quantidade'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $purchase_need_id = $product['purchase_need_id'];
            $sale_quantity = (float) $product['quantity'];

            if ($sale_quantity <= 0) {
                continue;
            }

            $this->db->select('pn.*, itm.description, itm.rate as selling_price, itm.cost');
            $this->db->from(db_prefix() . 'purchase_needs pn');
            $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
            $this->db->where('pn.id', $purchase_need_id);
            $this->db->where('pn.invoice_id', $invoice_id);
            $purchase_need = $this->db->get()->row();

            if (!$purchase_need) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Necessidade de compra não encontrada para ID: ' . $purchase_need_id
                ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            if ($sale_quantity > $purchase_need->qtde) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Quantidade de venda (' . $sale_quantity . ') não pode exceder quantidade do pedido (' . $purchase_need->qtde . ') para o produto: ' . $purchase_need->description
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $item_discount = isset($product['discount']) ? (float) $product['discount'] : 0;
            $item_price = $purchase_need->selling_price - $item_discount;
            if ($item_price < 0)
                $item_price = 0;

            $line_total = $sale_quantity * $item_price;
            $total_amount += $line_total;

            $sale_items[] = [
                'purchase_need_id' => $purchase_need_id,
                'item_id' => $purchase_need->item_id,
                'description' => $purchase_need->description,
                'quantity' => $sale_quantity,
                'price' => $item_price,
                'cost' => $purchase_need->cost,
                'total' => $line_total,
                'discount' => $item_discount
            ];

            $this->db->where('id', $purchase_need_id);
            $this->db->set('qtde_sold', 'IFNULL(qtde_sold, 0) + ' . $sale_quantity, FALSE);
            $this->db->update(db_prefix() . 'purchase_needs');

            $this->db->where('id', $purchase_need->item_id);
            $this->db->where('warehouse_id', $warehouse_id);
            $this->db->set('stock', 'stock - ' . $sale_quantity, FALSE);
            $this->db->update(db_prefix() . 'items');
        }

        if (empty($sale_items)) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Nenhum produto válido para venda'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Apply global discount
        $total_after_discount = $total_amount - $discount;
        if ($total_after_discount < 0)
            $total_after_discount = 0;

        $sale_data = [
            'invoice_id' => $invoice_id,
            'customer_id' => $customer_id,
            'warehouse_id' => $warehouse_id,
            'payment_method' => $payment_method,
            'total_amount' => $total_after_discount,
            'discount' => $discount,
            'subtotal' => $total_amount,
            'date' => date('Y-m-d H:i:s'),
            'created_by' => $this->session->userdata('staff_user_id') ?? 1,
            'status' => 1
        ];

        $this->db->insert(db_prefix() . 'sales', $sale_data);
        $sale_id = $this->db->insert_id();

        if (!$sale_id) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Falha ao criar registro de venda'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        foreach ($sale_items as $item) {
            $item['sale_id'] = $sale_id;
            $this->db->insert(db_prefix() . 'sale_items', $item);
        }

        $this->db->where('id', $invoice_id);
        $this->db->update(db_prefix() . 'invoices', ['status' => 4]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => 'Falha na transação'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Venda processada com sucesso',
            'sale_id' => $sale_id,
            'total_amount' => $total_after_discount,
            'discount' => $discount,
            'subtotal' => $total_amount,
            'items_count' => count($sale_items)
        ], REST_Controller::HTTP_OK);
    }

    public function add_to_cart_post()
    {
        try {
            $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

            // Verifica se o user_id foi enviado
            if (!isset($data['user_id'])) {
                $this->response(['status' => false, 'message' => 'user_id é obrigatório'], 400);
                return;
            }

            // Verifica se o item_id foi enviado
            if (!isset($data['item_id'])) {
                $this->response(['status' => false, 'message' => 'item_id é obrigatório'], 400);
                return;
            }

            // Verifica se o warehouse_id foi enviado
            if (!isset($data['warehouse_id'])) {
                $this->response(['status' => false, 'message' => 'warehouse_id é obrigatório'], 400);
                return;
            }

            // Verifica se o quantity foi enviado
            if (!isset($data['quantity'])) {
                $this->response(['status' => false, 'message' => 'quantity é obrigatório'], 400);
                return;
            }

            $result = $this->Invoices_model->add_to_cart($data);

            if ($result) {
                $this->response(['status' => true, 'message' => 'Item adicionado ao carrinho']);
            } else {
                $this->response(['status' => false, 'message' => 'Erro ao adicionar item ao carrinho'], 400);
            }
        } catch (Exception $e) {
            $this->response(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function remove_from_cart_post()
    {
        try {
            $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

            if (!isset($data['item_id']) || !isset($data['warehouse_id'])) {
                $this->response([
                    'status' => false,
                    'message' => 'item_id e warehouse_id são obrigatórios'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                $this->response([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], REST_Controller::HTTP_UNAUTHORIZED);
                return;
            }

            $data['user_id'] = $decodedToken['data']->user->staffid;

            $result = $this->Invoices_model->remove_from_cart($data);

            if ($result) {
                $this->response([
                    'status' => true,
                    'message' => 'Item removido do carrinho com sucesso'
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao remover item do carrinho'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_cart_item_post()
    {
        try {
            $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

            if (!isset($data['item_id']) || !isset($data['quantity']) || !isset($data['warehouse_id'])) {
                $this->response([
                    'status' => false,
                    'message' => 'item_id, quantity e warehouse_id são obrigatórios'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                $this->response([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], REST_Controller::HTTP_UNAUTHORIZED);
                return;
            }

            $data['user_id'] = $decodedToken['data']->user->staffid;

            $result = $this->Invoices_model->update_cart_item($data);

            if ($result) {
                $this->response([
                    'status' => true,
                    'message' => 'Item atualizado no carrinho com sucesso'
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao atualizar item no carrinho'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_cart_items_post()
    {
        try {
            $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

            if (!isset($data['warehouse_id'])) {
                $this->response([
                    'status' => false,
                    'message' => 'warehouse_id é obrigatório'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                $this->response([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], REST_Controller::HTTP_UNAUTHORIZED);
                return;
            }

            $user_id = $decodedToken['data']->user->staffid;
            $warehouse_id = $data['warehouse_id'];

            $items = $this->Invoices_model->get_cart_items($user_id, $warehouse_id);

            $this->response([
                'status' => true,
                'items' => $items
            ], REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function clear_cart_post()
    {
        try {
            $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

            if (!isset($data['warehouse_id'])) {
                $this->response([
                    'status' => false,
                    'message' => 'warehouse_id é obrigatório'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                $this->response([
                    'status' => false,
                    'message' => 'Usuário não autenticado'
                ], REST_Controller::HTTP_UNAUTHORIZED);
                return;
            }

            $user_id = $decodedToken['data']->user->staffid;
            $warehouse_id = $data['warehouse_id'];

            $result = $this->Invoices_model->clear_cart($user_id, $warehouse_id);

            if ($result) {
                $this->response([
                    'status' => true,
                    'message' => 'Carrinho limpo com sucesso'
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao limpar carrinho'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (Exception $e) {
            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // funcao para remover item do carrinho depois de expirar - CRON JOB
    public function remove_expired_items_post()
    {
        $this->load->model('invoices_model');
        $result = $this->invoices_model->remove_expired_cart_items();

        if ($result['status']) {
            $this->response([
                'status' => true,
                'message' => $result['message'],
                'removed_items' => $result['removed_items']
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => $result['message']
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    // Endpoint para retornar o estoque atual de um item
    public function get_stock_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['item_id']) || !isset($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'item_id e warehouse_id são obrigatórios'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $item_id = $_POST['item_id'];
        $warehouse_id = $_POST['warehouse_id'];
        $stock = $this->Invoices_model->get_stock($item_id, $warehouse_id);

        $this->response([
            'status' => TRUE,
            'stock' => $stock
        ], REST_Controller::HTTP_OK);
    }

    public function update_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invoice ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $invoice_id = $_POST['id'];
        $update_data = [];

        // Campos que podem ser atualizados
        if (isset($_POST['expense_id'])) {
            $update_data['expense_id'] = $_POST['expense_id'];
        }

        if (isset($_POST['status'])) {
            $update_data['status'] = $_POST['status'];
        }

        if (empty($update_data)) {
            $this->response([
                'status' => FALSE,
                'message' => 'No valid fields to update'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        try {
            // Desabilitar temporariamente os hooks para evitar interferência
            $this->db->trans_start();

            // Usar atualização direta no banco para evitar hooks que alteram o status
            $this->db->where('id', $invoice_id);
            $this->db->update(db_prefix() . 'invoices', $update_data);

            if ($this->db->affected_rows() > 0) {
                // Se estamos atualizando o status, garantir que ele não seja alterado por hooks
                if (isset($_POST['status'])) {
                    // Forçar a atualização do status novamente para garantir que não foi alterado
                    $this->db->where('id', $invoice_id);
                    $this->db->update(db_prefix() . 'invoices', ['status' => $_POST['status']]);
                }

                $this->db->trans_complete();

                $this->response([
                    'status' => TRUE,
                    'message' => 'Invoice updated successfully'
                ], REST_Controller::HTTP_OK);
            } else {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Invoice not found or no changes made'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_expense_id_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invoice ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['expense_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Expense ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $invoice_id = $_POST['id'];
        $expense_id = $_POST['expense_id'];

        try {
            // Usar atualização direta no banco para evitar hooks que alteram o status
            $this->db->where('id', $invoice_id);
            $this->db->update(db_prefix() . 'invoices', ['expense_id' => $expense_id]);

            if ($this->db->affected_rows() > 0) {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Invoice expense_id updated successfully'
                ], REST_Controller::HTTP_OK);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Invoice not found or no changes made'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Error updating invoice: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
