<?php

defined('BASEPATH') or exit('No direct script access allowed');
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
class Roles extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Roles_model');

    }

   


    public function data_get($id = '')
    {
        $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
        $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
        $sortField = $this->get('sortField') ?: 'roleid'; // Campo para ordenação, padrão 'id'
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente

        $data = $this->Roles_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function data_post()
    {

        // Recebendo e decodificando os dados do body da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Verificando se o type foi passado corretamente na URL
        $type = $this->input->get('type');
        if (!$type) {
            $message = array('status' => FALSE, 'message' => 'Type parameter is required.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verifica se $_POST está vazio
        if (empty($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Request body is empty.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verificando se é um array de objetos ou um único objeto
        if (isset($_POST[0]) && is_array($_POST[0])) {
            // Processando múltiplos registros
            $output = [];
            foreach ($_POST as $data) {
                $output[] = $this->processData($type, $data);
            }
        } else {
            // Processando um único registro
            $output = $this->processData($type, $_POST);
        }

        // Verifica se a inserção foi bem-sucedida
        if (!empty($output) && (is_array($output) || $output > 0)) {
            $message = array('status' => TRUE, 'message' => 'Added successfully.', 'data' => $output);
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Added fail.');
            $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Função auxiliar para processar a inserção com base no type
     */
    private function processData($type, $data)
    {
        switch ($type) {
            case 'search':
                return $this->Gptw_model->add_api_search($data);
            case 'good':
                return $this->Gptw_model->add_api_good($data);
            case 'feedbacks':
                return $this->Gptw_model->add_api_feedbacks($data);
            case 'recognition':
                return $this->Gptw_model->add_api_recognition($data);
            case 'training':
                return $this->Gptw_model->add_api_training($data);
            default:
                return null;
        }
    }



    public function data_delete()
    {
        $id = $this->input->get('id', true);  // Obtém o ID da URL
        $type = $this->input->get('type', true);  // Obtém o tipo da URL

        // Segurança: Limpa os inputs recebidos
        $id = $this->security->xss_clean($id);
        $type = $this->security->xss_clean($type);

        // Validação do ID
        if (empty($id) || !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid ID provided.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Carrega o model
        $this->load->model('Gptw_model');

        // Verifica o "type" e chama a função correspondente
        if ($type == 'search') {
            $output = $this->Gptw_model->delete_api_search($id);
        } elseif ($type == 'good') {
            $output = $this->Gptw_model->delete_api_good($id);
        } elseif ($type == 'feedbacks') {
            $output = $this->Gptw_model->delete_api_feedbacks($id);
        } elseif ($type == 'recognition') {
            $output = $this->Gptw_model->delete_api_recognition($id);
        } elseif ($type == 'training') {
            $output = $this->Gptw_model->delete_api_training($id);
        } else {
            $message = array('status' => FALSE, 'message' => 'Invalid type provided.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verifica se o delete foi bem-sucedido
        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Record deleted successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Record not found or could not be deleted.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }


    /**
     * Função auxiliar para processar a exclusão com base no type
     */
    private function processDelete($type, $id)
    {
        switch ($type) {
            case 'search':
                return $this->Gptw_model->delete_search($id);
            case 'good':
                return $this->Gptw_model->delete_good($id);
            case 'feedbacks':
                return $this->Gptw_model->delete_feedbacks($id);
            case 'recognition':
                return $this->Gptw_model->delete_recognition($id);
            case 'training':
                return $this->Gptw_model->delete_training($id);
            default:
                return false;
        }
    }


    public function data_patch()
    {
        $id = $this->input->get('id', true);
        $type = $this->input->get('type', true);

        // Segurança: Sanitiza os inputs
        $id = $this->security->xss_clean($id);
        $type = $this->security->xss_clean($type);

        // Valida se o ID é um número válido
        if (empty($id) || !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid ID provided.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Obtém o JSON da requisição
        $json_data = file_get_contents("php://input");

        // Verifica se os dados foram realmente recebidos
        if (!$json_data) {
            $message = array('status' => FALSE, 'message' => 'No data received.');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }

        // Decodifica o JSON para array
        $data = json_decode($json_data, true);

        // Verifica se o JSON foi corretamente decodificado
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || empty($data)) {
            $message = array('status' => FALSE, 'message' => 'Invalid JSON format or empty data.');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            return;
        }

        // Carrega o model
        $this->load->model('Gptw_model');

        // Verifica o "type" e chama a função correspondente
        switch ($type) {
            case 'search':
                $output = $this->Gptw_model->update_api_search($id, $data);
                break;
            case 'good':
                $output = $this->Gptw_model->update_api_good($id, $data);
                break;
            case 'feedbacks':
                $output = $this->Gptw_model->update_api_feedbacks($id, $data);
                break;
            case 'recognition':
                $output = $this->Gptw_model->update_api_recognition($id, $data);
                break;
            case 'training':
                $output = $this->Gptw_model->update_api_training($id, $data);
                break;
            default:
                $message = array('status' => FALSE, 'message' => 'Invalid type provided.');
                $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
                return;
        }

        // Verifica se a atualização foi bem-sucedida
        if ($output) {
            $message = array(
                'status' => TRUE,
                'message' => 'Record updated successfully.',
                'data' => $this->Gptw_model->get($id)
            );
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'No changes made or record not found.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }




}
