<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require __DIR__ . '/REST_Controller.php';

class Suppliers extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Clients_model');
    }

    // Create (POST)
    public function data_post() {
        // Recebe os dados do supplier
        $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Validação dos dados
        if (empty($data)) {
            $this->response(['status' => FALSE, 'message' => 'No data provided'], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Insere o supplier no banco de dados
        $supplier_id = $this->Clients_model->add_supplier($data);

        if ($supplier_id) {
            $this->response(['status' => TRUE, 'message' => 'Supplier added successfully', 'id' => $supplier_id], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to add supplier'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Read (GET)
    public function data_get($id = '') {
        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('limit') ? (int) $this->get('limit') : 10;
        $search = $this->get('search') ?: '';
        $sortField = $this->get('sortField') ?: 'userid';
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';
        $data = $this->Clients_model->get_supplier($id, $page, $limit, $search, $sortField, $sortOrder);


        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No suppliers found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }

    // Update (PUT)
    public function data_put($id = '') {
        // Recebe os dados do supplier
        $data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

        // Validação dos dados
        if (empty($data) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid data or supplier ID'], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Atualiza o supplier no banco de dados
        $updated = $this->Clients_model->update_supplier($id, $data);

        if ($updated) {
            $this->response(['status' => TRUE, 'message' => 'Supplier updated successfully'], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to update supplier'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Delete (DELETE)
    public function data_delete($id = '') {
        // Validação do ID
        if (empty($id) || !is_numeric($id)) {
            $this->response(['status' => FALSE, 'message' => 'Invalid supplier ID'], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Remove o supplier do banco de dados
        $deleted = $this->Clients_model->delete_supplier($id);

        if ($deleted) {
            $this->response(['status' => TRUE, 'message' => 'Supplier deleted successfully'], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'Failed to delete supplier'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}