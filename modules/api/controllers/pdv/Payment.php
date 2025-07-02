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
class Payment extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        
         $this->load->model('Authentication_model');
         $this->load->model('Cashs_model');
        
        $decodedToken = $this->authservice->decodeToken($this->token_jwt);
        if (!$decodedToken['status']) {
            $this->response([
                'status' => FALSE,
                'message' => 'Usuario nao autenticado '
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        
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
    public function process_post()
    {
        
       

         $this->response(['status' => true,'payment_id'=>1, 'qrcode'=>'123', 'message' => 'Aguardando pagamento'], REST_Controller::HTTP_OK);

           
        
    }
    public function verify_post()
    {
        
        //busca
        
       $status = 'paid';

         $this->response(['status' => true, 'status_payment'=>$status, 'payment_id'=>1, 'message' => 'Aguardando pagamento'], REST_Controller::HTTP_OK);

           
        
    }
     public function finish_post()
    {
         
         $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
         $client_id = isset($_POST['client_id'])?$_POST['client_id']:0;
         $payments = $_POST['payments'];
         $doc =  $_POST['cpf'];
         $discount =  $_POST['discount'];
         $itens=  $_POST['cart'];
         $cash_id=  $_POST['cashId'];
         $warehouse_id = $_POST['warehouseId'];
         $user_id = $this->authservice->user->staffid;
         $total =0;
         $item_order = 1;
         
         foreach($itens as $item){
             $total += $item['subtotal'];
             $newitems[] = array(
                 'id'=>$item['codigo'],
                 'description'=>$item['descricao'],
                 'qty'=>$item['quantidade'],
                 'rate'=>$item['precoUnitario'],
                 'subtotal'=>$item['subtotal'],
                 'discount'=>isset($item['desconto'])?$item['desconto']:0,
                 'barcode'=>$item['commodity_barcode'],
                 'unit'=>'UN',
                 'item_order'=>$item_order,
                 );
             $item_order++;
         }
         
         $data= array(
             'client_id'=>$client_id,
             'cash_id'=>$cash_id,
             'user_id'=>$user_id,
             'type'=>'credit',
             'subtotal'=>$total-$discount,
             'discount'=>$discount,
             'total'=>$total,
             'nota'=>'',
             'doc'=>$doc,
             'warehouse_id'=>$warehouse_id,
             'newitems'=>$newitems,
             'form_payments'=>json_encode($payments),
             'operacao'=>'paid'
             
         );
         
      
         
         if($this->Cashs_model->add($data)){

         $this->response(['status' => true, 'status_payment'=>'paid', 'payment_id'=>1, 'message' => 'Pagamento realizado'], REST_Controller::HTTP_OK);
         
         }else{
             
               $this->response(['status' => FALSE, 'message' => 'Erro ao efetuar compra'], REST_Controller::HTTP_NOT_FOUND);
         }
           
        
    }
    
    
    
}
