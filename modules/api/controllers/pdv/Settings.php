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
class Settings extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Settings_model');
    }


    public function options_get()
    {
        $data = [
            [
                "type" => "menu",
                "category" => "system",
                "list" => [
                    "menu" => [
                        [
                            "value" => "indice",
                            "label" => "my_account",
                            "color" => "",
                            "icon" => "lucide:home",
                            "width" => "",
                            "path" => "/home"
                        ],

                        [
                            "value" => "GPTW",
                            "label" => "my_account",
                            "color" => "",
                            "icon" => "lucide:heart",
                            "width" => "",
                            "path" => "/gptw"
                        ],

                        [
                            "value" => "Gestão GPTW ",
                            "label" => "my_account",
                            "color" => "",
                            "icon" => "lucide:wrench",
                            "width" => "",
                            "path" => "/gptw-management"
                        ],
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
                            "value" => "Ordens de Compras",
                            "label" => "Transações",
                            "color" => "",
                            "icon" => "lucide:shopping-cart",
                            "width" => "",
                            "path" => "/sales-orders"
                        ],
                        [
                            "value" => "Orçamentos",
                            "label" => "pdv",
                            "color" => "",
                            "icon" => "lucide:shopping-bag",
                            "width" => "",
                            "path" => "https:/buy-orders"
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
                            "value" => "Representadas",
                            "label" => "cadastros",
                            "color" => "",
                            "icon" => "lucide:folder",
                            "width" => "",
                            "path" => "/representatives"
                        ],
                        [
                            "value" => "Representantes",
                            "label" => "cadastros",
                            "color" => "",
                            "icon" => "lucide:user",
                            "width" => "",
                            "path" => "/sales-reps"
                        ],
                        [
                            "value" => "Fornecedores",
                            "label" => "cadastros",
                            "color" => "",
                            "icon" => "lucide:building",
                            "width" => "",
                            "path" => "/suppliers"
                        ],
                        [
                            "value" => "Transportadoras",
                            "label" => "cadastros",
                            "color" => "",
                            "icon" => "lucide:truck",
                            "width" => "",
                            "path" => "/carriers"
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
                            "value" => "Categorias",
                            "label" => "pdv",
                            "color" => "",
                            "icon" => "lucide:edit",
                            "width" => "",
                            "path" => "/categories"
                        ],
                        [
                            "value" => "Produto",
                            "label" => "cadastros",
                            "color" => "",
                            "icon" => "lucide:edit",
                            "width" => "",
                            "path" => "/produto"
                        ],
                        [
                            "value" => "Lojas",
                            "label" => "cadastros",
                            "color" => "",
                            "icon" => "lucide:edit",
                            "width" => "",
                            "path" => "/warehouse"
                        ],
                        [
                            "value" => "Contas a pagar",
                            "label" => "pdv",
                            "color" => "",
                            "icon" => "lucide:credit-card",
                            "width" => "",
                            "path" => "/financial"
                        ],
                        [
                            "value" => "Contas a pagar",
                            "label" => "financial",
                            "color" => "",
                            "icon" => "lucide:credit-card",
                            "width" => "",
                            "path" => "/financial"
                        ],
                        [
                            "value" => "Carteira de Títulos ",
                            "label" => "financial",
                            "color" => "",
                            "icon" => "lucide:wallet",
                            "width" => "",
                            "path" => "/financial-erp/titles"
                        ],
                        [
                            "value" => "Simulador de Encargos ",
                            "label" => "financial",
                            "color" => "",
                            "icon" => "lucide:book",
                            "width" => "",
                            "path" => "/financial-erp/contability"
                        ],
                        [
                            "value" => "Relatórios",
                            "label" => "home",
                            "color" => "",
                            "icon" => "lucide:file-text",
                            "width" => "",
                            "path" => "/reports"
                        ],
                        [
                            "value" => "Painel do Franqueado",
                            "label" => "Franquias",
                            "color" => "",
                            "icon" => "lucide:bar-chart-2",
                            "width" => "",
                            "path" => "/franchisees/dashboard"
                        ],
                        [
                            "value" => "Gestão de Franquias",
                            "label" => "Franquias",
                            "color" => "",
                            "icon" => "lucide:store",
                            "width" => "",
                            "path" => "/franchisees/management/list"
                        ],
                        [
                            "value" => "Contratos",

                            "label" => "Franquias",
                            "color" => "",
                            "icon" => "lucide:handshake",
                            "width" => "",
                            "path" => "/franchisees/contracts/list"
                        ],
                        [
                            "value" => "Pedidos",
                            "label" => "Franquias",
                            "color" => "",
                            "icon" => "lucide:file-text",
                            "width" => "",
                            "path" => "/franchisees/orders"
                        ],
                        [
                            "value" => "Suporte",
                            "label" => "Franquias",
                            "color" => "",
                            "icon" => "lucide:message-circle",
                            "width" => "",
                            "path" => "/franchisees/support"
                        ],

                        // [
                        //     "value" => "Treinamentos",
                        //     "label" => "Franquias",
                        //     "color" => "",
                        //     "icon" => "lucide:graduation-cap",
                        //     "width" => "",
                        //     "path" => "/franchisees/training"
                        // ],
                        // [
                        //     "value" => "Gestão de Treinamentos",
                        //     "label" => "Franquias",
                        //     "color" => "",
                        //     "icon" => "lucide:book-open",
                        //     "width" => "",
                        //     "path" => "/Franquias/training/management"
                        // ],
                        /*
                         * 
                         */


                        [
                            "value" => "users",
                            "label" => "admin",
                            "color" => "",
                            "icon" => "lucide:users",
                            "width" => "",
                            "path" => "/admin/user/list"
                        ],
                        /*
                [
                    "value" => "languages",
                    "label" => "admin",
                    "color" => "",
                    "icon" => "lucide:globe-2",
                    "width" => "",
                    "path" => "/admin/languages"
                ],
                 * 
                
                [
                    "value" => "options",
                    "label" => "admin",
                    "color" => "",
                    "icon" => "lucide:sliders-horizontal",
                    "width" => "",
                    "path" => "/admin/options"
                ],
                 * 
                 */
                        [
                            "value" => "config",
                            "label" => "admin",
                            "color" => "",
                            "icon" => "lucide:settings",
                            "width" => "",
                            "path" => "/admin/config"
                        ],
                        [
                            "value" => "emails",
                            "label" => "admin",
                            "color" => "",
                            "icon" => "lucide:mail",
                            "width" => "",
                            "path" => "/admin/emails"
                        ],
                        /*
                [
                    "value" => "apis",
                    "label" => "admin",
                    "color" => "",
                    "icon" => "lucide:unlock-keyhole",
                    "width" => "",
                    "path" => "/admin/apis"
                ],
                 * 
                 */
                        [
                            "value" => "Limpar",
                            "label" => "admin",
                            "color" => "",
                            "icon" => "lucide:trash",
                            "width" => "",
                            "path" => "/auth/clean"
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


    public function options_get1()
    {
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
                            "label" => "home",
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
                            "value" => "Painel do Franqueado",
                            "label" => "franchisees",
                            "color" => "",
                            "icon" => "lucide:bar-chart-2",
                            "width" => "",
                            "path" => "/franchisees/dashboard"
                        ],
                        [
                            "value" => "Gestão de Franquias",
                            "label" => "franchisees",
                            "color" => "",
                            "icon" => "lucide:store",
                            "width" => "",
                            "path" => "/franchisees/management/list"
                        ],
                        [
                            "value" => "Contratos",
                            "label" => "franchisees",
                            "color" => "",
                            "icon" => "lucide:handshake",
                            "width" => "",
                            "path" => "/franchisees/contracts/list"
                        ],
                        [
                            "value" => "Pedidos",
                            "label" => "franchisees",
                            "color" => "",
                            "icon" => "lucide:file-text",
                            "width" => "",
                            "path" => "/franchisees/orders"
                        ],
                        [
                            "value" => "Suporte",
                            "label" => "Franquias",
                            "color" => "",
                            "icon" => "lucide:message-circle",
                            "width" => "",
                            "path" => "/franchisees/support"
                        ],


                        // [
                        //     "value" => "Treinamentos",
                        //     "label" => "franchisees",
                        //     "color" => "",
                        //     "icon" => "lucide:graduation-cap",
                        //     "width" => "",
                        //     "path" => "/franchisees/training"
                        // ],
                        // [
                        //     "value" => "Gestão de Treinamentos",
                        //     "label" => "franchisees",
                        //     "color" => "",
                        //     "icon" => "lucide:book-open",
                        //     "width" => "",
                        //     "path" => "/franchisees/training/management"
                        // ],

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

    public function create_post()
    {

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

    public function config_get()
    {
        $output = $this->Settings_model->get_options();

        $warehouses = $this->Settings_model->get_warehouses();

        $formattedOutput = [];
        foreach ($output as $item) {
            $formattedOutput[$item['name']] = $item['value'];
        }
        $formattedOutput['warehouses'] = $warehouses;

        if (!empty($formattedOutput)) {
            $this->response($formattedOutput, REST_Controller::HTTP_OK);
        } else {
            $this->response('Erro', REST_Controller::HTTP_NOT_ACCEPTABLE);
        }
    }

    public function upload_put($option_name, $warehouse_id = '')
    {
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!in_array($option_name, ['logoDark', 'logoLight', 'iconDark', 'iconLight', 'company_logo'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid option name for image upload'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $raw_body = $this->input->raw_input_stream;

        $content_type = isset($this->input->request_headers()['Content-Type'])
            ? $this->input->request_headers()['Content-Type']
            : (isset($this->input->request_headers()['content-type'])
                ? $this->input->request_headers()['content-type']
                : null);

        if (!$content_type) {
            $this->response([
                'status' => FALSE,
                'message' => 'Content-Type header is missing'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (preg_match('/boundary=(.*)$/', $content_type, $matches)) {
            if (isset($matches[1])) {
                $boundary = '--' . trim($matches[1]);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Invalid boundary in Content-Type'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Boundary not found in Content-Type'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $parts = explode($boundary, $raw_body);

        $upload_dir = './uploads/settings/';
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
                    $allowed_types = ['jpeg', 'jpg', 'png', 'svg'];
                    if (!in_array(strtolower($extension), $allowed_types)) {
                        $this->response([
                            'status' => FALSE,
                            'message' => 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types)
                        ], REST_Controller::HTTP_BAD_REQUEST);
                        return;
                    }

                    $max_file_size = 5 * 1024 * 1024;
                    $file_size = strlen($file_content);
                    if ($file_size > $max_file_size) {
                        $this->response([
                            'status' => FALSE,
                            'message' => 'File is too large. Maximum size is 5MB'
                        ], REST_Controller::HTTP_BAD_REQUEST);
                        return;
                    }

                    $unique_filename = $option_name . '_' . uniqid() . '.' . $extension;
                    $upload_path = $upload_dir . $unique_filename;

                    if (file_put_contents($upload_path, $file_content)) {
                        $server_url = base_url();
                        $relative_path = str_replace('./', '', $upload_path);
                        $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                        $this->db->where('name', $option_name);
                        $this->db->where('warehouse_id', $warehouse_id);
                        $this->db->update(db_prefix() . 'options', [
                            'value' => $full_url,
                            'type' => 'pdv'
                        ]);

                        if ($this->db->affected_rows() > 0) {
                            $this->response([
                                'status' => TRUE,
                                'message' => 'Image uploaded successfully',
                                'file' => $full_url
                            ], REST_Controller::HTTP_OK);
                            return;
                        } else {
                            $this->db->insert(db_prefix() . 'options', [
                                'name' => $option_name,
                                'value' => $full_url,
                                'autoload' => 1,
                                'type' => 'pdv',
                                'warehouse_id' => $warehouse_id
                            ]);

                            $this->response([
                                'status' => TRUE,
                                'message' => 'Image uploaded and new option created',
                                'file' => $full_url
                            ], REST_Controller::HTTP_OK);
                            return;
                        }
                    }
                }
            }
        }

        $this->response([
            'status' => FALSE,
            'message' => 'No file found in request'
        ], REST_Controller::HTTP_BAD_REQUEST);
    }

    public function update_config_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST)) {
            $this->response([
                'status' => FALSE,
                'message' => 'No configuration data provided'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Validate warehouse_id
        if (!isset($_POST['warehouse_id']) || empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $_POST['warehouse_id'];
        unset($_POST['warehouse_id']);

        $allowed_configs = [
            'appName' => [
                'type' => 'string',
                'required' => true,
                'max_length' => 100
            ],
            'pdv_desconto_produto' => [
                'type' => 'boolean',
                'required' => false
            ],
            'pdv_nfe_dinheiro' => [
                'type' => 'boolean',
                'required' => false
            ],
            'pdv_nfe_cartao' => [
                'type' => 'boolean',
                'required' => false
            ]
        ];

        $updates = [];
        $errors = [];

        foreach ($_POST as $key => $value) {
            if (!isset($allowed_configs[$key])) {
                $errors[] = "Configuration key '{$key}' is not allowed";
                continue;
            }

            $config = $allowed_configs[$key];
            $is_valid = true;
            $processed_value = $value;

            if ($config['required'] && empty($value)) {
                $errors[] = "Configuration '{$key}' is required";
                $is_valid = false;
            }

            if ($is_valid) {
                if ($config['type'] === 'boolean') {
                    $processed_value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($processed_value === null) {
                        $errors[] = "Configuration '{$key}' must be a boolean value (true/false or 1/0)";
                        $is_valid = false;
                    } else {
                        $processed_value = $processed_value ? 1 : 0;
                    }
                } elseif ($config['type'] === 'string') {
                    if (isset($config['max_length']) && strlen($value) > $config['max_length']) {
                        $errors[] = "Configuration '{$key}' exceeds maximum length of {$config['max_length']} characters";
                        $is_valid = false;
                    }
                }
            }

            if ($is_valid) {
                $updates[] = [
                    'name' => $key,
                    'value' => $processed_value,
                    'type' => 'pdv',
                    'warehouse_id' => $warehouse_id
                ];
            }
        }

        if (!empty($errors)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Validation errors occurred',
                'errors' => $errors
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->trans_start();

        $updated = 0;
        $created = 0;

        foreach ($updates as $update) {
            $this->db->where('name', $update['name']);
            $this->db->where('warehouse_id', $warehouse_id);
            $this->db->update(db_prefix() . 'options', [
                'value' => $update['value'],
                'type' => $update['type']
            ]);

            if ($this->db->affected_rows() > 0) {
                $updated++;
            } else {
                $this->db->insert(db_prefix() . 'options', [
                    'name' => $update['name'],
                    'value' => $update['value'],
                    'autoload' => 1,
                    'type' => $update['type'],
                    'warehouse_id' => $update['warehouse_id']
                ]);
                if ($this->db->affected_rows() > 0) {
                    $created++;
                }
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to update configurations'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->db->where_in('name', array_column($updates, 'name'));
        $this->db->where('warehouse_id', $warehouse_id);
        $updated_configs = $this->db->get(db_prefix() . 'options')->result_array();

        $result = [];
        foreach ($updated_configs as $config) {
            $result[$config['name']] = $config['value'];
        }

        $this->response([
            'status' => TRUE,
            'message' => sprintf(
                'Configurations updated successfully. Updated: %d, Created: %d',
                $updated,
                $created
            ),
            'data' => $result
        ], REST_Controller::HTTP_OK);
    }
}
