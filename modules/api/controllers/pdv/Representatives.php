<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Representatives extends REST_Controller
{
  function __construct()
  {
    parent::__construct();
  }

  public function create_post()
  {
    $content_type = isset($this->input->request_headers()['Content-Type'])
      ? $this->input->request_headers()['Content-Type']
      : (isset($this->input->request_headers()['content-type'])
        ? $this->input->request_headers()['content-type']
        : null);

    $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

    if ($is_multipart) {
      $input = $this->input->post();

      if (isset($input['contacts']) && is_string($input['contacts'])) {
        $input['contacts'] = json_decode($input['contacts'], true);
      }

      if (isset($input['bankAccounts']) && is_string($input['bankAccounts'])) {
        $input['bankAccounts'] = json_decode($input['bankAccounts'], true);
      }

      if (isset($input['pricesTables']) && is_string($input['pricesTables'])) {
        $input['pricesTables'] = json_decode($input['pricesTables'], true);
      }
    } else {
      $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
    }

    $required_fields = ['corporateName', 'documentType', 'document', 'email', 'warehouse_id'];
    foreach ($required_fields as $field) {
      if (empty($input[$field])) {
        $this->response([
          'status' => FALSE,
          'message' => "Field {$field} is required"
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
      }
    }

    $percentage_fields = [
      'representativeBasePercentage',
      'representativeBonusForGoal',
      'representativeTaxDiscount',
      'agentBasePercentage',
      'agentBonusForGoal',
      'agentTaxDiscount'
    ];
    foreach ($percentage_fields as $field) {
      if (isset($input[$field]) && ($input[$field] < 0 || $input[$field] > 100)) {
        $this->response([
          'status' => FALSE,
          'message' => "Field {$field} must be between 0 and 100"
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
      }
    }

    $due_day_fields = ['representativeDueDate', 'agentDueDate'];
    foreach ($due_day_fields as $field) {
      if (isset($input[$field]) && ($input[$field] < 1 || $input[$field] > 31)) {
        $this->response([
          'status' => FALSE,
          'message' => "Field {$field} must be between 1 and 31"
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
      }
    }

    $this->db->trans_start();

    $representative_data = [
      'document_type' => strtolower($input['documentType']),
      'document' => $input['document'],
      'corporate_name' => $input['corporateName'],
      'trade_name' => $input['tradeName'] ?? null,
      'phone' => $input['phone'] ?? null,
      'email' => $input['email'],
      'status' => isset($input['status']) ? ($input['status'] ? 1 : 0) : 1,
      'code' => $input['code'] ?? null,
      'type' => $input['type'] ?? 'representative',
      'state_registration' => $input['stateRegistration'] ?? null,
      'warehouse_id' => $input['warehouse_id'],
      'created_at' => date('Y-m-d H:i:s'),

      'zip_code' => $input['zipCode'] ?? null,
      'street' => $input['street'] ?? null,
      'number' => $input['number'] ?? null,
      'complement' => $input['complement'] ?? null,
      'neighborhood' => $input['neighborhood'] ?? null,
      'city' => $input['city'] ?? null,
      'state' => $input['state'] ?? null,
      'country' => $input['country'] ?? 'BR',

      'segment' => $input['segment'] ?? null,
      'region' => $input['region'] ?? null,
      'commercial_policy' => $input['commercialPolicy'] ?? null,
      'minimum_monthly_goal' => !empty($input['minimumMonthlyGoal']) ? (float) $input['minimumMonthlyGoal'] : 0,
      'delivery_deadline' => !empty($input['deliveryDeadline']) ? (int) $input['deliveryDeadline'] : 0,
      'minimum_order' => !empty($input['minimumOrder']) ? (float) $input['minimumOrder'] : 0,
      'freight_type' => $input['freightType'] ?? null,

      'representative_commission_type' => $input['representativeCommissionType'] ?? null,
      'representative_base_percentage' => !empty($input['representativeBasePercentage']) ? (float) $input['representativeBasePercentage'] : 0,
      'representative_payment_method' => $input['representativePaymentMethod'] ?? null,
      'representative_due_date' => !empty($input['representativeDueDate']) ? (int) $input['representativeDueDate'] : 0,
      'representative_bonus_for_goal' => !empty($input['representativeBonusForGoal']) ? (float) $input['representativeBonusForGoal'] : 0,
      'representative_tax_discount' => !empty($input['representativeTaxDiscount']) ? (float) $input['representativeTaxDiscount'] : 0,

      'agent_commission_type' => $input['agentCommissionType'] ?? null,
      'agent_base_percentage' => !empty($input['agentBasePercentage']) ? (float) $input['agentBasePercentage'] : 0,
      'agent_payment_method' => $input['agentPaymentMethod'] ?? null,
      'agent_due_date' => !empty($input['agentDueDate']) ? (int) $input['agentDueDate'] : 0,
      'agent_bonus_for_goal' => !empty($input['agentBonusForGoal']) ? (float) $input['agentBonusForGoal'] : 0,
      'agent_tax_discount' => !empty($input['agentTaxDiscount']) ? (float) $input['agentTaxDiscount'] : 0,

      'nf_system' => $input['nfSystem'] ?? null,
      'digital_certificate_validity' => $input['digitalCertificateValidity'] ?? null,
      'legal_responsible' => $input['legalResponsible'] ?? null
    ];

    $this->db->insert(db_prefix() . 'representatives', $representative_data);
    $representative_id = $this->db->insert_id();

    if (!$representative_id) {
      $this->db->trans_rollback();
      $this->response([
        'status' => FALSE,
        'message' => 'Failed to create representative'
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    if (!empty($input['contacts']) && is_array($input['contacts'])) {
      foreach ($input['contacts'] as $contact) {
        if (empty($contact['name'])) {
          continue;
        }

        $contact_data = [
          'representative_id' => $representative_id,
          'name' => $contact['name'],
          'department' => $contact['department'] ?? null,
          'position' => $contact['position'] ?? null,
          'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert(db_prefix() . 'representative_contacts', $contact_data);
        $contact_id = $this->db->insert_id();

        if (!empty($contact['phones']) && is_array($contact['phones'])) {
          foreach ($contact['phones'] as $phone) {
            if (!empty($phone)) {
              $phone_data = [
                'contact_id' => $contact_id,
                'phone' => $phone
              ];
              $this->db->insert(db_prefix() . 'representative_contact_phones', $phone_data);

            }
          }
        }

        if (!empty($contact['emails']) && is_array($contact['emails'])) {
          foreach ($contact['emails'] as $email) {
            if (!empty($email)) {
              $email_data = [
                'contact_id' => $contact_id,
                'email' => $email
              ];
              $this->db->insert(db_prefix() . 'representative_contact_emails', $email_data);

            }
          }
        }
      }
    }

    if (!empty($input['bankAccounts']) && is_array($input['bankAccounts'])) {
      foreach ($input['bankAccounts'] as $account) {
        if (empty($account['bank']) || empty($account['accountNumber']) || empty($account['agency'])) {
          continue;
        }

        $bank_data = [
          'representative_id' => $representative_id,
          'bank' => $account['bank'],
          'account_type' => $account['accountType'],
          'agency' => $account['agency'],
          'account_number' => $account['accountNumber'],
          'holder' => $account['holder'],
          'document' => $account['document'],
          'pix_key' => $account['pixKey'] ?? null
        ];

        $this->db->insert(db_prefix() . 'representative_bank_accounts', $bank_data);
      }
    }

    if (!empty($input['pricesTables']) && is_array($input['pricesTables'])) {
      foreach ($input['pricesTables'] as $priceTable) {
        if (empty($priceTable['tableName'])) {
          continue;
        }

        $price_table_data = [
          'name' => $priceTable['tableName'],
          'currency' => $priceTable['currency'] ?? 'BRL',
          'max_discount' => !empty($priceTable['maxDiscount']) ? (float) $priceTable['maxDiscount'] : 0,
          'average_term' => !empty($priceTable['averageTerm']) ? (int) $priceTable['averageTerm'] : 0,
          'payment_terms' => $priceTable['paymentConditions'] ?? null,
          'installments' => !empty($priceTable['installments']) ? (int) $priceTable['installments'] : 1,
          'custom_terms' => $priceTable['customTerms'] ?? null,
          'start_date' => !empty($priceTable['startDate']) ? date('Y-m-d', strtotime($priceTable['startDate'])) : null,
          'end_date' => !empty($priceTable['endDate']) ? date('Y-m-d', strtotime($priceTable['endDate'])) : null,
          'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->where('name', $price_table_data['name']);
        $existing_table = $this->db->get(db_prefix() . 'pricing_tables')->row();

        $pricing_table_id = null;
        if ($existing_table) {
          $pricing_table_id = $existing_table->id;
        } else {
          $this->db->insert(db_prefix() . 'pricing_tables', $price_table_data);
          $pricing_table_id = $this->db->insert_id();
        }

        if ($pricing_table_id) {
          $link_data = [
            'representative_id' => $representative_id,
            'pricing_table_id' => $pricing_table_id,
            'created_at' => date('Y-m-d H:i:s')
          ];
          $this->db->insert(db_prefix() . 'representative_pricing_tables', $link_data);
        }
      }
    }

    if ($is_multipart) {
      $upload_dir = './uploads/representatives/' . $representative_id . '/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      $allowed_types = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      ];

      $max_size = 5 * 1024 * 1024;
      $file_fields = [
        'image' => 'profile_image',
        'socialContract' => 'social_contract',
        'cnpjCard' => 'cnpj_card',
        'negativeCertificate' => 'negative_certificate',
        'representationContract' => 'representation_contract'
      ];

      foreach ($file_fields as $field_name => $db_field) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
          $file = $_FILES[$field_name];

          if (!in_array($file['type'], $allowed_types)) {
            $this->db->trans_rollback();
            $this->response([
              'status' => FALSE,
              'message' => 'File type not allowed for ' . $field_name . '. Allowed types: JPG, PNG, PDF, DOC, DOCX'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
          }

          if ($file['size'] > $max_size) {
            $this->db->trans_rollback();
            $this->response([
              'status' => FALSE,
              'message' => 'File ' . $field_name . ' is too large. Maximum size is 5MB'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
          }

          $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
          $filename = $db_field . '_' . uniqid() . '.' . $extension;
          $upload_path = $upload_dir . $filename;

          if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $this->db->trans_rollback();
            $this->response([
              'status' => FALSE,
              'message' => 'Failed to upload file ' . $field_name
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
          }

          $server_url = base_url();
          $relative_path = str_replace('./', '', $upload_path);
          $file_url = rtrim($server_url, '/') . '/' . $relative_path;

          $this->db->where('id', $representative_id);
          $this->db->update(db_prefix() . 'representatives', [$db_field => $file_url]);
        }
      }
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      $this->response([
        'status' => FALSE,
        'message' => 'Transaction failed'
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    $this->response([
      'status' => TRUE,
      'message' => 'Representative created successfully',
      'representative_id' => $representative_id
    ], REST_Controller::HTTP_OK);
  }

  public function get_get($id = null)
  {
    if (empty($id) || !is_numeric($id)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Invalid representative ID'
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $this->db->where('id', $id);
    $representative = $this->db->get(db_prefix() . 'representatives')->row_array();

    if (!$representative) {
      $this->response([
        'status' => FALSE,
        'message' => 'Representative not found'
      ], REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $this->db->where('representative_id', $id);
    $contacts = $this->db->get(db_prefix() . 'representative_contacts')->result_array();

    $all_contacts = [];
    foreach ($contacts as $contact) {
      $this->db->where('contact_id', $contact['id']);
      $phones = $this->db->get(db_prefix() . 'representative_contact_phones')->result_array();

      $this->db->where('contact_id', $contact['id']);
      $emails = $this->db->get(db_prefix() . 'representative_contact_emails')->result_array();

      $all_contacts[] = [
        'name' => $contact['name'],
        'department' => $contact['department'],
        'position' => $contact['position'],
        'phones' => array_column($phones, 'phone'),
        'emails' => array_column($emails, 'email')
      ];
    }

    $this->db->where('representative_id', $id);
    $bank_accounts = $this->db->get(db_prefix() . 'representative_bank_accounts')->result_array();

    // Get pricing tables
    $this->db->select('pt.*');
    $this->db->from(db_prefix() . 'pricing_tables pt');
    $this->db->join(db_prefix() . 'representative_pricing_tables rpt', 'pt.id = rpt.pricing_table_id');
    $this->db->where('rpt.representative_id', $id);
    $pricing_tables = $this->db->get()->result_array();

    $formatted_pricing_tables = [];
    foreach ($pricing_tables as $table) {
      $formatted_pricing_tables[] = [
        'id' => $table['id'],
        'tableName' => $table['name'],
        'currency' => $table['currency'],
        'maxDiscount' => (float) $table['max_discount'],
        'averageTerm' => (int) $table['average_term'],
        'paymentConditions' => $table['payment_terms'],
        'installments' => (int) $table['installments'],
        'customTerms' => $table['custom_terms'] ?? '',
        'startDate' => $table['start_date'] ? new \DateTime($table['start_date']) : null,
        'endDate' => $table['end_date'] ? new \DateTime($table['end_date']) : null
      ];
    }

    $response_data = [
      'id' => $representative['id'],
      'code' => $representative['code'],
      'documentType' => $representative['document_type'],
      'document' => $representative['document'],
      'corporateName' => $representative['corporate_name'],
      'tradeName' => $representative['trade_name'],
      'email' => $representative['email'],
      'phone' => $representative['phone'],
      'status' => $representative['status'] ? true : false,
      'type' => $representative['type'],
      'stateRegistration' => $representative['state_registration'],
      'zipCode' => $representative['zip_code'],
      'street' => $representative['street'],
      'number' => $representative['number'],
      'complement' => $representative['complement'],
      'neighborhood' => $representative['neighborhood'],
      'city' => $representative['city'],
      'state' => $representative['state'],
      'country' => $representative['country'],
      'segment' => $representative['segment'],
      'region' => $representative['region'],
      'commercialPolicy' => $representative['commercial_policy'],
      'minimumMonthlyGoal' => (float) $representative['minimum_monthly_goal'],
      'deliveryDeadline' => (int) $representative['delivery_deadline'],
      'minimumOrder' => (float) $representative['minimum_order'],
      'freightType' => $representative['freight_type'],
      'representativeCommissionType' => $representative['representative_commission_type'],
      'representativeBasePercentage' => (float) $representative['representative_base_percentage'],
      'representativePaymentMethod' => $representative['representative_payment_method'],
      'representativeDueDate' => (int) $representative['representative_due_date'],
      'representativeBonusForGoal' => (float) $representative['representative_bonus_for_goal'],
      'representativeTaxDiscount' => (float) $representative['representative_tax_discount'],
      'agentCommissionType' => $representative['agent_commission_type'],
      'agentBasePercentage' => (float) $representative['agent_base_percentage'],
      'agentPaymentMethod' => $representative['agent_payment_method'],
      'agentDueDate' => (int) $representative['agent_due_date'],
      'agentBonusForGoal' => (float) $representative['agent_bonus_for_goal'],
      'agentTaxDiscount' => (float) $representative['agent_tax_discount'],
      'nfSystem' => $representative['nf_system'],
      'digitalCertificateValidity' => $representative['digital_certificate_validity'],
      'legalResponsible' => $representative['legal_responsible'],
      'profileImage' => $representative['profile_image'],
      'socialContract' => $representative['social_contract'],
      'cnpjCard' => $representative['cnpj_card'],
      'negativeCertificate' => $representative['negative_certificate'],
      'representationContract' => $representative['representation_contract'],
      'contacts' => $all_contacts,
      'bankAccounts' => $bank_accounts,
      'pricesTables' => $formatted_pricing_tables
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
    $sortField = $this->get('sortField') ?: 'id';
    $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';
    $startDate = $this->get('startDate');
    $endDate = $this->get('endDate');

    $this->db->select('*');
    $this->db->from(db_prefix() . 'representatives');

    if (!empty($search)) {
      $this->db->group_start();
      $this->db->like('corporate_name', $search);
      $this->db->or_like('document', $search);
      $this->db->or_like('email', $search);
      $this->db->or_like('phone', $search);
      $this->db->group_end();
    }

    if ($status === 'active') {
      $this->db->where('status', 1);
    } else if ($status === 'inactive') {
      $this->db->where('status', 0);
    }

    if (!empty($startDate)) {
      $this->db->where('DATE(created_at) >=', date('Y-m-d', strtotime($startDate)));
    }
    if (!empty($endDate)) {
      $this->db->where('DATE(created_at) <=', date('Y-m-d', strtotime($endDate)));
    }

    $validSortFields = [
      'id',
      'code',
      'corporate_name',
      'document',
      'email',
      'city',
      'state',
      'country',
      'created_at',
      'status'
    ];

    $sortFieldDB = in_array($sortField, $validSortFields) ? $sortField : 'id';
    $this->db->order_by($sortFieldDB, $sortOrder);

    $total = $this->db->count_all_results('', false);

    $this->db->limit($limit, ($page - 1) * $limit);
    $representatives = $this->db->get()->result_array();

    $data = [];
    foreach ($representatives as $representative) {
      $this->db->where('representative_id', $representative['id']);
      $contact_count = $this->db->count_all_results(db_prefix() . 'representative_contacts');

      $data[] = [
        'id' => $representative['id'],
        'code' => $representative['code'],
        'documentType' => $representative['document_type'],
        'document' => $representative['document'],
        'corporateName' => $representative['corporate_name'],
        'tradeName' => $representative['trade_name'],
        'email' => $representative['email'],
        'phone' => $representative['phone'],
        'city' => $representative['city'],
        'state' => $representative['state'],
        'status' => $representative['status'] ? 'active' : 'inactive',
        'createdAt' => $representative['created_at'],
        'contactCount' => $contact_count,
        'profileImage' => $representative['profile_image']
      ];
    }

    $this->response([
      'status' => TRUE,
      'total' => (int) $total,
      'page' => (int) $page,
      'limit' => (int) $limit,
      'data' => $data
    ], REST_Controller::HTTP_OK);
  }

  public function delete_delete($id = null)
  {
    if (!empty($id) && is_numeric($id)) {
      $ids = [$id];
    } else {
      $post_data = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

      if (empty($post_data) || !isset($post_data['ids']) || !is_array($post_data['ids']) || empty($post_data['ids'])) {
        $this->response([
          'status' => FALSE,
          'message' => 'No valid IDs provided for deletion'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
      }

      $ids = $post_data['ids'];
    }

    $this->db->trans_start();

    $deleted_count = 0;
    $failed_ids = [];

    foreach ($ids as $representative_id) {
      if (!is_numeric($representative_id)) {
        $failed_ids[] = $representative_id;
        continue;
      }

      $this->db->select('id');
      $this->db->where('representative_id', $representative_id);
      $contact_ids = $this->db->get(db_prefix() . 'representative_contacts')->result_array();
      $contact_ids = array_column($contact_ids, 'id');

      if (!empty($contact_ids)) {
        // Delete phones
        $this->db->where_in('contact_id', $contact_ids);
        $this->db->delete(db_prefix() . 'representative_contact_phones');

        $this->db->where_in('contact_id', $contact_ids);
        $this->db->delete(db_prefix() . 'representative_contact_emails');
      }

      $this->db->where('representative_id', $representative_id);
      $this->db->delete(db_prefix() . 'representative_contacts');

      $this->db->where('representative_id', $representative_id);
      $this->db->delete(db_prefix() . 'representative_bank_accounts');

      $this->db->where('representative_id', $representative_id);
      $this->db->delete(db_prefix() . 'representative_pricing_tables');

      $this->db->where('id', $representative_id);
      $this->db->delete(db_prefix() . 'representatives');

      if ($this->db->affected_rows() > 0) {
        $deleted_count++;
      } else {
        $failed_ids[] = $representative_id;
      }
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      $this->response([
        'status' => FALSE,
        'message' => 'Transaction failed, no representatives were deleted'
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    if ($deleted_count > 0) {
      $message = ($deleted_count === 1)
        ? 'Representative deleted successfully'
        : $deleted_count . ' representatives deleted successfully';

      if (!empty($failed_ids)) {
        $message .= '. Failed to delete IDs: ' . implode(', ', $failed_ids);
      }

      $this->response([
        'status' => TRUE,
        'message' => $message,
        'deleted_count' => $deleted_count,
        'failed_ids' => $failed_ids
      ], REST_Controller::HTTP_OK);
    } else {
      $this->response([
        'status' => FALSE,
        'message' => 'Representatives not found or already deleted',
        'failed_ids' => $failed_ids
      ], REST_Controller::HTTP_NOT_FOUND);
    }
  }

  public function update_post($id = null)
  {
    if (empty($id) || !is_numeric($id)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Invalid representative ID'
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $this->db->where('id', $id);
    $existing_representative = $this->db->get(db_prefix() . 'representatives')->row_array();

    if (!$existing_representative) {
      $this->response([
        'status' => FALSE,
        'message' => 'Representative not found'
      ], REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $content_type = isset($this->input->request_headers()['Content-Type'])
      ? $this->input->request_headers()['Content-Type']
      : (isset($this->input->request_headers()['content-type'])
        ? $this->input->request_headers()['content-type']
        : null);

    $is_multipart = $content_type && strpos($content_type, 'multipart/form-data') !== false;

    if ($is_multipart) {
      $input = $this->input->post();

      if (isset($input['contacts']) && is_string($input['contacts'])) {
        $input['contacts'] = json_decode($input['contacts'], true);
      }

      if (isset($input['bankAccounts']) && is_string($input['bankAccounts'])) {
        $input['bankAccounts'] = json_decode($input['bankAccounts'], true);
      }

      if (isset($input['pricesTables']) && is_string($input['pricesTables'])) {
        $input['pricesTables'] = json_decode($input['pricesTables'], true);
      }
    } else {
      $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
    }

    if (isset($input['email']) && empty($input['email'])) {
      $this->response([
        'status' => FALSE,
        'message' => "Email is required"
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $percentage_fields = [
      'representativeBasePercentage',
      'representativeBonusForGoal',
      'representativeTaxDiscount',
      'agentBasePercentage',
      'agentBonusForGoal',
      'agentTaxDiscount'
    ];
    foreach ($percentage_fields as $field) {
      if (isset($input[$field]) && ($input[$field] < 0 || $input[$field] > 100)) {
        $this->response([
          'status' => FALSE,
          'message' => "Field {$field} must be between 0 and 100"
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
      }
    }

    $due_day_fields = ['representativeDueDate', 'agentDueDate'];
    foreach ($due_day_fields as $field) {
      if (isset($input[$field]) && ($input[$field] < 1 || $input[$field] > 31)) {
        $this->response([
          'status' => FALSE,
          'message' => "Field {$field} must be between 1 and 31"
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
      }
    }

    $this->db->trans_start();

    $field_mappings = [
      'documentType' => 'document_type',
      'document' => 'document',
      'corporateName' => 'corporate_name',
      'tradeName' => 'trade_name',
      'phone' => 'phone',
      'email' => 'email',
      'status' => 'status',
      'code' => 'code',
      'type' => 'type',
      'stateRegistration' => 'state_registration',
      'warehouse_id' => 'warehouse_id',
      'zipCode' => 'zip_code',
      'street' => 'street',
      'number' => 'number',
      'complement' => 'complement',
      'neighborhood' => 'neighborhood',
      'city' => 'city',
      'state' => 'state',
      'country' => 'country',
      'segment' => 'segment',
      'region' => 'region',
      'commercialPolicy' => 'commercial_policy',
      'minimumMonthlyGoal' => 'minimum_monthly_goal',
      'deliveryDeadline' => 'delivery_deadline',
      'minimumOrder' => 'minimum_order',
      'freightType' => 'freight_type',
      'representativeCommissionType' => 'representative_commission_type',
      'representativeBasePercentage' => 'representative_base_percentage',
      'representativePaymentMethod' => 'representative_payment_method',
      'representativeDueDate' => 'representative_due_date',
      'representativeBonusForGoal' => 'representative_bonus_for_goal',
      'representativeTaxDiscount' => 'representative_tax_discount',
      'agentCommissionType' => 'agent_commission_type',
      'agentBasePercentage' => 'agent_base_percentage',
      'agentPaymentMethod' => 'agent_payment_method',
      'agentDueDate' => 'agent_due_date',
      'agentBonusForGoal' => 'agent_bonus_for_goal',
      'agentTaxDiscount' => 'agent_tax_discount',
      'nfSystem' => 'nf_system',
      'digitalCertificateValidity' => 'digital_certificate_validity',
      'legalResponsible' => 'legal_responsible'
    ];

    $representative_data = [];

    foreach ($field_mappings as $frontend_field => $db_field) {
      if (isset($input[$frontend_field])) {
        if ($frontend_field === 'status') {
          $representative_data[$db_field] = $input[$frontend_field] ? 1 : 0;
        } else if (in_array($frontend_field, ['minimumMonthlyGoal', 'minimumOrder', 'representativeBasePercentage', 'representativeBonusForGoal', 'representativeTaxDiscount', 'agentBasePercentage', 'agentBonusForGoal', 'agentTaxDiscount'])) {
          $representative_data[$db_field] = (float) $input[$frontend_field];
        } else if (in_array($frontend_field, ['deliveryDeadline', 'representativeDueDate', 'agentDueDate'])) {
          $representative_data[$db_field] = (int) $input[$frontend_field];
        } else {
          $representative_data[$db_field] = $input[$frontend_field];
        }
      }
    }

    $representative_data['updated_at'] = date('Y-m-d H:i:s');

    if (!empty($representative_data)) {
      $this->db->where('id', $id);
      $this->db->update(db_prefix() . 'representatives', $representative_data);
    }

    if (isset($input['contacts'])) {
      $this->db->select('id');
      $this->db->where('representative_id', $id);
      $existing_contacts = $this->db->get(db_prefix() . 'representative_contacts')->result_array();
      $existing_contact_ids = array_column($existing_contacts, 'id');

      $this->db->where('representative_id', $id);
      $this->db->delete(db_prefix() . 'representative_contacts');

      if (!empty($existing_contact_ids)) {
        $this->db->where_in('contact_id', $existing_contact_ids);
        $this->db->delete(db_prefix() . 'representative_contact_phones');

        $this->db->where_in('contact_id', $existing_contact_ids);
        $this->db->delete(db_prefix() . 'representative_contact_emails');
      }

      if (!empty($input['contacts']) && is_array($input['contacts'])) {
        foreach ($input['contacts'] as $contact) {
          if (empty($contact['name'])) {
            continue;
          }

          $contact_data = [
            'representative_id' => $id,
            'name' => $contact['name'],
            'department' => $contact['department'] ?? null,
            'position' => $contact['position'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
          ];

          $this->db->insert(db_prefix() . 'representative_contacts', $contact_data);
          $contact_id = $this->db->insert_id();

          if (!empty($contact['phones']) && is_array($contact['phones'])) {
            foreach ($contact['phones'] as $phone) {
              if (!empty($phone)) {
                $phone_data = [
                  'contact_id' => $contact_id,
                  'phone' => $phone
                ];
                $this->db->insert(db_prefix() . 'representative_contact_phones', $phone_data);
              }
            }
          }

          if (!empty($contact['emails']) && is_array($contact['emails'])) {
            foreach ($contact['emails'] as $email) {
              if (!empty($email)) {
                $email_data = [
                  'contact_id' => $contact_id,
                  'email' => $email
                ];
                $this->db->insert(db_prefix() . 'representative_contact_emails', $email_data);
              }
            }
          }
        }
      }
    }

    if (isset($input['bankAccounts'])) {
      $this->db->where('representative_id', $id);
      $this->db->delete(db_prefix() . 'representative_bank_accounts');

      if (!empty($input['bankAccounts']) && is_array($input['bankAccounts'])) {
        foreach ($input['bankAccounts'] as $account) {
          if (empty($account['bank']) || empty($account['accountNumber']) || empty($account['agency'])) {
            continue;
          }

          $bank_data = [
            'representative_id' => $id,
            'bank' => $account['bank'],
            'account_type' => $account['accountType'],
            'agency' => $account['agency'],
            'account_number' => $account['accountNumber'],
            'holder' => $account['holder'],
            'document' => $account['document'],
            'pix_key' => $account['pixKey'] ?? null
          ];

          $this->db->insert(db_prefix() . 'representative_bank_accounts', $bank_data);
        }
      }
    }

    if (isset($input['pricesTables'])) {
      $this->db->where('representative_id', $id);
      $this->db->delete(db_prefix() . 'representative_pricing_tables');

      if (!empty($input['pricesTables']) && is_array($input['pricesTables'])) {
        foreach ($input['pricesTables'] as $priceTable) {
          if (empty($priceTable['tableName'])) {
            continue;
          }

          $price_table_data = [
            'name' => $priceTable['tableName'],
            'currency' => $priceTable['currency'] ?? 'BRL',
            'max_discount' => !empty($priceTable['maxDiscount']) ? (float) $priceTable['maxDiscount'] : 0,
            'average_term' => !empty($priceTable['averageTerm']) ? (int) $priceTable['averageTerm'] : 0,
            'payment_terms' => $priceTable['paymentConditions'] ?? null,
            'installments' => !empty($priceTable['installments']) ? (int) $priceTable['installments'] : 1,
            'custom_terms' => $priceTable['customTerms'] ?? null,
            'start_date' => !empty($priceTable['startDate']) ? date('Y-m-d', strtotime($priceTable['startDate'])) : null,
            'end_date' => !empty($priceTable['endDate']) ? date('Y-m-d', strtotime($priceTable['endDate'])) : null,
            'created_at' => date('Y-m-d H:i:s')
          ];

          $this->db->where('name', $price_table_data['name']);
          $existing_table = $this->db->get(db_prefix() . 'pricing_tables')->row();

          $pricing_table_id = null;
          if ($existing_table) {
            $pricing_table_id = $existing_table->id;

            $this->db->where('id', $pricing_table_id);
            $this->db->update(db_prefix() . 'pricing_tables', $price_table_data);
          } else {
            $this->db->insert(db_prefix() . 'pricing_tables', $price_table_data);
            $pricing_table_id = $this->db->insert_id();
          }

          if ($pricing_table_id) {
            $link_data = [
              'representative_id' => $id,
              'pricing_table_id' => $pricing_table_id,
              'created_at' => date('Y-m-d H:i:s')
            ];
            $this->db->insert(db_prefix() . 'representative_pricing_tables', $link_data);
          }
        }
      }
    }

    if ($is_multipart) {
      $upload_dir = './uploads/representatives/' . $id . '/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }

      $allowed_types = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      ];

      $max_size = 5 * 1024 * 1024;
      $file_fields = [
        'image' => 'profile_image',
        'socialContract' => 'social_contract',
        'cnpjCard' => 'cnpj_card',
        'negativeCertificate' => 'negative_certificate',
        'representationContract' => 'representation_contract'
      ];

      foreach ($file_fields as $field_name => $db_field) {
        if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
          $file = $_FILES[$field_name];

          if (!in_array($file['type'], $allowed_types)) {
            $this->db->trans_rollback();
            $this->response([
              'status' => FALSE,
              'message' => 'File type not allowed for ' . $field_name . '. Allowed types: JPG, PNG, PDF, DOC, DOCX'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
          }

          if ($file['size'] > $max_size) {
            $this->db->trans_rollback();
            $this->response([
              'status' => FALSE,
              'message' => 'File ' . $field_name . ' is too large. Maximum size is 5MB'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
          }

          $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
          $filename = $db_field . '_' . uniqid() . '.' . $extension;
          $upload_path = $upload_dir . $filename;

          if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            $this->db->trans_rollback();
            $this->response([
              'status' => FALSE,
              'message' => 'Failed to upload file ' . $field_name
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
          }

          $server_url = base_url();
          $relative_path = str_replace('./', '', $upload_path);
          $file_url = rtrim($server_url, '/') . '/' . $relative_path;

          $this->db->where('id', $id);
          $this->db->update(db_prefix() . 'representatives', [$db_field => $file_url]);
        }
      }
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      $this->response([
        'status' => FALSE,
        'message' => 'Transaction failed'
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
      return;
    }

    $this->response([
      'status' => TRUE,
      'message' => 'Representative updated successfully',
      'representative_id' => $id
    ], REST_Controller::HTTP_OK);
  }
}