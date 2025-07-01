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
class Roles extends REST_Controller
{
    

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Roles_model');

    }


    public function list_get($id = '')
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

    public function list_post($id = '')
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: ''; // Alterado para this->post
        $sortField = $this->post('sortField') ?: 'id'; // Alterado para this->post
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC'; // Alterado para this->post
        $data = $this->Roles_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function data_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

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

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Client ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $client = $this->Roles_model->get($id);

        if ($client) {
            $this->response([
                'status' => TRUE,
                'data' => $client
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');

        // Recebendo e decodificando os dados do body da requisição
        $raw_input = file_get_contents("php://input");
        $_POST = json_decode($this->security->xss_clean($raw_input), true);

        // Verifica se $_POST está vazio
        if (empty($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Request body is empty.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Debug: Verifique os dados recebidos
        error_log("Received data: " . print_r($_POST, true));

        // Verifica se o campo 'name' está presente
        if (!isset($_POST['name'])) {
            $message = array('status' => FALSE, 'message' => 'Role name is required.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Verifica se 'permissions' está presente no corpo da requisição
        if (!isset($_POST['permissions']) || !is_array($_POST['permissions'])) {
            $message = array('status' => FALSE, 'message' => 'Permissions are required and must be an array.');
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Debug: Verifique as permissões
        error_log("Permissions: " . print_r($_POST['permissions'], true));

        // Prepara os dados para inserção
// Prepara os dados para inserção
        $data = [
            'name' => $_POST['name'],
            'permissions' => isset($_POST['permissions']['permissions']) ? $_POST['permissions']['permissions'] : $_POST['permissions'],
        ];


        // Chama o método add do model para inserir os dados
        $this->load->model('Roles_model');
        $insert_id = $this->Roles_model->add($data);

        // Verifica se a inserção foi bem-sucedida
        if ($insert_id) {
            $message = array('status' => TRUE, 'message' => 'Role added successfully.', 'data' => ['id' => $insert_id]);
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to add role.');
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


    public function update_post()
    {
        // Receber dados da requisição (sem o ID)
        $input = json_decode(file_get_contents("php://input"), true);

        // Extrair o ID da URL (no 5º segmento)
        $id = $this->uri->segment(5);  // Pegando o 5º segmento corretamente

        // Debug: Verificar o ID extraído
        var_dump($id);  // Exibindo o ID extraído da URL

        // Verificar se o ID é válido
        if (!isset($id) || !is_numeric($id) || intval($id) <= 0) {
            echo json_encode(['status' => false, 'message' => 'ID inválido.']);
            return;
        }

        // Agora o ID está corretamente extraído da URL, podemos remover o campo 'id' do array de dados
        unset($input['id']); // Caso o 'id' ainda esteja no corpo da requisição, removemos

        // Chamar o modelo para atualizar
        $this->load->model('Roles_model');
        $result = $this->Roles_model->update_role($input, $id);

        // Retornar resposta JSON
        if ($result) {
            echo json_encode(['status' => true, 'message' => 'Atualização realizada com sucesso.']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Falha ao atualizar. Nenhuma alteração detectada ou erro na atualização.']);
        }
    }

    public function remove_post()
    {
        // Receber dados da requisição
        $input = json_decode(file_get_contents("php://input"), true);

        // Verificar se o campo 'rows' (ou um campo com o ID da role) foi enviado corretamente
        if (!isset($input['rows']) || empty($input['rows'])) {
            echo json_encode(['status' => false, 'message' => 'Nenhum ID foi enviado.']);
            return;
        }

        // Aqui consideramos que o ID da role é um valor dentro do array 'rows'
        $roles = $input['rows'];

        $this->load->model('Roles_model');
        $result = [];
        foreach ($roles as $role) {
            // Chama o método delete do model para cada role
            $deleteResult = $this->Roles_model->delete($role);

            if ($deleteResult === true) {
                $result[] = ['roleid' => $role, 'status' => 'deleted'];
            } elseif (isset($deleteResult['referenced']) && $deleteResult['referenced'] === true) {
                $result[] = ['roleid' => $role, 'status' => 'referenced'];
            } else {
                $result[] = ['roleid' => $role, 'status' => 'error'];
            }
        }

        // Responder com o resultado das deleções
        echo json_encode(['status' => true, 'result' => $result]);
    }




}
