<?php

defined('BASEPATH') or exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Produto extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Invoice_items_model');
        $this->load->library('upload');
    }


    public function list_post($id = '')
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') ?: 'DESC';
        $send = $this->post('send') ?: null;



        $status = $this->post('status');
        $category = $this->post('category');
        $subcategory = $this->post('subcategory');

        $statusFilter = null;
        if (is_array($status) && !empty($status)) {
            $statusFilter = $status;
        }

        $start_date = $this->post('startDate') ?: '';
        $end_date = $this->post('endDate') ?: '';

        $data = $this->Invoice_items_model->get_api(
            $id,
            $page,
            $limit,
            $search,
            $sortField,
            $sortOrder,
            $statusFilter,
            $start_date,
            $end_date,
            $category,
            $subcategory,
            $warehouse_id,
            $send
        );

        if ($data['total'] > 0) {

            $this->response(
                [
                    'status' => true,
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_OK
            );
        } else {
            $this->response(
                [
                    'status' => false,
                    'message' => 'Produto não encontrado',
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_NOT_FOUND
            );


        }
    }

    public function list_ecommerce_post($id = '')
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') ?: 'DESC';
        $send = $this->post('send') ?: null;



        $status = $this->post('status');
        $category = $this->post('category');
        $subcategory = $this->post('subcategory');

        $statusFilter = null;
        if (is_array($status) && !empty($status)) {
            $statusFilter = $status;
        }

        $start_date = $this->post('startDate') ?: '';
        $end_date = $this->post('endDate') ?: '';

        $data = $this->Invoice_items_model->get_api2(
            $id,
            $page,
            $limit,
            // $search,
            // $sortField,
            // $sortOrder,
            // $statusFilter,
            // $start_date,
            // $end_date,
            // $category,
            // $subcategory,
            // $send,
            $warehouse_id,

        );

        if ($data['total'] > 0) {

            $this->response(
                [
                    'status' => true,
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_OK
            );
        } else {
            $this->response(
                [
                    'status' => false,
                    'message' => 'Produto não encontrado',
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_NOT_FOUND
            );


        }
    }

    public function create_post()
    {

        ini_set('display_errors', 1);
        ini_set('display_startup_erros', 1);
        error_reporting(E_ALL);

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        /*
        $product_data = [
            'description' => $_POST['description'] ?? null,
            'long_description' => $_POST['long_description'] ?? null,
            'rate' => $_POST['rate'] ?? 0.00,
            'tax' => $_POST['taxid'] ?? null,
            'tax2' => $_POST['taxid_2'] ?? null,
            'unit_id' => $_POST['unit_id'] ?? null,
            'unit' => $_POST['unit'] ?? null,
            'group_id' => $_POST['group_id'] ?? null,
            'sub_group' => $_POST['sub_group'] ?? null,
            'userid' => $_POST['userid'] ?? null,
            'code' => $_POST['code'] ?? null,
            'sku_code' => $_POST['sku_code'] ?? null,
            'commodity_barcode' => $_POST['barcode'] ?? null,
            'status' => $_POST['status'] ?? 'pending',
            'cost' => $_POST['cost'] ?? null,
            'promoPrice' => $_POST['promoPrice'] ?? null,
            'promoStart' => $_POST['promoStart'] ?? null,
            'promoEnd' => $_POST['promoEnd'] ?? null,
            'stock' => $_POST['stock'] ?? 0,
            'minStock' => $_POST['minStock'] ?? 0,
            'product_unit' => $_POST['product_unit'] ?? null,
            'warehouse_id' => $_POST['warehouse_id'],
            'cfop' => $_POST['cfop'] ?? '',
            'nfci' => $_POST['nfci'] ?? '',
            'code' => $_POST['code'] ?? null,
            'createdAt' => date('Y-m-d H:i:s'),
            'cest' => $_POST['cest'] ?? null,
            'ncm' => $_POST['ncm'] ?? null,
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        */

        $this->form_validation->set_data($_POST);
        $this->form_validation->set_rules('description', 'Description', 'trim|required|max_length[600]');
        $this->form_validation->set_rules('rate', 'Rate', 'numeric');
        $this->form_validation->set_rules('stock', 'Stock', 'numeric');
        $this->form_validation->set_rules('minStock', 'Minimum Stock', 'numeric');
        $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $message = [
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            ];
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $product_id = $this->Invoice_items_model->add($_POST);

        if ($product_id) {
            $product = $this->Invoice_items_model->get_api($product_id);

            $message = [
                'status' => TRUE,
                'message' => 'Product created successfully',
                'data' => $product['data']
            ];
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                'status' => FALSE,
                'message' => 'Failed to create product'
            ];
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload_put($item_id)
    {
        $raw_body = $this->input->raw_input_stream;

        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        if (!$content_type) {
            echo json_encode(['status' => FALSE, 'message' => 'Content-Type header is missing']);
            return;
        }

        if (preg_match('/boundary=(.*)$/', $content_type, $matches)) {
            if (isset($matches[1])) {
                $boundary = '--' . trim($matches[1]);
            } else {
                echo json_encode(['status' => FALSE, 'message' => 'Invalid boundary in Content-Type']);
                return;
            }
        } else {
            echo json_encode(['status' => FALSE, 'message' => 'Boundary not found in Content-Type']);
            return;
        }

        $parts = explode($boundary, $raw_body);

        $upload_dir = './uploads/items/' . $item_id . '/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($parts as $part) {
            if (strpos($part, 'Content-Disposition:') !== false) {
                preg_match('/name="([^"]+)"/', $part, $name_match);
                preg_match('/filename="([^"]+)"/', $part, $filename_match);
                preg_match('/Content-Type: ([\S]+)/', $part, $type_match);

                if (isset($filename_match[1])) {
                    $file_content_start = strpos($part, "\r\n\r\n") + 4;
                    $file_content = substr($part, $file_content_start, -4);

                    $extension = pathinfo($filename_match[1], PATHINFO_EXTENSION);
                    $allowed_types = ['jpeg', 'jpg', 'png'];
                    $file_size = strlen($file_content);
                    if (!in_array(strtolower($extension), $allowed_types)) {
                        echo json_encode(['status' => FALSE, 'message' => 'Tipo de arquivo não permitido.']);
                        return;
                    }

                    $max_file_size = 2 * 1024 * 1024; // 2MB
                    if ($file_size > $max_file_size) {
                        echo json_encode(['status' => FALSE, 'message' => 'O arquivo é muito grande.']);
                        return;
                    }

                    $unique_filename = uniqid() . '.' . $extension;
                    $upload_path = $upload_dir . $unique_filename;

                    if (file_put_contents($upload_path, $file_content)) {
                        $server_url = base_url();
                        $relative_path = str_replace('./', '', $upload_path);
                        $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                        $this->db->where('id', $item_id);
                        $this->db->update(db_prefix() . 'items', ['image' => $full_url]);

                        echo json_encode(['status' => TRUE, 'file' => $full_url]);
                        return;
                    }
                }
            }
        }

        echo json_encode(['status' => FALSE, 'message' => 'Nenhuma parte de arquivo encontrada.']);
    }


    public function upload_mult_put($product_id)
    {

        $raw_body = $this->input->raw_input_stream;

        preg_match('/boundary=(.*)$/', $this->input->request_headers()['Content-Type'], $matches);
        $boundary = '--' . trim($matches[1]);

        $parts = explode($boundary, $raw_body);
        $uploaded_files = [];

        foreach ($parts as $part) {
            if (strpos($part, 'Content-Disposition:') !== false) {
                preg_match('/name="([^"]+)"/', $part, $name_match);
                preg_match('/filename="([^"]+)"/', $part, $filename_match);
                preg_match('/Content-Type: ([\S]+)/', $part, $type_match);

                if (isset($filename_match[1])) {
                    $file_content_start = strpos($part, "\r\n\r\n") + 4;
                    $file_content = substr($part, $file_content_start, -4);

                    $upload_path = './uploads/' . $filename_match[1];

                    file_put_contents($upload_path, $file_content);

                    $uploaded_files[] = $upload_path;
                }
            }
        }

        if (!empty($uploaded_files)) {
            echo json_encode(['status' => TRUE, 'files' => $uploaded_files]);
        } else {
            echo json_encode(['status' => FALSE, 'message' => 'No file parts found.']);
        }
    }

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Product ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $product = $this->Invoice_items_model->get_item($id);

        if ($product) {

            if (!empty($product->group_id)) {
                $this->db->select('name as category_name');
                $this->db->where('id', $product->group_id);
                $category = $this->db->get(db_prefix() . 'items_groups')->row();
                $product->category_name = $category ? $category->category_name : null;
            } else {
                $product->category_name = null;
            }

            // Get subcategory name
            if (!empty($product->sub_group)) {
                $this->db->select('sub_group_name');
                $this->db->where('id', $product->sub_group);
                $subcategory = $this->db->get(db_prefix() . 'wh_sub_group')->row();
                $product->subcategory_name = $subcategory ? $subcategory->sub_group_name : null;
            } else {
                $product->subcategory_name = null;
            }

            $this->response([
                'status' => TRUE,
                'data' => $product
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function get_ecommerce_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Product ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $product = $this->Invoice_items_model->get_item($id);

        if ($product) {

            if (!empty($product->group_id)) {
                $this->db->select('name as category_name');
                $this->db->where('id', $product->group_id);
                $category = $this->db->get(db_prefix() . 'items_groups')->row();
                $product->category_name = $category ? $category->category_name : null;
            } else {
                $product->category_name = null;
            }

            // Get subcategory name
            if (!empty($product->sub_group)) {
                $this->db->select('sub_group_name');
                $this->db->where('id', $product->sub_group);
                $subcategory = $this->db->get(db_prefix() . 'wh_sub_group')->row();
                $product->subcategory_name = $subcategory ? $subcategory->sub_group_name : null;
            } else {
                $product->subcategory_name = null;
            }

            $this->response([
                'status' => TRUE,
                'data' => $product
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function remove_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['rows']) || empty($_POST['rows'])) {
            $message = array('status' => FALSE, 'message' => 'Invalid request: rows array is required');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = $_POST['rows'];
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            $output = $this->Invoice_items_model->delete($id);
            if ($output === TRUE) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        if ($success_count > 0) {
            $message = array(
                'status' => TRUE,
                'message' => $success_count . ' customer(s) deleted successfully'
            );
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to delete customers',
                'failed_ids' => $failed_ids
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function data_put($id = '')
    {


        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $_POST['commodity_barcode'] = $_POST['barcode'];

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Products ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            $this->load->model('Invoice_items_model');
            $output = $this->Invoice_items_model->edit($update_data, $id);
            if ($output > 0 && !empty($output)) {


                log_activity('Produto atualizado com [Name: Teste]', 1);


                $message = array('status' => TRUE, 'message' => 'Products Update Successful.', 'data' => $this->Invoice_items_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Item Atualizado.');
                $this->response($message, REST_Controller::HTTP_OK);
            }
        }
    }

    public function groups_post()
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('g.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'wh_sub_group WHERE group_id = g.id AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as subcategories_count,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = g.id AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as total_products
        ');
        $this->db->from(db_prefix() . 'items_groups g');
        $this->db->where('g.warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('name', $search);
            $this->db->or_like('commodity_group_code', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by('name', $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $groups = $this->db->get()->result_array();

        $this->response([
            'status' => TRUE,
            'total' => $total,
            'data' => $groups
        ], REST_Controller::HTTP_OK);
    }

    public function subgroups_post($group_id)
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        if (empty($group_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Group ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        // First verify if the group belongs to this warehouse
        $this->db->where('id', $group_id);
        $this->db->where('warehouse_id', $warehouse_id);
        $group = $this->db->get(db_prefix() . 'items_groups')->row();

        if (!$group) {
            $this->response([
                'status' => TRUE,
                'total' => 0,
                'data' => []
            ], REST_Controller::HTTP_OK);
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('sg.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = ' . $this->db->escape($group_id) . ' 
            AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as total_products');
        $this->db->from(db_prefix() . 'wh_sub_group sg');
        $this->db->where('sg.group_id', $group_id);
        $this->db->where('sg.warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('sub_group_name', $search);
            $this->db->or_like('sub_group_code', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by('sub_group_name', $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $subgroups = $this->db->get()->result_array();

        $this->response([
            'status' => TRUE,
            'total' => $total,
            'data' => $subgroups
        ], REST_Controller::HTTP_OK);
    }


    function generate_pdf_post()
    {
        try {
            $pdf = generic_pdf(array());
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        $estimate_number = format_estimate_number($estimate->id);
        $companyname = get_option('invoice_company_name');
        if ($companyname != '') {
            $estimate_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
        }

        $filename = hooks()->apply_filters('customers_area_download_estimate_filename', mb_strtoupper(slug_it($estimate_number), 'UTF-8') . '.pdf', $estimate);

        $pdf->Output($filename, 'D');
        die();
    }





    public function del_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['rows']) || empty($_POST['rows'])) {
            $message = array('status' => FALSE, 'message' => 'Invalid request: rows array is required');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = $_POST['rows'];
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            $output = $this->Invoice_items_model->delete_group($id);
            if ($output === TRUE) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        if ($success_count > 0) {
            $message = array(
                'status' => TRUE,
                'message' => $success_count . 'deleted successfully'
            );
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to delete',
                'failed_ids' => $failed_ids
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function groups_put($id = '')
    {


        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Category ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            // update data
            $this->load->model('Invoice_items_model');
            $output = $this->Invoice_items_model->edit_group($update_data, $id);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Category Update Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Category Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function groupcreate_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $_POST['warehouse_id'] = (int) $_POST['warehouse_id'];
            $output = $this->Invoice_items_model->add_group($_POST);
            if ($output > 0 && !empty($output)) {
                $message = array('status' => 'success', 'message' => 'Group added successfully');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Failed to add group.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function subgroupcreate_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->form_validation->set_rules('group_id', 'Group ID', 'trim|required|numeric');
        $this->form_validation->set_rules('sub_group_name', 'Subgroup Name', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verify if the group belongs to this warehouse
        $this->db->where('id', $_POST['group_id']);
        $this->db->where('warehouse_id', $_POST['warehouse_id']);
        $group = $this->db->get(db_prefix() . 'items_groups')->row();

        if (!$group) {
            $this->response([
                'status' => FALSE,
                'message' => 'Group not found in this warehouse'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = array(
            'group_id' => $_POST['group_id'],
            'sub_group_name' => $_POST['sub_group_name'],
            'warehouse_id' => $_POST['warehouse_id'],
            'display' => 1,
            'order' => 0
        );

        $output = $this->Invoice_items_model->add_subgroup($data);
        if ($output > 0 && !empty($output)) {
            $message = array(
                'status' => 'success',
                'message' => 'Subgroup added successfully',
                'id' => $output
            );
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to add subgroup'
            );
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function subgroup_put($id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Subgroup ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }

        $this->form_validation->set_data($_POST);
        $this->form_validation->set_rules('group_id', 'Group ID', 'trim|required|numeric');
        $this->form_validation->set_rules('sub_group_name', 'Subgroup Name', 'trim|required|max_length[100]');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }

        $update_data = array(
            'group_id' => $_POST['group_id'],
            'sub_group_name' => $_POST['sub_group_name']
        );

        if (isset($_POST['display'])) {
            $update_data['display'] = $_POST['display'];
        }
        if (isset($_POST['order'])) {
            $update_data['order'] = $_POST['order'];
        }

        $output = $this->Invoice_items_model->edit_subgroup($update_data, $id);

        if ($output) {
            $message = array(
                'status' => TRUE,
                'message' => 'Subgroup updated successfully'
            );
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to update subgroup'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function subgroup_del_post($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Subgroup ID');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $output = $this->Invoice_items_model->delete_subgroup($id);

        if ($output === TRUE) {
            $message = array(
                'status' => TRUE,
                'message' => 'Subgroup deleted successfully'
            );
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array(
                'status' => FALSE,
                'message' => 'Failed to delete subgroup'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function supplier_needs_post()
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->db->select('
            c.userid as supplier_id,
            c.company as supplier_name,
            c.vat as supplier_document,
            c.phonenumber as supplier_phone,
            c.min_purchase,
            COUNT(DISTINCT pn.id) as total_items,
            SUM(pn.qtde) as total_quantity,
            SUM(pn.qtde * i.cost) as total_cost
        ');

        $this->db->from(db_prefix() . 'items i');
        $this->db->join(db_prefix() . 'purchase_needs pn', 'pn.item_id = i.id');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = pn.user_id');
        $this->db->where('pn.status', 0);
        $this->db->where('c.is_supplier', 1);
        $this->db->where('i.warehouse_id', $warehouse_id);
        $this->db->group_by('c.userid');
        $this->db->having('COUNT(DISTINCT pn.id) > 0');

        $suppliers = $this->db->get()->result_array();

        //  $last_query = $this->db->last_query();

        //    echo $last_query;

        $this->response([
            'status' => TRUE,
            'data' => $suppliers
        ], REST_Controller::HTTP_OK);
    }

    public function check_stock_post()
    {
        $supplier_id = $this->post('supplier_id');
        $warehouse_id = $this->post('warehouse_id');

        if (empty($supplier_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Supplier ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->select('
            pn.id,
            pn.item_id,
            pn.warehouse_id,
            pn.qtde,
            CAST(pn.status AS CHAR) as purchase_status,
            pn.date,
            CAST(pn.user_id AS CHAR) as user_id,
            CAST(pn.invoice_id AS CHAR) as invoice_id,
            i.description as product_name,
            i.sku_code,
            i.stock,
            i.cost,
            i.defaultPurchaseQuantity,
            i.minStock,
            c.company as supplier_name,
            c.vat as supplier_document
        ');

        $this->db->from(db_prefix() . 'purchase_needs pn');
        $this->db->join(db_prefix() . 'items i', 'i.id = pn.item_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = pn.user_id', 'left');
        $this->db->where('pn.status', 0);
        $this->db->where('pn.user_id', $supplier_id);
        $this->db->where('i.warehouse_id', $warehouse_id);
        //  $this->db->where('c.is_supplier', 1);



        $products = $this->db->get()->result_array();

        // $last_query = $this->db->last_query();

        //  echo $last_query;

        $total_cost = 0;
        $purchase_needs = [];

        foreach ($products as $product) {
            $item_total = $product['qtde'] * $product['cost'];
            $total_cost += $item_total;

            $purchase_needs[] = [
                'id' => $product['id'],
                'product' => [
                    'id' => $product['item_id'],
                    'name' => $product['product_name'],
                    'sku' => $product['sku_code']
                ],
                'warehouse_id' => $product['warehouse_id'],
                'currentStock' => (int) $product['stock'],
                'minimumStock' => (int) $product['minStock'],
                'quantity' => (int) $product['qtde'],
                'cost' => (float) $product['cost'],
                'total' => $item_total,
                'defaultPurchaseQuantity' => (int) $product['defaultPurchaseQuantity'],
                'status' => $product['purchase_status'],
                'user_id' => $product['user_id'],
                'date' => $product['date'],
                'invoice_id' => $product['invoice_id']
            ];
        }

        $supplier_info = [
            'supplier_id' => $supplier_id,
            'supplier_name' => empty($products) ? '' : $products[0]['supplier_name'],
            'supplier_document' => empty($products) ? '' : $products[0]['supplier_document'],
            'total_items' => count($products),
            'total_quantity' => array_sum(array_column($products, 'qtde')),
            'total_cost' => $total_cost,
            'items' => $purchase_needs
        ];

        $this->response([
            'status' => TRUE,
            'data' => $supplier_info
        ], REST_Controller::HTTP_OK);
    }

    public function import_post()
    {
        if (empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_FILES['file']) || empty($_FILES['file'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No file was uploaded'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['mapping'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No field mapping provided'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $file = $_FILES['file'];
        $mapping = json_decode($_POST['mapping'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid mapping JSON format'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $mapping = array_filter($mapping, function ($value) {
            return !empty($value);
        });

        if (!isset($mapping['description']) || !isset($mapping['rate'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'description and rate fields are required in the mapping'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            $this->response([
                'status' => FALSE,
                'message' => 'Only CSV files are allowed'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to read file'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $headers = fgetcsv($handle);
        if ($headers === FALSE) {
            fclose($handle);
            $this->response([
                'status' => FALSE,
                'message' => 'Empty CSV file'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $success_count = 0;
        $errors = [];
        $rows = [];

        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count(array_filter($row)) > 0) {
                $rows[] = $row;
            }
        }

        try {
            $this->db->trans_start();

            foreach ($rows as $index => $row) {
                $current_row = $index + 2;
                $product_data = ['warehouse_id' => $_POST['warehouse_id']];

                foreach ($mapping as $field => $csv_column) {
                    $column_index = array_search($csv_column, $headers);
                    if ($column_index !== FALSE && isset($row[$column_index])) {
                        $value = trim($row[$column_index]);
                        if (!empty($value)) {
                            $product_data[$field] = $value;
                        }
                    }
                }

                if (!isset($product_data['description']) || empty($product_data['description'])) {
                    $errors[] = "Row {$current_row}: Description is required";
                    continue;
                }

                if (!isset($product_data['rate']) || floatval($product_data['rate']) <= 0) {
                    $errors[] = "Row {$current_row}: Rate must be greater than zero";
                    continue;
                }

                $product_id = $this->Invoice_items_model->add($product_data);
                if ($product_id) {
                    $success_count++;
                } else {
                    $errors[] = "Row {$current_row}: Failed to insert product";
                }
            }

            $this->db->trans_complete();

            fclose($handle);

            if ($this->db->trans_status() === FALSE) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Transaction failed',
                    'errors' => $errors
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }

            $response = [
                'status' => TRUE,
                'message' => "{$success_count} products imported successfully",
                'total_rows' => count($rows)
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            $this->response($response, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            $this->response([
                'status' => FALSE,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => $errors
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

    }


    public function units_post()
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'order';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('
            unit_type_id,
            unit_code,
            unit_name,
            unit_symbol,
            `order`,
            display,
            note
        ');
        $this->db->from(db_prefix() . 'ware_unit_type');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('unit_code', $search);
            $this->db->or_like('unit_name', $search);
            $this->db->or_like('unit_symbol', $search);
            $this->db->or_like('note', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $units = $this->db->get()->result_array();

        $units = array_map(function ($unit) {
            $unit['display'] = (bool) $unit['display'];
            return $unit;
        }, $units);

        $this->response([
            'status' => TRUE,
            'total' => $total,
            'data' => $units
        ], REST_Controller::HTTP_OK);
    }
    public function category_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Category ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $this->get('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->select('g.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'wh_sub_group WHERE group_id = g.id AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as subcategories_count,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = g.id AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as total_products
        ');
        $this->db->from(db_prefix() . 'items_groups g');
        $this->db->where('g.id', $id);
        $this->db->where('g.warehouse_id', $warehouse_id);

        $category = $this->db->get()->row_array();

        if ($category) {
            $this->response([
                'status' => TRUE,
                'data' => $category
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Category not found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function category_subcategories_get($category_id = '')
    {
        if (empty($category_id) || !is_numeric($category_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Category ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $this->get('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->where('id', $category_id);
        $this->db->where('warehouse_id', $warehouse_id);
        $category = $this->db->get(db_prefix() . 'items_groups')->row();

        if (!$category) {
            $this->response([
                'status' => FALSE,
                'message' => 'Category not found'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Get subcategories
        $this->db->select('sg.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items 
            WHERE group_id = ' . $this->db->escape($category_id) . ' 
            AND warehouse_id = ' . $this->db->escape($warehouse_id) . '
            AND sub_group = sg.id) as products_count'
        );
        $this->db->from(db_prefix() . 'wh_sub_group sg');
        $this->db->where('sg.group_id', $category_id);
        $this->db->where('sg.warehouse_id', $warehouse_id);
        $this->db->order_by('sg.sub_group_name', 'ASC');

        $subcategories = $this->db->get()->result_array();

        $this->response([
            'status' => TRUE,
            'data' => [
                'category' => $category,
                'subcategories' => $subcategories
            ]
        ], REST_Controller::HTTP_OK);
    }

}
