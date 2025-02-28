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
class Cash extends REST_Controller
{


    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('cashs_model');
        $this->load->model('Authentication_model');

        $decodedToken = $this->authservice->decodeToken($this->token_jwt);
        if (!$decodedToken['status']) {
            $this->response([
                'status' => FALSE,
                'message' => 'Usuario nao autenticado'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }



    }
    public function get_by_number_get($id)
    {



        $data = $this->cashs_model->get_by_number($id);
        if ($data) {
            $this->response(['status' => true, 'total' => 1, 'data' => $data], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }



    }
    public function list_post($id = '')
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: ''; // Alterado para this->post
        $sortField = $this->post('sortField') ?: 'id'; // Alterado para this->post
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Alterado para this->post
        $warehouse_id = $this->post('warehouse_id') ?: 0; // Se não vier, define como 0

        $data = $this->cashs_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, $warehouse_id);

        if ($data['total'] == 0) {

            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_OK);
        } else {

            if ($data) {
                $this->response(['status' => true, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
            } else {
                $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
            }
        }


    }

    public function list_inactive_get($warehouse_id)
    {

        $data = $this->cashs_model->get_inactive($warehouse_id);

        if ($data['total'] == 0) {

            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        } else {

            if ($data) {
                $this->response(['status' => true, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
            } else {
                $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
            }
        }


    }

    public function extracts_get($id = '')
    {

        /*
          $this->load->model('clients_model');

          $this->clients_model->add_import_items();
          exit;
         * 
         */

        $page = $this->post('page') ? (int) $this->post('page') : 0; // Página atual, padrão 1

        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10; // Itens por página, padrão 10
        $search = $this->post('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->post('sortField') ?: 'id'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
        $data = $this->cashs_model->get_extracts($id, $page, $limit, $search, $sortField, $sortOrder);

        if ($data['total'] == 0) {

            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        } else {

            if ($data) {
                $this->response(['status' => true, 'total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
            } else {
                $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }





    public function create_post()
    {
        // Lê os dados do corpo da requisição
        $input = json_decode(file_get_contents('php://input'), true);

        // Verifica se os dados foram recebidos
        if (empty($input)) {
            log_message('error', 'Nenhum dado recebido.');
            $this->response([
                'status' => false,
                'message' => 'Nenhum dado recebido.'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verifica se warehouse_id foi enviado e é válido
        if (!isset($input['warehouse_id']) || !is_numeric($input['warehouse_id'])) {
            log_message('error', 'Erro de validação: warehouse_id ausente ou inválido.');
            $this->response([
                'status' => false,
                'message' => 'warehouse_id é obrigatório e deve ser um número válido.'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Prepara os dados para inserção
        $_input = [
            'number' => isset($input['number']) ? (int) $input['number'] : null,
            'status' => isset($input['status']) ? (int) $input['status'] : null,
            'observation' => isset($input['observation']) ? (string) $input['observation'] : null,
            'warehouse_id' => (int) $input['warehouse_id'], // Adicionando warehouse_id
        ];

        // Valida os campos obrigatórios
        if (in_array(null, $_input, true)) {
            log_message('error', 'Erro de validação: Campos obrigatórios ausentes ou mal formados.');
            $this->response([
                'status' => false,
                'message' => 'Campos obrigatórios estão ausentes ou mal formados.'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Insere os dados no banco de dados
        try {
            if ($this->db->insert('cashs', $_input)) {
                log_message('debug', 'Caixa criado com sucesso: ' . $this->db->last_query());
                $this->response([
                    'status' => true,
                    'message' => 'Caixa criado com sucesso.',
                    'data' => [
                        'id' => $this->db->insert_id(),
                        'input' => $_input
                    ]
                ], REST_Controller::HTTP_OK);
            } else {
                log_message('error', 'Erro ao inserir caixa: ' . $this->db->last_query());
                $this->response([
                    'status' => false,
                    'message' => 'Erro ao criar caixa. Tente novamente.'
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (Exception $e) {
            log_message('error', 'Exceção ao criar caixa: ' . $e->getMessage());
            $this->response([
                'status' => false,
                'message' => 'Erro interno no servidor. Tente novamente mais tarde.'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function remove_post()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $id_cash = $data['rows'][0];
        $extract = $this->cashs_model->count_extracts($id_cash);



        if ($extract == 0) {



            if ($this->cashs_model->delete_finaly($id_cash)) {



                $this->response([
                    'status' => FALSE,
                    'message' => 'Excluido com sucesse'
                ], REST_Controller::HTTP_OK);

            } else {

                $this->response([
                    'status' => FALSE,
                    'message' => 'Erro ao tentar excluir'
                ], REST_Controller::HTTP_BAD_REQUEST);
                return;


            }

        }





        $email = $this->authservice->user->email;
        $password = $data['password'];


        $data_pw = $this->Authentication_model->login_api($email, $password);

        if (!$data_pw['success']) {

            $this->response([
                'status' => FALSE,
                'message' => 'Senha inválida'
            ], REST_Controller::HTTP_OK);
        }

        if (!isset($data['rows']) || empty($data['rows'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid request: rows array is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = $data['rows'];
        $success_count = 0;
        $failed_ids = [];

        if (!is_array($ids)) {
            $this->response([
                'status' => FALSE,
                'message' => 'O campo "rows" deve ser um array.'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        foreach ($ids as $id) {

            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            try {
                $output = $this->cashs_model->update(array('status' => 2), $id);
                if ($output === TRUE) {
                    $success_count++;
                } else {
                    $failed_ids[] = $id;
                }
            } catch (Exception $e) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Erro ao tentar excluir: ' . $e->getMessage()
                ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }
        }

        if ($success_count > 0) {
            $message = [
                'status' => TRUE,
                'message' => $success_count . ' caixa(s) deletado(s) com sucesso.'
            ];
            if (!empty($failed_ids)) {
                $message['failed_ids'] = $failed_ids;
            }
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = [
                'status' => FALSE,
                'message' => 'Falha ao deletar caixa(s)',
                'failed_ids' => $failed_ids
            ];
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }


    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Cash ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $cash = $this->Cashs_model->get($id);

        if ($cash) {
            $this->response([
                'status' => TRUE,
                'data' => $cash
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }
    public function transactions_post($id = '')
    {
        $page = $this->post('page') !== null ? (int) $this->post('page') : 0;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') !== null ? $this->post('search') : '';
        $sortField = $this->post('sortField') ?: 'id';
        $sortOrder = strtoupper($this->post('sortOrder')) === 'DESC' ? 'DESC' : 'ASC';

        // Get additional filters
        $filters = [
            'start_date' => $this->post('start_date'),
            'end_date' => $this->post('end_date'),
            'status' => $this->post('status'),
        ];

        $cash_id = $this->post('cash_id');

        $data = $this->cashs_model->get_transactions($id, $page + 1, $limit, $search, $sortField, $sortOrder, $filters, $cash_id);

        // Always return HTTP_OK with the data and total, even if total is 0
        $this->response([
            'status' => true,
            'total' => $data['total'],
            'data' => $data['data'] ?? []
        ], REST_Controller::HTTP_OK);
    }


    public function extracts_post($id = '')
    {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $number = $_POST['caixaId'];
        $page = $_POST['page'] ? (int) $_POST['page'] : 0; // Página atual, padrão 1
        $page = $page + 1;
        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10; // Itens por página, padrão 10
        $search = $this->post('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->post('sortField') ?: 'userid'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->post('sortOrder') === 'asc' ? 'ASC' : 'DESC'; // Ordem, padrão crescente


        $detalhes_caixa = $this->cashs_model->get_by_number($number);

        if (!$detalhes_caixa) {
            $this->response([
                'status' => FALSE,
                'message' => 'Caixa nao encontrado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }



        $cash = $this->cashs_model->get_transactions($id = '', $page = 1, $limit = 20, $search = '', $sortField = 'id', $sortOrder = 'DESC', $filters = null, $detalhes_caixa->id = 0);

        if ($cash) {
            $this->response([
                'status' => TRUE,
                'data' => $cash
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function update_put($id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $this->form_validation->set_data($_POST);
        if (empty($id) || !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Cash Register ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $_POST;
            $this->load->model('cashs_model');
            $output = $this->cashs_model->update($update_data, $id);

            if ($output) {
                $message = array('status' => TRUE, 'message' => 'Cash Register Update Successful.', 'data' => $this->cashs_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'Cash Register Update Failed.');
                $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    public function update_patch($id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        $update_data = array(
            'status' => $_POST['status']
        );

        if ($this->cashs_model->update_extracts($update_data, $id)) {
            $this->response([
                'status' => TRUE,
                'message' => 'Status atualizado com sucesso',
                'data' => $subgroups
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao atualizar status'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function active_patch()
    {
        
        



        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        $number = $_POST['caixaId'];
        $caixaId= $_POST['caixaId'];
        $valor = $_POST['valor'];
        $warehouse_id = $_POST['warehouse_id'];
        $status = $_POST['status'];
        $client_id = @$_POST['client_id'];
        $status_txt_caixa = $status == 0 ? "Fechado" : "Aberto";
        $status_caixa = $status == 0 ? "close_cash" : "open_cash";
        $nota_caixa = $status == 0 ? "Fechado de Caixa" : "Abertura de Caixa";
        $type = $status == 0 ? "debito" : "credito";
        $user_id = $this->authservice->user->staffid;



        $email = $this->authservice->user->email;
        $password = $_POST['password'];
        $data = $this->Authentication_model->login_api($email, $password);

        if (!$data['success']) {

            $this->response([
                'status' => FALSE,
                'message' => 'Senha inválida'
            ], REST_Controller::HTTP_OK);
        }
    
        $detalhes_caixa = $this->cashs_model->get_by_id($caixaId);

        if ($status == 1) {


            $update_data = array(
                'status' => $status,
                'open_cash' => (double) $valor,
                'balance' => $valor,
                'balance_dinheiro' => $valor,
                'user_id' => $user_id,

            );


        } elseif ($status == 0) {


            $valor = $detalhes_caixa->balance;
            $update_data = array(
                'status' => $status,
                'open_cash' => 0,
                'balance' => 0,
                'close_cash' => $detalhes_caixa->balance,
                'balance_dinheiro' => 0,
                'user_id' => $user_id,

            );
        }


        if (!$detalhes_caixa) {
            $this->response([
                'status' => FALSE,
                'message' => 'Caixa nao encontrado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        if ($status == $detalhes_caixa->status) {

            $this->response([
                'status' => FALSE,
                'message' => 'Caixa já esta ' . $status_txt_caixa
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        if ($this->cashs_model->update_by_number($update_data, $number)) {

            $data_extract = array(
                'client_id' => $client_id,
                'user_id' => $user_id,
                'cash_id' => $detalhes_caixa->id,
                'type' => $type,
                'subtotal' => $valor,
                'total' => $valor,
                'nota' => $nota_caixa,
                'operacao' => $status_caixa

            );
            $this->cashs_model->add_extract($data_extract);

            $this->response([
                'status' => TRUE,
                'message' => 'Caixa ' . $status_txt_caixa . ' com sucesso',
                'data' => $detalhes_caixa
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao atualizar status'
            ], REST_Controller::HTTP_NOT_FOUND);
        }


    }

    public function sangria_patch()
    {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        $number = $_POST['caixaId'];
        $valor = $_POST['valor'];
        $user_id = $this->authservice->user->staffid;

        $email = $this->authservice->user->email;
        $password = $_POST['password'];
        $data = $this->Authentication_model->login_api($email, $password);

        if (!$data['success']) {

            $this->response([
                'status' => FALSE,
                'message' => 'Senha inválida'
            ], REST_Controller::HTTP_OK);
        }



        $detalhes_caixa = $this->cashs_model->get_by_number($number);
        if (!$detalhes_caixa) {
            $this->response([
                'status' => FALSE,
                'message' => 'Caixa nao encontrado'
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        if ($valor > $detalhes_caixa->balance_dinheiro) {
            $this->response([
                'status' => FALSE,
                'message' => 'Valor em dinheiro insuficiente no caixa '
            ], REST_Controller::HTTP_NOT_FOUND);
        }

        //   $balance = $detalhes_caixa->balance-$valor;
        $balance_dinheiro = $detalhes_caixa->balance_dinheiro - $valor;
        $sangria = $detalhes_caixa->sangria + $valor;


        $update_data = array(
            'balance_dinheiro' => $balance_dinheiro,
            'sangria' => $sangria,
            'user_id' => $user_id

        );

        if ($this->cashs_model->update_by_number($update_data, $number)) {

            if ($tatus == 0) {
                $subtotal = 0;
            }

            $data_extract = array(
                'user_id' => $user_id,
                'cash_id' => $detalhes_caixa->id,
                'type' => 'debit',
                'subtotal' => $subtotal,
                'total' => $valor,
                'nota' => 'Sangria',
                'operacao' => 'sangria'

            );
            $this->cashs_model->add_extract($data_extract);

            $this->response([
                'status' => TRUE,
                'message' => 'Sangria realizada com sucesso',
                'data' => $detalhes_caixa
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Erro ao atualizar status'
            ], REST_Controller::HTTP_NOT_FOUND);
        }


    }
}
