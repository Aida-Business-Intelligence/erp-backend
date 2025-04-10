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

        // Mapeando os dados de entrada
        $input = [
            'type' => 'representative',
            'firstname' => $_POST['firstname'] ?? null,
            'lastname' => $_POST['lastname'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phonenumber' => $_POST['phonenumber'] ?? null,
            'active' => $_POST['active'] ?? '1',
            'documentType' => $_POST['documentType'] ?? null,
            'vat' => $_POST['vat'] ?? null,
            'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
            'inscricao_municipal' => $_POST['inscricao_municipal'] ?? null,
            'endereco' => $_POST['endereco'] ?? null,
            'cidade' => $_POST['cidade'] ?? null,
            'estado' => $_POST['estado'] ?? null,
            'cep' => $_POST['cep'] ?? null,
            'tipo_pessoa' => $_POST['tipo_pessoa'] ?? null,
            'tipo_empresa' => $_POST['tipo_empresa'] ?? null,
            'segmento' => $_POST['segmento'] ?? null,
            'porte_empresa' => $_POST['porte_empresa'] ?? null,
            'observacoes' => $_POST['observacoes'] ?? null,
            'tipo_comissao_representante' => $_POST['tipo_comissao_representante'] ?? null,
            'percentual_base_representante' => $_POST['percentual_base_representante'] ?? null,
            'forma_pagamento_representante' => $_POST['forma_pagamento_representante'] ?? null,
            'dia_vencimento_representante' => $_POST['dia_vencimento_representante'] ?? null,
            'sistema_emissao_nf' => $_POST['sistema_emissao_nf'] ?? null,
            'warehouse_id' => $_POST['warehouse_id'] ?? null,
            'is_not_staff' => 1,
            'datecreated' => date('Y-m-d H:i:s'),
            'password' => app_hash_password('temp_' . time()),
            // Dados bancários
            'cod_banco' => $_POST['cod_banco'] ?? null,
            'tipo_conta' => $_POST['tipo_conta'] ?? null,
            'agencia_bank' => $_POST['agencia_bank'] ?? null,
            'num_conta_bank' => $_POST['num_conta_bank'] ?? null,
            'conta_titular' => $_POST['conta_titular'] ?? null,
            'conta_document' => $_POST['conta_document'] ?? null,
            'vat_titular' => $_POST['vat_titular'] ?? null,
            'tipo_pix' => $_POST['tipo_pix'] ?? null,
            'chave_pix' => $_POST['chave_pix'] ?? null,
        ];

        // Validação básica
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[100]|is_unique[' . db_prefix() . 'staff.email]');
        $this->form_validation->set_rules('firstname', 'Razão Social', 'trim|required|max_length[50]');
        $this->form_validation->set_rules('vat', 'CNPJ/CPF', 'trim|required|max_length[20]');
        $this->form_validation->set_rules('documentType', 'Tipo de Documento', 'trim|required|in_list[CPF,CNPJ]');

        if ($this->form_validation->run() == FALSE) {
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
        } else {
            try {
                // Chama o método do modelo para adicionar o novo representante
                $staffId = $this->Representatives_model->add($input);

                if ($staffId) {
                    // Sucesso: representante foi adicionado com sucesso
                    $message = array(
                        'status' => 'success',
                        'message' => 'Representante criado com sucesso',
                        'data' => $this->Representatives_model->get($staffId)
                    );
                    $this->response($message, REST_Controller::HTTP_OK);
                } else {
                    // Erro: falha ao adicionar o representante
                    $message = array('status' => FALSE, 'message' => 'Falha ao criar representante');
                    $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                }
            } catch (Exception $e) {
                $message = array('status' => FALSE, 'message' => $e->getMessage());
                $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
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
