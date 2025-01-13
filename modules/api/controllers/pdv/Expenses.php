<?php

defined('BASEPATH') OR exit('No direct script access allowed');
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
class Expenses extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Expenses_model');
    }

    /**
     * @api {get} api/customers/:id Request customer information
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
     *       "status": faget
     *       "message": "No data were found"
     *     }
     */
    public function list_get() {

        // Pegando os parâmetros de entrada
        $page = $this->get('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->get('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->get('search') ?: ''; // Parâmetro de busca
        $sortField = $this->get('sortField') ?: db_prefix() . 'expenses.id'; // Ordenação padrão pelo campo 'id' da tabela de despesas
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, crescente por padrão

        // Configurando a query com base no contexto de despesas
        $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as taxrate,' . db_prefix() . 'taxes_2.name as tax_name2, ' . db_prefix() . 'taxes_2.taxrate as taxrate2, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'expenses.addedfrom as addedfrom, recurring_from');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
        $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');

        // Aplicando busca, se houver
        if (!empty($search)) {
            $this->db->like('expenses.description', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('expenses_categories.name', $search);
        }

        // Aplicando ordenação
        $this->db->order_by($sortField, $sortOrder);

        // Definindo o limite e o offset (página atual)
        $this->db->limit($limit, ($page - 1) * $limit);

        // Executando a consulta
        $data = $this->db->get()->result_array();

        // Verificando se há dados
        if (empty($data)) {
            $this->response(['status' => FALSE, 'message' => 'Nenhum dado foi encontrado'], REST_Controller::HTTP_NOT_FOUND);
        } else {
            // Pegando o total de resultados
            $this->db->from(db_prefix() . 'expenses');
            if (!empty($search)) {
                $this->db->like('expenses.description', $search);
                $this->db->or_like('clients.company', $search);
                $this->db->or_like('expenses_categories.name', $search);
            }
            $total = $this->db->count_all_results();

            $this->response(['status' => TRUE, 'total' => $total, 'data' => $data], REST_Controller::HTTP_OK);
        }
    }

    public function list_by_date_get() {
        $page = $this->get('page') ? (int) $this->get('page') : 0;
        $page = $page + 1;
        $limit = $this->get('pageSize') ? (int) $this->get('pageSize') : 10;
        $search = $this->get('search') ?: '';
        $sortField = $this->get('sortField') ?: db_prefix() . 'expenses.id';
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');

        $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as taxrate,' . db_prefix() . 'taxes_2.name as tax_name2, ' . db_prefix() . 'taxes_2.taxrate as taxrate2, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'expenses.addedfrom as addedfrom, recurring_from');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
        $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');

        if (!empty($start_date))
        {
            $this->db->where('date >=', $start_date);
        }
        if (!empty($end_date))
        {
            $this->db->where('date <=', $end_date);
        }

        if (!empty($search))
        {
            $this->db->group_start();
            $this->db->like('expenses.note', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('expenses_categories.name', $search);
            $this->db->group_end();
        }

        $this->db->order_by($sortField, $sortOrder);

        $this->db->limit($limit, ($page - 1) * $limit);

        $data = $this->db->get()->result_array();

        if (empty($data))
        {
            $this->response(['status' => FALSE, 'message' => 'Nenhum dado foi encontrado'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $this->db->from(db_prefix() . 'expenses');
        if (!empty($search))
        {
            $this->db->like('expenses.note', $search);
            $this->db->or_like('clients.company', $search);
            $this->db->or_like('expenses_categories.name', $search);
        }
        if (!empty($start_date))
        {
            $this->db->where('date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('date <=', $end_date);
        }
        $total = $this->db->count_all_results();

        $this->response(['status' => TRUE, 'total' => $total, 'data' => $data], REST_Controller::HTTP_OK);
    }


    private function format_currency($value) {
        return number_format($value, 2);
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

        // Recebendo e decodificando os dados
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Validações obrigatórias
        $this->form_validation->set_rules('category', 'Categoria', 'required');
        $this->form_validation->set_rules('amount', 'Valor', 'required|numeric');
        $this->form_validation->set_rules('date', 'Data', 'required');
        $this->form_validation->set_rules('note', 'Descrição', 'required');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        }
        else
        {
            // Preparando os dados para inserção
            $data = array(
                'category' => $this->input->post('category'),
                'amount' => $this->input->post('amount'),
                'date' => $this->input->post('date'),
                'note' => $this->input->post('note'),
                'clientid' => $this->input->post('clientid') ?? null,
                'paymentmode' => $this->input->post('paymentmode') ?? null,
                'tax' => $this->input->post('tax') ?? null,
                'tax2' => $this->input->post('tax2') ?? null,
                'reference_no' => $this->input->post('reference_no') ?? null,
                'currency' => $this->input->post('currency') ?? get_base_currency(),
                'addedfrom' => get_staff_user_id()
            );

            // Tentando criar a despesa
            $expense_id = $this->Expenses_model->add($data);

            if ($expense_id) {
                // Sucesso na criação
                $expense = $this->Expenses_model->get($expense_id);
                $message = array(
                    'status' => TRUE,
                    'message' => 'Despesa criada com sucesso',
                    'data' => $expense
                );
                $this->response($message, REST_Controller::HTTP_CREATED);
            }
            else
            {
                $message = array(
                    'status' => FALSE,
                    'message' => 'Falha ao criar despesa'
                );
                $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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
    public function data_delete($id = '') {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Customer ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $this->load->model('clients_model');
            $output = $this->clients_model->delete($id);
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
    public function data_put() 
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) 
        {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }

        $this->form_validation->set_data($_POST);

        // Validando se o id foi enviado
        if (empty($_POST['id']) || !is_numeric($_POST['id'])) 
        {
            $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $update_data = $this->input->post();
        $expense_id = $_POST['id'];
        
        $this->load->model('Expenses_model');
        $output = $this->Expenses_model->update($update_data, $expense_id);

        if (!$output || empty($output)) 
        {
            $message = array('status' => FALSE, 'message' => 'Expenses Update Fail.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $message = array(
            'status' => TRUE, 
            'message' => 'Expenses Update Successful.', 
            'data' => $this->Expenses_model->get($expense_id)
        );
        $this->response($message, REST_Controller::HTTP_OK);
    }
}
