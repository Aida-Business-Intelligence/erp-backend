<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Romaneio extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('clients_model');
        $this->load->model('warehouse_model');
        $this->decodedToken = $this->authservice->decodeToken($this->token_jwt);
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
        $type= $this->get('type');

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
            $this->db->where('r.type', $type);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('r.id', $search);
                $this->db->or_like('r.customer_name', $search);
                $this->db->group_end();
            }

            if (!empty($status)) {
                $this->db->where('r.status', $status);
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

            $validSortFields = ['id', 'customer_name', 'date_created', 'status'];
            $sortFieldDB = in_array($sortField, $validSortFields) ? 'r.' . $sortField : 'r.id';

            $this->db->order_by($sortFieldDB, $sortOrder);

            $totalQuery = $this->db->get_compiled_select();

            $query = $this->db->query("SELECT COUNT(*) as total FROM ({$totalQuery}) as total_query");
            $total = $query->row()->total;

            $this->db->select('r.*, COUNT(o.id) as order_count');
            $this->db->from(db_prefix() . 'romaneios r');
            $this->db->join(db_prefix() . 'romaneio_orders o', 'o.romaneio_id = r.id', 'left');
            $this->db->where('r.warehouse_id', $warehouse_id);
            $this->db->where('r.type', $type);

            if (!empty($search)) {
                $this->db->group_start();
                $this->db->like('r.id', $search);
                $this->db->or_like('r.customer_name', $search);
                $this->db->group_end();
            }

            if (!empty($status)) {
                $this->db->where('r.status', $status);
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
                } else {
                    $romaneio['total_items'] = 0;
                    $romaneio['total_cost'] = 0;
                    $romaneio['total_price'] = 0;
                    $romaneio['margin'] = 0;
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
            'user_id' => $this->decodedToken['data']->user->staffid,
            'user_name' => $this->decodedToken['data']->user->firstname. ' '.$this->decodedToken['data']->user->lastname,
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

            $supplierIdNumber = str_replace("WAR_", "", $order['supplierId']);
            $order_data = [
                'romaneio_id' => $romaneio_id,
                'supplier_id' => (int)$supplierIdNumber,
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
            $data = array(
                'warehouse_id' => $supplierIdNumber, 
                'user_id' => $this->decodedToken['data']->user->staffid,
                'hash' => app_generate_hash());
                

            foreach ($order['products'] as $product) {

                
               $product_margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) * 100) / $product['price']  : 0;

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
                updateStock($data, $product, array('id' => $romaneio_id, 'type' => 'romaneio'));

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
    public function update_put() {
        $_PUT = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        $id = $_PUT ['romaneio_id'];

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
        $orders = $this->get_orders_romaneio($id);

   

        foreach($orders as $ord){

          $this->delete_orders_romaneio($ord['id']); 
          $this->delete_itens_romaneio($ord['id']);

        }



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
            'customer_id' => $_PUT['customer']['id'],
            'customer_name' => $_PUT['customer']['name'],
            'user_id' => $this->decodedToken['data']->user->staffid,
            'user_name' => $this->decodedToken['data']->user->firstname. ' '.$this->decodedToken['data']->user->lastname,
        ];



       $this->update_romaneio($id, $romaneio_data);

        if (isset($_PUT['orders']) && is_array($_PUT['orders'])) {

         foreach ($_PUT['orders'] as $order) {

            $total_cost = 0;
            $total_price = 0;
            $total_items = 0;

            

               $order_data = [
                'romaneio_id' => $id,
                'supplier_id' => (int)$order['supplierId'],
                'supplier_name' => $order['supplierName'],
                'date_updated' => date('Y-m-d H:i:s'),
                'status' => $order['status'] ?? 'pending',
                'total_cost' => $order['totals']['cost'],
                'total_price' => $order['totals']['price'],
                'total_items' => $order['totals']['items'],
                'margin' => $order['totals']['margin'],
                'warehouse_id' => $_PUT['warehouse_id']
            ];

            $this->db->insert(db_prefix() . 'romaneio_orders', $order_data);
             $order_id = $this->db->insert_id();

             
            foreach ($order['products'] as $product) {

            $product_margin = $product['price'] > 0 ? (($product['price'] - $product['cost']) * 100) / $product['price']  : 0;


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
                    'margin' => number_format($product_margin,2),
                    'warehouse_id' => $_PUT['warehouse_id'],
                ];

                $this->db->insert(db_prefix() . 'romaneio_order_items', $product_data);
            }


                if (!isset($order['id']) || empty($order['id'])) {
                    continue;
                }

             


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
    public function delete_itens_romaneio($id) {

                 $this->db->where('order_id', $id);
                return $this->db->delete(db_prefix() . 'romaneio_order_items');

    }

    public function delete_orders_romaneio($id) {

                 $this->db->where('id', $id);
                return $this->db->delete(db_prefix() . 'romaneio_orders');

    }

    public function update_romaneio($id, $romaneio_data) {

       $this->db->where('id', $id);
       return $this->db->update(db_prefix() . 'romaneios', $romaneio_data);

    }

     public function update_status_patch() {


       $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

       $id = $_POST['romaneio_id'];
       $status =  $_POST['status'];

       $this->db->where('id', $id);
       return $this->db->update(db_prefix() . 'romaneios', array('status'=>$status));

    }

     public function get_orders_romaneio($id) {

       $this->db->where('romaneio_id', $id);
       return $this->db->get(db_prefix() . 'romaneio_orders')->result_array();
      

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

        if(getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') == 10){

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
        }


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

        if(getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') == 10){
            $combined = $fornecedores;
           
        }else{
            $combined = array_merge($suppliers, $fornecedores);
        }

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
        
        if(getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') == 10){
      
        $this->db->select('staffid as id, CONCAT(firstname, lastname) as name, vat as document');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $this->db->where('type', 'franchisees');

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
    }


        $warehouses = $this->warehouse_model->get("", "(type = 'filial' OR type = 'franquia')");


        $fornecedores = array();

        foreach ($warehouses as $wa) {
            $fornecedores[] = array(
                "id" => "WAR_" . $wa['warehouse_id'],
                "name" => $wa['warehouse_name'], // Ensuring this line uses warehouse_name for a more descriptive name if needed
                "document" => $wa['cnpj'] // Use a different key for cnpj to avoid overwriting 'name'
            );
        }

        if(getenv('NEXT_PUBLIC_CLIENT_MASTER_ID') == 10){
        $combined = array_merge($customers, $fornecedores);
        }else{
            $combined = $fornecedores;
        }

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
                    REST_Controller::HTTP_OK
            );
            return;
        }

        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('limit') ? (int) $this->get('limit') : 50;
        $offset = ($page - 1) * $limit;

        $this->db->select('id');
        $this->db->from(db_prefix() . 'items');
        /*
        if ('WAR' != substr($supplier_id, 0, 3)) {
            $this->db->where('userid', $supplier_id);
        }
            */
         if ('WAR' == substr($supplier_id, 0, 3)) {
            $warehouse_id = substr($supplier_id, 4);
        }

        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('active', 1);

        $this->db->group_start();
       
        if(is_numeric($search)){
            $this->db->where('code', $search);
        }else{
            $this->db->where('code', $search);
            $this->db->or_where('sku_code', $search);
            $this->db->or_where('commodity_barcode', $search);
            $this->db->or_where('code', $search);
            $this->db->or_like('description', $search);
        }

        $this->db->group_end();
        $total_query = $this->db->get_compiled_select();
        $total = $this->db->query("SELECT COUNT(*) as count FROM ($total_query) as subquery")->row()->count;

        $this->db->select('id, sku_code, description, cost, rate as price, commodity_barcode, code');
        $this->db->from(db_prefix() . 'items');
        /*
        if ('WAR' != substr($supplier_id, 0, 3)) {
            $this->db->where('userid', $supplier_id);
        }
            */
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('active', 1);
        $this->db->group_start();
        if(is_numeric($search)){
            $this->db->where('code', $search);
        }else{
      
        $this->db->where('code', $search);
        $this->db->or_where('sku_code', $search);
        $this->db->or_where('commodity_barcode', $search);
        $this->db->or_like('description', $search);
      
        }
        $this->db->group_end();

        $this->db->order_by('description', 'ASC');
        $this->db->limit($limit, $offset);

        $products = $this->db->get()->result_array();

        //echo $this->db->last_query(); exit;

        foreach ($products as &$product) {
            $cost = (float) $product['cost'];
            $price = (float) $product['price'];
            $product['cost'] = $cost;
            $product['commodity_barcode'] = $product['commodity_barcode'];
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
}
