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
class Dashboard extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('cashs_model');
        $this->load->model('reports_model');
    }

    public function get_post() {

        $minhas_vendas = 0;
        $balances = $this->reports_model->get_cash_extracts();
        foreach($balances as $b){
            $minhas_vendas += floatval($b['total_sum']);
        }

        // Pegar os valores do dia atual e anterior com segurança
        $venda_dia = 0;
        $venda_ontem = 0;
        
        // Encontrar valores do dia atual e anterior
        foreach($balances as $b) {
            if($b['sale_date'] == date('Y-m-d')) {
                $venda_dia = floatval($b['total_sum']);
            }
            if($b['sale_date'] == date('Y-m-d', strtotime('-1 day'))) {
                $venda_ontem = floatval($b['total_sum']);
            }
        }

        // Calcular porcentagem com proteção contra divisão por zero
        $porcent = $venda_ontem > 0 ? (($venda_dia/$venda_ontem)*100)-100 : 0;

        // Despesas
        $minhas_despesas = 0;
        $productP = $this->reports_model->get_expense_extracts();
        foreach($productP as $s){
            $minhas_despesas += floatval($s['total_sum']);
        }

        // Pegar valores de despesas do dia atual e anterior
        $despesas_dia = 0;
        $despesas_ontem = 0;
        
        foreach($productP as $p) {
            if($p['expense_date'] == date('Y-m-d')) {
                $despesas_dia = floatval($p['total_sum']);
            }
            if($p['expense_date'] == date('Y-m-d', strtotime('-1 day'))) {
                $despesas_ontem = floatval($p['total_sum']);
            }
        }

        // Calcular porcentagem com proteção contra divisão por zero
        $porcentB = $despesas_ontem > 0 ? (($despesas_dia/$despesas_ontem)*100) : 0;

        $data = [
            "balance" => [
                "credit" => $venda_dia,
                "debit" => 0,
                "total" => $venda_dia,
                "percent" => $porcent,
                "comparison" => "vs yesterday"
            ],
            "product" => [
                "credit" => $despesas_dia,
                "debit" => 0,
                "total" => $despesas_dia,
                "percent" => $porcentB,
                "comparison" => "vs yesterday"
            ],
            "seller" => [
                "credit" => 100,
                "debit" => 50,
                "total" => 50,
                "percent" => 25,
                "comparison" => "vs yesterday"
            ],
            "lastDays" => [
                "balance" => [
                    "categories" => [
                        $balances[0]['sale_date'],
                        $balances[1]['sale_date'],
                        $balances[2]['sale_date'],
                        $balances[3]['sale_date'],
                        $balances[4]['sale_date'],
                        $balances[5]['sale_date'],
                        $balances[6]['sale_date']
                    ],
                    "series" => [
                        $balances[0]['total_sum'],
                        $balances[1]['total_sum'],
                        $balances[2]['total_sum'],
                        $balances[3]['total_sum'],
                        $balances[4]['total_sum'],
                        $balances[5]['total_sum'],
                        $balances[6]['total_sum']
                    ],
                    "total" => $minhas_vendas
                ],
                "product" => [
                    "categories" => [
                        $productP[0]['expense_date'],
                        $productP[1]['expense_date'],
                        $productP[2]['expense_date'],
                        $productP[3]['expense_date'],
                        $productP[4]['expense_date'],
                        $productP[5]['expense_date'],
                        $productP[6]['expense_date']
                    ],
                    "series" => [
                        $productP[0]['total_sum'],
                        $productP[1]['total_sum'],
                        $productP[2]['total_sum'],
                        $productP[3]['total_sum'],
                        $productP[4]['total_sum'],
                        $productP[5]['total_sum'],
                        $productP[6]['total_sum']
                    ],
                    "total" => $minhas_despesas
                ],
                "seller" => [
                    "categories" => [
                        "21/12/2024",
                        "22/12/2024",
                        "23/12/2024",
                        "24/12/2024",
                        "25/12/2024",
                        "26/12/2024",
                        "27/12/2024",
                        "28/12/2024",
                        "29/12/2024",
                        "30/12/2024"
                    ],
                    "series" => [
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0
                    ],
                    "total" => 0
                ]
            ],
            "lastMonth" => [
                "categories" => [
                    "1/2024",
                    "2/2024",
                    "3/2024",
                    "4/2024",
                    "5/2024",
                    "6/2024",
                    "7/2024",
                    "8/2024",
                    "9/2024",
                    "10/2024",
                    "11/2024",
                    "12/2024"
                ],
                "series" => [
                    [
                        "name" => "2024",
                        "data" => [
                            [
                                "name" => "product",
                                "data" => [
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0
                                ],
                                "total" => 0
                            ],
                            [
                                "name" => "seller",
                                "data" => [
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0
                                ],
                                "total" => 0
                            ],
                            [
                                "name" => "balance",
                                "data" => [
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    0,
                                    5979.959999999999
                                ],
                                "total" => 5979.959999999999
                            ]
                            ]
                    ]
                ]
            ]
        ];
        $this->response($data, REST_Controller::HTTP_OK);

    }

    
    
     public function get_post1() {
         
         $minhas_vendas = 0;
         $balances = $this->cashs_model->get_cash_extracts();
         foreach($balances as $b){
             $minhas_vendas += $b['total_sum'];
         }
         $venda_dia = $balances[7]['total_sum']== 0?1:$balances[7]['total_sum'];
         $venda_ontem = $balances[6]['total_sum'] == 0?1:$balances[6]['total_sum'];
         
         $porcent = (($venda_dia/$venda_ontem)*100)-100;
         
       
         
         
$data = [
    "balance" => [
        "credit" => $venda_dia,
        "debit" => 0,
        "total" => $venda_dia,
        "percent" => $porcent
    ],
    "product" => [
        "credit" => 0,
        "debit" => 0,
        "total" => 0,
        "percent" => 0
    ],
    "seller" => [
        "credit" => 0,
        "debit" => 0,
        "total" => 0,
        "percent" => 0
    ],
    "lastDays" => [
        "balance" => [
            "categories" => [
                $balances[0]['sale_date'],
                $balances[1]['sale_date'],
                $balances[2]['sale_date'],
                $balances[3]['sale_date'],
                $balances[4]['sale_date'],
                $balances[5]['sale_date'],
                $balances[6]['sale_date'],
            ],
            "series" => [
                $balances[0]['total_sum'],
                $balances[1]['total_sum'],
                $balances[2]['total_sum'],
                $balances[3]['total_sum'],
                $balances[4]['total_sum'],
                $balances[5]['total_sum'],
                $balances[6]['total_sum'],
            ],
            "total" => $minhas_vendas
        ],
        "product" => [
            "categories" => [
                "21/12/2024",
                "22/12/2024",
                "23/12/2024",
                "24/12/2024",
                "25/12/2024",
                "26/12/2024",
                "27/12/2024",
                "28/12/2024",
                "29/12/2024",
                "30/12/2024"
            ],
            "series" => [
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0
            ],
            "total" => 0
        ],
        "seller" => [
            "categories" => [
                "21/12/2024",
                "22/12/2024",
                "23/12/2024",
                "24/12/2024",
                "25/12/2024",
                "26/12/2024",
                "27/12/2024",
                "28/12/2024",
                "29/12/2024",
                "30/12/2024"
            ],
            "series" => [
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0,
                0
            ],
            "total" => 0
        ]
    ],
    "lastMonth" => [
        "categories" => [
            "1/2024",
            "2/2024",
            "3/2024",
            "4/2024",
            "5/2024",
            "6/2024",
            "7/2024",
            "8/2024",
            "9/2024",
            "10/2024",
            "11/2024",
            "12/2024"
        ],
        "series" => [
            [
                "name" => "2024",
                "data" => [
                    [
                        "name" => "product",
                        "data" => [
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0
                        ],
                        "total" => 0
                    ],
                    [
                        "name" => "seller",
                        "data" => [
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0
                        ],
                        "total" => 0
                    ],
                    [
                        "name" => "balance",
                        "data" => [
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            5979.959999999999
                        ],
                        "total" => 5979.959999999999
                    ]
                ]
            ]
            ]
    ]
];
          $this->response($data, REST_Controller::HTTP_OK);
       
    }
    
    public function config_get() {

        $data = [
    "appName" => "Sobre",
    "logoDark" => null,
    "logoLight" => null,
    "iconDark" => null,
    "iconLight" => null
];   
        

          $this->response($data, REST_Controller::HTTP_OK);
       
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
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_get($id = '') {



        $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
        $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->get('sortField') ?: 'id'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente


        $data = $this->Carriers_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
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
    public function data_post() {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
     

        $this->load->model('Carriers_model');
        $this->form_validation->set_rules('nome', 'nome', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {

            $output = $this->Carriers_model->add($_POST);
        
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Carrier add successful.', 'data'=>$output);
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $this->response('Error', REST_Controller::HTTP_NOT_ACCEPTABLE);
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
            $message = array('status' => FALSE, 'message' => 'Invalid Address ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $this->load->model('Carriers_model');
            $output = $this->Carriers_model->delete($id);
            if ($output === TRUE) {
                // success
                $message = array('status' => TRUE, 'message' => 'Carrier Delete Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Carrier Delete Fail.');
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
    public function data_put($id = '') {
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
            $this->load->model('Carriers_model');
            $output = $this->Carriers_model->update($update_data, $id);
            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data'=>$this->Carriers_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }
}
