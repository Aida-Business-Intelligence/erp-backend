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
class Suppliers extends REST_Controller
{

  function __construct()
  {
    // Construct the parent class
    parent::__construct();
    $this->load->model('clients_model');
  }

  public function data_get($id = '')
  {


    /*
          $this->load->model('clients_model');

          $this->clients_model->add_import_items();
          exit;
         * 
         */

    $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
    $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
    $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
    $sortField = $this->get('sortField') ?: 'userid'; // Campo para ordenação, padrão 'id'
    $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
    $data = $this->clients_model->get_supplier($id, $page, $limit, $search, $sortField, $sortOrder);

    if ($data) {
      $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
    } else {
      $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
    }
  }


  public function data_post()
  {



    \modules\api\core\Apiinit::the_da_vinci_code('api');
    // Recebendo e decodificando os dados
    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);


    // Verificando se os dados são um único objeto
    if (is_array($_POST) && count($_POST) === 0) {
      // Caso de um array vazio
      echo "O array está vazio.";
      exit;
    } elseif (is_array($_POST) && isset($_POST[0]) && is_array($_POST[0])) {
      // Se for um array de objetos
      foreach ($_POST as $representante) {
        $output = $this->clients_model->add($representante);
      }

      $message = array('status' => TRUE, 'message' => 'Import add successful.', 'data' => []);
      $this->response($message, REST_Controller::HTTP_OK);
    } else {


      /*
              if (is_array($insert_data)) {
              // Se for um array e conter mais de um objeto
              foreach ($insert_data as $representante) {
              // Processar cada representante para cadastro em massa
              echo "Cadastro em massa para o representante: ";
              print_r($representante);
              }
              }
             * 
             */


      // form validation
      $this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
      if ($this->form_validation->run() == FALSE) {
        // form validation error
        $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      } else {
        $groups_in = $this->Api_model->value($this->input->post('groups_in', TRUE));

        /*
                 */
        // insert data

        $output = $this->clients_model->add($insert_data);
        if ($output > 0 && !empty($output)) {
          // success
          $message = array('status' => TRUE, 'message' => 'Client add successful.', 'data' => $this->clients_model->get($output));
          $this->response($message, REST_Controller::HTTP_OK);
        } else {
          // error
          $message = array('status' => FALSE, 'message' => 'Client add fail.');
          $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
      }
    }
  }


  public function data_delete($id = '')
  {
    $id = $this->security->xss_clean($id);
    if (empty($id) && !is_numeric($id)) {
      $message = array('status' => FALSE, 'message' => 'Invalid Customer ID');
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
    } else {
      // delete data
      $this->load->model('clients_model');
      $output = $this->clients_model->delete($id);
      if ($output === TRUE) {
        // success
        $message = array('status' => TRUE, 'message' => 'Customer Delete Successful.');
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        // error
        $message = array('status' => FALSE, 'message' => 'Customer Delete Fail.');
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      }
    }
  }

  public function data_put($id = '')
  {


    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    if (empty($_POST) || !isset($_POST)) {
      $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
      $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
    }
    $this->form_validation->set_data($_POST);
    if (empty($id) && !is_numeric($id)) {
      $message = array('status' => FALSE, 'message' => 'Invalid Customers ID');
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
    } else {
      $update_data = $this->input->post();
      // update data
      $this->load->model('clients_model');
      $output = $this->clients_model->update($update_data, $id);
      if ($output > 0 && !empty($output)) {
        // success
        $message = array('status' => TRUE, 'message' => 'Customers Update Successful.', 'data' => $this->clients_model->get($id));
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        // error
        $message = array('status' => FALSE, 'message' => 'Customers Update Fail.');
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      }
    }
  }

  public function create_post()
  {
    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    try {
      $this->db->trans_start();

      $primary_contact = $_POST['contacts'][0] ?? null;
      $primary_document = $_POST['documents'][0] ?? null;

      if (!$primary_contact || !$primary_document) {
        throw new Exception('Primary contact and document are required');
      }

      $supplier_data = [
        'company' => $_POST['name'],
        'address' => $_POST['address'],
        'city' => $_POST['city'],
        'state' => $_POST['state'],
        'country' => $_POST['country'],
        'payment_terms' => $_POST['paymentTerm'],
        'active' => $_POST['status'] === 'active' ? 1 : 0,
        'is_supplier' => 1,
        'datecreated' => date('Y-m-d H:i:s'),
        'phonenumber' => $primary_contact['phone'],
        'vat' => $primary_document['number'],
        'documentType' => strtoupper($primary_document['type']),
        'email_default' => $_POST['emails'][0] ?? null
      ];

      $supplier_id = $this->clients_model->add($supplier_data);

      if (!$supplier_id) {
        throw new Exception('Failed to create supplier');
      }

      for ($i = 1; $i < count($_POST['documents']); $i++) {
        $document = $_POST['documents'][$i];
        $doc_data = [
          'supplier_id' => $supplier_id,
          'document' => $document['number'],
          'type' => strtoupper($document['type'])
        ];

        $this->db->insert(db_prefix() . 'document_supplier', $doc_data);
      }

      for ($i = 1; $i < count($_POST['emails']); $i++) {
        $email = $_POST['emails'][$i];
        if (!empty($email)) {
          $email_data = [
            'supplier_id' => $supplier_id,
            'email' => $email
          ];

          $this->db->insert(db_prefix() . 'email_supplier', $email_data);
        }
      }

      for ($i = 1; $i < count($_POST['contacts']); $i++) {
        $contact = $_POST['contacts'][$i];
        $contact_data = [
          'userid' => $supplier_id,
          'firstname' => $contact['name'],
          'phonenumber' => $contact['phone'],
          'active' => 1,
          'datecreated' => date('Y-m-d H:i:s')
        ];

        $this->clients_model->add_contact($contact_data, $supplier_id);
      }

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        throw new Exception('Transaction failed');
      }

      $this->response([
        'status' => TRUE,
        'message' => 'Supplier created successfully',
        'supplier_id' => $supplier_id
      ], REST_Controller::HTTP_OK);
    } catch (Exception $e) {
      $this->db->trans_rollback();

      $this->response([
        'status' => FALSE,
        'message' => 'Error: ' . $e->getMessage()
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function update_post($id)
  {
    if (empty($id) || !is_numeric($id)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Invalid supplier ID'
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    try {
      $this->db->trans_start();

      $primary_contact = $_POST['contacts'][0] ?? null;
      $primary_document = $_POST['documents'][0] ?? null;

      if (!$primary_contact || !$primary_document) {
        throw new Exception('Primary contact and document are required');
      }

      $supplier_data = [
        'company' => $_POST['name'],
        'address' => $_POST['address'],
        'city' => $_POST['city'],
        'state' => $_POST['state'],
        'country' => $_POST['country'],
        'payment_terms' => $_POST['paymentTerm'],
        'active' => $_POST['status'] === 'active' ? 1 : 0,
        'phonenumber' => $primary_contact['phone'],
        'vat' => $primary_document['number'],
        'documentType' => strtoupper($primary_document['type']),
        'email_default' => $_POST['emails'][0] ?? null
      ];

      $this->clients_model->update($supplier_data, $id);

      $this->db->where('supplier_id', $id);
      $this->db->delete(db_prefix() . 'document_supplier');

      for ($i = 1; $i < count($_POST['documents']); $i++) {
        $document = $_POST['documents'][$i];
        $doc_data = [
          'supplier_id' => $id,
          'document' => $document['number'],
          'type' => strtoupper($document['type'])
        ];

        $this->db->insert(db_prefix() . 'document_supplier', $doc_data);
      }

      $this->db->where('supplier_id', $id);
      $this->db->delete(db_prefix() . 'email_supplier');

      for ($i = 1; $i < count($_POST['emails']); $i++) {
        $email = $_POST['emails'][$i];
        if (!empty($email)) {
          $email_data = [
            'supplier_id' => $id,
            'email' => $email
          ];

          $this->db->insert(db_prefix() . 'email_supplier', $email_data);
        }
      }

      $this->db->where('userid', $id);
      $this->db->delete(db_prefix() . 'contacts');

      for ($i = 1; $i < count($_POST['contacts']); $i++) {
        $contact = $_POST['contacts'][$i];
        $contact_data = [
          'userid' => $id,
          'firstname' => $contact['name'],
          'phonenumber' => $contact['phone'],
          'active' => 1,
          'datecreated' => date('Y-m-d H:i:s')
        ];

        $this->clients_model->add_contact($contact_data, $id);
      }

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        throw new Exception('Transaction failed');
      }

      $this->response([
        'status' => TRUE,
        'message' => 'Supplier updated successfully'
      ], REST_Controller::HTTP_OK);
    } catch (Exception $e) {
      $this->db->trans_rollback();

      $this->response([
        'status' => FALSE,
        'message' => 'Error: ' . $e->getMessage()
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function get_get($id = '')
  {
    if (empty($id) || !is_numeric($id)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Invalid supplier ID'
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $this->db->where('userid', $id);
    $this->db->where('is_supplier', 1);
    $supplier = $this->db->get(db_prefix() . 'clients')->row_array();

    if (!$supplier) {
      $this->response([
        'status' => FALSE,
        'message' => 'Supplier not found'
      ], REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $this->db->where('supplier_id', $id);
    $documents = $this->db->get(db_prefix() . 'document_supplier')->result_array();

    $this->db->where('supplier_id', $id);
    $additional_emails = $this->db->get(db_prefix() . 'email_supplier')->result_array();

    $this->db->where('userid', $id);
    $additional_contacts = $this->db->get(db_prefix() . 'contacts')->result_array();

    $all_documents = array_merge(
      [[
        'type' => strtolower($supplier['documentType']),
        'number' => $supplier['vat']
      ]],
      array_map(function ($doc) {
        return [
          'type' => strtolower($doc['type']),
          'number' => $doc['document']
        ];
      }, $documents)
    );

    $all_emails = array_merge(
      [$supplier['email_default']],
      array_column($additional_emails, 'email')
    );

    $all_contacts = array_merge(
      [[
        'name' => $supplier['company'],
        'phone' => $supplier['phonenumber']
      ]],
      array_map(function ($contact) {
        return [
          'name' => $contact['firstname'],
          'phone' => $contact['phonenumber']
        ];
      }, $additional_contacts)
    );

    $response_data = [
      'name' => $supplier['company'],
      'address' => $supplier['address'],
      'city' => $supplier['city'],
      'state' => $supplier['state'],
      'country' => $supplier['country'],
      'paymentTerm' => $supplier['payment_terms'],
      'status' => $supplier['active'] ? 1 : 0,
      'documents' => $all_documents,
      'emails' => array_filter($all_emails),
      'contacts' => $all_contacts
    ];

    $this->response([
      'status' => TRUE,
      'data' => $response_data
    ], REST_Controller::HTTP_OK);
  }

  public function list_get()
  {
    $page = $this->get('page') ? (int)$this->get('page') : 1;
    $limit = $this->get('limit') ? (int)$this->get('limit') : 10;
    $search = $this->get('search') ?: '';
    $status = $this->get('status');

    $this->db->select('c.userid, c.company, c.vat, c.phonenumber, c.city, c.state, 
      c.country, c.active, c.datecreated, c.email_default, c.payment_terms,
      GROUP_CONCAT(DISTINCT ds.document) as additional_documents,
      GROUP_CONCAT(DISTINCT es.email) as additional_emails,
      COUNT(DISTINCT co.id) as contacts_count', false);
    $this->db->from(db_prefix() . 'clients c');
    $this->db->join(db_prefix() . 'document_supplier ds', 'ds.supplier_id = c.userid', 'left');
    $this->db->join(db_prefix() . 'email_supplier es', 'es.supplier_id = c.userid', 'left');
    $this->db->join(db_prefix() . 'contacts co', 'co.userid = c.userid', 'left');
    $this->db->where('c.is_supplier', 1);

    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like('c.company', $search);
      $this->db->or_like('c.vat', $search);
      $this->db->or_like('c.email_default', $search);
      $this->db->or_like('ds.document', $search);
      $this->db->or_like('es.email', $search);
      $this->db->group_end();
    }

    if ($status === 'active') {
      $this->db->where('c.active', 1);
    } else if ($status === 'inactive') {
      $this->db->where('c.active', 0);
    }

    $this->db->group_by('c.userid');

    $total = $this->db->count_all_results('', false);

    $this->db->limit($limit, ($page - 1) * $limit);
    $suppliers = $this->db->get()->result_array();

    foreach ($suppliers as &$supplier) {
      $this->db->select('firstname as name, phonenumber as phone');
      $this->db->where('userid', $supplier['userid']);
      $contacts = $this->db->get(db_prefix() . 'contacts')->result_array();

      $supplier['contacts'] = array_merge(
        [[
          'name' => $supplier['company'],
          'phone' => $supplier['phonenumber']
        ]],
        $contacts
      );
    }

    $this->response([
      'status' => TRUE,
      'total' => (int)$total,
      'page' => (int)$page,
      'limit' => (int)$limit,
      'data' => array_map(function ($supplier) {
        return [
          'userid' => $supplier['userid'],
          'company' => $supplier['company'],
          'documents' => array_merge(
            [['type' => 'cnpj', 'number' => $supplier['vat']]],
            array_map(function ($doc) {
              return ['type' => 'cnpj', 'number' => $doc];
            }, $supplier['additional_documents'] ? explode(',', $supplier['additional_documents']) : [])
          ),
          'city' => $supplier['city'],
          'state' => $supplier['state'],
          'payment_terms' => $supplier['payment_terms'],
          'emails' => array_filter(array_merge(
            [$supplier['email_default']],
            $supplier['additional_emails'] ? explode(',', $supplier['additional_emails']) : []
          )),
          'contacts' => $supplier['contacts'],
          'contacts_count' => (int)$supplier['contacts_count'],
          'status' => (int)$supplier['active'],
          'created_at' => $supplier['datecreated']
        ];
      }, $suppliers)
    ], REST_Controller::HTTP_OK);
  }
}
