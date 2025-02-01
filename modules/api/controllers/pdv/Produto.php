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
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $status = $this->post('status');

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
            $end_date
        );
        
        
       

        if ($data['total'] == 0) {
            $this->response(
                ['status' => FALSE, 'message' => 'No data were found'],
                REST_Controller::HTTP_NOT_FOUND
            );
        } else {
            if ($data) {
                $this->response(
                    [
                        'status' => true,
                        'total' => $data['total'],
                        'data' => $data['data']
                    ],
                    REST_Controller::HTTP_OK
                );
            } else {
                $this->response(
                    ['status' => FALSE, 'message' => 'No data were found'],
                    REST_Controller::HTTP_NOT_FOUND
                );
            }
        }
    }

    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $product_data = [
            'description' => $_POST['description'] ?? null,
            'long_description' => $_POST['long_description'] ?? null,
            'rate' => $_POST['rate'] ?? 0.00,
            'tax' => $_POST['taxid'] ?? null,
            'tax2' => $_POST['taxid_2'] ?? null,
            'unit' => $_POST['unit'] ?? null,
            'group_id' => $_POST['group_id'] ?? 0,
            'sku_code' => $_POST['sku_code'] ?? null,
            'barcode' => $_POST['barcode'] ?? null,
            'status' => $_POST['status'] ?? 'pending',
            'cost' => $_POST['cost'] ?? null,
            'promoPrice' => $_POST['promoPrice'] ?? null,
            'promoStart' => $_POST['promoStart'] ?? null,
            'promoEnd' => $_POST['promoEnd'] ?? null,
            'stock' => $_POST['stock'] ?? 0,
            'minStock' => $_POST['minStock'] ?? 0,
            'product_unit' => $_POST['product_unit'] ?? null,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $this->form_validation->set_data($product_data);
        $this->form_validation->set_rules('description', 'Description', 'trim|required|max_length[600]');
        $this->form_validation->set_rules('rate', 'Rate', 'numeric');
        $this->form_validation->set_rules('stock', 'Stock', 'numeric');
        $this->form_validation->set_rules('minStock', 'Minimum Stock', 'numeric');

        if ($this->form_validation->run() == FALSE) {
            $message = [
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            ];
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $product_id = $this->Invoice_items_model->add($product_data);

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

        preg_match('/boundary=(.*)$/', $this->input->request_headers()['Content-Type'], $matches);
        $boundary = '--' . trim($matches[1]);

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

                    $upload_path = $upload_dir . basename($filename_match[1]);

                    file_put_contents($upload_path, $file_content);

                    $server_url = base_url();
                    $relative_path = str_replace('./', '', $upload_path);
                    $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                    echo json_encode(['status' => TRUE, 'file' => $full_url]);
                    return;
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

        error_reporting(-1);
        ini_set('display_errors', 1);


        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Customers ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            $this->load->model('Invoice_items_model');
            $output = $this->Invoice_items_model->edit($update_data, $id);
            if ($output > 0 && !empty($output)) {
                $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data' => $this->Invoice_items_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function groups_post()
    {
        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('g.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'wh_sub_group WHERE group_id = g.id) as subcategories_count,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = g.id) as total_products
        ');
        $this->db->from(db_prefix() . 'items_groups g');

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

        if ($total > 0) {
            $this->response([
                'status' => TRUE,
                'total' => $total,
                'data' => $groups
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No groups were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function subgroups_post($group_id)
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('*');
        $this->db->from(db_prefix() . 'wh_sub_group');
        $this->db->where('group_id', $group_id);

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

        if ($total > 0) {
            $this->response([
                'status' => TRUE,
                'total' => $total,
                'data' => $subgroups
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No subgroups were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
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
        $companyname     = get_option('invoice_company_name');
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

        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[100]');

        if ($this->form_validation->run() == FALSE) {
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $output = $this->Invoice_items_model->add_group($_POST);
            if ($output > 0 && !empty($output)) {
                $message = array('status' => 'success', 'message' => 'Group add sucess');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Group add fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function subgroupcreate_post()
    {
        
        ini_set('display_errors',1);
                ini_set('display_startup_erros',1);
                error_reporting(E_ALL);
        
        
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $this->form_validation->set_rules('group_id', 'Group ID', 'trim|required|numeric');
        $this->form_validation->set_rules('sub_group_name', 'Subgroup Name', 'trim|required|max_length[100]');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $data = array(
                'group_id' => $_POST['group_id'],
                'sub_group_name' => $_POST['sub_group_name'],
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
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
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

    public function check_stock_post()
    {
        $this->db->select('
            id,
            description as name,
            sku_code as sku,
            stock as currentStock,
            minStock as minimumStock,
            unit as product_unit,
            status,
            updatedAt as lastOrderDate
        ');
        $this->db->from(db_prefix() . 'items');
        $this->db->where('stock <= minStock');

        $products = $this->db->get()->result_array();

        $purchase_needs = [];

        foreach ($products as $product) {
            $suggested_qty = (int)($product['minimumStock'] - $product['currentStock'] + ($product['minimumStock'] * 0.2));

            $status = 'pending';
            if ($product['status'] === 'ordered') {
                $status = 'ordered';
            } else if ($product['status'] === 'cancelled') {
                $status = 'cancelled';
            }

            $purchase_needs[] = [
                'product' => [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'sku' => $product['sku']
                ],
                'currentStock' => (int)$product['currentStock'],
                'minimumStock' => (int)$product['minimumStock'],
                'suggestedOrderQuantity' => $suggested_qty,
                'lastOrderDate' => $product['lastOrderDate'],
                'status' => $status
            ];
        }

        if (empty($purchase_needs)) {
            $this->response([
                'status' => TRUE,
                'data' => []
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => TRUE,
                'data' => $purchase_needs
            ], REST_Controller::HTTP_OK);
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

            foreach ($_POST['items'] as $item) {
                $this->db->where('itemid', $item['product']['id']);
                $this->db->update(db_prefix() . 'items', ['status' => 'ordered']);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Failed to create purchase order'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }

            $this->response([
                'status' => TRUE,
                'message' => 'Purchase order created successfully'
            ], REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            $this->response([
                'status' => FALSE,
                'message' => 'Error creating purchase order: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function import_post()
    {
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

        $mapping = array_filter($mapping, function($value) {
            return !empty($value);
        });

        $field_translations = [
            'sku' => 'sku_code',
            'name' => 'description',
            'price' => 'rate',
            'quantity' => 'stock'
        ];

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
        $row_number = 1;

        try {
            $this->db->trans_start();

            while (($row = fgetcsv($handle)) !== FALSE) {
                $row_number++;
                $product_data = [
                    'createdAt' => date('Y-m-d H:i:s'),
                    'updatedAt' => date('Y-m-d H:i:s')
                ];

                foreach ($mapping as $csv_field => $csv_column) {
                    $column_index = array_search($csv_column, $headers);
                    if ($column_index !== FALSE && isset($row[$column_index])) {
                        $db_field = isset($field_translations[$csv_field]) ? $field_translations[$csv_field] : $csv_field;
                        $product_data[$db_field] = $row[$column_index];
                    }
                }

                if (empty($product_data['description'])) {
                    $errors[] = "Row {$row_number}: Product name/description is required";
                    continue;
                }

                if (isset($product_data['rate'])) {
                    $product_data['rate'] = floatval(str_replace(['R$', ' ', ','], ['', '', '.'], $product_data['rate']));
                }
                if (isset($product_data['stock'])) {
                    $product_data['stock'] = intval($product_data['stock']);
                }

                $product_data['status'] = $product_data['status'] ?? 'pending';
                $product_data['group_id'] = $product_data['group_id'] ?? 0;

                $product_id = $this->Invoice_items_model->add($product_data);
                if ($product_id) {
                    $success_count++;
                } else {
                    $errors[] = "Row {$row_number}: Failed to insert product";
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
                'message' => "{$success_count} products imported successfully"
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
}

