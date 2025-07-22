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

	ini_set('display_errors', 1);
		ini_set('display_startup_erros', 1);
		error_reporting(E_ALL);

            $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
            
            // Validação básica
            $client_id = isset($_POST['client_id']) ? $_POST['client_id'] : 0;
            $payments = isset($_POST['payments']) ? $_POST['payments'] : [];
            $doc = isset($_POST['cpf']) ? $_POST['cpf'] : '';
            $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;
            $itens = isset($_POST['cart']) ? $_POST['cart'] : [];
            $cash_id = isset($_POST['cashId']) ? $_POST['cashId'] : null;
            $warehouse_id = isset($_POST['warehouseId']) ? $_POST['warehouseId'] : null;
            $user_id = $this->authservice->user->staffid;

            $total = 0;
            $newitems = [];
            $item_order = 1;

            // Processa itens do carrinho
            foreach ($itens as $item) {
                $subtotal = isset($item['subtotal']) ? floatval($item['subtotal']) : 0;
                $quantidade = isset($item['quantidade']) ? floatval($item['quantidade']) : 0;
                $precoUnitario = isset($item['precoUnitario']) ? floatval($item['precoUnitario']) : 0;

                $total += $subtotal;

                $newitems[] = [
                    'id' => $item['codigo'] ?? '',
                    'description' => $item['descricao'] ?? '',
                    'qty' => $quantidade,
                    'rate' => $precoUnitario,
                    'subtotal' => $subtotal,
                    'discount' => isset($item['desconto']) ? floatval($item['desconto']) : 0,
                    'barcode' => $item['commodity_barcode'] ?? '',
                    'unit' => 'UN',
                    'item_order' => $item_order,
                ];
                $item_order++;
            }

            // Calcula subtotal líquido (com desconto)
            $subtotal_liquido = $total - $discount;

            $data = [
                'client_id' => $client_id,
                'cash_id' => $cash_id,
                'user_id' => $user_id,
                'type' => 'credit',
                'subtotal' => $subtotal_liquido,
                'discount' => $discount,
                'total' => $total,
                'nota' => '', // preenchido depois se necessário
                'doc' => $doc,
                'warehouse_id' => $warehouse_id,
                'newitems' => $newitems,
                'form_payments' => json_encode($payments),
                'operacao' => 'paid',
            ];

            // Persiste a venda
            $venda_id = $this->Cashs_model->add($data);

            if ($venda_id) {
                // Geração da NFC-e se necessário
                $nfce = false;
                foreach ($payments as $payment) {

               

                    if (!$nfce && (strtolower($payment['type'])  != 'dinheiro') || $doc != '') {

                        $result_nfce = gerarNFC($data, $venda_id);

                 
                        if ($result_nfce && $result_nfce->status == 'aprovado' ) {
                            $nfce = $result_nfce;

                            // Insere dados NFC-e
                            $this->Cashs_model->insert_nfce([
                                'status' => $nfce->status,
                                'documento' => $nfce->status,
                                'data_autorizacao' => $nfce->data_autorizacao,
                                'tributo_incidente' => $nfce->tributo_incidente,
                                'url_sefaz' => $nfce->url_sefaz,
                                'nfe' => $nfce->nfe,
                                'serie' => $nfce->serie,
                                'qrcode' => $nfce->qrcode,
                                'protocolo' => $nfce->protocolo,
                                'recibo' => $nfce->recibo,
                                'chave' => $nfce->chave,
                            ]);

                            // Atualiza a venda com os dados da NFC-e
                            $this->Cashs_model->update([
                                'id_nfce' => $id_nfce ?? null,
                                'nfe' => $nfce->nfe,
                                'serie' => $nfce->serie,
                                'qrcode' => $nfce->qrcode,
                                'protocolo' => $nfce->protocolo,
                                'chave' => $nfce->chave,
                            ], $venda_id);
                        }
                    }
                }

                // Responde com sucesso
                $this->response([
                    'status' => true,
                    'nfce' => $nfce,
                    'status_payment' => 'paid',
                    'payment_id' => 1, // Pode adaptar para o ID do pagamento real
                    'message' => 'Pagamento realizado com sucesso'
                ], REST_Controller::HTTP_OK);
            } else {
                // Caso haja erro ao inserir a venda
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao efetuar a compra'
                ], REST_Controller::HTTP_NOT_FOUND);
            }
        }



}
     
