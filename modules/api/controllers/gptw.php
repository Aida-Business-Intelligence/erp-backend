<?php

defined('BASEPATH') or exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/REST_Controller.php';

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
class Gptw extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
                $this->load->model('Gptw_model');

    }


    public function data_get($id = ''){
        $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
        $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->get('sortField') ?: 'id'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
           
        $type = $this->get('type');
        
        if ($type == 'search'){
            $data = $this->Gptw_model->get_api_search($id, $page, $limit, $search, $sortField, $sortOrder);
        }
        
        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function data_post()
    {



        \modules\api\core\Apiinit::the_da_vinci_code('api');
        // Recebendo e decodificando os dados
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Verificando se os dados são um único objeto
        if (is_array($_POST) && count($_POST) === 0) {
            // Caso de um array vazio
            echo "O array está vazio.";
            exit;
        } elseif (is_array($_POST) && isset($_POST[0]) && is_array($_POST[0])) {
            // Se for um array de objetos
            foreach ($_POST as $representante) {
                $output = $this->Gptw_model->add($representante);
            }

            $message = array('status' => TRUE, 'message' => 'Import add successful.', 'data' => []);
            $this->response($message, REST_Controller::HTTP_OK);
        } else {


            /*
              if (is_array($insert_data)) {
              // Se for um array e conter mais de um objeto
              foreach ($insert_data as $representante) {
              // Processar cada representante para cadastro em massa
              echo "Cadastro em massa para o representante: ";
              print_r($representante);
              }
              }
             * 
             */


            // form validation
            $this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
            if ($this->form_validation->run() == FALSE) {
                // form validation error
                $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            } else {
                $groups_in = $this->Api_model->value($this->input->post('groups_in', TRUE));

                /*
                  $insert_data = ['company' => $this->input->post('company', TRUE), 'vat' => $this->Api_model->value($this->input->post('vat', TRUE)), 'phonenumber' => $this->Api_model->value($this->input->post('phonenumber', TRUE)), 'website' => $this->Api_model->value($this->input->post('website', TRUE)), 'default_currency' => $this->Api_model->value($this->input->post('default_currency', TRUE)), 'default_language' => $this->Api_model->value($this->input->post('default_language', TRUE)), 'address' => $this->Api_model->value($this->input->post('address', TRUE)), 'city' => $this->Api_model->value($this->input->post('city', TRUE)), 'state' => $this->Api_model->value($this->input->post('state', TRUE)), 'zip' => $this->Api_model->value($this->input->post('zip', TRUE)), 'country' => $this->Api_model->value($this->input->post('country', TRUE)), 'billing_street' => $this->Api_model->value($this->input->post('billing_street', TRUE)), 'billing_city' => $this->Api_model->value($this->input->post('billing_city', TRUE)), 'billing_state' => $this->Api_model->value($this->input->post('billing_state', TRUE)), 'billing_zip' => $this->Api_model->value($this->input->post('billing_zip', TRUE)), 'billing_country' => $this->Api_model->value($this->input->post('billing_country', TRUE)), 'shipping_street' => $this->Api_model->value($this->input->post('shipping_street', TRUE)), 'shipping_city' => $this->Api_model->value($this->input->post('shipping_city', TRUE)), 'shipping_state' => $this->Api_model->value($this->input->post('shipping_state', TRUE)), 'shipping_zip' => $this->Api_model->value($this->input->post('shipping_zip', TRUE)), 'shipping_country' => $this->Api_model->value($this->input->post('shipping_country', TRUE))];
                  if (!empty($this->input->post('custom_fields', TRUE))) {
                  $insert_data['custom_fields'] = $this->Api_model->value($this->input->post('custom_fields', TRUE));
                  }
                  if ($groups_in != '') {
                  $insert_data['groups_in'] = $groups_in;
                  }
                 * 
                 */
                // insert data




                $output = $this->Gptw_model->add($_POST);
                if ($output > 0 && !empty($output)) {
                    // success
                    $message = array('status' => TRUE, 'message' => 'Client add successful.', 'data' => $this->Gptw_model->get($output));
                    $this->response($message, REST_Controller::HTTP_OK);
                } else {
                    // error
                    $message = array('status' => FALSE, 'message' => 'Client add fail.');
                    $this->response($message, REST_Controller::HTTP_NOT_FOUND);
                }
            }
        }
    }

    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Customer ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $this->load->model('Gptw_model');
            $output = $this->Gptw_model->delete($id);
            if ($output === TRUE) {
                // success
                $message = array('status' => TRUE, 'message' => 'Customer Delete Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Customer Delete Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
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
            $this->load->model('Gptw_model');
            $output = $this->Gptw_model->update($update_data, $id);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data' => $this->Gptw_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

}
