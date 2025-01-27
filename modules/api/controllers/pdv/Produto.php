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
    
   public function upload_put($item_id) {
    // Captura o conteúdo da requisição bruta
    $raw_body = $this->input->raw_input_stream;

    // Defina o boundary (o valor do 'boundary' deve estar presente nos cabeçalhos da requisição)
    preg_match('/boundary=(.*)$/', $this->input->request_headers()['Content-Type'], $matches);
    $boundary = '--' . trim($matches[1]);

    // Divide a contagem com base no boundary
    $parts = explode($boundary, $raw_body);

    // Define o diretório de upload
    $upload_dir = './uploads/items/' . $item_id . '/';

    // Cria o diretório se não existir
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true); // Criação recursiva do diretório
    }

    foreach ($parts as $part) {
        // Verifique se a parte contém dados de arquivo
        if (strpos($part, 'Content-Disposition:') !== false) {
            // Extraia o nome do arquivo e seu conteúdo
            preg_match('/name="([^"]+)"/', $part, $name_match);
            preg_match('/filename="([^"]+)"/', $part, $filename_match);
            preg_match('/Content-Type: ([\S]+)/', $part, $type_match);
            
            if (isset($filename_match[1])) {
                $file_content_start = strpos($part, "\r\n\r\n") + 4; // Ignora os cabeçalhos
                $file_content = substr($part, $file_content_start, -4); // Remove os delimitadores no final

                // Validação do tipo de arquivo
                $extension = pathinfo($filename_match[1], PATHINFO_EXTENSION);
                $allowed_types = ['jpeg', 'jpg', 'png'];
                $file_size = strlen($file_content); // Tamanho do arquivo em bytes

                if (!in_array(strtolower($extension), $allowed_types)) {
                    echo json_encode(['status' => FALSE, 'message' => 'Tipo de arquivo não permitido.']);
                    return;
                }

                // Limitar o tamanho do arquivo (por exemplo: 2MB)
                $max_file_size = 2 * 1024 * 1024; // 2MB
                if ($file_size > $max_file_size) {
                    echo json_encode(['status' => FALSE, 'message' => 'O arquivo é muito grande.']);
                    return;
                }

                $upload_path = $upload_dir . basename($filename_match[1]);

                // Salva o arquivo
                file_put_contents($upload_path, $file_content);

                echo json_encode(['status' => TRUE, 'file' => $upload_path]);
                return; // Para evitar que outros arquivos sejam processados
            }
        }
    }
    
    echo json_encode(['status' => FALSE, 'message' => 'Nenhuma parte de arquivo encontrada.']);
}
    
    
     
    
    
    public function upload_mult_put($product_id) {
        
   
    // Captura o conteúdo da requisição bruta
    $raw_body = $this->input->raw_input_stream;

    // Define o boundary (o valor do 'boundary' deve estar presente nos cabeçalhos da requisição)
    preg_match('/boundary=(.*)$/', $this->input->request_headers()['Content-Type'], $matches);
    $boundary = '--' . trim($matches[1]);

    // Divide o conteúdo com base no boundary
    $parts = explode($boundary, $raw_body);
    $uploaded_files = [];

    foreach ($parts as $part) {
        // Procura por cabeçalho de Content-Disposition
        if (strpos($part, 'Content-Disposition:') !== false) {
            // Extrai o nome do arquivo, seu conteúdo e outros detalhes
            preg_match('/name="([^"]+)"/', $part, $name_match);
            preg_match('/filename="([^"]+)"/', $part, $filename_match);
            preg_match('/Content-Type: ([\S]+)/', $part, $type_match);
            
            if (isset($filename_match[1])) {
                $file_content_start = strpos($part, "\r\n\r\n") + 4; // Ignora os cabeçalhos
                $file_content = substr($part, $file_content_start, -4); // Remove os delimitadores no final

                // Diretório de upload e salva o arquivo
                $upload_path = './uploads/' . $filename_match[1];
                
                file_put_contents($upload_path, $file_content);

                // Armazena o caminho do arquivo enviado
                $uploaded_files[] = $upload_path;
            }
        }
    }

    // Resposta com arquivos carregados ou mensagem de erro
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

    
    function generate_pdf_post() {
        
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
