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
class Settings extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Settings_model');
    }

    public function options_get() {
        $data = [
    [
        "type" => "menu",
        "category" => "system",
        "list" => [
            "menu" => [
                
                [
                    "value" => "dashboard",
                    "label" => "home",
                    "color" => "",
                    "icon" => "lucide:chart-pie",
                    "width" => "",
                    "path" => "/dashboard"
                ],
               
                [
                    "value" => "Catálogo",
                    "label" => "home",
                    "color" => "",
                    "icon" => "lucide:album",
                    "width" => "",
                    "path" => "/products"
                ],
                [
                    "value" => "cash",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:calculator",
                    "width" => "",
                    "path" => "/cash"
                ],
                [
                    "value" => "POS",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:monitor",
                    "width" => "",
                    "path" => "/pdv"
                ],
                [
                    "value" => "Ordens de Compra",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:shopping-bag",
                    "width" => "",
                    "path" => "/sales-orders"
                ],
                [
                    "value" => "Vendas",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:receipt",
                    "width" => "",
                    "path" => "/transactions"
                ],
                [
                    "value" => "clients",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:book-user",
                    "width" => "",
                    "path" => "/client"
                ],
               
                [
                    "value" => "Categorias",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:list",
                    "width" => "",
                    "path" => "/categories"
                ],
        
                [
                    "value" => "product",
                    "label" => "pdv",
                    "color" => "",
                    "icon" => "lucide:edit",
                    "width" => "",
                    "path" => "/produto"
                ],
                [
                    "value" => "Contas a pagar",
                    "label" => "financial",
                    "color" => "",
                    "icon" => "lucide:wallet",
                    "width" => "",
                    "path" => "/financial"
                ],
                [
                    "value" => "Relatórios",
                    "label" =>  "home",
                    "color" => "",
                    "icon" => "lucide:file-text",
                    "width" => "",
                    "path" => "/reports"
                ],
                [
                    "value" => "users",
                    "label" => "admin",
                    "color" => "",
                    "icon" => "lucide:users",
                    "width" => "",
                    "path" => "/admin/user/list"
                ],
               
                [
                    "value" => "config",
                    "label" => "admin",
                    "color" => "",
                    "icon" => "lucide:settings",
                    "width" => "",
                    "path" => "/admin/config"
                ],
                    [
                        "value" => "Limpar",
                        "label" => "admin",
                        "color" => "",
                        "icon" => "lucide:trash",
                        "width" => "",
                        "path" => "/auth/clean"
                    ],
                    [
                        "value" => "Sair",
                        "label" => "admin",
                        "color" => "",
                        "icon" => "lucide:log-out",
                        "width" => "",
                        "path" => "/auth/logout"
                    ]
                
            ]
        ]
    ],
    [
        "type" => "status",
        "category" => "system",
        "list" => [
            "status" => [
                [
                    "value" => "active",
                    "label" => "Active",
                    "color" => "success",
                    "icon" => "solar:check-bold",
                    "width" => "",
                    "path" => ""
                ],
                [
                    "value" => "inactive",
                    "label" => "Pending",
                    "color" => "warning",
                    "icon" => "solar:clock-bold",
                    "width" => "",
                    "path" => ""
                ],
                [
                    "value" => "banned",
                    "label" => "Banned",
                    "color" => "error",
                    "icon" => "solar:ban-bold",
                    "width" => "",
                    "path" => ""
                ],
                [
                    "value" => "rejected",
                    "label" => "Rejected",
                    "color" => "default",
                    "icon" => "solar:close-circle-bold",
                    "width" => "",
                    "path" => ""
                ]
            ]
        ]
    ]
];
        

          $this->response($data, REST_Controller::HTTP_OK);
    }

    public function create_post() {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        $this->load->model('Settings_model');
        $this->form_validation->set_rules('title', 'title', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {

            $output = $this->Settings_model->insert_settings($_POST);

            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Settings added successful.', 'data' => $output);
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $this->response('Error', REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
    }
    
    public function config_get(){
        
   
         $output = $this->Settings_model->get_options();

            $this->load->model('Settings_model');
    $output = $this->Settings_model->get_options();

    $formattedOutput = [];
    foreach ($output as $item) {
      
        $formattedOutput[$item['name']] = $item['value'];
        
    }

    if (!empty($formattedOutput)) {
        $this->response($formattedOutput, REST_Controller::HTTP_OK);
    } else {
        $this->response('Erro', REST_Controller::HTTP_NOT_ACCEPTABLE);
    }
        /*
         * {
    "appName": "Sobre",
    "logoDark": null,
    "logoLight": null,
    "iconDark": null,
    "iconLight": null
}
         */
    }
}
