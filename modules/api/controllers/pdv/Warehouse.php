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
class Warehouse extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Warehouse_model');
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
        $page = (int) $this->post('page') ?: 1; // Página atual, padrão 1
        $limit = (int) $this->post('pageSize') ?: 10; // Itens por página, padrão 10
        $search = $this->post('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->post('sortField') ?: 'warehouse_id'; // Campo para ordenação, padrão 'warehouse_id'
        $sortOrder = strtolower($this->post('sortOrder')) === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente

        // Chamada ao modelo
        $data = $this->Warehouse_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        // Verifica se encontrou dados
        if (empty($data['data'])) {
            $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
        } else {
            $this->response([
                'status' => TRUE,
                'total' => $data['total'],
                'data' => $data['data']
            ], REST_Controller::HTTP_OK);
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

    public function create_post() {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid input data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Ajustando os campos de entrada para os campos reais da tabela
        $_input = [
            'warehouse_code' => $_POST['warehouse_code'] ?? null,
            'warehouse_name' => $_POST['warehouse_name'] ?? null,
            'warehouse_address' => $_POST['warehouse_address'] ?? null,
            'order' => $_POST['order'] ?? null,
            'display' => $_POST['display'] ?? null,
            'note' => $_POST['note'] ?? null,
            'city' => $_POST['city'] ?? null,
            'state' => $_POST['state'] ?? null,
            'zip_code' => $_POST['zip_code'] ?? null,
            'country' => $_POST['country'] ?? null,
            'franqueado_id' => $_POST['franqueado_id'] ?? null
        ];

        // Validação dos campos
        $this->form_validation->set_data($_input);
        $this->form_validation->set_rules('warehouse_name', 'Warehouse Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('warehouse_address', 'Warehouse Address', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('order', 'Order', 'trim|required|numeric');
        $this->form_validation->set_rules('display', 'Display', 'trim|required|in_list[0,1]');
        $this->form_validation->set_rules('city', 'City', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('state', 'State', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('zip_code', 'Zip Code', 'trim|required|max_length[10]');
        $this->form_validation->set_rules('country', 'Country', 'trim|required|numeric');

        if ($this->form_validation->run() === FALSE) {
            $this->response(['status' => FALSE, 'error' => $this->form_validation->error_array()], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $output = $this->Warehouse_model->add($_input);
        if ($output) {
            $this->response(['status' => TRUE, 'message' => 'Warehouse created successfully', 'data' => $this->Warehouse_model->get($output)], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to create warehouse'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_get($id = '') {
        if (!is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid Warehouse ID'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse = $this->Warehouse_model->get($id);
        if ($warehouse) {
            $this->response(['status' => TRUE, 'data' => $warehouse], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function update_post($id = '') {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid Warehouse ID or Data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Ajustar os campos permitidos para atualização
        $update_data = array_intersect_key($_POST, array_flip([
            'warehouse_code', 
            'warehouse_name', 
            'warehouse_address', 
            'order', 
            'display', 
            'note', 
            'city', 
            'state', 
            'zip_code', 
            'country', 
            'franqueado_id'
        ]));

        // Verificar se há dados para atualizar
        if (empty($update_data)) {
            $this->response(['status' => FALSE, 'message' => 'No valid data to update'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Atualizar o armazém
        $output = $this->Warehouse_model->update($update_data, $id);
        if ($output) {
            $this->response(['status' => TRUE, 'message' => 'Warehouse updated successfully', 'data' => $this->Warehouse_model->get($id)], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to update warehouse'], REST_Controller::HTTP_NOT_FOUND);
        }
    }
    
    public function remove_post($id = '') {
    // Verificar se o ID foi passado corretamente na URL
    if (empty($id) || !is_numeric($id)) {
        $this->response(['status' => FALSE, 'message' => 'Invalid Warehouse ID'], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    // Tentar deletar o armazém pelo ID
    $success = $this->Warehouse_model->delete($id);

    if ($success) {
        $this->response(['status' => TRUE, 'message' => 'Warehouse deleted successfully'], REST_Controller::HTTP_OK);
    } else {
        $this->response(['status' => FALSE, 'message' => 'Failed to delete warehouse'], REST_Controller::HTTP_NOT_FOUND);
    }
}


}
