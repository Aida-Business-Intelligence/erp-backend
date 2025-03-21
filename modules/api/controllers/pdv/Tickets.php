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
class Tickets extends REST_Controller
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('Tickets_model');
    }

    /**
     * @api {get} api/contracts/:id Request Contract information
     * @apiVersion 0.3.0
     * @apiName GetContract
     * @apiGroup Contracts
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiParam {Number} id Contact unique ID
     *
     * @apiSuccess {Object} Contracts information.
     *
     * @apiSuccessExample Success-Response:
     *   HTTP/1.1 200 OK
     *	    {
     *	    "id": "1",
     *	    "content": "",
     *	    "description": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.",
     *	    "subject": "New Contract",
     *	    "client": "9",
     *	    "datestart": "2022-11-21",
     *	    "dateend": "2027-11-21",
     *	    "contract_type": "1",
     *	    "project_id": "0",
     *	    "addedfrom": "1",
     *	    "dateadded": "2022-11-21 12:45:58",
     *	    "isexpirynotified": "0",
     *	    "contract_value": "13456.00",
     *	    "trash": "0",
     *	    "not_visible_to_client": "0",
     *	    "hash": "31caaa36b9ea1f45a688c7e859d3ae70",
     *	    "signed": "0",
     *	    "signature": null,
     *	    "marked_as_signed": "0",
     *	    "acceptance_firstname": null,
     *	    "acceptance_lastname": null,
     *	    "acceptance_email": null,
     *	    "acceptance_date": null,
     *	    "acceptance_ip": null,
     *	    "short_link": null,
     *	    "name": "Development Contracts",
     *	    "userid": "9",
     *	    "company": "8web",
     *	    "vat": "",
     *	    "phonenumber": "",
     *	    "country": "0",
     *	    "city": "",
     *	    "zip": "",
     *	    "state": "",
     *	    "address": "",
     *	    "website": "",
     *	    "datecreated": "2022-08-11 14:07:26",
     *	    "active": "1",
     *	    "leadid": null,
     *	    "billing_street": "",
     *	    "billing_city": "",
     *	    "billing_state": "",
     *	    "billing_zip": "",
     *	    "billing_country": "0",
     *	    "shipping_street": "",
     *	    "shipping_city": "",
     *	    "shipping_state": "",
     *	    "shipping_zip": "",
     *	    "shipping_country": "0",
     *	    "longitude": null,
     *	    "latitude": null,
     *	    "default_language": "",
     *	    "default_currency": "0",
     *	    "show_primary_contact": "0",
     *	    "stripe_id": null,
     *	    "registration_confirmed": "1",
     *	    "type_name": "Development Contracts",
     *	    "attachments": [],
     *	    "customfields": [],
     *	    }
     */




    public function list_post($id = '')
    {

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: ''; // Alterado para this->post
        $sortField = $this->post('sortField') ?: 'date'; // Alterado para this->post
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC'; // Alterado para this->post
        $warehouse_id = $this->post('warehouse_id') ?: 0;
        // $franqueado_id = $this->post('franqueado_id') ?: 0;

        $data = $this->Tickets_model->get_api($id, $page, $limit, $search, $sortField, $sortOrder, $warehouse_id);


        // var_dump($data);
        // exit;

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

    public function list_types_post($id = '')
    {

        $page = $this->post('page') ? (int) $this->post('page') : 0;
        $page = $page + 1;

        $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
        $search = $this->post('search') ?: ''; // Alterado para this->post
        $sortField = $this->post('sortField') ?: 'typeid'; // Alterado para this->post
        $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC'; // Alterado para this->post
        // $warehouse_id = $this->post('warehouse_id') ?: 0;
        // $franqueado_id = $this->post('franqueado_id') ?: 0;

        $data = $this->Tickets_model->get_ticket_types_api($id, $page, $limit, $search, $sortField, $sortOrder);

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

    public function create_types_post()
    {

        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);


        $this->load->model('Tickets_model');
        $this->form_validation->set_rules('name', 'name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
        if ($this->form_validation->run() == FALSE) {
            // form validation error
            $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {

            $output = $this->Tickets_model->add_type($_POST);

            if ($output > 0 && !empty($output)) {
                // success
                $message = array('status' => TRUE, 'message' => 'Ticket add successful.', 'data' => $output);
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                $this->response('Error', REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
    }

    public function update_types_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Log para depuração
        log_message('debug', 'Dados recebidos: ' . print_r($_POST, true));

        if (empty($_POST) || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->response(['status' => FALSE, 'message' => 'Invalid type ID or Data'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $id = $_POST['id']; // Obtenha o ID do payload

        // Ajustar os campos permitidos para atualização
        $update_data = array_intersect_key($_POST, array_flip([
            'name',
            'description',
            'color',
            'type',
        ]));

        // Verificar se há dados para atualizar
        if (empty($update_data)) {
            $this->response(['status' => FALSE, 'message' => 'No valid data to update'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Atualizar o tipo
        $output = $this->Tickets_model->update_type($update_data, $id);
        if ($output) {
            $this->response(['status' => TRUE, 'message' => 'Type updated successfully', 'data' => $this->Tickets_model->get($id)], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to update type'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function remove_types_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Log para depuração
        log_message('debug', 'Dados recebidos para exclusão: ' . print_r($_POST, true));

        if (empty($_POST) || !isset($_POST['id']) || !is_numeric($_POST['id'])) {
            $this->response(['status' => FALSE, 'message' => 'ID inválido ou dados ausentes'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $id = $_POST['id']; // Obtenha o ID do payload

        // Excluir o tipo de ticket
        $output = $this->Tickets_model->delete_type($id);
        if ($output) {
            $this->response(['status' => TRUE, 'message' => 'Tipo excluído com sucesso'], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Falha ao excluir tipo'], REST_Controller::HTTP_NOT_FOUND);
        }
    }


    public function remove_post()
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        if (empty($_POST['rows']) || !is_array($_POST['rows'])) {
            $this->response(['status' => FALSE, 'message' => 'Invalid request: rows array is required'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $ids = array_filter($_POST['rows'], 'is_numeric');
        $success_count = 0;
        $failed_ids = [];

        foreach ($ids as $id) {
            if ($this->Contracts_model->delete2($id)) {
                $success_count++;
            } else {
                $failed_ids[] = $id;
            }
        }

        $this->response([
            'status' => $success_count > 0,
            'message' => $success_count . 'Contract(s) deleted successfully',
            'failed_ids' => $failed_ids
        ], $success_count > 0 ? REST_Controller::HTTP_OK : REST_Controller::HTTP_NOT_FOUND);
    }



}
