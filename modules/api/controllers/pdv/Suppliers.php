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
    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);


    if (is_array($_POST) && count($_POST) === 0) {
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

      $this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
      if ($this->form_validation->run() == FALSE) {
        $message = array('status' => FALSE, 'error' => $this->form_validation->error_array(), 'message' => validation_errors());
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      } else {
        $groups_in = $this->Api_model->value($this->input->post('groups_in', TRUE));

        $output = $this->clients_model->add($insert_data);
        if ($output > 0 && !empty($output)) {
          $message = array('status' => TRUE, 'message' => 'Client add successful.', 'data' => $this->clients_model->get($output));
          $this->response($message, REST_Controller::HTTP_OK);
        } else {
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
      $this->load->model('clients_model');
      $output = $this->clients_model->delete($id);
      if ($output === TRUE) {
        $message = array('status' => TRUE, 'message' => 'Customer Delete Successful.');
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        $message = array('status' => FALSE, 'message' => 'Customer Delete Fail.');
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

      $required_fields = ['name', 'address', 'city', 'state', 'country', 'company_type', 'business_type', 'segment', 'company_size'];
      foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
          throw new Exception("Field {$field} is required");
        }
      }

      $percentage_fields = ['commission', 'commission_base_percentage', 'agent_commission_base_percentage'];
      foreach ($percentage_fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '' && $_POST[$field] !== null && ($_POST[$field] < 0 || $_POST[$field] > 100)) {
          throw new Exception("Field {$field} must be between 0 and 100");
        }
      }

      $due_day_fields = ['commission_due_day', 'agent_commission_due_day'];
      foreach ($due_day_fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '' && $_POST[$field] !== null && ($_POST[$field] < 1 || $_POST[$field] > 31)) {
          throw new Exception("Field {$field} must be between 1 and 31");
        }
      }

      $supplier_data = [
        'company' => $_POST['name'],
        'address' => $_POST['address'],
        'city' => $_POST['city'],
        'state' => $_POST['state'],
        'country' => $_POST['country'],
        'cep' => $_POST['cep'] ?? null,
        'payment_terms' => $_POST['paymentTerm'] ?? null,
        'active' => ($_POST['status'] === 'active') ? 1 : 0,
        'is_supplier' => 1,
        'datecreated' => date('Y-m-d H:i:s'),
        'phonenumber' => $primary_contact['phone'],
        'vat' => $primary_document['number'],
        'documentType' => strtoupper($primary_document['type']),
        'email_default' => $_POST['emails'][0] ?? null,
        'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
        'inscricao_municipal' => $_POST['inscricao_municipal'] ?? null,
        'warehouse_id' => $_POST['warehouse_id'] ?? 0,
        'company_type' => $_POST['company_type'],
        'business_type' => $_POST['business_type'],
        'segment' => $_POST['segment'],
        'company_size' => $_POST['company_size'],
        'observations' => $_POST['observations'] ?? null,
        'commission' => isset($_POST['commission']) ? (float)$_POST['commission'] : 0,
        'commercial_conditions' => $_POST['commercial_conditions'] ?? null,
        'commission_type' => $_POST['commission_type'] ?? null,
        'commission_base_percentage' => isset($_POST['commission_base_percentage']) ? (float)$_POST['commission_base_percentage'] : 0,
        'commission_payment_type' => $_POST['commission_payment_type'] ?? null,
        'commission_due_day' => isset($_POST['commission_due_day']) ? (int)$_POST['commission_due_day'] : null,
        'agent_commission_type' => $_POST['agent_commission_type'] ?? null,
        'agent_commission_base_percentage' => isset($_POST['agent_commission_base_percentage']) ? (float)$_POST['agent_commission_base_percentage'] : 0,
        'agent_commission_payment_type' => $_POST['agent_commission_payment_type'] ?? null,
        'agent_commission_due_day' => isset($_POST['agent_commission_due_day']) ? (int)$_POST['agent_commission_due_day'] : null,
        'tipo_frete' => $_POST['freight_type'] ?? null,
        'freight_value' => isset($_POST['freight_value']) ? (float)$_POST['freight_value'] : 0,
        'min_payment_term' => isset($_POST['min_payment_term']) ? (int)$_POST['min_payment_term'] : null,
        'max_payment_term' => isset($_POST['max_payment_term']) ? (int)$_POST['max_payment_term'] : null,
        'min_order_value' => isset($_POST['min_order_value']) ? (float)$_POST['min_order_value'] : null,
        'max_order_value' => isset($_POST['max_order_value']) ? (float)$_POST['max_order_value'] : null,
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

  public function update_put($id)
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

      $required_fields = ['name', 'address', 'city', 'state', 'country', 'company_type', 'business_type', 'segment', 'company_size'];
      foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
          throw new Exception("Field {$field} is required");
        }
      }

      $percentage_fields = ['commission', 'commission_base_percentage', 'agent_commission_base_percentage'];
      foreach ($percentage_fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '' && $_POST[$field] !== null && ($_POST[$field] < 0 || $_POST[$field] > 100)) {
          throw new Exception("Field {$field} must be between 0 and 100");
        }
      }

      $due_day_fields = ['commission_due_day', 'agent_commission_due_day'];
      foreach ($due_day_fields as $field) {
        if (isset($_POST[$field]) && $_POST[$field] !== '' && $_POST[$field] !== null && ($_POST[$field] < 1 || $_POST[$field] > 31)) {
          throw new Exception("Field {$field} must be between 1 and 31");
        }
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
        'email_default' => $_POST['emails'][0] ?? null,
        'inscricao_estadual' => $_POST['inscricao_estadual'] ?? null,
        'inscricao_municipal' => $_POST['inscricao_municipal'] ?? null,
        'company_type' => $_POST['company_type'] ?? null,
        'business_type' => $_POST['business_type'] ?? null,
        'segment' => $_POST['segment'] ?? null,
        'company_size' => $_POST['company_size'] ?? null,
        'observations' => $_POST['observations'] ?? null,
        'commission' => !empty($_POST['commission']) ? (float) $_POST['commission'] : 0,
        'commercial_conditions' => $_POST['commercial_conditions'] ?? null,
        'commission_type' => $_POST['commission_type'] ?? null,
        'commission_base_percentage' => !empty($_POST['commission_base_percentage']) ? (float) $_POST['commission_base_percentage'] : 0,
        'commission_payment_type' => $_POST['commission_payment_type'] ?? null,
        'commission_due_day' => !empty($_POST['commission_due_day']) ? (int) $_POST['commission_due_day'] : 0,
        'agent_commission_type' => $_POST['agent_commission_type'] ?? null,
        'agent_commission_base_percentage' => !empty($_POST['agent_commission_base_percentage']) ? (float) $_POST['agent_commission_base_percentage'] : 0,
        'agent_commission_payment_type' => $_POST['agent_commission_payment_type'] ?? null,
        'agent_commission_due_day' => !empty($_POST['agent_commission_due_day']) ? (int) $_POST['agent_commission_due_day'] : 0
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

      $this->db->where('userid', $id);
      $this->db->where('is_supplier', 1);
      $updated_supplier = $this->db->get(db_prefix() . 'clients')->row_array();

      $this->db->where('supplier_id', $id);
      $updated_documents = $this->db->get(db_prefix() . 'document_supplier')->result_array();

      $this->db->where('supplier_id', $id);
      $updated_emails = $this->db->get(db_prefix() . 'email_supplier')->result_array();

      $this->db->where('userid', $id);
      $updated_contacts = $this->db->get(db_prefix() . 'contacts')->result_array();

      $all_documents = array_merge(
        [
          [
            'type' => strtolower($updated_supplier['documentType']),
            'number' => $updated_supplier['vat']
          ]
        ],
        array_map(function ($doc) {
          return [
            'type' => strtolower($doc['type']),
            'number' => $doc['document']
          ];
        }, $updated_documents)
      );

      $all_emails = array_merge(
        [$updated_supplier['email_default']],
        array_column($updated_emails, 'email')
      );

      $all_contacts = array_merge(
        [
          [
            'name' => $updated_supplier['company'],
            'phone' => $updated_supplier['phonenumber']
          ]
        ],
        array_map(function ($contact) {
          return [
            'name' => $contact['firstname'],
            'phone' => $contact['phonenumber']
          ];
        }, $updated_contacts)
      );

      $response_data = [
        'userid' => $updated_supplier['userid'],
        'name' => $updated_supplier['company'],
        'address' => $updated_supplier['address'],
        'city' => $updated_supplier['city'],
        'state' => $updated_supplier['state'],
        'country' => $updated_supplier['country'],
        'paymentTerm' => $updated_supplier['payment_terms'],
        'status' => $updated_supplier['active'] ? 'active' : 'inactive',
        'documents' => $all_documents,
        'emails' => array_filter($all_emails),
        'contacts' => $all_contacts,
        'inscricao_estadual' => $updated_supplier['inscricao_estadual'],
        'inscricao_municipal' => $updated_supplier['inscricao_municipal'],
        'company_type' => $updated_supplier['company_type'],
        'business_type' => $updated_supplier['business_type'],
        'segment' => $updated_supplier['segment'],
        'company_size' => $updated_supplier['company_size'],
        'observations' => $updated_supplier['observations'],
        'commission' => (float) $updated_supplier['commission'],
        'commercial_conditions' => $updated_supplier['commercial_conditions'],
        'commission_type' => $updated_supplier['commission_type'],
        'commission_base_percentage' => (float) $updated_supplier['commission_base_percentage'],
        'commission_payment_type' => $updated_supplier['commission_payment_type'],
        'commission_due_day' => (int) $updated_supplier['commission_due_day'],
        'agent_commission_type' => $updated_supplier['agent_commission_type'],
        'agent_commission_base_percentage' => (float) $updated_supplier['agent_commission_base_percentage'],
        'agent_commission_payment_type' => $updated_supplier['agent_commission_payment_type'],
        'agent_commission_due_day' => (int) $updated_supplier['agent_commission_due_day']
      ];

      $this->response([
        'status' => TRUE,
        'message' => 'Supplier updated successfully',
        'data' => $response_data
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
      [
        [
          'type' => strtolower($supplier['documentType']),
          'number' => $supplier['vat']
        ]
      ],
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
      [
        [
          'name' => $supplier['company'],
          'phone' => $supplier['phonenumber']
        ]
      ],
      array_map(function ($contact) {
        return [
          'name' => $contact['firstname'],
          'phone' => $contact['phonenumber']
        ];
      }, $additional_contacts)
    );

    $response_data = [
      'userid' => $supplier['userid'],
      'name' => $supplier['company'],
      'address' => $supplier['address'],
      'city' => $supplier['city'],
      'state' => $supplier['state'],
      'country' => $supplier['country'],
      'paymentTerm' => $supplier['payment_terms'],
      'status' => $supplier['active'] ? 'active' : 'inactive',
      'documents' => $all_documents,
      'emails' => array_filter($all_emails),
      'contacts' => $all_contacts,
      'inscricao_estadual' => $supplier['inscricao_estadual'],
      'inscricao_municipal' => $supplier['inscricao_municipal'],
      'company_type' => $supplier['company_type'],
      'business_type' => $supplier['business_type'],
      'segment' => $supplier['segment'],
      'company_size' => $supplier['company_size'],
      'observations' => $supplier['observations'],
      'commission' => (float) $supplier['commission'],
      'commercial_conditions' => $supplier['commercial_conditions'],
      'commission_type' => $supplier['commission_type'],
      'commission_base_percentage' => (float) $supplier['commission_base_percentage'],
      'commission_payment_type' => $supplier['commission_payment_type'],
      'commission_due_day' => (int) $supplier['commission_due_day'],
      'agent_commission_type' => $supplier['agent_commission_type'],
      'agent_commission_base_percentage' => (float) $supplier['agent_commission_base_percentage'],
      'agent_commission_payment_type' => $supplier['agent_commission_payment_type'],
      'agent_commission_due_day' => (int) $supplier['agent_commission_due_day']
    ];

    $this->response([
      'status' => TRUE,
      'data' => $response_data
    ], REST_Controller::HTTP_OK);
  }

  public function list_get()
  {
    $page = $this->get('page') ? (int) $this->get('page') : 1;
    $limit = $this->get('limit') ? (int) $this->get('limit') : 10;
    $search = $this->get('search') ?: '';
    $status = $this->get('status');
    $sortField = $this->get('sortField') ?: 'userid';
    $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';
    $startDate = $this->get('startDate');
    $endDate = $this->get('endDate');
    $warehouse_id = $this->get('warehouse_id') ?: 0;

    $this->db->select('c.userid, c.company, c.vat, c.phonenumber, c.city, c.state, 
      c.country, c.active, c.datecreated, c.email_default, c.payment_terms,
      c.person_type, c.business_type, c.segment, c.company_size,
      c.inscricao_estadual, c.inscricao_municipal, c.observations,
      c.commission, c.commercial_conditions, c.commission_type,
      c.commission_base_percentage, c.commission_payment_type,
      c.commission_due_day, c.agent_commission_type,
      c.agent_commission_base_percentage, c.agent_commission_payment_type,
      c.agent_commission_due_day, c.address,
      GROUP_CONCAT(DISTINCT ds.document) as additional_documents,
      GROUP_CONCAT(DISTINCT es.email) as additional_emails,
      COUNT(DISTINCT co.id) as contacts_count', false);
    $this->db->from(db_prefix() . 'clients c');
    $this->db->join(db_prefix() . 'document_supplier ds', 'ds.supplier_id = c.userid', 'left');
    $this->db->join(db_prefix() . 'email_supplier es', 'es.supplier_id = c.userid', 'left');
    $this->db->join(db_prefix() . 'contacts co', 'co.userid = c.userid', 'left');
    $this->db->where('c.is_supplier', 1);
    $this->db->where('c.warehouse_id', $warehouse_id);
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

    if (!empty($startDate)) {
      $this->db->where('DATE(c.datecreated) >=', date('Y-m-d', strtotime($startDate)));
    }
    if (!empty($endDate)) {
      $this->db->where('DATE(c.datecreated) <=', date('Y-m-d', strtotime($endDate)));
    }

    $this->db->group_by('c.userid');

    $validSortFields = [
      'userid' => 'c.userid',
      'name' => 'c.company',
      'company' => 'c.company',
      'city' => 'c.city',
      'state' => 'c.state',
      'country' => 'c.country',
      'created_at' => 'c.datecreated',
      'status' => 'c.active'
    ];

    $sortFieldDB = isset($validSortFields[$sortField]) ? $validSortFields[$sortField] : 'c.company';
    $this->db->order_by($sortFieldDB, $sortOrder);

    $total = $this->db->count_all_results('', false);

    $this->db->limit($limit, ($page - 1) * $limit);
    $suppliers = $this->db->get()->result_array();

    foreach ($suppliers as &$supplier) {
      $this->db->select('firstname as name, phonenumber as phone');
      $this->db->where('userid', $supplier['userid']);
      $contacts = $this->db->get(db_prefix() . 'contacts')->result_array();

      $supplier['contacts'] = array_merge(
        [
          [
            'name' => $supplier['company'],
            'phone' => $supplier['phonenumber']
          ]
        ],
        $contacts
      );
    }

    $this->response([
      'status' => TRUE,
      'total' => (int) $total,
      'page' => (int) $page,
      'limit' => (int) $limit,
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
          'address' => $supplier['address'] ?? null,
          'city' => $supplier['city'] ?? null,
          'state' => $supplier['state'] ?? null,
          'country' => $supplier['country'] ?? null,
          'payment_terms' => $supplier['payment_terms'] ?? null,
          'emails' => array_filter(array_merge(
            [$supplier['email_default']],
            $supplier['additional_emails'] ? explode(',', $supplier['additional_emails']) : []
          )),
          'contacts' => $supplier['contacts'] ?? [],
          'contacts_count' => (int) ($supplier['contacts_count'] ?? 0),
          'status' => $supplier['active'] ? 'active' : 'inactive',
          'created_at' => $supplier['datecreated'] ?? null,
          'inscricao_estadual' => $supplier['inscricao_estadual'] ?? null,
          'inscricao_municipal' => $supplier['inscricao_municipal'] ?? null,
          'company_type' => $supplier['company_type'] ?? null,
          'business_type' => $supplier['business_type'] ?? null,
          'segment' => $supplier['segment'] ?? null,
          'company_size' => $supplier['company_size'] ?? null,
          'observations' => $supplier['observations'] ?? null,
          'commission' => !empty($supplier['commission']) ? (float) $supplier['commission'] : 0,
          'commercial_conditions' => $supplier['commercial_conditions'] ?? null,
          'commission_type' => $supplier['commission_type'] ?? null,
          'commission_base_percentage' => !empty($supplier['commission_base_percentage']) ? (float) $supplier['commission_base_percentage'] : 0,
          'commission_payment_type' => $supplier['commission_payment_type'] ?? null,
          'commission_due_day' => !empty($supplier['commission_due_day']) ? (int) $supplier['commission_due_day'] : 0,
          'agent_commission_type' => $supplier['agent_commission_type'] ?? null,
          'agent_commission_base_percentage' => !empty($supplier['agent_commission_base_percentage']) ? (float) $supplier['agent_commission_base_percentage'] : 0,
          'agent_commission_payment_type' => $supplier['agent_commission_payment_type'] ?? null,
          'agent_commission_due_day' => !empty($supplier['agent_commission_due_day']) ? (int) $supplier['agent_commission_due_day'] : 0
        ];
      }, $suppliers)
    ], REST_Controller::HTTP_OK);
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

    try {
      $this->db->trans_start();

      foreach ($ids as $id) {
        $id = $this->security->xss_clean($id);

        if (empty($id) || !is_numeric($id)) {
          $failed_ids[] = $id;
          continue;
        }

        $this->db->where('supplier_id', $id);
        $this->db->delete(db_prefix() . 'document_supplier');

        $this->db->where('supplier_id', $id);
        $this->db->delete(db_prefix() . 'email_supplier');

        $this->db->where('userid', $id);
        $this->db->delete(db_prefix() . 'contacts');

        $this->db->where('userid', $id);
        $this->db->where('is_supplier', 1);
        $this->db->delete(db_prefix() . 'clients');

        if ($this->db->affected_rows() > 0) {
          $success_count++;
        } else {
          $failed_ids[] = $id;
        }
      }

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        throw new Exception('Transaction failed');
      }

      if ($success_count > 0) {
        $message = array(
          'status' => TRUE,
          'message' => $success_count . ' supplier(s) deleted successfully'
        );
        if (!empty($failed_ids)) {
          $message['failed_ids'] = $failed_ids;
        }
        $this->response($message, REST_Controller::HTTP_OK);
      } else {
        $message = array(
          'status' => FALSE,
          'message' => 'Failed to delete suppliers',
          'failed_ids' => $failed_ids
        );
        $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      }
    } catch (Exception $e) {
      $this->db->trans_rollback();

      $message = array(
        'status' => FALSE,
        'message' => 'Error: ' . $e->getMessage(),
        'failed_ids' => $failed_ids
      );
      $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function create_from_nf_post()
  {
    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
    try {
      $this->db->trans_start();

      // Validar campos obrigatórios
      $required_fields = ['name', 'documents', 'contacts', 'emails'];
      foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
          throw new Exception("Campo {$field} é obrigatório");
        }
      }

      // Validar CNPJ
      $cnpj = $_POST['documents'][0]['number'] ?? '';
      if (empty($cnpj)) {
        throw new Exception("CNPJ é obrigatório");
      }

      // Verificar se CNPJ já existe
      $this->db->where('vat', $cnpj);
      $existing_supplier = $this->db->get(db_prefix() . 'clients')->row();
      if ($existing_supplier) {
        throw new Exception("CNPJ já cadastrado");
      }

      $primary_contact = $_POST['contacts'][0] ?? null;
      $primary_document = $_POST['documents'][0] ?? null;

      $supplier_data = [
        'company' => $_POST['name'],
        'vat' => $primary_document['number'],
        'documentType' => strtoupper($primary_document['type']),
        'phonenumber' => $primary_contact['phone'],
        'email_default' => $_POST['emails'][0] ?? null,
        'active' => 1,
        'is_supplier' => 1,
        'datecreated' => date('Y-m-d H:i:s'),
      ];

      $supplier_id = $this->clients_model->add($supplier_data);

      if (!$supplier_id) {
        throw new Exception('Falha ao criar fornecedor');
      }

      // Adicionar contato
      $contact_data = [
        'userid' => $supplier_id,
        'firstname' => $_POST['name'],
        'phonenumber' => $primary_contact['phone'],
        'active' => 1,
        'datecreated' => date('Y-m-d H:i:s')
      ];

      $this->clients_model->add_contact($contact_data, $supplier_id);

      $this->db->trans_complete();

      if ($this->db->trans_status() === FALSE) {
        throw new Exception('Falha na transação');
      }

      // Buscar dados do fornecedor criado
      $this->db->where('userid', $supplier_id);
      $this->db->where('is_supplier', 1);
      $created_supplier = $this->db->get(db_prefix() . 'clients')->row_array();

      $this->response([
        'status' => TRUE,
        'message' => 'Fornecedor criado com sucesso',
        'data' => [
          'userid' => $created_supplier['userid'],
          'company' => $created_supplier['company'],
          'vat' => $created_supplier['vat'],
          'documentType' => $created_supplier['documentType'],
          'phonenumber' => $created_supplier['phonenumber'],
          'email_default' => $created_supplier['email_default'],
        ]
      ], REST_Controller::HTTP_OK);

    } catch (Exception $e) {
      $this->db->trans_rollback();

      $this->response([
        'status' => FALSE,
        'message' => 'Erro: ' . $e->getMessage()
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
