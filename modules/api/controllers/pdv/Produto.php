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
        $this->load->model('warehouse_model');
        $this->load->library('upload');
        $this->load->model('Settings_model');
    }

    public function get_by_sku_or_commodity_post()
    {

        $id = $this->post('sku_code');
        $warehouse_id = $this->post('warehouse_id');


        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }
        $data = $this->Invoice_items_model->get_by_sku_or_commodity(
            $id,
            $warehouse_id

        );



        if ($data) {

            $this->response(
                [
                    'status' => true,
                    'total' => 1,
                    'data' => $data
                ],
                REST_Controller::HTTP_OK
            );
        } else {
            $this->response(
                [
                    'status' => false,
                    'message' => 'Produto não encontrado',
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_NOT_FOUND
            );
        }
    }

    public function list_post($id = '')
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') ?: 'DESC';
        $send = $this->post('send') ?: null;

        $status = $this->post('status');
        $category = $this->post('category');
        $subcategory = $this->post('subcategory');

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
            $end_date,
            $category,
            $subcategory,
            $warehouse_id,
            $send
        );

        $output = $this->Settings_model->get_options($warehouse_id);

        if ($data['total'] > 0) {

            $output = $this->Settings_model->get_options($warehouse_id);

            $pdv_desconto_produto = 0;

            foreach ($output as $item) {
                if ($item["name"] === "pdv_desconto_produto") {
                    $pdv_desconto_produto = $item["value"];
                    break; // Para sair do loop assim que encontrar
                }
            }


            // Adiciona 'pdv_desconto_produto' a cada elemento do array $data['data']
            /* foreach ($data['data'] as &$dataItem) {

                 if ((int) $dataItem['maxDiscount'] >= $dataItem['pdv_desconto_produto']) {
                     $dataItem['pdv_desconto_produto'] = $dataItem['maxDiscount'];
                 } else {
                     $dataItem['pdv_desconto_produto'] = $pdv_desconto_produto;
                 }
             }
            */

            // corrigido - lucas
            foreach ($data['data'] as &$dataItem) {
                $dataItem['pdv_desconto_produto'] = $pdv_desconto_produto;

                if (isset($dataItem['maxDiscount']) && $dataItem['maxDiscount'] !== null) {
                    if ((float) $dataItem['maxDiscount'] < (float) $pdv_desconto_produto) {
                        $dataItem['pdv_desconto_produto'] = $dataItem['maxDiscount'];
                    }
                }
            }

            $this->response(
                [
                    'status' => true,
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_OK
            );
        } else {
            $this->response(
                [
                    'status' => false,
                    'message' => 'Produto não encontrado',
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_NOT_FOUND
            );
        }
    }

    public function list_ecommerce_post($id = '')
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = $this->post('sortOrder') ?: 'DESC';
        $send = $this->post('send') ?: null;

        // Novos filtros
        $category = $this->post('category');
        $subcategory = $this->post('subcategory');
        $minPrice = $this->post('minPrice');
        $maxPrice = $this->post('maxPrice');
        $company = $this->post('company');

        $data = $this->Invoice_items_model->get_api2(
            $id,
            $page,
            $limit,
            $search,
            $sortField,
            $sortOrder,
            null, // statusFilter removido pois não é mais necessário
            null, // startDate removido pois não é mais necessário
            null, // endDate removido pois não é mais necessário
            $category,
            $subcategory,
            $warehouse_id,
            $send,
            $minPrice,
            $maxPrice,
            $company
        );

        if ($data['total'] > 0) {
            $this->response(
                [
                    'status' => true,
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_OK
            );
        } else {
            $this->response(
                [
                    'status' => false,
                    'message' => 'Produto não encontrado',
                    'total' => $data['total'] ?? 0,
                    'data' => $data['data'] ?? []
                ],
                REST_Controller::HTTP_NOT_FOUND
            );
        }
    }



    public function create_post()
    {
        if (isset($_POST['data'])) {
            $_POST = json_decode($_POST['data'], true);
        }

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $warehouses = $this->getWarehouses($_POST['warehouse_id']);

        if (!$warehouses) {
            $this->response(
                ['status' => FALSE, 'message' => 'No warehouses found'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }



        $createdCount = 0;
        $failedCount = 0;
        $errors = [];

        $products = isset($_POST['products']) ? $_POST['products'] : [$_POST];





        foreach ($warehouses as $warehouse) {
            foreach ($products as $index => $productData) {
                unset($productData['images_base64'], $productData['packagings'], $productData['reservedStock'], $productData['primary_image_index'], $productData['itemType']);

                if (isset($productData['price_franquia'])) {
                    $productData['cost'] = $productData['price_franquia'];
                }

                $productData['warehouse_id'] = $warehouse['warehouse_id'];
                $product_id = $this->Invoice_items_model->add($productData);

                if ($product_id) {
                    $createdCount++;

                    $upload_dir = './uploads/items/' . $product_id . '/';
                    $max_size = 10 * 1024 * 1024; // 10 MB

                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    if (isset($_FILES['images']['tmp_name'][$index])) {
                        // Verificação para garantir que os valores existence
                        $upload_dir = './uploads/items/' . $product_id . '/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_paths = []; // Um array para armazenar caminhos de arquivos únicos

                        foreach ($_FILES['images']['tmp_name'] as $key => $file_temp) {
                            $file_name = $_FILES['images']['name'][$key];
                            $file_size = $_FILES['images']['size'][$key];
                            $file_error = $_FILES['images']['error'][$key];

                            if ($file_error === UPLOAD_ERR_OK) {
                                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                                $unique_filename = uniqid() . '.' . $file_extension;
                                $upload_path = $upload_dir . $unique_filename;

                                if (move_uploaded_file($file_temp, $upload_path)) {
                                    $server_url = base_url();
                                    $relative_path = str_replace('./', '', $upload_path);
                                    $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                                    // Adicionar o caminho ao array de caminhos de arquivo
                                    array_push($file_paths, $full_url);

                                    // Insere este caminho no banco de dados para o histórico de imagens
                                    $this->db->insert(db_prefix() . 'item_images', [
                                        'item_id' => $product_id,
                                        'url' => $full_url,
                                        'name' => $relative_path
                                    ]);
                                }
                            }
                        }

                        // Atualizando as colunas específicas com o número de imagens disponíveis
                        $update_data = [];

                        if (!empty($file_paths[0]))
                            $update_data['image'] = $file_paths[0];
                        if (!empty($file_paths[1]))
                            $update_data['image2'] = $file_paths[1];
                        if (!empty($file_paths[2]))
                            $update_data['image3'] = $file_paths[2];
                        if (!empty($file_paths[3]))
                            $update_data['image4'] = $file_paths[3];
                        if (!empty($file_paths[4]))
                            $update_data['image5'] = $file_paths[4];

                        if (!empty($update_data)) {
                            $this->db->where('id', $product_id);
                            $this->db->update(db_prefix() . 'items', $update_data);
                        }
                    }

                } else {
                    $failedCount++;
                    $errors[] = [
                        'warehouse_id' => $warehouse['warehouse_id'],
                        'message' => 'Failed to create product for warehouse ID: ' . $warehouse['warehouse_id']
                    ];
                }
            }
        }

        $message = [
            'status' => TRUE,
            'message' => 'Products creation summary',
            'created_count' => $createdCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ];

        $this->response($message, REST_Controller::HTTP_OK);
    }


    public function create1_post()
    {


        // $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);






        if (isset($_POST['data'])) {

            $_POST = (array) json_decode($_POST['data'], true);

        }




        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $warehouses = $this->getWarehouses($_POST['warehouse_id']);

        if (!$warehouses) {
            $this->response(
                ['status' => FALSE, 'message' => 'No warehouses found'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $createdCount = 0;
        $failedCount = 0;
        $errors = [];

        // Verificando se $_POST['products'] é um array. Se não for, converta para array.
        $products = isset($_POST['products']) ? $_POST['products'] : [$_POST];




        foreach ($warehouses as $warehouse) {
            foreach ($products as $productData) {


                unset($productData['images_base64']);
                unset($productData['packagings']);
                unset($productData['reservedStock']);
                unset($productData['primary_image_index']);
                unset($productData['itemType']);


                if (isset($productData['price_franquia'])) {
                    $productData['cost'] = $productData['price_franquia'];

                }




                $dataToValidate = array_merge($productData, $warehouse);

                /*

                $this->form_validation->set_data($dataToValidate);
                $this->form_validation->set_rules('description', 'Description', 'trim|required|max_length[600]');
                $this->form_validation->set_rules('rate', 'Rate', 'numeric');
                $this->form_validation->set_rules('stock', 'Stock', 'numeric');
                $this->form_validation->set_rules('minStock', 'Minimum Stock', 'numeric');
                $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

                if ($this->form_validation->run() == FALSE) {
                    $errors[] = [
                        'warehouse_id' => $warehouse['warehouse_id'],
                        'error' => $this->form_validation->error_array(),
                        'message' => validation_errors()
                    ];
                    $failedCount++;
                    continue;
                }
                    */

                $productData['warehouse_id'] = $warehouse['warehouse_id'];
                $product_id = $this->Invoice_items_model->add($productData);

                if ($product_id) {
                    $createdCount++;


                    // Configure o diretório de upload e outros parâmetros
                    $upload_dir = './uploads/items/' . $product_id . '/';
                    $max_size = 10 * 1024 * 1024; // 10 MB em bytes


                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }


                    $file_temp = $_FILES['image']['tmp_name'];
                    $file_name = $_FILES['image']['name'];
                    $file_size = $_FILES['image']['size'];
                    $file_error = $_FILES['image']['error'];

                    // Verifique se houve um erro no upload
                    if ($file_error === UPLOAD_ERR_OK) {
                        // Verifica se o tamanho do arquivo é maior que 4 MB
                        /*
                        if ($file_size > $max_size) {
                            echo json_encode(['status' => FALSE, 'error' => 'File exceeds the maximum allowed size of 4 MB.']);
                            return;
                        }
                            */

                        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $unique_filename = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $unique_filename;

                        // Mover o arquivo carregado para o diretório final
                        if (move_uploaded_file($file_temp, $upload_path)) {
                            $server_url = base_url();
                            $relative_path = str_replace('./', '', $upload_path);
                            $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                            // Atualiza o banco de dados com o novo caminho da imagem
                            $this->db->where('id', $product_id);
                            $this->db->update(db_prefix() . 'items', ['image' => $full_url]);

                        }
                    }
                    /*
                    else {
                        echo json_encode(['status' => FALSE, 'error' => 'Upload error. Code: ' . $file_error]);
                        return;
                    }
                        */





                } else {
                    $errors[] = [
                        'warehouse_id' => $warehouse['warehouse_id'],
                        'message' => 'Failed to create product for warehouse ID: ' . $warehouse['warehouse_id']
                    ];
                    $failedCount++;
                }
            }
        }

        $message = [
            'status' => TRUE,
            'message' => 'Products creation summary',
            'created_count' => $createdCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ];

        $this->response($message, REST_Controller::HTTP_OK);
    }
    public function include_products_nf_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['data']['items']) || !is_array($_POST['data']['items'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Items array is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $items = $_POST['data']['items'];
        $warehouse_id = null;
        $createdCount = 0;
        $failedCount = 0;
        $errors = [];

        // Verificar se todos os itens têm o mesmo warehouse_id
        foreach ($items as $item) {
            if (empty($item['warehouse_id'])) {
                $this->response(
                    ['status' => FALSE, 'message' => 'Warehouse ID is required in each item'],
                    REST_Controller::HTTP_BAD_REQUEST
                );
                return;
            }

            if ($warehouse_id === null) {
                $warehouse_id = $item['warehouse_id'];
            } else if ($warehouse_id !== $item['warehouse_id']) {
                $this->response(
                    ['status' => FALSE, 'message' => 'All items must have the same warehouse_id'],
                    REST_Controller::HTTP_BAD_REQUEST
                );
                return;
            }
        }

        $warehouses = $this->getWarehouses($warehouse_id);

        if (!$warehouses) {
            $this->response(
                ['status' => FALSE, 'message' => 'No warehouses found'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        foreach ($warehouses as $warehouse) {
            foreach ($items as $item) {
                // Validação básica dos campos obrigatórios
                $this->form_validation->set_data($item);
                $this->form_validation->set_rules('description', 'Description', 'trim|required|max_length[600]');
                $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

                if ($this->form_validation->run() == FALSE) {
                    $errors[] = [
                        'warehouse_id' => $warehouse['warehouse_id'],
                        'item' => $item,
                        'error' => $this->form_validation->error_array(),
                        'message' => validation_errors()
                    ];
                    $failedCount++;
                    continue;
                }

                // Adicionar campos padrão se não existirem
                $item['status'] = $item['status'] ?? 'approved';
                $item['product_unit'] = $item['product_unit'] ?? 'unidade';
                $item['createdAt'] = date('Y-m-d H:i:s');
                $item['updatedAt'] = date('Y-m-d H:i:s');

                $product_id = $this->Invoice_items_model->add_products_nf($item);

                if ($product_id) {
                    $createdCount++;
                } else {
                    $errors[] = [
                        'warehouse_id' => $warehouse['warehouse_id'],
                        'item' => $item,
                        'message' => 'Failed to create product'
                    ];
                    $failedCount++;
                }
            }
        }

        $message = [
            'status' => TRUE,
            'message' => 'Products creation summary',
            'created_count' => $createdCount,
            'failed_count' => $failedCount,
            'errors' => $errors
        ];

        $this->response($message, REST_Controller::HTTP_OK);
    }

    public function upload_put($item_id)
    {
        $raw_body = $this->input->raw_input_stream;

        $content_type = isset($this->input->request_headers()['Content-Type']) ? $this->input->request_headers()['Content-Type'] : (isset($this->input->request_headers()['content-type']) ? $this->input->request_headers()['content-type'] : null);

        if (!$content_type) {
            echo json_encode(['status' => FALSE, 'message' => 'Content-Type header is missing']);
            return;
        }

        if (preg_match('/boundary=(.*)$/', $content_type, $matches)) {
            if (isset($matches[1])) {
                $boundary = '--' . trim($matches[1]);
            } else {
                echo json_encode(['status' => FALSE, 'message' => 'Invalid boundary in Content-Type']);
                return;
            }
        } else {
            echo json_encode(['status' => FALSE, 'message' => 'Boundary not found in Content-Type']);
            return;
        }

        $parts = explode($boundary, $raw_body);

        $upload_dir = './uploads/items/' . $item_id . '/';

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
                    $allowed_types = ['jpeg', 'jpg', 'png'];
                    $file_size = strlen($file_content);
                    if (!in_array(strtolower($extension), $allowed_types)) {
                        echo json_encode(['status' => FALSE, 'message' => 'Tipo de arquivo não permitido.']);
                        return;
                    }

                    $max_file_size = 2 * 1024 * 1024; // 2MB
                    if ($file_size > $max_file_size) {
                        echo json_encode(['status' => FALSE, 'message' => 'O arquivo é muito grande.']);
                        return;
                    }

                    $unique_filename = uniqid() . '.' . $extension;
                    $upload_path = $upload_dir . $unique_filename;

                    if (file_put_contents($upload_path, $file_content)) {
                        $server_url = base_url();
                        $relative_path = str_replace('./', '', $upload_path);
                        $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                        $this->db->where('id', $item_id);
                        $this->db->update(db_prefix() . 'items', ['image' => $full_url]);

                        echo json_encode(['status' => TRUE, 'file' => $full_url]);
                        return;
                    }
                }
            }
        }

        echo json_encode(['status' => FALSE, 'message' => 'Nenhuma parte de arquivo encontrada.']);
    }

    public function upload_mult_put($product_id)
    {

        $raw_body = $this->input->raw_input_stream;

        preg_match('/boundary=(.*)$/', $this->input->request_headers()['Content-Type'], $matches);
        $boundary = '--' . trim($matches[1]);

        $parts = explode($boundary, $raw_body);
        $uploaded_files = [];

        foreach ($parts as $part) {
            if (strpos($part, 'Content-Disposition:') !== false) {
                preg_match('/name="([^"]+)"/', $part, $name_match);
                preg_match('/filename="([^"]+)"/', $part, $filename_match);
                preg_match('/Content-Type: ([\S]+)/', $part, $type_match);

                if (isset($filename_match[1])) {
                    $file_content_start = strpos($part, "\r\n\r\n") + 4;
                    $file_content = substr($part, $file_content_start, -4);

                    $upload_path = './uploads/' . $filename_match[1];

                    file_put_contents($upload_path, $file_content);

                    $uploaded_files[] = $upload_path;
                }
            }
        }

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

        if ($product) {

            if (!empty($product->group_id)) {
                $this->db->select('name as category_name');
                $this->db->where('id', $product->group_id);
                $category = $this->db->get(db_prefix() . 'items_groups')->row();
                $product->category_name = $category ? $category->category_name : null;
            } else {
                $product->category_name = null;
            }

            // Get subcategory name
            if (!empty($product->sub_group)) {
                $this->db->select('sub_group_name');
                $this->db->where('id', $product->sub_group);
                $subcategory = $this->db->get(db_prefix() . 'wh_sub_group')->row();
                $product->subcategory_name = $subcategory ? $subcategory->sub_group_name : null;
            } else {
                $product->subcategory_name = null;
            }

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

        $warehouses = $this->getWarehouses($_POST['warehouse_id']);

        if (!$warehouses) {
            $this->response(
                ['status' => FALSE, 'message' => 'No warehouses found'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $createdCount = 0;
        $failedCount = 0;
        $errors = [];


        foreach ($ids as $id) {
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }
            if (count($warehouses) > 1) {

                $item = $this->Invoice_items_model->get($id);
                $sku = $item->sku_code;
                if ($sku != "") {

                    $output = $this->Invoice_items_model->delete_by_sku($sku);
                } else {

                    $output = $this->Invoice_items_model->delete($id);

                }

            } else {

                $output = $this->Invoice_items_model->delete($id);
            }



            if ($output === TRUE) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        if ($success_count > 0) {
            $message = array(
                'status' => TRUE,
                'message' => $success_count . ' products(s) deleted successfully'
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
            $message = array('status' => FALSE, 'message' => 'Invalid Products ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {


            $warehouses = $this->getWarehouses($_POST['warehouse_id']);

            if (!$warehouses) {
                $this->response(
                    ['status' => FALSE, 'message' => 'No warehouses found'],
                    REST_Controller::HTTP_BAD_REQUEST
                );
                return;
            }

            $update_data = $this->input->post();
            $this->load->model('Invoice_items_model');
            unset($update_data['warehouse_id']);
            unset($update_data['images_base64']);
            unset($update_data['packaging']);
            $update_data['cost'] = $update_data['price_franquia'];

            if (count($warehouses) > 1) {

                if ($_POST['sku_code'] != "") {
                    $item = $this->Invoice_items_model->get($id);

                    $sku = $item->sku_code;

                    foreach ($warehouses as $warehouse) {




                        if ($warehouse['type'] != 'distribuidor') {
                            unset($update_data['sku_code']);
                        }

                        $output = $this->Invoice_items_model->edit_by_sku($update_data, $sku, $warehouse['warehouse_id']);

                    }


                } else {

                    $output = $this->Invoice_items_model->edit($update_data, $id);
                }

            } else {

                $output = $this->Invoice_items_model->edit($update_data, $id);
            }

            if ($output > 0 && !empty($output)) {


                log_activity('Produto atualizado com [Name: Teste]', 1);

                $message = array('status' => TRUE, 'message' => 'Products Update Successful.', 'data' => $this->Invoice_items_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Item Atualizado.');
                $this->response($message, REST_Controller::HTTP_OK);
            }
        }
    }

    public function data_post($id)
    {
        if (isset($_POST['data'])) {
            $_POST = json_decode($_POST['data'], true);
        }

        if (empty($_POST) || !isset($id) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Data Not Acceptable or Invalid Product ID'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verifica se o produto existe
        $product = $this->Invoice_items_model->get($id);
        if (!$product) {
            $this->response(['status' => FALSE, 'message' => 'Product not found'], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Prepare os dados para atualização
        $update_data = $_POST;

        unset($update_data['image'], $update_data['image1'], $update_data['image2'], $update_data['image3'], $update_data['image4'], $update_data['images_base64'], $update_data['packaging'], $update_data['packagings'], $update_data['reservedStock'], $update_data['primary_image_index'], $update_data['itemType'], $update_data['total_images']);

        if (isset($update_data['price_franquia'])) {
            $update_data['cost'] = $update_data['price_franquia'];
        }

        // Atualiza os dados do produto
        $update_result = $this->Invoice_items_model->edit($update_data, $id);

        // Processa as imagens
        $upload_dir = './uploads/items/' . $id . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Remove todas as imagens antigas do banco
        $this->db->where('item_id', $id);
        $this->db->delete(db_prefix() . 'item_images');

        // Inicializa todos os campos de imagem como null
        $file_paths = ['image' => null, 'image2' => null, 'image3' => null, 'image4' => null, 'image5' => null];

        if (isset($_FILES['images'])) {
            // Se existem imagens carregadas, processa cada uma
            $files = $_FILES['images']['tmp_name'];
            $names = $_FILES['images']['name'];
            $errors = $_FILES['images']['error'];

            $image_count = 0;

            foreach ($files as $key => $file_temp) {
                if ($errors[$key] === UPLOAD_ERR_OK && $image_count < 5) {
                    $file_name = $names[$key];
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $unique_filename = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $unique_filename;

                    if (move_uploaded_file($file_temp, $upload_path)) {
                        $server_url = base_url();
                        $relative_path = str_replace('./', '', $upload_path);
                        $full_url = rtrim($server_url, '/') . '/' . $relative_path;

                        // Atualiza o campo correspondente baseado na posição
                        $field_name = array_keys($file_paths)[$key];
                        $file_paths[$field_name] = $full_url;

                        // Inserir a imagem no banco de dados
                        $this->db->insert(db_prefix() . 'item_images', [
                            'item_id' => $id,
                            'url' => $full_url,
                            'name' => $relative_path
                        ]);

                        $image_count++;
                        log_message('debug', 'Processed image ' . $image_count . ' - Field: ' . $field_name);
                    }
                }
            }
        }

        // Atualiza as colunas específicas de imagens
        $update_cols = [
            'image' => $file_paths['image'],
            'image2' => $file_paths['image2'],
            'image3' => $file_paths['image3'],
            'image4' => $file_paths['image4'],
            'image5' => $file_paths['image5']
        ];

        log_message('debug', 'Updating image fields: ' . json_encode($update_cols));

        // Atualiza cada campo
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'items', $update_cols);

        // Após atualizar as imagens, podemos retornar uma mensagem de sucesso
        $this->response(['status' => TRUE, 'message' => 'Produto atualizado com sucesso.', 'data' => $this->Invoice_items_model->get($id)], REST_Controller::HTTP_OK);
    }


    public function groups_post()
    {

        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 30;
        $search = $this->post('search') ?: '';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('g.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'wh_sub_group WHERE group_id = g.id ) as subcategories_count,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = g.id ) as total_products
        ');
        $this->db->from(db_prefix() . 'items_groups g');
        //  $this->db->where('g.warehouse_id', $warehouse_id);

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

        $this->response([
            'status' => TRUE,
            'total' => $total,
            'data' => $groups
        ], REST_Controller::HTTP_OK);
    }

    public function subgroups_post($group_id)
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        if (empty($group_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Group ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        // First verify if the group belongs to this warehouse
        $this->db->where('id', $group_id);
        // $this->db->where('warehouse_id', $warehouse_id);
        $group = $this->db->get(db_prefix() . 'items_groups')->row();

        if (!$group) {
            $this->response([
                'status' => TRUE,
                'total' => 0,
                'data' => []
            ], REST_Controller::HTTP_OK);
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 30;
        $search = $this->post('search') ?: '';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('sg.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = ' . $this->db->escape($group_id) . ' 
            AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as total_products');
        $this->db->from(db_prefix() . 'wh_sub_group sg');
        $this->db->where('sg.group_id', $group_id);
        //  $this->db->where('sg.warehouse_id', $warehouse_id);

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

        $this->response([
            'status' => TRUE,
            'total' => $total,
            'data' => $subgroups
        ], REST_Controller::HTTP_OK);
    }

    function generate_pdf_post()
    {
        try {
            $pdf = generic_pdf(array());
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        $estimate_number = format_estimate_number($estimate->id);
        $companyname = get_option('invoice_company_name');
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

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->form_validation->set_rules('name', 'Name', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $_POST['warehouse_id'] = (int) $_POST['warehouse_id'];
            $output = $this->Invoice_items_model->add_group($_POST);
            if ($output > 0 && !empty($output)) {
                $message = array('status' => 'success', 'message' => 'Group added successfully');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Failed to add group.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function subgroupcreate_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->form_validation->set_rules('group_id', 'Group ID', 'trim|required|numeric');
        $this->form_validation->set_rules('sub_group_name', 'Subgroup Name', 'trim|required|max_length[100]');
        $this->form_validation->set_rules('warehouse_id', 'Warehouse', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors()
            );
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verify if the group belongs to this warehouse
        $this->db->where('id', $_POST['group_id']);
        //  $this->db->where('warehouse_id', $_POST['warehouse_id']);
        $group = $this->db->get(db_prefix() . 'items_groups')->row();

        if (!$group) {
            $this->response([
                'status' => FALSE,
                'message' => 'Group not found in this warehouse'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data = array(
            'group_id' => $_POST['group_id'],
            'sub_group_name' => $_POST['sub_group_name'],
            'warehouse_id' => $_POST['warehouse_id'],
            'display' => 1,
            'order' => 0
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
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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

    public function supplier_needs_post()
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $this->db->select('
            c.userid as supplier_id,
            c.company as supplier_name,
            c.vat as supplier_document,
            c.phonenumber as supplier_phone,
            c.min_purchase,
            COUNT(DISTINCT pn.id) as total_items,
            SUM(pn.qtde) as total_quantity,
            SUM(pn.qtde * i.cost) as total_cost
        ');

        $this->db->from(db_prefix() . 'items i');
        $this->db->join(db_prefix() . 'purchase_needs pn', 'pn.item_id = i.id');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = pn.user_id');
        $this->db->where('pn.status', 0);
        $this->db->where('c.is_supplier', 1);
        $this->db->where('i.warehouse_id', $warehouse_id);
        $this->db->group_by('c.userid');
        $this->db->having('COUNT(DISTINCT pn.id) > 0');

        $suppliers = $this->db->get()->result_array();

        //  $last_query = $this->db->last_query();
        //    echo $last_query;

        $this->response([
            'status' => TRUE,
            'data' => $suppliers
        ], REST_Controller::HTTP_OK);
    }

    public function check_stock_post()
    {
        $supplier_id = $this->post('supplier_id');
        $warehouse_id = $this->post('warehouse_id');

        if (empty($supplier_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Supplier ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->select('
            pn.id,
            pn.item_id,
            pn.warehouse_id,
            pn.qtde,
            CAST(pn.status AS CHAR) as purchase_status,
            pn.date,
            CAST(pn.user_id AS CHAR) as user_id,
            CAST(pn.invoice_id AS CHAR) as invoice_id,
            i.description as product_name,
            i.sku_code,
            i.stock,
            i.cost,
            i.defaultPurchaseQuantity,
            i.minStock,
            c.company as supplier_name,
            c.vat as supplier_document
        ');

        $this->db->from(db_prefix() . 'purchase_needs pn');
        $this->db->join(db_prefix() . 'items i', 'i.id = pn.item_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = pn.user_id', 'left');
        $this->db->where('pn.status', 0);
        $this->db->where('pn.user_id', $supplier_id);
        $this->db->where('i.warehouse_id', $warehouse_id);
        //  $this->db->where('c.is_supplier', 1);



        $products = $this->db->get()->result_array();

        // $last_query = $this->db->last_query();
        //  echo $last_query;

        $total_cost = 0;
        $purchase_needs = [];

        foreach ($products as $product) {
            $item_total = $product['qtde'] * $product['cost'];
            $total_cost += $item_total;

            $purchase_needs[] = [
                'id' => $product['id'],
                'product' => [
                    'id' => $product['item_id'],
                    'name' => $product['product_name'],
                    'sku' => $product['sku_code']
                ],
                'warehouse_id' => $product['warehouse_id'],
                'currentStock' => (int) $product['stock'],
                'minimumStock' => (int) $product['minStock'],
                'quantity' => (int) $product['qtde'],
                'cost' => (float) $product['cost'],
                'total' => $item_total,
                'defaultPurchaseQuantity' => (int) $product['defaultPurchaseQuantity'],
                'status' => $product['purchase_status'],
                'user_id' => $product['user_id'],
                'date' => $product['date'],
                'invoice_id' => $product['invoice_id']
            ];
        }

        $supplier_info = [
            'supplier_id' => $supplier_id,
            'supplier_name' => empty($products) ? '' : $products[0]['supplier_name'],
            'supplier_document' => empty($products) ? '' : $products[0]['supplier_document'],
            'total_items' => count($products),
            'total_quantity' => array_sum(array_column($products, 'qtde')),
            'total_cost' => $total_cost,
            'items' => $purchase_needs
        ];

        $this->response([
            'status' => TRUE,
            'data' => $supplier_info
        ], REST_Controller::HTTP_OK);
    }

    public function import_post()
    {
        ini_set('max_execution_time', 1200); // 300 segundos = 5 minutos


        if (empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $produtos_existentes = $this->Invoice_items_model->totalItens($_POST['warehouse_id']);

        if ($produtos_existentes > 0) {
            $this->response([
                'status' => FALSE,
                'message' => 'Ja existe produtos cadastrados'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }



        if (!isset($_FILES['file']) || empty($_FILES['file'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No file was uploaded'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['mapping'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'No field mapping provided'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $file = $_FILES['file'];
        $mapping = json_decode($_POST['mapping'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid mapping JSON format'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $mapping = array_filter($mapping, function ($value) {
            return !empty($value);
        });

        if (!isset($mapping['description']) || !isset($mapping['rate'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'description and rate fields are required in the mapping'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'csv') {
            $this->response([
                'status' => FALSE,
                'message' => 'Only CSV files are allowed'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => 'Failed to read file'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $headers = fgetcsv($handle);
        if ($headers === FALSE) {
            fclose($handle);
            $this->response([
                'status' => FALSE,
                'message' => 'Empty CSV file'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $success_count = 0;
        $errors = [];
        $rows = [];

        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count(array_filter($row)) > 0) {
                $rows[] = $row;
            }
        }



        try {
            $this->db->trans_start();

            $warehouse = $this->warehouse_model->get($_POST['warehouse_id']);

            if ($warehouse->type == 'distribuidor') {

                $warehouses = $this->warehouse_model->get("", "(type = 'filial' OR type = 'franquia'  OR type = 'distribuidor'  )");
            } else {

                $warehouses[0] = (array) $warehouse;
            }
            foreach ($warehouses as $wr) {


                foreach ($rows as $index => $row) {
                    $current_row = $index + 2;
                    $product_data = ['warehouse_id' => $wr['warehouse_id']];

                    foreach ($mapping as $field => $csv_column) {
                        $column_index = array_search($csv_column, $headers);
                        if ($column_index !== FALSE && isset($row[$column_index])) {
                            $value = trim($row[$column_index]);
                            if (!empty($value)) {
                                $product_data[$field] = $value;

                                if ($field == 'group_name') {

                                    $category = $this->Invoice_items_model->get_category_id_by_name($value);
                                    if ($category) {
                                        $product_data['group_id'] = $category->id;
                                    }
                                    unset($product_data['group_name']);
                                }

                                if ($field == 'rate') {

                                    $product_data['price_cliente_final'] = $value;
                                }

                                if ($field == 'sku_code') {

                                    $product_data['commodity_barcode'] = $value;
                                    $product_data['commodity_code'] = $value;
                                    $product_data['code'] = $value;
                                }



                                if ($field == 'unit_name') {

                                    $unit = $this->Invoice_items_model->get_unit_id_by_name($value);
                                    if ($unit) {
                                        $product_data['unit_id'] = $unit->id;
                                    }
                                    unset($product_data['unit_name']);
                                }
                            }
                        }
                    }


                    if (!isset($product_data['description']) || empty($product_data['description'])) {
                        $errors[] = "Row {$current_row}: Description is required";
                        continue;
                    }

                    if (!isset($product_data['rate']) || floatval($product_data['rate']) <= 0) {
                        $errors[] = "Row {$current_row}: Rate must be greater than zero";
                        continue;
                    }

                    $product_id = $this->Invoice_items_model->add($product_data);
                    if ($product_id) {
                        $success_count++;
                    } else {
                        $errors[] = "Row {$current_row}: Failed to insert product";
                    }
                }
            }

            $this->db->trans_complete();

            fclose($handle);

            if ($this->db->trans_status() === FALSE) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Transaction failed',
                    'errors' => $errors
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }

            $response = [
                'status' => TRUE,
                'message' => "{$success_count} products imported successfully",
                'total_rows' => count($rows)
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
            }

            $this->response($response, REST_Controller::HTTP_OK);
        } catch (Exception $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            $this->response([
                'status' => FALSE,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => $errors
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getWarehouses($warehouse_id)
    {


        $warehouse = $this->warehouse_model->get($warehouse_id);

        if ($warehouse->type == 'distribuidor') {

            $warehouses = $this->warehouse_model->get("", "(type = 'filial' OR type = 'franquia'  OR type = 'distribuidor'  )");
        } else {

            $warehouses[] = (array) $warehouse;
        }

        return $warehouses;
    }

    public function units_post()
    {
        $warehouse_id = $this->post('warehouse_id');

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $page = $this->post('page') ? (int) $this->post('page') : 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'order';
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $this->db->select('
            unit_type_id,
            unit_code,
            unit_name,
            unit_symbol,
            `order`,
            display,
            note
        ');
        $this->db->from(db_prefix() . 'ware_unit_type');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('unit_code', $search);
            $this->db->or_like('unit_name', $search);
            $this->db->or_like('unit_symbol', $search);
            $this->db->or_like('note', $search);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $units = $this->db->get()->result_array();

        $units = array_map(function ($unit) {
            $unit['display'] = (bool) $unit['display'];
            return $unit;
        }, $units);

        $this->response([
            'status' => TRUE,
            'total' => $total,
            'data' => $units
        ], REST_Controller::HTTP_OK);
    }

    public function category_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Category ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $this->get('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->select('g.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'wh_sub_group WHERE group_id = g.id AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as subcategories_count,
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items WHERE group_id = g.id AND warehouse_id = ' . $this->db->escape($warehouse_id) . ') as total_products
        ');
        $this->db->from(db_prefix() . 'items_groups g');
        $this->db->where('g.id', $id);
        $this->db->where('g.warehouse_id', $warehouse_id);

        $category = $this->db->get()->row_array();

        if ($category) {
            $this->response([
                'status' => TRUE,
                'data' => $category
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Category not found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function category_subcategories_get($category_id = '')
    {
        if (empty($category_id) || !is_numeric($category_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Category ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $this->get('warehouse_id');
        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db->where('id', $category_id);
        $this->db->where('warehouse_id', $warehouse_id);
        $category = $this->db->get(db_prefix() . 'items_groups')->row();

        if (!$category) {
            $this->response([
                'status' => FALSE,
                'message' => 'Category not found'
            ], REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        // Get subcategories
        $this->db->select('sg.*, 
            (SELECT COUNT(*) FROM ' . db_prefix() . 'items 
            WHERE group_id = ' . $this->db->escape($category_id) . ' 
            AND warehouse_id = ' . $this->db->escape($warehouse_id) . '
            AND sub_group = sg.id) as products_count'
        );
        $this->db->from(db_prefix() . 'wh_sub_group sg');
        $this->db->where('sg.group_id', $category_id);
        $this->db->where('sg.warehouse_id', $warehouse_id);
        $this->db->order_by('sg.sub_group_name', 'ASC');

        $subcategories = $this->db->get()->result_array();

        $this->response([
            'status' => TRUE,
            'data' => [
                'category' => $category,
                'subcategories' => $subcategories
            ]
        ], REST_Controller::HTTP_OK);
    }

    public function ncm_get()
    {
        $search = $this->get('search') ?: '';
        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('pageSize') ? (int) $this->get('pageSize') : 10;
        $category = $this->get('category') ?: '';
        $sortField = $this->get('sortField') ?: 'code';
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $result = $this->Invoice_items_model->get_ncm($search, $page, $limit, $category, $sortField, $sortOrder);

        $this->response(
            array_merge(['status' => TRUE], $result),
            REST_Controller::HTTP_OK
        );
    }

    public function bulk_create_post()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_erros', 1);
        error_reporting(E_ALL);

        $raw_data = file_get_contents("php://input");

        $input = json_decode($raw_data, true);

        if (empty($input) || !isset($input['products']) || !is_array($input['products'])) {
            $this->response(
                ['status' => FALSE, 'message' => 'Invalid request format. Expected products array.'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $products = $input['products'];
        $warehouse_id = $input['warehouse_id'] ?? null;

        if (empty($warehouse_id) && !empty($products) && isset($products[0]['warehouse_id'])) {
            $warehouse_id = $products[0]['warehouse_id'];
        }

        if (empty($warehouse_id)) {
            $this->response(
                ['status' => FALSE, 'message' => 'Warehouse ID is required'],
                REST_Controller::HTTP_BAD_REQUEST
            );
            return;
        }

        $result = $this->Invoice_items_model->bulk_create_products($products, $warehouse_id);

        if ($result['status']) {
            $this->response($result, REST_Controller::HTTP_OK);
        } else {
            $this->response($result, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    public function default_image_post($product_id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        $item = $this->Invoice_items_model->get($product_id);
        $image = $item->image;
        $image2 = $item->image2;
        $image3 = $item->image3;
        $image4 = $item->image4;
        $image5 = $item->image5;

        if ($_POST['image_index'] == 0) {
            $update_data = array('image' => $image);
        } elseif ($_POST['image_index'] == 1) {
            $update_data = array('image' => $image2, 'image2' => $image);
        } elseif ($_POST['image_index'] == 2) {
            $update_data = array('image' => $image3, 'image3' => $image);
        } elseif ($_POST['image_index'] == 3) {
            $update_data = array('image' => $image4, 'image4' => $image);
        } elseif ($_POST['image_index'] == 4) {
            $update_data = array('image' => $image5, 'image5' => $image);
        }

        $this->db->where('id', $product_id);
        $result = $this->db->update(db_prefix() . 'items', $update_data);

        $this->response(array('status' => true, 'message' => 'Atualizado com sucesso'), REST_Controller::HTTP_OK);
    }


    public function edit_product_erp_put($product_id = '')
    {
        $raw_data = file_get_contents("php://input");
        $input = json_decode($raw_data, true);

        $result = $this->Invoice_items_model->bulk_create_products([$input], null, $product_id);

        $this->response($result, $result['status'] ? REST_Controller::HTTP_OK : REST_Controller::HTTP_BAD_REQUEST);
    }

    public function category_checker_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['warehouse_id'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        if (!isset($_POST['names']) || !is_array($_POST['names']) || empty($_POST['names'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Names array is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $warehouse_id = $_POST['warehouse_id'];
        $input_names = $_POST['names'];
        $similarity_threshold = isset($_POST['similarity_threshold']) ? (float) $_POST['similarity_threshold'] : 80.0;

        $this->db->select('id, name');
        $this->db->from(db_prefix() . 'items_groups');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->order_by('name', 'ASC');
        $existing_categories = $this->db->get()->result_array();

        $results = [];

        foreach ($input_names as $input_name) {
            $input_name = trim($input_name);
            if (empty($input_name)) {
                continue;
            }

            $best_matches = [];
            $exact_match = null;

            foreach ($existing_categories as $existing) {
                $existing_name = $existing['name'];

                if (strcasecmp($input_name, $existing_name) === 0) {
                    $exact_match = [
                        'id' => $existing['id'],
                        'name' => $existing_name,
                        'similarity_score' => 100,
                        'match_type' => 'exact'
                    ];
                    break;
                }

                $similarity_percent = 0;
                similar_text(strtolower($input_name), strtolower($existing_name), $similarity_percent);

                $levenshtein_distance = levenshtein(strtolower($input_name), strtolower($existing_name));
                $max_length = max(strlen($input_name), strlen($existing_name));
                $levenshtein_similarity = $max_length > 0 ? (($max_length - $levenshtein_distance) / $max_length) * 100 : 0;

                $metaphone_input = metaphone($input_name);
                $metaphone_existing = metaphone($existing_name);
                $metaphone_match = ($metaphone_input === $metaphone_existing);

                $final_similarity = max($similarity_percent, $levenshtein_similarity);
                if ($metaphone_match && $final_similarity < 60) {
                    $final_similarity = 60;
                }

                if ($final_similarity >= $similarity_threshold) {
                    $match_type = 'similar';
                    if ($metaphone_match) {
                        $match_type = 'phonetic';
                    }
                    if ($final_similarity >= 95) {
                        $match_type = 'very_similar';
                    }

                    $best_matches[] = [
                        'id' => $existing['id'],
                        'name' => $existing_name,
                        'similarity_score' => round($final_similarity, 2),
                        'match_type' => $match_type,
                        'levenshtein_distance' => $levenshtein_distance,
                        'metaphone_match' => $metaphone_match
                    ];
                }
            }

            usort($best_matches, function ($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });

            $best_matches = array_slice($best_matches, 0, 5);

            $result_item = [
                'input_name' => $input_name,
                'exact_match' => $exact_match,
                'similar_matches' => $best_matches,
                'has_matches' => ($exact_match !== null || !empty($best_matches)),
                'recommendation' => $this->getCategoryRecommendation($exact_match, $best_matches, $input_name)
            ];

            $results[] = $result_item;
        }

        $this->response([
            'status' => TRUE,
            'data' => $results,
            'summary' => [
                'total_checked' => count($input_names),
                'exact_matches' => count(array_filter($results, function ($r) {
                    return $r['exact_match'] !== null;
                })),
                'similar_matches' => count(array_filter($results, function ($r) {
                    return !empty($r['similar_matches']);
                })),
                'no_matches' => count(array_filter($results, function ($r) {
                    return !$r['has_matches'];
                }))
            ]
        ], REST_Controller::HTTP_OK);
    }

    private function getCategoryRecommendation($exact_match, $similar_matches, $input_name)
    {
        if ($exact_match) {
            return [
                'action' => 'use_existing',
                'category_id' => $exact_match['id'],
                'category_name' => $exact_match['name'],
                'reason' => 'Exact match found'
            ];
        }

        if (!empty($similar_matches)) {
            $best_match = $similar_matches[0];
            if ($best_match['similarity_score'] >= 90) {
                return [
                    'action' => 'use_existing',
                    'category_id' => $best_match['id'],
                    'category_name' => $best_match['name'],
                    'reason' => 'Very high similarity (' . $best_match['similarity_score'] . '%)'
                ];
            } else {
                return [
                    'action' => 'review_similar',
                    'suggested_category_id' => $best_match['id'],
                    'suggested_category_name' => $best_match['name'],
                    'reason' => 'Similar category found (' . $best_match['similarity_score'] . '%) - manual review recommended'
                ];
            }
        }

        return [
            'action' => 'create_new',
            'suggested_name' => $input_name,
            'reason' => 'No similar categories found'
        ];
    }

    public function check_fields_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (!isset($_POST['product_ids']) || !is_array($_POST['product_ids']) || empty($_POST['product_ids'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Product IDs array is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $product_ids = $_POST['product_ids'];
        $warehouse_id = $_POST['warehouse_id'] ?? null;

        if (empty($warehouse_id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Warehouse ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $results = [];
        $summary = [
            'total_checked' => 0,
            'complete_products' => 0,
            'incomplete_products' => 0,
            'not_found' => 0
        ];

        foreach ($product_ids as $product_id) {
            $product_id = $this->security->xss_clean($product_id);

            if (empty($product_id) || !is_numeric($product_id)) {
                $results[] = [
                    'product_id' => $product_id,
                    'status' => 'invalid',
                    'message' => 'Invalid product ID',
                    'checks' => null
                ];
                continue;
            }

            $this->db->select('id, description, image, stock, rate, cfop');
            $this->db->where('id', $product_id);
            $this->db->where('warehouse_id', $warehouse_id);
            $product = $this->db->get(db_prefix() . 'items')->row();

            $summary['total_checked']++;

            if (!$product) {
                $results[] = [
                    'product_id' => (int) $product_id,
                    'status' => 'not_found',
                    'message' => 'Product not found in warehouse',
                    'checks' => null
                ];
                $summary['not_found']++;
                continue;
            }

            $checks = [
                'image' => [
                    'status' => !empty($product->image) && $product->image !== null,
                    'value' => $product->image,
                    'message' => !empty($product->image) ? 'Image present' : 'Image missing'
                ],
                'stock' => [
                    'status' => isset($product->stock) && is_numeric($product->stock) && $product->stock >= 0,
                    'value' => $product->stock,
                    'message' => (isset($product->stock) && is_numeric($product->stock) && $product->stock >= 0) ? 'Stock valid' : 'Stock missing or invalid'
                ],
                'rate' => [
                    'status' => isset($product->rate) && is_numeric($product->rate) && $product->rate > 0,
                    'value' => $product->rate,
                    'message' => (isset($product->rate) && is_numeric($product->rate) && $product->rate > 0) ? 'Rate valid' : 'Rate missing or invalid'
                ],
                'cfop' => [
                    'status' => !empty($product->cfop) && $product->cfop !== null,
                    'value' => $product->cfop,
                    'message' => !empty($product->cfop) ? 'CFOP present' : 'CFOP missing'
                ]
            ];

            $all_checks_passed = $checks['image']['status'] &&
                $checks['stock']['status'] &&
                $checks['rate']['status'] &&
                $checks['cfop']['status'];

            $missing_fields = array_keys(array_filter($checks, function ($check) {
                return !$check['status'];
            }));

            $product_result = [
                'product_id' => (int) $product_id,
                'product_name' => $product->description,
                'status' => $all_checks_passed ? 'complete' : 'incomplete',
                'message' => $all_checks_passed ? 'All required fields present' : 'Missing required fields: ' . implode(', ', $missing_fields),
                'checks' => $checks,
                'missing_fields' => $missing_fields,
                'completion_percentage' => round((array_sum(array_column($checks, 'status')) / count($checks)) * 100, 2)
            ];

            $results[] = $product_result;

            if ($all_checks_passed) {
                $summary['complete_products']++;
            } else {
                $summary['incomplete_products']++;
            }
        }

        $summary['completion_rate'] = $summary['total_checked'] > 0 ?
            round(($summary['complete_products'] / $summary['total_checked']) * 100, 2) : 0;

        $this->response([
            'status' => TRUE,
            'message' => 'Product check completed',
            'data' => $results,
            'summary' => $summary
        ], REST_Controller::HTTP_OK);
    }
}
