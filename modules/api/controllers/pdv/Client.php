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
class Client extends REST_Controller
{

  function __construct()
  {
    // Construct the parent class
    parent::__construct();
    $this->load->model('Clients_model');
  }

  /**
   * @api {get} api/client/:id Request customer information
   * @apiName GetCustomer
   * @apiGroup Customer
   *
   * @apiHeader {String} Authorization Basic Access Authentication token.
   *
   * @apiParam {Number} id customer unique ID.
   *
   * @apiSuccess {Object} customer information.
   *
   * @apiSuccessExample Success-Response:
   *     HTTP/1.1 200 OK
   *     {
   *          "id": "28",
   *          "name": "Test1",
   *          "description": null,
   *          "status": "1",
   *          "clientid": "11",
   *          "billing_type": "3",
   *          "start_date": "2019-04-19",
   *          "deadline": "2019-08-30",
   *          "customer_created": "2019-07-16",
   *          "date_finished": null,
   *          "progress": "0",
   *          "progress_from_tasks": "1",
   *          "customer_cost": "0.00",
   *          "customer_rate_per_hour": "0.00",
   *          "estimated_hours": "0.00",
   *          "addedfrom": "5",
   *          "rel_type": "customer",
   *          "potential_revenue": "0.00",
   *          "potential_margin": "0.00",
   *          "external": "E",
   *         ...
   *     }
   *
   * @apiError {Boolean} status Request status.
   * @apiError {String} message No data were found.
   *
   * @apiErrorExample Error-Response:
   *     HTTP/1.1 404 Not Found
   *     {
   *       "status": false,
   *       "message": "No data were found"
   *     }
   */
  public function list_post($id = '')
  {


    $page = $this->post('page') ? (int) $this->post('page') : 0;
    $page = $page + 1;

    $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
    $search = $this->post('search') ?: ''; // Alterado para this->post
    $sortField = $this->post('sortField') ?: 'userid'; // Alterado para this->post
    $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC'; // Alterado para this->post
    $warehouse_id = $this->post('warehouse_id') ?: 0;
    $type= $this->post('type') ?: null;
   
    $data = $this->Clients_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, array(), '', '', $warehouse_id, 0, $type);

    if ($data['total'] == 0) {

      $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
    } else {

      if ($data) {
        $this->response(['status' => true, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
      } else {
        $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
      }
    }
  }


  /**
   * @api {post} api/customers Add New Customer
   * @apiName PostCustomer
   * @apiGroup Customer
   *
   * @apiHeader {String} Authorization Basic Access Authentication token.
   *
   * @apiParam {String} company               Mandatory Customer company.
   * @apiParam {String} [vat]                 Optional Vat.
   * @apiParam {String} [phonenumber]         Optional Customer Phone.
   * @apiParam {String} [website]             Optional Customer Website.
   * @apiParam {Number[]} [groups_in]         Optional Customer groups.
   * @apiParam {String} [default_language]    Optional Customer Default Language.
   * @apiParam {String} [default_currency]    Optional default currency.
   * @apiParam {String} [address]             Optional Customer address.
   * @apiParam {String} [city]                Optional Customer City.
   * @apiParam {String} [state]               Optional Customer state.
   * @apiParam {String} [zip]                 Optional Zip Code.
   * @apiParam {String} [partnership_type]    Optional Customer partnership type.
   * @apiParam {String} [country]             Optional country.
   * @apiParam {String} [billing_street]      Optional Billing Address: Street.
   * @apiParam {String} [billing_city]        Optional Billing Address: City.
   * @apiParam {Number} [billing_state]       Optional Billing Address: State.
   * @apiParam {String} [billing_zip]         Optional Billing Address: Zip.
   * @apiParam {String} [billing_country]     Optional Billing Address: Country.
   * @apiParam {String} [shipping_street]     Optional Shipping Address: Street.
   * @apiParam {String} [shipping_city]       Optional Shipping Address: City.
   * @apiParam {String} [shipping_state]      Optional Shipping Address: State.
   * @apiParam {String} [shipping_zip]        Optional Shipping Address: Zip.
   * @apiParam {String} [shipping_country]    Optional Shipping Address: Country.
   *
   * @apiParamExample {Multipart Form} Request-Example:
   *   array (size=22)
   *     'company' => string 'Themesic Interactive' (length=38)
   *     'vat' => string '123456789' (length=9)
   *     'phonenumber' => string '123456789' (length=9)
   *     'website' => string 'AAA.com' (length=7)
   *     'groups_in' =>
   *       array (size=2)
   *         0 => string '1' (length=1)
   *         1 => string '4' (length=1)
   *     'default_currency' => string '3' (length=1)
   *     'default_language' => string 'english' (length=7)
   *     'address' => string '1a The Alexander Suite Silk Point' (length=27)
   *     'city' => string 'London' (length=14)
   *     'state' => string 'London' (length=14)
   *     'zip' => string '700000' (length=6)
   *     'country' => string '243' (length=3)
   *     'billing_street' => string '1a The Alexander Suite Silk Point' (length=27)
   *     'billing_city' => string 'London' (length=14)
   *     'billing_state' => string 'London' (length=14)
   *     'billing_zip' => string '700000' (length=6)
   *     'billing_country' => string '243' (length=3)
   *     'shipping_street' => string '1a The Alexander Suite Silk Point' (length=27)
   *     'shipping_city' => string 'London' (length=14)
   *     'shipping_state' => string 'London' (length=14)
   *     'shipping_zip' => string '700000' (length=6)
   *     'shipping_country' => string '243' (length=3)
   *
   *
   * @apiSuccess {Boolean} status Request status.
   * @apiSuccess {String} message Customer add successful.
   *
   * @apiSuccessExample Success-Response:
   *     HTTP/1.1 200 OK
   *     {
   *       "status": true,
   *       "message": "Customer add successful."
   *     }
   *
   * @apiError {Boolean} status Request status.
   * @apiError {String} message Customer add fail.
   *
   * @apiErrorExample Error-Response:
   *     HTTP/1.1 404 Not Found
   *     {
   *       "status": false,
   *       "message": "Customer add fail."
   *     }
   *
   */


  public function create_post()
  {
    // Recebendo e decodificando os dados
    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    // Adiciona o warehouse_id ao array de entrada, se presente
    $_input['warehouse_id'] = $_POST['warehouse_id'] ?? null;

    // Outros campos do cliente
    $_input['vat'] = $_POST['vat'] ?? $_POST['documentNumber'];
    $_input['is_supplier'] = 0;
    $_input['phonenumber'] = $_POST['phonenumber'] ?? $_POST['primaryPhone'];
    $_input['secondaryPhone'] = $_POST['secondaryPhone'] ?? null;
    $_input['documentType'] = $_POST['documentType'] ?? null;
    $_input['person_type'] = $_POST['documentType'] == "CPF" ? "F" :"J";
    $_input['email_default'] = $_POST['email_default'] ?? $_POST['email'];
    $_input['gender'] = $_POST['gender'] ?? null;
    $_input['birthDate'] = $_POST['birthDate'] ?? null;
    $_input['zip'] = $_POST['zip'] ?? $_POST['cep'];
    $_input['billing_zip'] = $_POST['zip'] ?? $_POST['cep'];
    $_input['shipping_zip'] = $_POST['zip'] ?? $_POST['cep'];
    $_input['cep'] = $_POST['zip'] ?? $_POST['cep'];
    $_input['address'] = $_POST['billing_street'] ??  $_POST['street'] ;
    $_input['billing_street'] = $_POST['billing_street'] ?? $_POST['street'];
    $_input['shipping_street'] = $_POST['billing_street'] ?? $_POST['street'];
    $_input['billing_number'] = $_POST['billing_number'] ??  $_POST['number'] ;
    $_input['billing_complement'] = $_POST['billing_complement'] ?? $_POST['complement'];
    $_input['billing_neighborhood'] = $_POST['billing_neighborhood'] ?? $_POST['neighborhood'];
    $_input['billing_city'] = $_POST['billing_city'] ?? $_POST['city'];
    $_input['shipping_city'] = $_POST['billing_city'] ?? $_POST['city'];
    $_input['city'] = $_POST['billing_city'] ?? $_POST['city'];
    $_input['billing_state'] = $_POST['billing_state'] ?? $_POST['state'];
    $_input['shipping_state'] = $_POST['billing_state'] ?? $_POST['state'];
    $_input['state'] = $_POST['billing_state'] ?? $_POST['state'];
    $_input['warehouse_id'] = $_POST['warehouse_id'];
    $_input['company'] = $_POST['company'] ?? $_POST['fullName'] ;
    $_input['marketingConsent'] = $_POST['marketingConsent'] ?? false;
    $_input['type'] = $_POST['type'] ?? 'pdv';
    $_input['communicationPreference'] = $_POST['communicationPreference'] ?? null;

    // Validação de campos

    $this->form_validation->set_data($_input);
    $this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]');
    $this->form_validation->set_rules('email_default', 'Email', 'trim|required|max_length[100]');

    if ($this->form_validation->run() == FALSE) {
      // Validação falha
      $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
    } else {
      // Chama o modelo para inserir os dados no banco

      $output = $this->clients_model->add($_input);
      if ($output > 0 && !empty($output)) {
        // Sucesso
        $message = array(
          'status' => 'success',
          'message' => 'Client created successfully',
          'client_id' => $output,
          'data' => $this->clients_model->get($output)
        );
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        // Erro
        $message = array('status' => FALSE, 'message' => 'Client add fail.');
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      }
    }
  }


  /**
   * @api {delete} api/delete/customers/:id Delete a Customer
   * @apiName DeleteCustomer
   * @apiGroup Customer
   *
   * @apiHeader {String} Authorization Basic Access Authentication token.
   *
   * @apiParam {Number} id Customer unique ID.
   *
   * @apiSuccess {String} status Request status.
   * @apiSuccess {String} message Customer Delete Successful.
   *
   * @apiSuccessExample Success-Response:
   *     HTTP/1.1 200 OK
   *     {
   *       "status": true,
   *       "message": "Customer Delete Successful."
   *     }
   *
   * @apiError {Boolean} status Request status.
   * @apiError {String} message Customer Delete Fail.
   *
   * @apiErrorExample Error-Response:
   *     HTTP/1.1 404 Not Found
   *     {
   *       "status": false,
   *       "message": "Customer Delete Fail."
   *     }
   */
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

      $output = $this->clients_model->delete($id);
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

  /**
   * @api {get} api/pdv/client/get/:id Get Client by ID
   * @apiName GetClient
   * @apiGroup Client
   *
   * @apiHeader {String} Authorization Basic Access Authentication token.
   *
   * @apiParam {Number} id Client unique ID.
   *
   * @apiSuccess {Boolean} status Request status.
   * @apiSuccess {Object} data Client information.
   *
   * @apiSuccessExample Success-Response:
   *     HTTP/1.1 200 OK
   *     {
   *       "status": true,
   *       "data": {
   *         "userid": "1",
   *         "company": "Test Company",
   *         "vat": "123456789",
   *         "phonenumber": "123-456-7890",
   *         ...
   *       }
   *     }
   *
   * @apiError {Boolean} status Request status.
   * @apiError {String} message No data were found.
   *
   * @apiErrorExample Error-Response:
   *     HTTP/1.1 404 Not Found
   *     {
   *       "status": false,
   *       "message": "No data were found"
   *     }
   */
  public function get_get($id = '')
  {
    if (empty($id) || !is_numeric($id)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Invalid Client ID'
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }




    $client = $this->Clients_model->get($id);



    if ($client) {
      $this->response([
        'status' => TRUE,
        'data' => $client
      ], REST_Controller::HTTP_OK);
    } else {
      $this->response([
        'status' => FALSE,
        'message' => 'No data were found'
      ], REST_Controller::HTTP_NOT_FOUND);
    }
  }


  /**
   * @api {put} api/customers/:id Update a Customer
   * @apiName PutCustomer
   * @apiGroup Customer
   *
   * @apiHeader {String} Authorization Basic Access Authentication token.
   *
   * @apiParam {String} company               Mandatory Customer company.
   * @apiParam {String} [vat]                 Optional Vat.
   * @apiParam {String} [phonenumber]         Optional Customer Phone.
   * @apiParam {String} [website]             Optional Customer Website.
   * @apiParam {Number[]} [groups_in]         Optional Customer groups.
   * @apiParam {String} [default_language]    Optional Customer Default Language.
   * @apiParam {String} [default_currency]    Optional default currency.
   * @apiParam {String} [address]             Optional Customer address.
   * @apiParam {String} [city]                Optional Customer City.
   * @apiParam {String} [state]               Optional Customer state.
   * @apiParam {String} [zip]                 Optional Zip Code.
   * @apiParam {String} [country]             Optional country.
   * @apiParam {String} [billing_street]      Optional Billing Address: Street.
   * @apiParam {String} [billing_city]        Optional Billing Address: City.
   * @apiParam {Number} [billing_state]       Optional Billing Address: State.
   * @apiParam {String} [billing_zip]         Optional Billing Address: Zip.
   * @apiParam {String} [billing_country]     Optional Billing Address: Country.
   * @apiParam {String} [shipping_street]     Optional Shipping Address: Street.
   * @apiParam {String} [shipping_city]       Optional Shipping Address: City.
   * @apiParam {String} [shipping_state]      Optional Shipping Address: State.
   * @apiParam {String} [shipping_zip]        Optional Shipping Address: Zip.
   * @apiParam {String} [shipping_country]    Optional Shipping Address: Country.
   *
   * @apiParamExample {json} Request-Example:
   *  {
   *     "company": "Công ty A",
   *     "vat": "",
   *     "phonenumber": "0123456789",
   *     "website": "",
   *     "default_language": "",
   *     "default_currency": "0",
   *     "country": "243",
   *     "city": "TP London",
   *     "zip": "700000",
   *     "state": "Quận 12",
   *     "address": "hẻm 71, số 34\/3 Đường TA 16, Phường Thới An, Quận 12",
   *     "billing_street": "hẻm 71, số 34\/3 Đường TA 16, Phường Thới An, Quận 12",
   *     "billing_city": "TP London",
   *     "billing_state": "Quận 12",
   *     "billing_zip": "700000",
   *     "billing_country": "243",
   *     "shipping_street": "",
   *     "shipping_city": "",
   *     "shipping_state": "",
   *     "shipping_zip": "",
   *     "shipping_country": "0"
   *   }
   *
   * @apiSuccess {Boolean} status Request status.
   * @apiSuccess {String} message Customer Update Successful.
   *
   * @apiSuccessExample Success-Response:
   *     HTTP/1.1 200 OK
   *     {
   *       "status": true,
   *       "message": "Customer Update Successful."
   *     }
   *
   * @apiError {Boolean} status Request status.
   * @apiError {String} message Customer Update Fail.
   *
   * @apiErrorExample Error-Response:
   *     HTTP/1.1 404 Not Found
   *     {
   *       "status": false,
   *       "message": "Customer Update Fail."
   *     }
   */
  public function update_put($id = '')
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
      $this->load->model('clients_model');
      $output = $this->clients_model->update($update_data, $id);
      if ($output > 0 && !empty($output)) {
        // success
        $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data' => $this->clients_model->get($id));
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        // error
        $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      }
    }
  }


   public function update_post($id = '')
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
      $this->load->model('clients_model');
      $output = $this->clients_model->update($update_data, $id);
      if ($output > 0 && !empty($output)) {
        // success
        $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data' => $this->clients_model->get($id));
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        // error
        $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      }
    }
  }
  // Lista clients que são Franchisees
  public function list_franchisee_client_post()
  {
    $page = $this->post('page') ? (int) $this->post('page') : 1; // Já começa em 1 agora
    $limit = $this->post('limit') ? (int) $this->post('limit') : 10; // Mudei de pageSize para limit
    $search = $this->post('search') ?: '';
    $sortField = $this->post('sortField') ?: 'userid';
    $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
    $status = $this->post('status') ? (array) $this->post('status') : []; // Agora trata como array
    $startDate = $this->post('startDate') ?: '';
    $endDate = $this->post('endDate') ?: '';
    $warehouse_id = $this->post('warehouse_id') ? (int) $this->post('warehouse_id') : 0;

    $data = $this->Clients_model->get_api_franchisee_client('', $page, $limit, $search, $sortField, $sortOrder, $status, $startDate, $endDate, $warehouse_id);

    if (empty($data['data'])) {
      // Adicione um log para debug
      log_message('debug', 'Franchisee query returned empty. Params: ' . json_encode($this->post()));
      $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
    } else {
      $this->response([
        'status' => TRUE,
        'total' => $data['total'],
        'data' => $data['data']
      ], REST_Controller::HTTP_OK);
    }
  }

  // Lista fornecedores que são Franchisees
  public function list_franchisee_supplier_post()
  {
    $page = $this->post('page') ? (int) $this->post('page') : 1; // Já começa em 1 agora
    $limit = $this->post('limit') ? (int) $this->post('limit') : 10; // Mudei de pageSize para limit
    $search = $this->post('search') ?: '';
    $sortField = $this->post('sortField') ?: 'userid';
    $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
    $status = $this->post('status') ? (array) $this->post('status') : []; // Agora trata como array
    $startDate = $this->post('startDate') ?: '';
    $endDate = $this->post('endDate') ?: '';
    $warehouse_id = $this->post('warehouse_id') ? (int) $this->post('warehouse_id') : 0;

    $data = $this->Clients_model->get_api_franchisee_supplier('', $page, $limit, $search, $sortField, $sortOrder, $status, $startDate, $endDate, $warehouse_id);

    if (empty($data['data'])) {
      // Adicione um log para debug
      log_message('debug', 'Franchisee query returned empty. Params: ' . json_encode($this->post()));
      $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
    } else {
      $this->response([
        'status' => TRUE,
        'total' => $data['total'],
        'data' => $data['data']
      ], REST_Controller::HTTP_OK);
    }
  }
}
