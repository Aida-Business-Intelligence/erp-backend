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
        
        $warehouse_id = $_GET['warehouse_id'];
        
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }
        
    
        $output = $this->Settings_model->get_options($warehouse_id);

        $warehouses = $this->Settings_model->get_warehouses();

        $formattedOutput = [];
        foreach ($output as $item) {
            $formattedOutput[$item['name']] = $item['value'];
        }
        $formattedOutput['warehouses'] = $warehouses;

        // Conversão explícita de campos booleanos
        $boolean_fields = [
            'backup_auto',
            'backup_compress',
            'backup_to_cloud',
            // Adicione outros campos booleanos relevantes aqui
        ];
        foreach ($boolean_fields as $field) {
            if (isset($formattedOutput[$field])) {
                $formattedOutput[$field] = ($formattedOutput[$field] == '1' || $formattedOutput[$field] === 1 || $formattedOutput[$field] === true);
            }
        }

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
            ],
            'pdv_senha_gerente_close_cash' => [
                'type' => 'boolean',
                'required' => false
            ],
            'pdv_senha_gerente_open_cash' => [
                'type' => 'boolean',
                'required' => false
            ],
            'pdv_senha_deletar_produto' => [
                'type' => 'boolean',
                'required' => false
            ],
            'pdv_senha_editar_produto' => [
                'type' => 'boolean',
                'required' => false
            ],
            'purchase_needs_enabled' => [
                'type' => 'boolean',
                'required' => false
            ],
            'pdv_porcentagem' => [
                'type' => 'string',
                'required' => false,
                'max_length' => 100
            ],
            // E-commerce Franquias
            'ecommerce_franquias_limite_itens_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_limite_itens_regra' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_limite_itens_valor' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_limite_itens_duracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_limite_itens_apos_expiracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_limite_itens_reducao_percentual' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_limite_itens_tags' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'ecommerce_franquias_limite_itens_categorias' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'ecommerce_franquias_limite_itens_subcategorias' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'ecommerce_franquias_timer_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_timer_duracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_timer_resetar_remover' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_timer_visivel' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_timer_alerta_antes' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_timer_tempo_alerta' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_reserva_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_reserva_tempo' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_reserva_apos_expiracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_reserva_tempo_extensao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_franquias_reserva_notificar_cliente' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_reserva_notificar_admin' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_b2_duplicacao_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_b2_duplicacao_preco' => ['type' => 'string', 'required' => false, 'max_length' => 20],
            'ecommerce_franquias_b2_duplicacao_automatica' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_b2_duplicacao_copiar_anexos' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_b2_duplicacao_notificar_cliente' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_b2_duplicacao_aplicar_todos' => ['type' => 'boolean', 'required' => false],
            'ecommerce_franquias_b2_duplicacao_franqueados' => ['type' => 'string', 'required' => false, 'max_length' => 2000],
            // E-commerce Representantes
            'ecommerce_rep_max_representadas' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'ecommerce_rep_max_clientes' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'ecommerce_rep_max_vendedores' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'ecommerce_rep_max_assistentes' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'ecommerce_rep_limite_itens_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_limite_itens_regra' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_limite_itens_valor' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_limite_itens_duracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_limite_itens_apos_expiracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_limite_itens_reducao_percentual' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_limite_itens_tags' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'ecommerce_rep_limite_itens_categorias' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'ecommerce_rep_limite_itens_subcategorias' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'ecommerce_rep_timer_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_timer_duracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_timer_resetar_remover' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_timer_visivel' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_timer_alerta_antes' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_timer_tempo_alerta' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_reserva_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_reserva_tempo' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_reserva_apos_expiracao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_reserva_tempo_extensao' => ['type' => 'string', 'required' => false, 'max_length' => 50],
            'ecommerce_rep_reserva_notificar_cliente' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_reserva_notificar_admin' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_b2_duplicacao_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_b2_duplicacao_preco' => ['type' => 'string', 'required' => false, 'max_length' => 20],
            'ecommerce_rep_b2_duplicacao_automatica' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_b2_duplicacao_copiar_anexos' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_b2_duplicacao_notificar_cliente' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_relacionamentos_ativar' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_relacionamentos_todas_representadas' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_relacionamentos_todos_clientes' => ['type' => 'boolean', 'required' => false],
            'ecommerce_rep_relacionamentos_representadas' => ['type' => 'string', 'required' => false, 'max_length' => 2000],
            'ecommerce_rep_relacionamentos_clientes' => ['type' => 'string', 'required' => false, 'max_length' => 2000],
            // Pedidos
            'orders_aprovacao_exigir' => ['type' => 'boolean', 'required' => false],
            'orders_aprovacao_status_padrao' => ['type' => 'string', 'required' => false, 'max_length' => 20],
            'orders_aprovacao_notificar_admins' => ['type' => 'boolean', 'required' => false],
            'orders_aprovacao_notificar_vendas' => ['type' => 'boolean', 'required' => false],
            'orders_aprovacao_tempo' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'orders_aprovacao_auto_aprovar' => ['type' => 'boolean', 'required' => false],
            'orders_workflow_ativar' => ['type' => 'boolean', 'required' => false],
            'orders_workflow_etapas' => ['type' => 'string', 'required' => false, 'max_length' => 10000],
            'orders_workflow_pular_etapas' => ['type' => 'boolean', 'required' => false],
            'orders_workflow_comentario_pular' => ['type' => 'boolean', 'required' => false],
            'orders_workflow_modo_dev' => ['type' => 'boolean', 'required' => false],
            'orders_rastreamento_ativar' => ['type' => 'boolean', 'required' => false],
            'orders_rastreamento_notificar_cliente' => ['type' => 'boolean', 'required' => false],
            'orders_rastreamento_email' => ['type' => 'boolean', 'required' => false],
            'orders_rastreamento_sms' => ['type' => 'boolean', 'required' => false],
            'orders_rastreamento_mensagens_personalizadas' => ['type' => 'boolean', 'required' => false],
            'orders_rastreamento_frequencia' => ['type' => 'string', 'required' => false, 'max_length' => 20],
            'orders_notificacoes_alerta_pendentes' => ['type' => 'boolean', 'required' => false],
            'orders_notificacoes_limite_pendentes' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'orders_notificacoes_alerta_estagnados' => ['type' => 'boolean', 'required' => false],
            'orders_notificacoes_limite_estagnados' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'orders_notificacoes_notificar_gerentes' => ['type' => 'boolean', 'required' => false],
            'orders_notificacoes_escalonamento_ativar' => ['type' => 'boolean', 'required' => false],
            'orders_notificacoes_escalonamento_tempo' => ['type' => 'string', 'required' => false, 'max_length' => 10],
            'orders_relatorios_diario' => ['type' => 'boolean', 'required' => false],
            'orders_relatorios_destinatarios' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
            'orders_relatorios_estatisticas' => ['type' => 'boolean', 'required' => false],
            'orders_relatorios_itens_detalhados' => ['type' => 'boolean', 'required' => false],
            'orders_relatorios_destacar_atrasados' => ['type' => 'boolean', 'required' => false],
            'backup_auto' => [
                'type' => 'boolean',
                'required' => false
            ],
            'backup_frequency' => [
                'type' => 'string',
                'required' => false
            ],
            'backup_retention_days' => [
                'type' => 'integer',
                'required' => false
            ],
            'backup_compress' => [
                'type' => 'boolean',
                'required' => false
            ],
            'backup_to_cloud' => [
                'type' => 'boolean',
                'required' => false
            ],
            'backup_storage_type' => [
                'type' => 'string',
                'required' => false
            ],
            'backup_modules' => [
                'type' => 'string', // JSON
                'required' => false
            ],
            'backup_time' => [
                'type' => 'string',
                'required' => false
            ],
        ];

        $updates = [];
        $errors = [];
        
          unset($_POST['pdv_limite_itens']);
            unset($_POST['pdv_limite_itens_quantidade']);
            unset($_POST['pdv_tempo_carrinho']);
            unset($_POST['pdv_tempo_carrinho_minutos']);
            unset($_POST['pdv_reserva_itens']);
            unset($_POST['pdv_reserva_itens_minutos']);
            unset($_POST['pdv_tags']);
            unset($_POST['pdv_categorias']);
            unset($_POST['pdv_subcategorias']);


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
                    // Função robusta para booleano
                    if (is_bool($value)) {
                        $processed_value = $value ? 1 : 0;
                    } elseif (is_numeric($value)) {
                        $processed_value = ((int)$value) ? 1 : 0;
                    } elseif (is_string($value)) {
                        $v = strtolower(trim($value));
                        $processed_value = ($v === '1' || $v === 'true') ? 1 : 0;
                    } else {
                        $processed_value = $value ? 1 : 0;
                    }
                } elseif ($config['type'] === 'string') {
                    if (isset($config['max_length']) && strlen($value) > $config['max_length']) {
                        $errors[] = "Configuration '{$key}' exceeds maximum length of {$config['max_length']} characters";
                        $is_valid = false;
                    }
                } elseif ($config['type'] === 'integer') {
                    if (!is_numeric($value)) {
                        $errors[] = "Configuration '{$key}' must be an integer";
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
                // Verifica se já existe o registro
                $this->db->where('name', $update['name']);
                $this->db->where('warehouse_id', $warehouse_id);
                $exists = $this->db->get(db_prefix() . 'options')->row();

                if (!$exists) {
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

    public function create_menu_post()
    {

      

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);


        $this->load->library('form_validation');
        $this->form_validation->set_data($_POST);
        $this->form_validation->set_rules('value', 'Value', 'trim|required|max_length[255]');

        if ($this->form_validation->run() == FALSE) {
            $message = ['status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors()];
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            
            $tbmmenu = $this->Settings_model;

            if ($tbmmenu->save_menu($_POST)) {
                $message = ['status' => TRUE, 'message' => 'Menu item added successfully.', 'data' => $tbmmenu];
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $this->response('Error', REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
    }

    public function update_menu_patch($id = '')
    {
       
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST)) {
            $message = ['status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided'];
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $this->form_validation->set_data($_POST);
        if (empty($id) || !is_numeric($id)) {
            $message = ['status' => FALSE, 'message' => 'Invalid Menu ID'];
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $tbmmenu = $this->Settings_model->update_menu($id, $_POST);

     


            if ($tbmmenu) {
                $message = ['status' => TRUE, 'message' => 'Menu update successful.', 'data' => $tbmmenu];
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = ['status' => FALSE, 'message' => 'Menu update failed.'];
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function delete_menu_delete($id)
    {
        
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST)) {
            $message = ['status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided'];
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

       
            $tbmmenu = $this->Settings_model->delete_menu($id);

            if ($tbmmenu) {
                $message = ['status' => TRUE, 'message' => 'Menu deleted successful.', 'data' => $tbmmenu];
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = ['status' => FALSE, 'message' => 'Menu deleted failed.'];
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        
    }

    public function options_get()
    {
        // Fetch all items from the tbmmenu table using the model
        $menus = $this->Settings_model->get_menus();
    
        if (empty($menus)) {
            $this->response(['status' => FALSE, 'message' => 'No menu items found.'], REST_Controller::HTTP_NOT_FOUND);
        } else {
            // Sort menus by 'ordem', 'label', and 'value' as secondary and tertiary criteria
            usort($menus, function($a, $b) {
                if ($a['ordem'] == $b['ordem']) {
                    if ($a['label'] == $b['label']) {
                        return $a['value'] <=> $b['value'];
                    }
                    return $a['label'] <=> $b['label'];
                }
                return $a['ordem'] <=> $b['ordem'];
            });
    
            // Format the sorted menu items
            $formattedMenus = array_map(function($menu) {
                return [
                    'id' => $menu['id'],
                    'value' => $menu['value'],
                    'label' => $menu['label'],
                    'color' => $menu['color'],
                    'icon' => $menu['icon'],
                    'width' => $menu['width'],
                    'path' => $menu['path'],
                    'ordem' => $menu['ordem'],
                ];
            }, $menus);
    
            // Encapsulate the formatted menu items into the desired structure
            $responseData = [
                [
                    'type' => 'menu',
                    'category' => 'system',
                    'list' => [
                        'menu' => $formattedMenus
                    ]
                ]
            ];
    
            $this->response($responseData, REST_Controller::HTTP_OK);
        }
    }
    public function update_menus_patch()
    {
        // Retrieve and clean the input data
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
    
        if (empty($_POST) || !is_array($_POST)) {
            $message = ['status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided'];
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }
    
        // Iterate through the list of menu items to update each one
        $results = [];
        foreach ($_POST as $menu_item) {
            if (isset($menu_item['id']) && is_numeric($menu_item['id'])) {
                $id = $menu_item['id']; // Store ID before unsetting it
                unset($menu_item['id']); // Remove ID from the data to be updated
    
                // Attempt to update the menu
                $result = $this->Settings_model->update_menu($id, $menu_item);

                if ($result > 0) {
                    $results[] = ['id' => $id, 'status' => TRUE, 'message' => 'Menu updated successfully.'];
                } else {
                    $results[] = ['id' => $id, 'status' => FALSE, 'message' => 'Menu update failed.'];
                }
            } else {
                $results[] = ['status' => FALSE, 'message' => 'Invalid Menu ID'];
            }
        }
    
        // Respond with the results of each update operation
        $message = ['status' => TRUE, 'message' => 'Bulk update processed.', 'results' => $results];
        $this->response($message, REST_Controller::HTTP_OK);
    }
}

