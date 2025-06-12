<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Romaneio extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('clients_model');
        $this->load->model('warehouse_model');
    }

    public function list_get() {
        $warehouse_id = $this->get('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10;
        $search = $this->get('search') ?: '';
        $status = $this->get('status') ?: '';
        $sortField = $this->get('sortField') ?: 'id';
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';
        $startDate = $this->get('startDate');
        $endDate = $this->get('endDate');
        $customerId = $this->get('customerId');
        $supplierId = $this->get('supplierId');
        $type = $this->get('type') ?: '';

        if (!$this->db->table_exists(db_prefix() . 'romaneios') ||
                !$this->db->table_exists(db_prefix() . 'romaneio_orders')) {
            $this->response([
                'status' => TRUE,
                'total' => 0,
                'data' => []
                    ], REST_Controller::HTTP_OK);
            return;
        }

        try {
            $this->db->select('r.*, COUNT(o.id) as order_count');
            $this->db->from(db_prefix() . 'romaneios r');
            $this->db->join(db_prefix() . 'romaneio_orders o', 'o.romaneio_id = r.id', 'left');
            $this->db->where('r.warehouse_id', $warehouse_id);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('r.id', $search);
                $this->db->or_like('r.customer_name', $search);
                $this->db->or_like('r.supplier_name', $search);
                $this->db->group_end();
            }

            if (!empty($status)) {
                $this->db->where('r.status', $status);
            }
            
            if (!empty($type)) {
                $this->db->where('r.type', $type);
            }

            if (!empty($startDate)) {
                $this->db->where('DATE(r.date_created) >=', date('Y-m-d', strtotime($startDate)));
            }

            if (!empty($endDate)) {
                $this->db->where('DATE(r.date_created) <=', date('Y-m-d', strtotime($endDate)));
            }

            if (!empty($customerId)) {
                $this->db->where('r.customer_id', $customerId);
            }

            if (!empty($supplierId)) {
                $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'romaneio_orders WHERE romaneio_id = r.id AND supplier_id = ' . $this->db->escape($supplierId) . ')');
            }

            $this->db->group_by('r.id');

            $validSortFields = ['id', 'customer_name', 'supplier_name', 'date_created', 'status', 'type'];
            $sortFieldDB = in_array($sortField, $validSortFields) ? 'r.' . $sortField : 'r.id';

            $this->db->order_by($sortFieldDB, $sortOrder);

            $totalQuery = $this->db->get_compiled_select();

            $query = $this->db->query("SELECT COUNT(*) as total FROM ({$totalQuery}) as total_query");
            $total = $query->row()->total;

            $this->db->select('r.*, COUNT(o.id) as order_count');
            $this->db->from(db_prefix() . 'romaneios r');
            $this->db->join(db_prefix() . 'romaneio_orders o', 'o.romaneio_id = r.id', 'left');
            $this->db->where('r.warehouse_id', $warehouse_id);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('r.id', $search);
                $this->db->or_like('r.customer_name', $search);
                $this->db->or_like('r.supplier_name', $search);
                $this->db->group_end();
            }

            if (!empty($status)) {
                $this->db->where('r.status', $status);
            }
            
            if (!empty($type)) {
                $this->db->where('r.type', $type);
            }

            if (!empty($startDate)) {
                $this->db->where('DATE(r.date_created) >=', date('Y-m-d', strtotime($startDate)));
            }

            if (!empty($endDate)) {
                $this->db->where('DATE(r.date_created) <=', date('Y-m-d', strtotime($endDate)));
            }

            if (!empty($customerId)) {
                $this->db->where('r.customer_id', $customerId);
            }

            if (!empty($supplierId)) {
                $this->db->where('EXISTS (SELECT 1 FROM ' . db_prefix() . 'romaneio_orders WHERE romaneio_id = r.id AND supplier_id = ' . $this->db->escape($supplierId) . ')');
            }

            $this->db->group_by('r.id');
            $this->db->order_by($sortFieldDB, $sortOrder);
            $this->db->limit($limit, ($page - 1) * $limit);

            $romaneios = $this->db->get()->result_array();

            foreach ($romaneios as &$romaneio) {
                if ($this->db->table_exists(db_prefix() . 'romaneio_orders')) {
                    $this->db->select('SUM(total_items) as total_items, SUM(total_cost) as total_cost, SUM(total_price) as total_price');
                    $this->db->from(db_prefix() . 'romaneio_orders');
                    $this->db->where('romaneio_id', $romaneio['id']);
                    $totals = $this->db->get()->row_array();

                    $romaneio['total_items'] = (int) $totals['total_items'];
                    $romaneio['total_cost'] = (float) $totals['total_cost'];
                    $romaneio['total_price'] = (float) $totals['total_price'];
                    $romaneio['margin'] = $totals['total_cost'] > 0 ? (($totals['total_price'] - $totals['total_cost']) / $totals['total_cost'] * 100) : 0;
                    
                    if ($romaneio['type'] === 'entrada') {
                        $this->db->select('supplier_id, supplier_name');
                        $this->db->from(db_prefix() . 'romaneio_orders');
                        $this->db->where('romaneio_id', $romaneio['id']);
                        $this->db->group_by('supplier_id, supplier_name');
                        $suppliers = $this->db->get()->result_array();
                        
                        $romaneio['suppliers'] = $suppliers;
                        
                        $supplier_names = array_column($suppliers, 'supplier_name');
                        $romaneio['supplier_names'] = implode(', ', $supplier_names);
                    }
                } else {
                    $romaneio['total_items'] = 0;
                    $romaneio['total_cost'] = 0;
                    $romaneio['total_price'] = 0;
                    $romaneio['margin'] = 0;
                    
                    if ($romaneio['type'] === 'entrada') {
                        $romaneio['suppliers'] = [];
                        $romaneio['supplier_names'] = '';
                    }
                }
            }

            if ($romaneios) {
                $this->response(['total' => $total, 'data' => $romaneios], REST_Controller::HTTP_OK);
            } else {
                $this->response(['status' => TRUE, 'total' => 0, 'data' => []], REST_Controller::HTTP_OK);
            }
        } catch (Exception $e) {
            log_message('error', 'Error in Romaneio list_get: ' . $e->getMessage());
            $this->response(['status' => TRUE, 'total' => 0, 'data' => []], REST_Controller::HTTP_OK);
        }
    }

    public function get_get($id = '') {
        $warehouse_id = $this->get('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid romaneio ID'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->where('id', $id);
        $this->db->where('warehouse_id', $warehouse_id);
        $romaneio = $this->db->get(db_prefix() . 'romaneios')->row_array();

        if (!$romaneio) {
            $this->response([
                'status' => FALSE,
                'message' => 'Romaneio not found'
                    ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->db->where('romaneio_id', $id);
        $orders = $this->db->get(db_prefix() . 'romaneio_orders')->result_array();

        foreach ($orders as &$order) {
            $this->db->where('order_id', $order['id']);
            $order['items'] = $this->db->get(db_prefix() . 'romaneio_order_items')->result_array();
        }

        $romaneio['orders'] = $orders;

        $this->response([
            'status' => TRUE,
            'data' => $romaneio
                ], REST_Controller::HTTP_OK);
    }

    public function create_post() {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->db->trans_start();

        if (!isset($_POST['customer']) || !isset($_POST['customer']['id']) || empty($_POST['customer']['id'])) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Customer is required'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['orders']) || !is_array($_POST['orders']) || empty($_POST['orders'])) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'At least one order is required'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        foreach ($_POST['orders'] as $order) {
            if (!isset($order['supplierId']) || empty($order['supplierId'])) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Supplier ID is required for all orders'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            if (!isset($order['products']) || !is_array($order['products']) || empty($order['products'])) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'At least one product is required for each order'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }

            foreach ($order['products'] as $product) {
                if (!isset($product['productId']) || empty($product['productId'])) {
                    $this->db->trans_rollback();
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Product ID is required for all products'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }

                if (!isset($product['quantity']) || $product['quantity'] < 1) {
                    $this->db->trans_rollback();
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Valid quantity is required for all products'
                            ], REST_Controller::HTTP_BAD_REQUEST);
                    return;
                }
            }
        }

        $romaneio_data = [
            'customer_id' => $_POST['customer']['id'],
            'customer_name' => $_POST['customer']['name'],
            'date_created' => date('Y-m-d H:i:s'),
            'status' => $_POST['status'] ?? 'pending',
            'notes' => $_POST['notes'] ?? null,
            'created_by' => $this->session->userdata('staff_user_id') ?? 1,
            'warehouse_id' => $_POST['warehouse_id'],
        ];

        $this->db->insert(db_prefix() . 'romaneios', $romaneio_data);
        $romaneio_id = $this->db->insert_id();

        if (!$romaneio_id) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to create romaneio'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        foreach ($_POST['orders'] as $order) {
            $total_cost = 0;
            $total_price = 0;
            $total_items = 0;

            foreach ($order['products'] as $product) {
                $total_cost += $product['cost'] * $product['quantity'];
                $total_price += $product['price'] * $product['quantity'];
                $total_items += $product['quantity'];
            }

            $margin = $total_cost > 0 ? (($total_price - $total_cost) / $total_cost * 100) : 0;

            $order_data = [
                'romaneio_id' => $romaneio_id,
                'supplier_id' => $order['supplierId'],
                'supplier_name' => $order['supplierName'],
                'date_created' => date('Y-m-d H:i:s'),
                'status' => $order['status'] ?? 'pending',
                'total_cost' => isset($order['totals']['cost']) ? $order['totals']['cost'] : $total_cost,
                'total_price' => isset($order['totals']['price']) ? $order['totals']['price'] : $total_price,
                'total_items' => isset($order['totals']['items']) ? $order['totals']['items'] : $total_items,
                'margin' => isset($order['totals']['margin']) ? $order['totals']['margin'] : $margin,
                'warehouse_id' => $_POST['warehouse_id'],
            ];

            $this->db->insert(db_prefix() . 'romaneio_orders', $order_data);
            $order_id = $this->db->insert_id();

            foreach ($order['products'] as $product) {
                $product_margin = $product['cost'] > 0 ? (($product['price'] - $product['cost']) / $product['cost'] * 100) : 0;

                $product_data = [
                    'order_id' => $order_id,
                    'product_id' => $product['productId'],
                    'code' => $product['code'],
                    'description' => $product['description'],
                    'quantity' => $product['quantity'],
                    'cost' => $product['cost'],
                    'price' => $product['price'],
                    'total_cost' => $product['quantity'] * $product['cost'],
                    'total_price' => $product['quantity'] * $product['price'],
                    'margin' => $product_margin,
                    'warehouse_id' => $_POST['warehouse_id'],
                ];

                $this->db->insert(db_prefix() . 'romaneio_order_items', $product_data);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Transaction failed'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Romaneio created successfully',
            'romaneio_id' => $romaneio_id
                ], REST_Controller::HTTP_OK);
    }

    /**
     * Update an existing romaneio
     */
    public function update_put($id) {
        $_PUT = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_PUT['warehouse_id'])) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid romaneio ID'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->trans_start();

        $this->db->where('id', $id);
        $this->db->where('warehouse_id', $_PUT['warehouse_id']);
        $romaneio = $this->db->get(db_prefix() . 'romaneios')->row_array();

        if (!$romaneio) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Romaneio not found'
                    ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $romaneio_data = [
            'status' => $_PUT['status'] ?? $romaneio['status'],
            'notes' => $_PUT['notes'] ?? $romaneio['notes'],
            'date_updated' => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'romaneios', $romaneio_data);

        if (isset($_PUT['orders']) && is_array($_PUT['orders'])) {
            foreach ($_PUT['orders'] as $order) {
                if (!isset($order['id']) || empty($order['id'])) {
                    continue;
                }

                $order_data = [
                    'status' => $order['status'] ?? null,
                    'date_updated' => date('Y-m-d H:i:s'),
                ];

                $this->db->where('id', $order['id']);
                $this->db->where('warehouse_id', $_PUT['warehouse_id']);
                $this->db->update(db_prefix() . 'romaneio_orders', $order_data);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Transaction failed'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Romaneio updated successfully'
                ], REST_Controller::HTTP_OK);
    }

    /**
     * Delete a romaneio
     */
    public function delete_patch() {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

      $_POST['ids'] = array((int)$_POST['romaneio_id']);
 

        if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid request: ids array is required'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = $_POST['ids'];
        
   
        
        $success_count = 0;
        $failed_ids = [];

        $this->db->trans_start();

        foreach ($ids as $id) {
            
         
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            $this->db->where('id', $id);
            //$this->db->where('warehouse_id', $_POST['warehouse_id']);
            $romaneio = $this->db->get(db_prefix() . 'romaneios')->row_array();

            if (!$romaneio) {
                $failed_ids[] = $id;
                continue;
            }
            $this->db->where('romaneio_id', $id);
            $orders = $this->db->get(db_prefix() . 'romaneio_orders')->result_array();

            foreach ($orders as $order) {
                $this->db->where('order_id', $order['id']);
                $this->db->delete(db_prefix() . 'romaneio_order_items');
            }

            $this->db->where('romaneio_id', $id);
            $this->db->delete(db_prefix() . 'romaneio_orders');

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'romaneios');

            $success_count++;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $message = [
                'status' => FALSE,
                'message' => 'Transaction failed',
                'failed_ids' => $failed_ids
            ];
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        if ($success_count > 0) {
            $message = [
                'status' => TRUE,
                'message' => $success_count . ' romaneio(s) deleted successfully'
            ];
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                'status' => FALSE,
                'message' => 'Failed to delete romaneios',
                'failed_ids' => $failed_ids
            ];
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }
    
    public function delete_order_patch() {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

      $_POST['ids'] = array((int)$_POST['order_id']);
 

        if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid request: ids array is required'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = $_POST['ids'];
        
   
        
        $success_count = 0;
        $failed_ids = [];

        $this->db->trans_start();

        foreach ($ids as $id) {
            
         
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            $this->db->where('id', $id);
            $orders = $this->db->get(db_prefix() . 'romaneio_orders')->result_array();

            foreach ($orders as $order) {
                $this->db->where('order_id', $order['id']);
                $this->db->delete(db_prefix() . 'romaneio_order_items');
            }

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'romaneio_orders');

          

            $success_count++;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $message = [
                'status' => FALSE,
                'message' => 'Transaction failed',
                'failed_ids' => $failed_ids
            ];
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        if ($success_count > 0) {
            $message = [
                'status' => TRUE,
                'message' => $success_count . ' romaneio(s) deleted successfully'
            ];
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                'status' => FALSE,
                'message' => 'Failed to delete romaneios',
                'failed_ids' => $failed_ids
            ];
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }
    

    /**
     * Get all suppliers for selection in romaneio
     */
    public function suppliers_get() {
        $warehouse_id = $this->get('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $search = $this->get('search') ?: '';
        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('limit') ? (int) $this->get('limit') : 20;
        $offset = ($page - 1) * $limit;

        $this->db->select('userid as id, company as name, vat as document');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where('is_supplier', 1);
        $this->db->where('active', 1);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('company', $search);
            $this->db->or_like('vat', $search);
            $this->db->group_end();
        }

        $total_query = $this->db->get_compiled_select();
        $total = $this->db->query("SELECT COUNT(*) as count FROM ($total_query) as subquery")->row()->count;

        $this->db->select('userid as id, company as name, vat as document');
        $this->db->from(db_prefix() . 'clients');
        $this->db->where('is_supplier', 1);
        $this->db->where('active', 1);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('company', $search);
            $this->db->or_like('vat', $search);
            $this->db->group_end();
        }

        $this->db->order_by('company', 'ASC');
        $this->db->limit($limit, $offset);

        $suppliers = $this->db->get()->result_array();

        $warehouses = $this->warehouse_model->get("", "(type = 'distribuidor' OR type = 'importador' OR type = 'ecommerce')");
        $fornecedores = array();

        foreach ($warehouses as $wa) {
            $fornecedores[] = array(
                "id" => "WAR_" . $wa['warehouse_id'],
                "name" => $wa['warehouse_name'], // Ensuring this line uses warehouse_name for a more descriptive name if needed
                "document" => $wa['cnpj'] // Use a different key for cnpj to avoid overwriting 'name'
            );
        }
        $combined = array_merge($suppliers, $fornecedores);

        $this->response([
            'status' => TRUE,
            'total' => count($combined), // update total count
            'page' => $page,
            'limit' => $limit,
            'data' => $combined
                ], REST_Controller::HTTP_OK);
    }

    /**
     * Get all customers for selection in romaneio
     */
    public function customers_get() {
        $warehouse_id = $this->get('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $search = $this->get('search') ?: '';
        $limit = $this->get('limit') ? (int) $this->get('limit') : 50;
        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $this->db->select('staffid as id, CONCAT(firstname, lastname) as name, vat as document');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('firstname', $search);
            $this->db->or_like('lastname', $search);
            $this->db->or_like('vat', $search);
            $this->db->group_end();
        }

        $this->db->order_by('firstname', 'ASC');
        $this->db->limit($limit);

        $customers = $this->db->get()->result_array();

        $warehouses = $this->warehouse_model->get("", "(type = 'filial' OR type = 'franquia')");
        $fornecedores = array();

        foreach ($warehouses as $wa) {
            $fornecedores[] = array(
                "id" => "WAR_" . $wa['warehouse_id'],
                "name" => $wa['warehouse_name'], // Ensuring this line uses warehouse_name for a more descriptive name if needed
                "document" => $wa['cnpj'] // Use a different key for cnpj to avoid overwriting 'name'
            );
        }
        $combined = array_merge($customers, $fornecedores);

        $this->response([
            'status' => TRUE,
            'total' => count($combined), // update total count
            'page' => $page,
            'limit' => $limit,
            'data' => $combined
                ], REST_Controller::HTTP_OK);
    }

    public function supplier_products_get($supplier_id) {
        $warehouse_id = $this->get('warehouse_id');

        if ('WAR' == substr($supplier_id, 0, 3)) {
            $warehouse_id = substr($supplier_id, 4);
        }



        if (empty($warehouse_id)) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $search = $this->get('search');

        if (empty($search)) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Search parameter is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('limit') ? (int) $this->get('limit') : 50;
        $offset = ($page - 1) * $limit;

        $this->db->select('id');
        $this->db->from(db_prefix() . 'items');
        if ('WAR' != substr($supplier_id, 0, 3)) {
            $this->db->where('userid', $supplier_id);
        }
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('active', 1);

        $this->db->group_start();
       // $this->db->like('code', $search);
        
        if(is_numeric($search)){
        $this->db->where('sku_code', $search)
        ->or_where("commodity_barcode", $search);
        }else{
        
        $this->db->like('description', $search);
        }
        $this->db->group_end();

        $total_query = $this->db->get_compiled_select();
        $total = $this->db->query("SELECT COUNT(*) as count FROM ($total_query) as subquery")->row()->count;

        $this->db->select('id, sku_code as code, description, cost, price_cliente_final as price');
        $this->db->from(db_prefix() . 'items');
        if ('WAR' != substr($supplier_id, 0, 3)) {
            $this->db->where('userid', $supplier_id);
        }
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('active', 1);

        $this->db->group_start();
       // $this->db->like('code', $search);
        /*
        $this->db->or_like('sku_code', $search);
        $this->db->or_like('description', $search);
         * 
         */
        if(is_numeric($search)){
        $this->db->where('sku_code', $search);
        }else{
        
        $this->db->like('description', $search);
        }
        $this->db->group_end();

        $this->db->order_by('description', 'ASC');
        $this->db->limit($limit, $offset);

        $products = $this->db->get()->result_array();

        foreach ($products as &$product) {
            $cost = (float) $product['cost'];
            $price = (float) $product['price'];
            $product['cost'] = $cost;
            $product['margin'] = $cost > 0 ? number_format((($price - $cost) / $cost * 100), 2) : '0.00';
        }

        $this->response([
            'status' => TRUE,
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
            'data' => $products
                ], REST_Controller::HTTP_OK);
    }

    
    public function status_put() {
        $_PUT = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_PUT['warehouse_id'])) {
            $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                    REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        if ((!isset($_PUT['romaneio_id']) || empty($_PUT['romaneio_id'])) &&
                (!isset($_PUT['order_id']) || empty($_PUT['order_id']))) {
            $this->response([
                'status' => FALSE,
                'message' => 'Either romaneio_id or order_id is required'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_PUT['status']) || empty($_PUT['status'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Status is required'
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($_PUT['status'], $valid_statuses)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid status. Valid values are: ' . implode(', ', $valid_statuses)
                    ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->trans_start();

        if (isset($_PUT['romaneio_id']) && !empty($_PUT['romaneio_id'])) {
            $romaneio_id = $_PUT['romaneio_id'];

            $this->db->where('id', $romaneio_id);
            $this->db->where('warehouse_id', $_PUT['warehouse_id']);
            $romaneio = $this->db->get(db_prefix() . 'romaneios')->row_array();

            if (!$romaneio) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Romaneio not found'
                        ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $this->db->where('id', $romaneio_id);
            $this->db->update(db_prefix() . 'romaneios', [
                'status' => $_PUT['status'],
                'date_updated' => date('Y-m-d H:i:s')
            ]);

            if (isset($_PUT['update_orders']) && $_PUT['update_orders'] === true) {
                $this->db->where('romaneio_id', $romaneio_id);
                $this->db->where('warehouse_id', $_PUT['warehouse_id']);
                $orders = $this->db->get(db_prefix() . 'romaneio_orders')->result_array();

                foreach ($orders as $order) {
                    $this->db->where('id', $order['id']);
                    $this->db->update(db_prefix() . 'romaneio_orders', [
                        'status' => $_PUT['status'],
                        'date_updated' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } else {
            $order_id = $_PUT['order_id'];

            $this->db->where('id', $order_id);
            $this->db->where('warehouse_id', $_PUT['warehouse_id']);
            $order = $this->db->get(db_prefix() . 'romaneio_orders')->row_array();

            if (!$order) {
                $this->db->trans_rollback();
                $this->response([
                    'status' => FALSE,
                    'message' => 'Order not found'
                        ], REST_Controller::HTTP_NOT_FOUND);
                return;
            }

            $this->db->where('id', $order_id);
            $this->db->update(db_prefix() . 'romaneio_orders', [
                'status' => $_PUT['status'],
                'date_updated' => date('Y-m-d H:i:s')
            ]);

            $romaneio_id = $order['romaneio_id'];

            $this->db->where('romaneio_id', $romaneio_id);
            $this->db->where('warehouse_id', $_PUT['warehouse_id']);
            $orders = $this->db->get(db_prefix() . 'romaneio_orders')->result_array();

            $all_same_status = true;
            foreach ($orders as $o) {
                if ($o['id'] != $order_id && $o['status'] != $_PUT['status']) {
                    $all_same_status = false;
                    break;
                }
            }

            if ($all_same_status) {
                $this->db->where('id', $romaneio_id);
                $this->db->where('warehouse_id', $_PUT['warehouse_id']);
                $this->db->update(db_prefix() . 'romaneios', [
                    'status' => $_PUT['status'],
                    'date_updated' => date('Y-m-d H:i:s')
                ]);
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Transaction failed'
                    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->response([
            'status' => TRUE,
            'message' => 'Status updated successfully'
                ], REST_Controller::HTTP_OK);
    }

    
    /**
     * Create a romaneio de entrada (incoming products)
     */
    public function entrada_post() {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = 'Invalid JSON data';
            switch (json_last_error()) {
                case JSON_ERROR_DEPTH:
                    $error_msg .= ': Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $error_msg .= ': Invalid or malformed JSON';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $error_msg .= ': Control character error';
                    break;
                case JSON_ERROR_SYNTAX:
                    $error_msg .= ': Syntax error';
                    break;
                case JSON_ERROR_UTF8:
                    $error_msg .= ': UTF-8 encoding error';
                    break;
                default:
                    $error_msg .= ': Unknown JSON error';
                    break;
            }
            $this->response(
                ['status' => FALSE, 'message' => $error_msg],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        
        $_POST = $data;

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $warehouse_id = $_POST['warehouse_id'];
        $target_warehouse_id = !empty($_POST['franchise_id']) ? $_POST['franchise_id'] : $warehouse_id;
        
        if (!isset($_POST['supplier']) || !isset($_POST['supplier']['id']) || empty($_POST['supplier']['id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Supplier is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['products']) || !is_array($_POST['products']) || empty($_POST['products'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'At least one product is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        
        if (!empty($_POST['franchise_id'])) {
            if (!is_numeric($_POST['franchise_id'])) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Invalid franchise ID: must be numeric'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
        }
        
        $this->db->trans_start();
        
        try {
            $this->ensureCategoriesExist($_POST['products'], $target_warehouse_id);
            $this->ensureSubcategoriesExist($_POST['products'], $target_warehouse_id);
            $this->ensureUnitsExist($_POST['products']);
            
            $romaneio_data = [
                'date_created' => date('Y-m-d H:i:s'),
                'status' => $_POST['status'] ?? 'pending',
                'notes' => $_POST['notes'] ?? null,
                'created_by' => $this->session->userdata('staff_user_id') ?? 1,
                'warehouse_id' => $warehouse_id,
                'type' => 'entrada'
            ];
            
            $this->db->insert(db_prefix() . 'romaneios', $romaneio_data);
            $romaneio_id = $this->db->insert_id();
            
            if (!$romaneio_id) {
                throw new Exception('Failed to create romaneio');
            }
            
            $this->load->model('Invoice_items_model');
            
            $result = $this->Invoice_items_model->bulk_create_products($_POST['products'], $target_warehouse_id);
            
            if (!$result['status']) {
                throw new Exception($result['message']);
            }
            
            $order_data = [
                'romaneio_id' => $romaneio_id,
                'supplier_id' => $_POST['supplier']['id'],
                'supplier_name' => $_POST['supplier']['name'],
                'date_created' => date('Y-m-d H:i:s'),
                'status' => $_POST['status'] ?? 'pending',
                'total_cost' => 0,
                'total_price' => 0,
                'total_items' => count($result['data']),
                'warehouse_id' => $target_warehouse_id,
                'type' => 'entrada'
            ];
            
            $this->db->insert(db_prefix() . 'romaneio_orders', $order_data);
            $order_id = $this->db->insert_id();
            
            if (!$order_id) {
                throw new Exception('Failed to create romaneio order');
            }
            
            $total_order_cost = 0;
            $total_order_price = 0;
            $total_order_items_count = 0;

            $input_products_map = [];
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $input_p) {
                    if (isset($input_p['sku_code'])) {
                        $input_products_map[$input_p['sku_code']] = $input_p;
                    }
                }
            }
            
            foreach ($result['data'] as $created_product_info) {
                $quantity = 1; 
                
                if (isset($created_product_info->sku_code) && isset($input_products_map[$created_product_info->sku_code])) {
                    $input_product_detail = $input_products_map[$created_product_info->sku_code];
                    if (isset($input_product_detail['stock'])) {
                        $parsed_quantity = (int)$input_product_detail['stock'];
                        if ($parsed_quantity > 0) {
                            $quantity = $parsed_quantity;
                        }
                    }
                }

                $cost_per_unit = (float)($created_product_info->cost ?? 0);
                $price_per_unit = (float)($created_product_info->rate ?? 0);
                
                $item_total_cost = $cost_per_unit * $quantity;
                $item_total_price = $price_per_unit * $quantity;
                
                $total_order_cost += $item_total_cost;
                $total_order_price += $item_total_price;
                $total_order_items_count += $quantity;
                
                $product_item_data = [
                    'order_id' => $order_id,
                    'product_id' => $created_product_info->id,
                    'code' => $created_product_info->sku_code,
                    'description' => $created_product_info->description,
                    'quantity' => $quantity,
                    'cost' => $cost_per_unit,
                    'price' => $price_per_unit,
                    'total_cost' => $item_total_cost,
                    'total_price' => $item_total_price,
                    'margin' => $cost_per_unit > 0 ? (($price_per_unit - $cost_per_unit) / $cost_per_unit * 100) : 0,
                    'warehouse_id' => $target_warehouse_id
                ];
                
                $this->db->insert(db_prefix() . 'romaneio_order_items', $product_item_data);
            }
            
            $this->db->where('id', $order_id);
            $this->db->update(db_prefix() . 'romaneio_orders', [
                'total_cost' => $total_order_cost,
                'total_price' => $total_order_price,
                'total_items' => $total_order_items_count, // Update with sum of quantities
                'margin' => $total_order_cost > 0 ? (($total_order_price - $total_order_cost) / $total_order_cost * 100) : 0
            ]);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }
            
            $response_data = [
                'status' => TRUE,
                'message' => 'Romaneio de entrada created successfully',
                'romaneio_id' => $romaneio_id,
                'products_created' => $result['created'],
                'products_failed' => $result['failed']
            ];
            
            if (!empty($_POST['franchise_id'])) {
                $response_data['message'] = 'Romaneio de entrada created successfully for franchise warehouse: ' . $target_warehouse_id;
            }
            
            $this->response($response_data, REST_Controller::HTTP_OK);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Error creating romaneio de entrada: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Edit an existing romaneio de entrada
     */
    public function entrada_put($romaneio_id = '') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if ($data === null) {
            $this->response(
                ['status' => FALSE, 'message' => 'Invalid JSON data'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        
        $_PUT = $data;

        if (empty($romaneio_id) && isset($_PUT['romaneio_id'])) {
            $romaneio_id = $_PUT['romaneio_id'];
        }

        if (empty($romaneio_id) || !is_numeric($romaneio_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid romaneio ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (empty($_PUT['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $_PUT['warehouse_id'];
        $target_warehouse_id = !empty($_PUT['franchise_id']) ? $_PUT['franchise_id'] : $warehouse_id;

        if (!empty($_PUT['franchise_id']) && !is_numeric($_PUT['franchise_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid franchise ID: must be numeric'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->trans_start();

        try {
            $this->db->where('id', $romaneio_id);
            $this->db->where('warehouse_id', $warehouse_id);
            $this->db->where('type', 'entrada');
            $romaneio = $this->db->get(db_prefix() . 'romaneios')->row_array();

            if (!$romaneio) {
                throw new Exception('Romaneio de entrada not found');
            }

            // Get the romaneio order
            $this->db->where('romaneio_id', $romaneio_id);
            $romaneio_order = $this->db->get(db_prefix() . 'romaneio_orders')->row_array();

            if (!$romaneio_order) {
                throw new Exception('Romaneio order not found');
            }

            $order_id = $romaneio_order['id'];

            if (isset($_PUT['deleted_products']) && is_array($_PUT['deleted_products'])) {
                foreach ($_PUT['deleted_products'] as $deleted_product) {
                    if (isset($deleted_product['id']) && is_numeric($deleted_product['id'])) {
                        $this->db->where('id', $deleted_product['id']);
                        $this->db->where('order_id', $order_id);
                        $this->db->delete(db_prefix() . 'romaneio_order_items');
                    }
                }
            }

            $total_cost = 0;
            $total_price = 0;
            $total_items = 0;
            $new_products_to_create = [];

            if (isset($_PUT['products']) && is_array($_PUT['products'])) {
                foreach ($_PUT['products'] as $product) {
                    $quantity = (int)($product['quantity'] ?? 1);
                    $cost = (float)($product['cost'] ?? 0);
                    $price = (float)($product['price'] ?? 0);

                    $item_total_cost = $cost * $quantity;
                    $item_total_price = $price * $quantity;
                    $total_cost += $item_total_cost;
                    $total_price += $item_total_price;
                    $total_items += $quantity;

                    if (isset($product['id']) && is_numeric($product['id'])) {
                        $update_data = [
                            'quantity' => $quantity,
                            'cost' => $cost,
                            'price' => $price,
                            'total_cost' => $item_total_cost,
                            'total_price' => $item_total_price,
                            'margin' => $cost > 0 ? (($price - $cost) / $cost * 100) : 0,
                            'warehouse_id' => $target_warehouse_id
                        ];

                        $this->db->where('id', $product['id']);
                        $this->db->where('order_id', $order_id);
                        $this->db->update(db_prefix() . 'romaneio_order_items', $update_data);
                    } else {
                        $new_product_data = [
                            'description' => $product['description'] ?? '',
                            'sku_code' => $product['sku'] ?? $product['code'] ?? '',
                            'commodity_barcode' => $product['barcode'] ?? '',
                            'cost' => $cost,
                            'rate' => $price,
                            'stock' => $quantity,
                            'minStock' => $product['minStock'] ?? 0,
                            'ncm' => $product['ncm'] ?? '',
                            'cfop' => $product['cfop'] ?? '',
                            'active' => $product['active'] ?? '1',
                            'unit_id' => $product['unit_id'] ?? '',
                            'net_weight' => $product['weight'] ?? '',
                            'length' => $product['length'] ?? '',
                            'width' => $product['width'] ?? '',
                            'height' => $product['height'] ?? '',
                            'warehouse_id' => $target_warehouse_id
                        ];
                        
                        $new_products_to_create[] = [
                            'product_data' => $new_product_data,
                            'romaneio_data' => [
                                'quantity' => $quantity,
                                'cost' => $cost,
                                'price' => $price,
                                'total_cost' => $item_total_cost,
                                'total_price' => $item_total_price
                            ]
                        ];
                    }
                }
            }

            if (!empty($new_products_to_create)) {
                $this->load->model('Invoice_items_model');
                
                $products_data = array_column($new_products_to_create, 'product_data');
                $this->ensureCategoriesExist($products_data, $target_warehouse_id);
                $this->ensureSubcategoriesExist($products_data, $target_warehouse_id);
                $this->ensureUnitsExist($products_data);

                $result = $this->Invoice_items_model->bulk_create_products($products_data, $target_warehouse_id);

                if ($result['status'] && !empty($result['data'])) {
                    foreach ($result['data'] as $index => $created_product) {
                        $romaneio_data = $new_products_to_create[$index]['romaneio_data'];
                        
                        $product_item_data = [
                            'order_id' => $order_id,
                            'product_id' => $created_product->id,
                            'code' => $created_product->sku_code,
                            'description' => $created_product->description,
                            'quantity' => $romaneio_data['quantity'],
                            'cost' => $romaneio_data['cost'],
                            'price' => $romaneio_data['price'],
                            'total_cost' => $romaneio_data['total_cost'],
                            'total_price' => $romaneio_data['total_price'],
                            'margin' => $romaneio_data['cost'] > 0 ? (($romaneio_data['price'] - $romaneio_data['cost']) / $romaneio_data['cost'] * 100) : 0,
                            'warehouse_id' => $target_warehouse_id
                        ];

                        $this->db->insert(db_prefix() . 'romaneio_order_items', $product_item_data);
                    }
                }
            }

            $order_update_data = [
                'total_cost' => $total_cost,
                'total_price' => $total_price,
                'total_items' => $total_items,
                'margin' => $total_cost > 0 ? (($total_price - $total_cost) / $total_cost * 100) : 0,
                'warehouse_id' => $target_warehouse_id,
                'date_updated' => date('Y-m-d H:i:s')
            ];

            if (isset($_PUT['supplier_id'])) {
                $order_update_data['supplier_id'] = $_PUT['supplier_id'];
            }
            if (isset($_PUT['supplier_name'])) {
                $order_update_data['supplier_name'] = $_PUT['supplier_name'];
            }

            $this->db->where('id', $order_id);
            $this->db->update(db_prefix() . 'romaneio_orders', $order_update_data);

            $romaneio_update_data = [
                'date_updated' => date('Y-m-d H:i:s')
            ];

            if (isset($_PUT['status'])) {
                $romaneio_update_data['status'] = $_PUT['status'];
            }
            if (isset($_PUT['notes'])) {
                $romaneio_update_data['notes'] = $_PUT['notes'];
            }

            $this->db->where('id', $romaneio_id);
            $this->db->update(db_prefix() . 'romaneios', $romaneio_update_data);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $response_data = [
                'status' => TRUE,
                'message' => 'Romaneio de entrada updated successfully',
                'romaneio_id' => $romaneio_id
            ];

            if (!empty($new_products_to_create)) {
                $response_data['new_products_created'] = count($new_products_to_create);
            }

            if (isset($_PUT['deleted_products']) && !empty($_PUT['deleted_products'])) {
                $response_data['products_deleted'] = count($_PUT['deleted_products']);
            }

            $this->response($response_data, REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Error updating romaneio de entrada: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Edit an existing romaneio de saída
     */
    public function saida_put($romaneio_id = '') {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);
        
        if ($data === null) {
            $this->response(
                ['status' => FALSE, 'message' => 'Invalid JSON data'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        
        $_PUT = $data;

        if (empty($romaneio_id) && isset($_PUT['romaneio_id'])) {
            $romaneio_id = $_PUT['romaneio_id'];
        }

        if (empty($romaneio_id) || !is_numeric($romaneio_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid romaneio ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (empty($_PUT['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $_PUT['warehouse_id'];

        $this->db->trans_start();

        try {
            $this->db->where('id', $romaneio_id);
            $this->db->where('warehouse_id', $warehouse_id);
            $this->db->where('type', 'saida');
            $romaneio = $this->db->get(db_prefix() . 'romaneios')->row_array();

            if (!$romaneio) {
                throw new Exception('Romaneio de saída not found');
            }

            if (isset($_PUT['deleted_products']) && is_array($_PUT['deleted_products'])) {
                foreach ($_PUT['deleted_products'] as $deleted_product) {
                    if (isset($deleted_product['id']) && is_numeric($deleted_product['id'])) {
                        $this->db->where('id', $deleted_product['id']);
                        $this->db->delete(db_prefix() . 'romaneio_order_items');
                    }
                }
            }

            if (isset($_PUT['orders']) && is_array($_PUT['orders'])) {
                foreach ($_PUT['orders'] as $order) {
                    $order_id = null;
                    $supplier_id = $order['supplier_id'] ?? null;
                    $supplier_name = $order['supplier_name'] ?? '';

                    if (isset($order['id']) && is_numeric($order['id'])) {
                        $this->db->where('id', $order['id']);
                        $this->db->where('romaneio_id', $romaneio_id);
                        $existing_order = $this->db->get(db_prefix() . 'romaneio_orders')->row_array();
                        
                        if ($existing_order) {
                            $order_id = $order['id'];
                        }
                    }

                    if (!$order_id && $supplier_id) {
                        $order_data = [
                            'romaneio_id' => $romaneio_id,
                            'supplier_id' => $supplier_id,
                            'supplier_name' => $supplier_name,
                            'date_created' => date('Y-m-d H:i:s'),
                            'status' => $order['status'] ?? 'pending',
                            'total_cost' => 0,
                            'total_price' => 0,
                            'total_items' => 0,
                            'margin' => 0,
                            'warehouse_id' => $warehouse_id,
                            'type' => 'saida'
                        ];

                        $this->db->insert(db_prefix() . 'romaneio_orders', $order_data);
                        $order_id = $this->db->insert_id();
                    }

                    if ($order_id) {
                        $order_total_cost = 0;
                        $order_total_price = 0;
                        $order_total_items = 0;
                        $new_products_to_create = [];

                        if (isset($order['items']) && is_array($order['items'])) {
                            foreach ($order['items'] as $item) {
                                $quantity = (int)($item['quantity'] ?? 1);
                                $cost = (float)($item['cost'] ?? 0);
                                $price = (float)($item['price'] ?? 0);

                                $item_total_cost = $cost * $quantity;
                                $item_total_price = $price * $quantity;
                                $order_total_cost += $item_total_cost;
                                $order_total_price += $item_total_price;
                                $order_total_items += $quantity;

                                $is_existing_item = false;
                                if (isset($item['id']) && is_numeric($item['id'])) {
                                    $this->db->where('id', $item['id']);
                                    $this->db->where('order_id', $order_id);
                                    $existing_item = $this->db->get(db_prefix() . 'romaneio_order_items')->row();
                                    
                                    if ($existing_item) {
                                        $update_data = [
                                            'quantity' => $quantity,
                                            'cost' => $cost,
                                            'price' => $price,
                                            'total_cost' => $item_total_cost,
                                            'total_price' => $item_total_price,
                                            'margin' => $cost > 0 ? (($price - $cost) / $cost * 100) : 0,
                                            'warehouse_id' => $warehouse_id
                                        ];

                                        $this->db->where('id', $item['id']);
                                        $this->db->where('order_id', $order_id);
                                        $this->db->update(db_prefix() . 'romaneio_order_items', $update_data);
                                        $is_existing_item = true;
                                    }
                                }

                                if (!$is_existing_item && ((isset($item['productId']) && is_numeric($item['productId'])) || (isset($item['id']) && is_numeric($item['id'])))) {
                                    $product_id = isset($item['productId']) ? $item['productId'] : $item['id'];
                                    
                                    $this->db->where('id', $product_id);
                                    $product = $this->db->get(db_prefix() . 'items')->row();

                                    if ($product) {
                                        $product_item_data = [
                                            'order_id' => $order_id,
                                            'product_id' => $product->id,
                                            'code' => $product->sku_code ?? $item['code'] ?? '',
                                            'description' => $product->description ?? $item['description'] ?? '',
                                            'quantity' => $quantity,
                                            'cost' => $cost,
                                            'price' => $price,
                                            'total_cost' => $item_total_cost,
                                            'total_price' => $item_total_price,
                                            'margin' => $cost > 0 ? (($price - $cost) / $cost * 100) : 0,
                                            'warehouse_id' => $warehouse_id
                                        ];

                                        $this->db->insert(db_prefix() . 'romaneio_order_items', $product_item_data);
                                    }
                                } elseif (!$is_existing_item) {
                                    $new_product_data = [
                                        'description' => $item['description'] ?? '',
                                        'sku_code' => $item['code'] ?? '',
                                        'cost' => $cost,
                                        'rate' => $price,
                                        'stock' => $quantity,
                                        'warehouse_id' => $warehouse_id
                                    ];
                                    
                                    $new_products_to_create[] = [
                                        'product_data' => $new_product_data,
                                        'romaneio_data' => [
                                            'quantity' => $quantity,
                                            'cost' => $cost,
                                            'price' => $price,
                                            'total_cost' => $item_total_cost,
                                            'total_price' => $item_total_price,
                                            'order_id' => $order_id
                                        ]
                                    ];
                                }
                            }
                        }

                        if (!empty($new_products_to_create)) {
                            $this->load->model('Invoice_items_model');
                            
                            $products_data = array_column($new_products_to_create, 'product_data');
                            $result = $this->Invoice_items_model->bulk_create_products($products_data, $warehouse_id);

                            if ($result['status'] && !empty($result['data'])) {
                                foreach ($result['data'] as $index => $created_product) {
                                    $romaneio_data = $new_products_to_create[$index]['romaneio_data'];
                                    
                                    $product_item_data = [
                                        'order_id' => $romaneio_data['order_id'],
                                        'product_id' => $created_product->id,
                                        'code' => $created_product->sku_code,
                                        'description' => $created_product->description,
                                        'quantity' => $romaneio_data['quantity'],
                                        'cost' => $romaneio_data['cost'],
                                        'price' => $romaneio_data['price'],
                                        'total_cost' => $romaneio_data['total_cost'],
                                        'total_price' => $romaneio_data['total_price'],
                                        'margin' => $romaneio_data['cost'] > 0 ? (($romaneio_data['price'] - $romaneio_data['cost']) / $romaneio_data['cost'] * 100) : 0,
                                        'warehouse_id' => $warehouse_id
                                    ];

                                    $this->db->insert(db_prefix() . 'romaneio_order_items', $product_item_data);
                                }
                            }
                        }

                        // Update order totals
                        $order_update_data = [
                            'supplier_id' => $supplier_id,
                            'supplier_name' => $supplier_name,
                            'total_cost' => $order_total_cost,
                            'total_price' => $order_total_price,
                            'total_items' => $order_total_items,
                            'margin' => $order_total_cost > 0 ? (($order_total_price - $order_total_cost) / $order_total_cost * 100) : 0,
                            'date_updated' => date('Y-m-d H:i:s')
                        ];

                        if (isset($order['status'])) {
                            $order_update_data['status'] = $order['status'];
                        }

                        $this->db->where('id', $order_id);
                        $this->db->update(db_prefix() . 'romaneio_orders', $order_update_data);
                    }
                }
            }

            $romaneio_update_data = [
                'date_updated' => date('Y-m-d H:i:s')
            ];

            if (isset($_PUT['customer_id'])) {
                $romaneio_update_data['customer_id'] = $_PUT['customer_id'];
            }
            if (isset($_PUT['customer_name'])) {
                $romaneio_update_data['customer_name'] = $_PUT['customer_name'];
            }
            if (isset($_PUT['status'])) {
                $romaneio_update_data['status'] = $_PUT['status'];
            }
            if (isset($_PUT['notes'])) {
                $romaneio_update_data['notes'] = $_PUT['notes'];
            }

            $this->db->where('id', $romaneio_id);
            $this->db->update(db_prefix() . 'romaneios', $romaneio_update_data);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Transaction failed');
            }

            $response_data = [
                'status' => TRUE,
                'message' => 'Romaneio de saída updated successfully',
                'romaneio_id' => $romaneio_id
            ];

            if (isset($_PUT['deleted_products']) && !empty($_PUT['deleted_products'])) {
                $response_data['products_deleted'] = count($_PUT['deleted_products']);
            }

            $this->response($response_data, REST_Controller::HTTP_OK);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->response([
                'status' => FALSE,
                'message' => 'Error updating romaneio de saída: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Ensure categories exist, create them if they don't
     */
    private function ensureCategoriesExist(&$products, $warehouse_id) {
        $category_names = [];
        
        foreach ($products as $product) {
            if (isset($product['group_id']) && !empty($product['group_id']) && !is_numeric($product['group_id'])) {
                $category_names[] = trim($product['group_id']);
            }
        }
        
        if (empty($category_names)) {
            return;
        }
        
        $category_names = array_unique($category_names);
        
        $this->db->select('id, name');
        $this->db->from(db_prefix() . 'items_groups');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where_in('name', $category_names);
        $existing_categories = $this->db->get()->result_array();
        
        $existing_map = [];
        foreach ($existing_categories as $cat) {
            $existing_map[strtolower(trim($cat['name']))] = $cat['id'];
        }
        
        foreach ($products as &$product) {
            if (isset($product['group_id']) && !empty($product['group_id']) && !is_numeric($product['group_id'])) {
                $category_name = trim($product['group_id']);
                $category_key = strtolower($category_name);
                
                if (isset($existing_map[$category_key])) {
                    $product['group_id'] = $existing_map[$category_key];
                } else {
                    $category_data = [
                        'name' => $category_name,
                        'warehouse_id' => $warehouse_id
                    ];
                    
                    $this->db->insert(db_prefix() . 'items_groups', $category_data);
                    $new_category_id = $this->db->insert_id();
                    
                    if ($new_category_id) {
                        $existing_map[$category_key] = $new_category_id;
                        $product['group_id'] = $new_category_id;
                        log_activity('Auto-created category: ' . $category_name . ' (ID: ' . $new_category_id . ')');
                    }
                }
            }
        }
    }

    /**
     * Ensure subcategories exist, create them if they don't
     */
    private function ensureSubcategoriesExist(&$products, $warehouse_id) {
        $subcategory_data = [];
        
        foreach ($products as $product) {
            if (isset($product['sub_group']) && !empty($product['sub_group']) && !is_numeric($product['sub_group'])) {
                $category_id = isset($product['group_id']) ? $product['group_id'] : null;
                if ($category_id && is_numeric($category_id)) {
                    $subcategory_name = trim($product['sub_group']);
                    $key = $category_id . '_' . strtolower($subcategory_name);
                    $subcategory_data[$key] = [
                        'name' => $subcategory_name,
                        'group_id' => $category_id
                    ];
                }
            }
        }
        
        if (empty($subcategory_data)) {
            return;
        }
        
        $subcategory_names = array_column($subcategory_data, 'name');
        $group_ids = array_unique(array_column($subcategory_data, 'group_id'));
        
        $this->db->select('id, sub_group_name, group_id');
        $this->db->from(db_prefix() . 'wh_sub_group');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where_in('group_id', $group_ids);
        $this->db->where_in('sub_group_name', $subcategory_names);
        $existing_subcategories = $this->db->get()->result_array();
        
        $existing_map = [];
        foreach ($existing_subcategories as $subcat) {
            $key = $subcat['group_id'] . '_' . strtolower(trim($subcat['sub_group_name']));
            $existing_map[$key] = $subcat['id'];
        }
        
        foreach ($products as &$product) {
            if (isset($product['sub_group']) && !empty($product['sub_group']) && !is_numeric($product['sub_group'])) {
                $category_id = isset($product['group_id']) ? $product['group_id'] : null;
                if ($category_id && is_numeric($category_id)) {
                    $subcategory_name = trim($product['sub_group']);
                    $key = $category_id . '_' . strtolower($subcategory_name);
                    
                    if (isset($existing_map[$key])) {
                        $product['sub_group'] = $existing_map[$key];
                    } else {
                        $subcat_data = [
                            'sub_group_name' => $subcategory_name,
                            'group_id' => $category_id,
                            'warehouse_id' => $warehouse_id,
                            'display' => 1,
                            'order' => 0
                        ];
                        
                        $this->db->insert(db_prefix() . 'wh_sub_group', $subcat_data);
                        $new_subcategory_id = $this->db->insert_id();
                        
                        if ($new_subcategory_id) {
                            $existing_map[$key] = $new_subcategory_id;
                            $product['sub_group'] = $new_subcategory_id;
                            log_activity('Auto-created subcategory: ' . $subcategory_name . ' (ID: ' . $new_subcategory_id . ') for category ID: ' . $category_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Ensure units exist, create them if they don't
     */
    private function ensureUnitsExist(&$products) {
        $unit_data = [];
        
        foreach ($products as $product) {
            if (isset($product['unit_id']) && !empty($product['unit_id']) && !is_numeric($product['unit_id'])) {
                $unit_code = trim($product['unit_id']);
                $unit_data[strtolower($unit_code)] = $unit_code;
            }
        }
        
        if (empty($unit_data)) {
            return;
        }
        
        $unit_codes = array_values($unit_data);
        $this->db->select('unit_type_id, unit_code, unit_name');
        $this->db->from(db_prefix() . 'ware_unit_type');
        $this->db->where_in('unit_code', $unit_codes);
        $existing_units = $this->db->get()->result_array();
        
        $existing_map = [];
        foreach ($existing_units as $unit) {
            $existing_map[strtolower(trim($unit['unit_code']))] = $unit['unit_type_id'];
        }
        
        foreach ($products as &$product) {
            if (isset($product['unit_id']) && !empty($product['unit_id']) && !is_numeric($product['unit_id'])) {
                $unit_code = trim($product['unit_id']);
                $unit_key = strtolower($unit_code);
                
                if (isset($existing_map[$unit_key])) {
                    $product['unit_id'] = $existing_map[$unit_key];
                } else {
                    $max_order_query = $this->db->select_max('order')->get(db_prefix() . 'ware_unit_type');
                    $max_order = $max_order_query->row()->order ?? 0;
                    
                    $unit_insert_data = [
                        'unit_code' => $unit_code,
                        'unit_name' => $unit_code, 
                        'unit_symbol' => $unit_code,
                        'order' => $max_order + 1,
                        'display' => 1,
                        'note' => 'Auto-created unit'
                    ];
                    
                    $this->db->insert(db_prefix() . 'ware_unit_type', $unit_insert_data);
                    $new_unit_id = $this->db->insert_id();
                    
                    if ($new_unit_id) {
                        $existing_map[$unit_key] = $new_unit_id;
                        $product['unit_id'] = $new_unit_id;
                        log_activity('Auto-created unit: ' . $unit_code . ' (ID: ' . $new_unit_id . ')');
                    }
                }
            }
        }
    }
}
