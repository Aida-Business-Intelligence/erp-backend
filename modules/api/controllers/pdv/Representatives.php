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
class Representatives extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Representatives_model');
    }

    public function permissions_get($userid)
    {

        $permissions = $this->Representatives_model->get_staff_permissions($userid);
        if (count($permissions)) {
            $this->response(['data' => $permissions], REST_Controller::HTTP_OK);
        }

        $this->response(['data' => 'Not permissions'], REST_Controller::HTTP_NOT_FOUND);
    }


    //-----> Representantes/representative - IMPLEMENTACAO
    public function list_post($id = '')
    {
        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: '';
        $sortField = $this->post('sortField') ?: 'staffid';
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
        $warehouse_id = $this->post('warehouse_id') ?: 0;

        // Garantir que a pesquisa seja aplicada corretamente na consulta
        $data = $this->Representatives_model->get_api2($id, $page, $limit, $search, $sortField, $sortOrder, 'representative', $warehouse_id);

        // Verificando se há dados após o filtro
        if (empty($data['data'])) {
            $this->response(
                [
                    'status' => FALSE,
                    'message' => 'No data were found'
                ],
                REST_Controller::HTTP_NOT_FOUND
            );
        } else {
            $this->response(
                [
                    'status' => true,
                    'total' => (int) $data['total'], // Total de registros filtrados
                    'data' => array_values($data['data']) // Dados filtrados
                ],
                REST_Controller::HTTP_OK
            );
        }
    }


    public function create_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        // Recebendo e decodificando os dados
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Mapeando os dados de entrada diretamente para o array $input
        $input = [
            'role' => $_POST['role'] ?? null,
            'password' => $_POST['password'] ?? null,
            'profile_image' => $_POST['profile_image'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phonenumber' => $_POST['phonenumber'] ?? null,
            'firstname' => $_POST['firstname'] ?? null,
            'lastname' => $_POST['lastname'] ?? null,
            'facebook' => $_POST['facebook'] ?? null,
            'type' => $_POST['type'] ?? null,
            'linkedin' => $_POST['linkedin'] ?? null,
            'documentType' => $_POST['documentType'] ?? null,
            'warehouse_id' => $_POST['warehouse_id'] ?? null,
            // 'franqueado_id' => $_POST['franqueado_id'] ?? null,
            'vat' => $_POST['vat'] ?? null,
        ];

        // Validação do email, para garantir que o email seja único
        $this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[100]', array('is_unique' => 'This %s already exists please enter another email'));

        if ($this->form_validation->run() == FALSE) {
            // Se a validação falhar
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // Chama o método do modelo para adicionar o novo usuário
            $output = $this->Representatives_model->add($input);

            if ($output > 0 && !empty($output)) {
                // Sucesso: usuário foi adicionado com sucesso
                $message = array('status' => 'success', 'message' => 'success', 'data' => $this->Representatives_model->get($output));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // Erro: falha ao adicionar o usuário
                $message = array('status' => FALSE, 'message' => 'Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
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

        foreach ($ids as $id) {
            $id = $this->security->xss_clean($id);

            if (empty($id) || !is_numeric($id)) {
                $failed_ids[] = $id;
                continue;
            }

            $output = $this->Representatives_model->delete($id);
            if ($output === TRUE) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        if ($success_count > 0) {
            $message = array(
                'status' => TRUE,
                'message' => $success_count . ' customer(s) deleted successfully'
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

    public function get_get($id = '')
    {
        if (empty($id) || !is_numeric($id)) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Client ID'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $client = $this->Representatives_model->get($id);


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
    public function update_post($id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST)) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        // Remover campos do contrato antes de processar
        unset($_POST['royalties']);
        unset($_POST['datestart']);
        unset($_POST['duration_years']);
        unset($_POST['contract_file']);

        $this->form_validation->set_data($_POST);

        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid users ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            $this->load->model('Represent_model');
            $output = $this->Representatives_model->update($update_data, $update_data['staffid']);

            if ($output > 0 && !empty($output)) {
                $message = array('status' => TRUE, 'message' => 'representatives Update Successful.', 'data' => $this->Representatives_model->get($id));
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $message = array('status' => FALSE, 'message' => 'representatives Update Fail.');
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function put_desativar_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $active = "0"; // Define o campo 'active' como "0" (inativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Representatives_model->update_active($ids, $active);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'representative Updated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Update representative.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function put_ativar_post()
    {
        // Recebe os dados enviados no corpo da requisição
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST) || !isset($_POST['ids'])) {
            $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
            $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
        }

        $ids = $_POST['ids'];
        $active = "1"; // Define o campo 'active' como "1" (ativo)

        // Atualiza o campo 'active' para os IDs fornecidos
        $output = $this->Representatives_model->update_active($ids, $active);

        if ($output) {
            $message = array('status' => TRUE, 'message' => 'Representative Activated Successfully.');
            $this->response($message, REST_Controller::HTTP_OK);
        } else {
            $message = array('status' => FALSE, 'message' => 'Failed to Activate Representative.');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
    }
}
