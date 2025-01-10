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

        // $data = [
        //     "sum" => 4,
        //     "data" => [
        //         [
        //             "id" => "4a28cf4c-8737-40aa-8bef-42816f600319",
        //             "productName" => "Mochila Escolar",
        //             "image" => "https://tse4.mm.bing.net/th?id=OIP.mmfw6ve4N9t6F1tFS4pS2QHaJc&pid=Api",
        //             "sku" => "BAG12345",
        //             "barcode" => "7891234567892",
        //             "category" => "Acessórios",
        //             "brand" => "Genérica",
        //             "description" => "Mochila escolar resistente com compartimentos para laptop e outros itens.",
        //             "unit" => "unidade",
        //             "price" => 89.99,
        //             "cost" => 50,
        //             "promoPrice" => 79.99,
        //             "promoStart" => "2024-12-01T00:00:00.000Z",
        //             "promoEnd" => "2024-12-31T23:59:59.000Z",
        //             "stock" => 100,
        //             "minStock" => 20,
        //             "active" => true,
        //             "variations" => '[{"sku":"BAG12345-BLUE","price":89.99,"cost":50,"stock":50},{"sku":"BAG12345-RED","price":89.99,"cost":50,"stock":50}]',
        //             "createdAt" => "2024-12-20T14:00:00.000Z",
        //             "updatedAt" => "2024-12-20T14:00:00.000Z"
        //         ],
        //         [
        //             "id" => "1785d627-4bb1-4a03-8c1f-7c9b617f4d2d",
        //             "productName" => "Relógio Smartwatch",
        //             "image" => "https://dcdn.mitiendanube.com/stores/002/578/628/products/eadba2c14d7ea47ccb1018b89f27a31bawsaccesskeyidakiatclmsgfx4j7tu445expires1692742014signaturerdlpzol2fyhojccxget5athm2bec3d-cbab12e3ce3d2b306d16901500672419-1024-1024.jpg",
        //             "sku" => "WATCH12345",
        //             "barcode" => "7891234567894",
        //             "category" => "Eletrônicos",
        //             "brand" => "Xiaomi",
        //             "description" => "Relógio inteligente com monitoramento de saúde, notificações e bateria de longa duração.",
        //             "unit" => "unidade",
        //             "price" => 349.99,
        //             "cost" => 200,
        //             "promoPrice" => 299.99,
        //             "promoStart" => "2024-12-01T00:00:00.000Z",
        //             "promoEnd" => "2024-12-31T23:59:59.000Z",
        //             "stock" => 50,
        //             "minStock" => 10,
        //             "active" => true,
        //             "variations" => '[{"sku":"WATCH12345-BLACK","price":349.99,"cost":200,"stock":30},{"sku":"WATCH12345-WHITE","price":349.99,"cost":200,"stock":20}]',
        //             "createdAt" => "2024-12-20T14:00:00.000Z",
        //             "updatedAt" => "2024-12-20T14:00:00.000Z"
        //         ],
        //         [
        //             "id" => "fd0d2f12-769c-4a82-b554-80e92bfa5283",
        //             "productName" => "Smartphone Samsung",
        //             "image" => "https://tse4.mm.bing.net/th?id=OIP.hlaa3ABICuuMTIAQdP0ntAHaHa&pid=Api",
        //             "sku" => "SAM12345",
        //           *  "barcode" => "7891234567890",
        //           *  "category" => "Eletrônicos",
        //           *  "brand" => "Samsung",
        //             "description" => "Smartphone Samsung Galaxy com tela AMOLED, câmera tripla e 128GB de armazenamento.",
        //           *  "unit" => "unidade",
        //             "price" => 1299.99,
        //            * "cost" => 1000,
        //            * "promoPrice" => 1199,
        //            * "promoPrice" => 1199.99,
        //            * "promoStart" => "2024-12-01T00:00:00.000Z",
        //            * "promoEnd" => "2024-12-31T23:59:59.000Z",
        //           *  "stock" => 50,
        //           *  "minStock" => 10,
        //           *  "active" => true,
        //             "variations" => '[{"sku":"SAM12345-BLACK","price":1299.99,"cost":1000,"stock":20},{"sku":"SAM12345-WHITE","price":1299.99,"cost":1000,"stock":30}]',
        //           *  "createdAt" => "2024-12-20T14:00:00.000Z",
        //           * "updatedAt" => "2024-12-20T14:00:00.000Z"
        //         ],
        //         [
        //             "id" => "b2ac96fd-5b93-48d0-832c-cf812ee65542",
        //             "productName" => "Notebook Dell",
        //             "image" => "https://tse3.mm.bing.net/th?id=OIP.yBoZgRb7vhXPhv8qLY8JLAHaFj&pid=Api",
        //             "sku" => "DELL12345",
        //             "barcode" => "7891234567891",
        //             "category" => "Computadores",
        //             "brand" => "Dell",
        //             "description" => "Notebook Dell com processador Intel Core i5, 8GB RAM e 256GB SSD.",
        //             "unit" => "unidade",
        //             "price" => 2899.99,
        //             "cost" => 2500,
        //             "promoPrice" => 2699.99,
        //             "promoStart" => "2024-12-01T00:00:00.000Z",
        //             "promoEnd" => "2024-12-31T23:59:59.000Z",
        //             "stock" => 30,
        //             "minStock" => 5,
        //             "active" => true,
        //             "variations" => '[{"sku":"DELL12345-SILVER","price":2899.99,"cost":2500,"stock":10},{"sku":"DELL12345-BLACK","price":2899.99,"cost":2500,"stock":20}]',
        //             "createdAt" => "2024-12-20T14:00:00.000Z",
        //             "updatedAt" => "2024-12-20T14:00:00.000Z"
        //         ]
        //     ]
        // ];
        // $this->response($data, REST_Controller::HTTP_OK);
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

        $data = $this->Invoice_items_model->get_api(
            $id,
            $page,
            $limit,
            $search,
            $sortField,
            $sortOrder,
            $statusFilter
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

        $product = $this->Invoice_items_model->get($id);

        echo $this->db->last_query();
        exit;

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
            $output = $this->Invoice_items_model->update($update_data, $id);
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
}
