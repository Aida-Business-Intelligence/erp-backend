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
    $this->load->model('Clients_model');
  }

  public function data_get($id = '')
  {

    $page = $this->get('page') ? (int) $this->get('page') : 1; // Página atual, padrão 1
    $limit = $this->get('limit') ? (int) $this->get('limit') : 10; // Itens por página, padrão 10
    $search = $this->get('search') ?: ''; // Parâmetro de busca, se fornecido
    $sortField = $this->get('sortField') ?: 'userid'; // Campo para ordenação, padrão 'id'
    $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC'; // Ordem, padrão crescente
    $data = $this->Clients_model->get_supplier($id, $page, $limit, $search, $sortField, $sortOrder);

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
        $output = $this->Clients_model->add($representante);
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

        $output = $this->Clients_model->add($insert_data);
        if ($output > 0 && !empty($output)) {
          $message = array('status' => TRUE, 'message' => 'Client add successful.', 'data' => $this->Clients_model->get($output));
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
      $this->load->model('Clients_model');
      $output = $this->Clients_model->delete($id);
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
    $raw_body = $this->input->raw_input_stream;
    $headers = $this->input->request_headers();
    $content_type = $headers['Content-Type'] ?? $headers['content-type'] ?? null;

    try {
      $this->db->trans_start();

      // Decodificar o JSON do corpo da requisição
      $_POST = json_decode($raw_body, true);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
      }

      $primary_contact = $_POST['contacts'][0] ?? null;
      $primary_document = $_POST['documents'][0] ?? null;
      if (!$primary_contact || !$primary_document) {
        throw new Exception('Primary contact and document are required');
      }

      // Processar a imagem se existir
      $profile_image = null;
      if (!empty($_POST['image'])) {
        $image_data = $_POST['image'];

        // Verificar se é uma string base64 válida
        if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
          $image_data = substr($image_data, strpos($image_data, ',') + 1);
          $type = strtolower($type[1]); // jpg, png, gif

          if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            throw new Exception('Tipo de imagem inválido');
          }

          $image_data = base64_decode($image_data);

          if ($image_data === false) {
            throw new Exception('Falha ao decodificar a imagem');
          }

          // Criar diretório de uploads se não existir
          $upload_path = FCPATH . '/uploads/suppliers/';
          if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
          }

          // Gerar nome único para o arquivo
          $filename = 'supplier_' . time() . '_' . uniqid() . '.' . $type;
          $file_path = $upload_path . $filename;

          // Salvar a imagem no sistema de arquivos
          if (file_put_contents($file_path, $image_data)) {
            // Armazenar apenas o caminho relativo no banco de dados
            $profile_image = 'uploads/suppliers/' . $filename;
          } else {
            throw new Exception('Falha ao salvar a imagem no servidor');
          }
        }
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
        'email_default' => $primary_contact['email'] ?? null,
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
        'profile_image' => $profile_image // Armazena o caminho relativo da imagem
      ];

      $supplier_id = $this->Clients_model->add($supplier_data);
      if (!$supplier_id) {
        throw new Exception('Failed to create supplier');
      }

      // Processar documentos adicionais
      for ($i = 1; $i < count($_POST['documents']); $i++) {
        $document = $_POST['documents'][$i];
        $doc_data = [
          'supplier_id' => $supplier_id,
          'document' => $document['number'],
          'type' => strtoupper($document['type'])
        ];
        $this->db->insert(db_prefix() . 'document_supplier', $doc_data);
      }

      // Processar contatos adicionais
      for ($i = 1; $i < count($_POST['contacts']); $i++) {
        $contact = $_POST['contacts'][$i];

        $nome = trim($contact['name']);
        $partes = explode(' ', $nome);

        $firstname = $partes[0] ?? 'Contato';
        $lastname = isset($partes[1]) ? implode(' ', array_slice($partes, 1)) : 'N/A';

        $contact_data = [
          'userid' => $supplier_id,
          'firstname' => $firstname,
          'lastname' => $lastname,
          'phonenumber' => $contact['phone'] ?? 'N/A',
          'email' => $contact['email'] ?? 'N/A',
          'active' => 1,
          'is_primary' => 0,
          'datecreated' => date('Y-m-d H:i:s'),
        ];
        $this->Clients_model->add_contact($contact_data, $supplier_id, false);
      }

      $this->db->trans_complete();
      if ($this->db->trans_status() === FALSE) {
        throw new Exception('Transaction failed');
      }

      $this->response([
        'status' => TRUE,
        'message' => 'Supplier created successfully',
        'supplier_id' => $supplier_id,
        'image_url' => $profile_image ? base_url($profile_image) : null // Retorna a URL completa da imagem
      ], REST_Controller::HTTP_OK);
    } catch (Exception $e) {
      $this->db->trans_rollback();
      $this->response([
        'status' => FALSE,
        'message' => 'Error: ' . $e->getMessage()
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function update_put($id = '')
{
  if (empty($id) || !is_numeric($id)) {
    return $this->response([
      'status' => FALSE,
      'message' => 'Invalid supplier ID'
    ], REST_Controller::HTTP_BAD_REQUEST);
  }

  $raw_body = $this->input->raw_input_stream;
  $headers = $this->input->request_headers();
  $content_type = $headers['Content-Type'] ?? $headers['content-type'] ?? null;

  try {
    $this->db->trans_start();

    $_PUT = json_decode($raw_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new Exception('Invalid JSON input');
    }

    // Buscar caminho da imagem atual direto do banco
    $query = $this->db->select('profile_image')
      ->from('tblclients')
      ->where('userid', $id)
      ->get();

    if ($query->num_rows() === 0) {
      throw new Exception('Supplier not found');
    }

    $profile_image_db = $query->row()->profile_image;
    $old_image_path = !empty($profile_image_db) ? FCPATH . ltrim($profile_image_db, '/') : null;

    // Processar nova imagem se enviada
    $profile_image = null;
    if (!empty($_PUT['image'])) {
      $image_data = $_PUT['image'];

      if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
        $type = strtolower($type[1]);

        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
          throw new Exception('Tipo de imagem inválido');
        }

        $image_data = base64_decode($image_data);
        if ($image_data === false) {
          throw new Exception('Falha ao decodificar a imagem');
        }

        $upload_path = FCPATH . 'uploads/suppliers/';
        if (!is_dir($upload_path)) {
          mkdir($upload_path, 0755, true);
        }

        $filename = 'supplier_' . time() . '_' . uniqid() . '.' . $type;
        $file_path = $upload_path . $filename;

        if (file_put_contents($file_path, $image_data)) {
          $profile_image = 'uploads/suppliers/' . $filename;
          $_PUT['profile_image'] = $profile_image;

          // Apagar imagem antiga com segurança
          if ($old_image_path && file_exists($old_image_path)) {
            unlink($old_image_path);
          }
        } else {
          throw new Exception('Falha ao salvar a imagem no servidor');
        }
      }
    } elseif (isset($_PUT['remove_image']) && $_PUT['remove_image'] === true) {
      // Apenas remover imagem se instruído
      $_PUT['profile_image'] = null;

      if ($old_image_path && file_exists($old_image_path)) {
        unlink($old_image_path);
      }
    }

    log_activity('Supplier Update Payload: ' . print_r($_PUT, true));

    $this->load->model('Clients_model');
    $updated = $this->Clients_model->update_supplier($id, $_PUT);

    $this->db->trans_complete();
    if ($this->db->trans_status() === FALSE || !$updated) {
      throw new Exception('Failed to update supplier');
    }

    log_activity('Supplier updated successfully ID: ' . $id);
    return $this->response([
      'status' => TRUE,
      'message' => 'Supplier updated successfully',
      'supplier_id' => $id,
      'image_url' => $profile_image ? base_url($profile_image) : null
    ], REST_Controller::HTTP_OK);
  } catch (Exception $e) {
    $this->db->trans_rollback();
    return $this->response([
      'status' => FALSE,
      'message' => 'Error: ' . $e->getMessage()
    ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
  }
}


  public function get_get($id = '')
  {
    if (empty($id) || !is_numeric($id)) {
      return $this->response([
        'status' => FALSE,
        'message' => 'Invalid supplier ID'
      ], REST_Controller::HTTP_BAD_REQUEST);
    }

    $this->load->model('Clients_model');

    $supplier = $this->Clients_model->get($id);

    if (!$supplier) {
      return $this->response([
        'status' => FALSE,
        'message' => 'Supplier not found'
      ], REST_Controller::HTTP_NOT_FOUND);
    }

    return $this->response([
      'status' => TRUE,
      'data' => $supplier
    ], REST_Controller::HTTP_OK);
  }


  public function list_post()
  {
    $page = $this->post('page') ? (int) $this->post('page') : 1; // Já começa em 1 agora
    $limit = $this->post('limit') ? (int) $this->post('limit') : 10; // Mudei de pageSize para limit
    $search = $this->post('search') ?: '';
    $sortField = $this->post('sortField') ?: 'userid';
    $sortOrder = $this->post('sortOrder') === 'DESC' ? 'DESC' : 'ASC';
    $status = $this->post('status') ? (array) $this->post('status') : []; // Agora trata como array
    $startDate = $this->post('startDate') ?: '';
    $endDate = $this->post('endDate') ?: '';
    $warehouse_id = $this->post('warehouse_id') ? (int) $this->post('warehouse_id') : 0;

    $data = $this->Clients_model->get_api('', $page, $limit, $search, $sortField, $sortOrder, $status, $startDate, $endDate, $warehouse_id);

    if (empty($data['data'])) {
      // Adicione um log para debug
      log_message('debug', 'Suppliers query returned empty. Params: ' . json_encode($this->post()));
      $this->response(['status' => FALSE, 'message' => 'No data found'], REST_Controller::HTTP_NOT_FOUND);
    } else {
      $this->response([
        'status' => TRUE,
        'total' => $data['total'],
        'data' => $data['data']
      ], REST_Controller::HTTP_OK);
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

      $supplier_id = $this->Clients_model->add($supplier_data);

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

      $this->Clients_model->add_contact($contact_data, $supplier_id);

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
