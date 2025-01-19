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

        // echo $this->db->last_query();
        // exit;

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
        // Recebendo e decodificando os dados
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $_input['vat'] = $_POST['documentNumber'] ?? null;
        $_input['email_default'] = $_POST['email'] ?? null;
        $_input['phonenumber'] = $_POST['primaryPhone'] ?? null;
        $_input['zip'] = $_POST['cep'] ?? null;
        $_input['billing_street'] = $_POST['street'] ?? null;
        $_input['billing_city'] = $_POST['city'] ?? null;
        $_input['billing_state'] = $_POST['state'] ?? null;
        $_input['billing_number'] = $_POST['number'] ?? null;
        $_input['billing_complement'] = $_POST['complement'] ?? null;
        $_input['billing_neighborhood'] = $_POST['neighborhood'] ?? null;
        $_input['company'] = $_POST['fullName'] ?? null;
        $_POST['company'] = $_POST['fullName'] ?? null;

        $this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]');

        // email
        $this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[100]', array('is_unique' => 'This %s already exists please enter another email'));

        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {


            $output = $this->Invoice_items_model->add($_input);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => 'success', 'message' => 'auth_signup_success', 'data' => $this->Invoice_items_model->get($output));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Client add fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
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

        // echo $this->db->last_query();
        // exit;

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
            // update data
            $this->load->model('Invoice_items_model');
            $output = $this->Invoice_items_model->edit($update_data, $id);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data' => $this->Invoice_items_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
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

        // Get groups with counts
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

        error_reporting(-1);
        ini_set('display_errors', 1);


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
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Set validation rules
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
            // Prepare data for insertion
            $data = array(
                'group_id' => $_POST['group_id'],
                'sub_group_name' => $_POST['sub_group_name'],
                'display' => 1, // Default to displayed
                'order' => 0    // Default order
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

        // Set validation rules
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

        // Optional fields
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
}
