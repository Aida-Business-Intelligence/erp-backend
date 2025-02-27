<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Invoices extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Invoices_model');

        $decodedToken = $this->authservice->decodeToken($this->token_jwt);
        if (!$decodedToken['status']) {
            $this->response([
                'status' => FALSE,
                'message' => 'Usuario nao autenticado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
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

        try {
            $this->db->trans_start();

            $decodedToken = $this->authservice->decodeToken($this->token_jwt);
            if (!$decodedToken['status']) {
                throw new Exception('User not authenticated');
            }

            $user_id = $decodedToken['data']->user->staffid;

            if (empty($user_id)) {
                throw new Exception('User ID not found in token');
            }

            $newitems = [];
            $total = 0;

            foreach ($_POST['items'] as $item) {
                $this->db->where('id', $item['id']);
                $purchase_need = $this->db->get(db_prefix() . 'purchase_needs')->row();

                if (!$purchase_need) {
                    throw new Exception('Purchase need not found with ID: ' . $item['id']);
                }

                $this->db->where('id', $purchase_need->id);
                $this->db->update(db_prefix() . 'purchase_needs', [
                    'status' => 1,
                    'user_id' => $user_id,
                    'date' => date('Y-m-d H:i:s')
                ]);

                if ($this->db->affected_rows() == 0) {
                    throw new Exception('Failed to update purchase need');
                }

                $this->db->where('id', $purchase_need->item_id);
                $product = $this->db->get(db_prefix() . 'items')->row();

                if (!$product) {
                    throw new Exception('Product not found for purchase need ID: ' . $item['id']);
                }

                $item_total = $product->cost * $purchase_need->qtde;
                $total += $item_total;

                $newitems[] = [
                    'description' => $product->description,
                    'long_description' => $product->long_description,
                    'qty' => $purchase_need->qtde,
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
                'addedfrom' => $user_id
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

    public function list_post()
    {
        $page = $this->post('page') ? (int)$this->post('page') : 1;
        $limit = $this->post('limit') ? (int)$this->post('limit') : 10;
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
                itm.cost as unit_cost
            ');
            $this->db->from(db_prefix() . 'purchase_needs pn');
            $this->db->join(db_prefix() . 'items itm', 'itm.id = pn.item_id', 'left');
            $this->db->where('pn.invoice_id', $invoice['id']);

            $invoice['items'] = $this->db->get()->result_array();
        }

        $this->response([
            'status' => TRUE,
            'total' => (int)$total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'data' => $invoices
        ], REST_Controller::HTTP_OK);
    }
}
