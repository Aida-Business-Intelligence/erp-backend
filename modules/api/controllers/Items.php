<?php

defined('BASEPATH') OR exit('No direct script access allowed');
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
class Items extends REST_Controller {

    function __construct() {
        // Construct the parent class
        parent::__construct();
          $this->load->model('Invoice_items_model');
    }

    /**
     * @api {get} api/items/items/:id Request items information
     * @apiVersion 0.1.0
     * @apiName GetItem
     * @apiGroup Items
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiSuccess {Object} Item item information.
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     * 	  "itemid": "1",
     *        "rate": "100.00",
     *        "taxrate": "5.00",
     *        "taxid": "1",
     *        "taxname": "PAYPAL",
     *        "taxrate_2": "9.00",
     *        "taxid_2": "2",
     *        "taxname_2": "CGST",
     *        "description": "JBL Soundbar",
     *        "long_description": "The JBL Cinema SB110 is a hassle-free soundbar",
     *        "group_id": "0",
     *        "group_name": null,
     *        "unit": ""
     *     }
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
        
          // Parâmetro de busca com conversão para string
        $search = $this->get('search');
        if (is_numeric($search)) {
            $search = strval($search); // Convertendo double ou inteiro para string
           
            
        } else {
            $search = $search ?: ''; // Atribuindo valor padrão se não houver busca
        }
        
         $search = str_replace(',', '.',$search);
        
        
        $sortField = $this->get('sortField') ?: 'id'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
        
        
        
        
        $data = $this->Invoice_items_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }
        
    }
    
    public function list_get($id = '') {
        
     $data = [
    "sum" => 4,
    "data" => [
        [
            "id" => "4a28cf4c-8737-40aa-8bef-42816f600319",
            "productName" => "Mochila Escolar",
            "image" => "https://tse4.mm.bing.net/th?id=OIP.mmfw6ve4N9t6F1tFS4pS2QHaJc&pid=Api",
            "sku" => "BAG12345",
            "barcode" => "7891234567892",
            "category" => "Acessórios",
            "brand" => "Genérica",
            "description" => "Mochila escolar resistente com compartimentos para laptop e outros itens.",
            "unit" => "unidade",
            "price" => 89.99,
            "cost" => 50,
            "promoPrice" => 79.99,
            "promoStart" => "2024-12-01T00:00:00.000Z",
            "promoEnd" => "2024-12-31T23:59:59.000Z",
            "stock" => 100,
            "minStock" => 20,
            "active" => true,
            "variations" => '[{"sku":"BAG12345-BLUE","price":89.99,"cost":50,"stock":50},{"sku":"BAG12345-RED","price":89.99,"cost":50,"stock":50}]',
            "createdAt" => "2024-12-20T14:00:00.000Z",
            "updatedAt" => "2024-12-20T14:00:00.000Z"
        ],
        [
            "id" => "1785d627-4bb1-4a03-8c1f-7c9b617f4d2d",
            "productName" => "Relógio Smartwatch",
            "image" => "https://dcdn.mitiendanube.com/stores/002/578/628/products/eadba2c14d7ea47ccb1018b89f27a31bawsaccesskeyidakiatclmsgfx4j7tu445expires1692742014signaturerdlpzol2fyhojccxget5athm2bec3d-cbab12e3ce3d2b306d16901500672419-1024-1024.jpg",
            "sku" => "WATCH12345",
            "barcode" => "7891234567894",
            "category" => "Eletrônicos",
            "brand" => "Xiaomi",
            "description" => "Relógio inteligente com monitoramento de saúde, notificações e bateria de longa duração.",
            "unit" => "unidade",
            "price" => 349.99,
            "cost" => 200,
            "promoPrice" => 299.99,
            "promoStart" => "2024-12-01T00:00:00.000Z",
            "promoEnd" => "2024-12-31T23:59:59.000Z",
            "stock" => 50,
            "minStock" => 10,
            "active" => true,
            "variations" => '[{"sku":"WATCH12345-BLACK","price":349.99,"cost":200,"stock":30},{"sku":"WATCH12345-WHITE","price":349.99,"cost":200,"stock":20}]',
            "createdAt" => "2024-12-20T14:00:00.000Z",
            "updatedAt" => "2024-12-20T14:00:00.000Z"
        ],
        [
            "id" => "fd0d2f12-769c-4a82-b554-80e92bfa5283",
            "productName" => "Smartphone Samsung",
            "image" => "https://tse4.mm.bing.net/th?id=OIP.hlaa3ABICuuMTIAQdP0ntAHaHa&pid=Api",
            "sku" => "SAM12345",
            "barcode" => "7891234567890",
            "category" => "Eletrônicos",
            "brand" => "Samsung",
            "description" => "Smartphone Samsung Galaxy com tela AMOLED, câmera tripla e 128GB de armazenamento.",
            "unit" => "unidade",
            "price" => 1299.99,
            "cost" => 1000,
            "promoPrice" => 1199,
             "promoPrice" => 1199.99,
            "promoStart" => "2024-12-01T00:00:00.000Z",
            "promoEnd" => "2024-12-31T23:59:59.000Z",
            "stock" => 50,
            "minStock" => 10,
            "active" => true,
            "variations" => '[{"sku":"SAM12345-BLACK","price":1299.99,"cost":1000,"stock":20},{"sku":"SAM12345-WHITE","price":1299.99,"cost":1000,"stock":30}]',
            "createdAt" => "2024-12-20T14:00:00.000Z",
            "updatedAt" => "2024-12-20T14:00:00.000Z"
        ],
        [
            "id" => "b2ac96fd-5b93-48d0-832c-cf812ee65542",
            "productName" => "Notebook Dell",
            "image" => "https://tse3.mm.bing.net/th?id=OIP.yBoZgRb7vhXPhv8qLY8JLAHaFj&pid=Api",
            "sku" => "DELL12345",
            "barcode" => "7891234567891",
            "category" => "Computadores",
            "brand" => "Dell",
            "description" => "Notebook Dell com processador Intel Core i5, 8GB RAM e 256GB SSD.",
            "unit" => "unidade",
            "price" => 2899.99,
            "cost" => 2500,
            "promoPrice" => 2699.99,
            "promoStart" => "2024-12-01T00:00:00.000Z",
            "promoEnd" => "2024-12-31T23:59:59.000Z",
            "stock" => 30,
            "minStock" => 5,
            "active" => true,
            "variations" => '[{"sku":"DELL12345-SILVER","price":2899.99,"cost":2500,"stock":10},{"sku":"DELL12345-BLACK","price":2899.99,"cost":2500,"stock":20}]',
            "createdAt" => "2024-12-20T14:00:00.000Z",
            "updatedAt" => "2024-12-20T14:00:00.000Z"
        ]
    ]
];
          $this->response($data, REST_Controller::HTTP_OK);

        
    }

    /**
     * @api {get} api/items/search/:keysearch Search invoice item information
     * @apiVersion 0.1.0
     * @apiName GetItemSearch
     * @apiGroup Items
     *
     * @apiHeader {String} Authorization Basic Access Authentication token
     *
     * @apiParam {String} keysearch Search Keywords
     *
     * @apiSuccess {Object} Item  Item Information
     *
     * @apiSuccessExample Success-Response:
     * 	HTTP/1.1 200 OK
     * 	{
     * 	  "rate": "100.00",
     * 	  "id": "1",
     * 	  "name": "(100.00) JBL Soundbar",
     * 	  "subtext": "The JBL Cinema SB110 is a hassle-free soundbar..."
     * 	}
     *
     * @apiError {Boolean} status Request status
     * @apiError {String} message No data were found
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '') {
        $data = $this->Api_model->search('invoice_items', $key);
        // Check if the data store contains
        if ($data) {
            $data = $this->Api_model->get_api_custom_data($data, "items");
            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }
    
    
     public function data_delete($id) {
         
         
        $id = $this->security->xss_clean($id);
        
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Item ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            
      
            $output = $this->Invoice_items_model->delete($id);
            
            
            if ($output === TRUE) {
                // success
                $message = array('status' => TRUE, 'message' => 'Customer Delete Successful.');
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array('status' => FALSE, 'message' => 'Item Delete Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }
    
    
    
}
