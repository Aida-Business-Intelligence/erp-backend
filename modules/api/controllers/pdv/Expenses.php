<?php
if (!headers_sent()) {
  header('Content-Type: application/json');
  header('X-Content-Type-Options: nosniff');
}

defined('BASEPATH') or exit('No direct script access allowed');

/** @noinspection PhpIncludeInspection */
require __DIR__ . '/../REST_Controller.php';

class Expenses extends REST_Controller
{


  function __construct()
  {
    parent::__construct();
    $this->load->model('Expenses_model');
  }


  public function list_post()
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $page = $this->post('page') ? (int) $this->post('page') : 0;
    $limit = $this->post('pageSize') ? (int) $this->post('pageSize') : 10;
    $search = $this->post('search') ?: '';
    $sortField = $this->post('sortField') ?: db_prefix() . 'expenses.id';
    $sortOrder = $this->post('sortOrder') === 'desc' ? 'DESC' : 'ASC';

    $page = $page + 1;
    $offset = ($page - 1) * $limit;

    $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as taxrate,' . db_prefix() . 'taxes_2.name as tax_name2, ' . db_prefix() . 'taxes_2.taxrate as taxrate2, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'expenses.addedfrom as addedfrom,' .
      db_prefix() . 'expenses.recurring, ' .
      db_prefix() . 'expenses.recurring_type, ' .
      db_prefix() . 'expenses.repeat_every, ' .
      db_prefix() . 'expenses.cycles, ' .
      db_prefix() . 'expenses.total_cycles, ' .
      db_prefix() . 'expenses.custom_recurring, ' .
      db_prefix() . 'expenses.last_recurring_date, ' .
      'recurring_from');
    $this->db->from(db_prefix() . 'expenses');
    $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
    $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
    $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
    $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
    $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');

    if (!empty($search)) {
      $this->db->like('expenses.description', $search);
      $this->db->or_like('clients.company', $search);
      $this->db->or_like('expenses_categories.name', $search);
    }

    $this->db->order_by($sortField, $sortOrder);

    $total_query = clone $this->db;
    if (!empty($search)) {
      $total_query->group_start();
      $total_query->like('expenses.description', $search);
      $total_query->or_like('clients.company', $search);
      $total_query->or_like('expenses_categories.name', $search);
      $total_query->group_end();
    }
    $total = $total_query->count_all_results();

    $this->db->limit($limit, $offset);

    $data = $this->db->get()->result_array();

    if (empty($data)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Nenhum dado foi encontrado'
      ], REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    foreach ($data as &$expense) {
      if ($expense['recurring'] == 1) {
        $expense['recurring_info'] = array(
          'recurring' => true,
          'recurring_type' => $expense['recurring_type'],
          'repeat_every' => $expense['repeat_every'],
          'cycles_completed' => $expense['cycles'],
          'total_cycles' => $expense['total_cycles'],
          'custom_recurring' => $expense['custom_recurring'] == 1,
          'last_recurring_date' => $expense['last_recurring_date'],
        );
      } else {
        $expense['recurring_info'] = null;
      }
    }

    $this->response([
      'status' => TRUE,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
      'total_pages' => ceil($total / $limit),
      'data' => $data
    ], REST_Controller::HTTP_OK);
  }

  public function list_by_date_get()
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $page = $this->get('page') ? (int) $this->get('page') : 1;
    $limit = $this->get('pageSize') ? (int) $this->get('pageSize') : 10;
    $offset = ($page - 1) * $limit;

    $search = $this->get('search') ?: '';
    $sortField = $this->get('sortField') ?: db_prefix() . 'expenses.id';
    $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';

    $start_date = $this->get('start_date');
    $end_date = $this->get('end_date');

    $this->db->select('*,' . db_prefix() . 'expenses.id as id,' .
        db_prefix() . 'expenses_categories.name as category_name,' .
        db_prefix() . 'payment_modes.name as payment_mode_name,' .
        db_prefix() . 'taxes.name as tax_name, ' .
        db_prefix() . 'taxes.taxrate as taxrate,' .
        db_prefix() . 'taxes_2.name as tax_name2, ' .
        db_prefix() . 'taxes_2.taxrate as taxrate2, ' .
        db_prefix() . 'expenses.id as expenseid,' .
        db_prefix() . 'expenses.addedfrom as addedfrom');

    $this->db->from(db_prefix() . 'expenses');
    $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
    $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
    $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
    $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
    $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');

    if (!empty($start_date)) {
        $this->db->where('date >=', $start_date);
    }
    if (!empty($end_date)) {
        $this->db->where('date <=', $end_date);
    }

    if (!empty($search)) {
        $this->db->group_start();
        $this->db->like('expenses.note', $search);
        $this->db->or_like('clients.company', $search);
        $this->db->or_like('expenses_categories.name', $search);
        $this->db->group_end();
    }

    $this->db->order_by($sortField, $sortOrder);

    $total_query = clone $this->db;
    $total = $total_query->count_all_results();

    $this->db->limit($limit, $offset);
    $expenses = $this->db->get()->result_array();

    if (empty($expenses)) {
        $this->response([
            'status' => FALSE,
            'message' => 'Nenhum dado foi encontrado'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
    }

    foreach ($expenses as &$expense) {
        if ($expense['recurring'] == 1) {
            $expense['recurring_info'] = array(
                'recurring' => true,
                'recurring_type' => $expense['recurring_type'],
                'repeat_every' => $expense['repeat_every'],
                'cycles_completed' => $expense['cycles'],
                'total_cycles' => $expense['total_cycles'],
                'custom_recurring' => $expense['custom_recurring'] == 1,
                'last_recurring_date' => $expense['last_recurring_date']
            );
        } else {
            $expense['recurring_info'] = null;
        }
    }

    $this->response([
        'status' => TRUE,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit),
        'data' => $expenses
    ], REST_Controller::HTTP_OK);
  }

  private function calculate_recurring_dates($start_date, $recurring_type, $repeat_every, $range_start, $range_end, $total_cycles, $cycles_completed)
  {
    $dates = [];

    $current_date = strtotime($start_date);
    $range_start = $range_start ? strtotime($range_start) : 0;
    $range_end = $range_end ? strtotime($range_end) : PHP_INT_MAX;

    if (!$current_date || $current_date === false) {
      return $dates;
    }

    $cycles_remaining = ($total_cycles > 0) ? ($total_cycles - $cycles_completed) : 100; // Limit to 100 instances if infinite

    $max_iterations = 1000;
    $iteration = 0;

    while ($current_date <= $range_end && $cycles_remaining > 0 && $iteration < $max_iterations) {
      $iteration++;
      if ($current_date >= $range_start) {
        $dates[] = date('Y-m-d', $current_date);
        $cycles_remaining--;
      }

      switch ($recurring_type) {
        case 'day':
          $interval = "{$repeat_every} days";
          break;
        case 'week':
          $interval = "{$repeat_every} weeks";
          break;
        case 'month':
          $interval = "{$repeat_every} months";
          break;
        case 'year':
          $interval = "{$repeat_every} years";
          break;
        default:
          return $dates;
      }

      $next_date = strtotime($interval, $current_date);

      if ($next_date === false || $next_date <= $current_date) {
        break;
      }

      $current_date = $next_date;
    }

    return $dates;
  }

  private function format_currency($value)
  {
    return number_format($value, 2);
  }



  public function create_post()
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    log_activity('Expense Create Input: ' . json_encode($input));

    if (empty($input['category']) || empty($input['amount']) || empty($input['date'])) {
      $message = array(
        'status' => FALSE,
        'message' => 'Missing required fields: category, amount, and date are required'
      );
      $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    if (!empty($input['type']) && !in_array($input['type'], ['despesa', 'receita'])) {
      $message = array(
        'status' => FALSE,
        'message' => 'Invalid type: must be either "despesa" or "receita"'
      );
      $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $data = array(
      'category' => $input['category'],
      'amount' => $input['amount'],
      'date' => $input['date'],
      'note' => $input['note'] ?? '',
      'clientid' => $input['clientid'] ?? null,
      'paymentmode' => $input['paymentmode'] ?? null,
      'tax' => $input['tax'] ?? null,
      'tax2' => $input['tax2'] ?? null,
      'currency' => $input['currency'] ?? 3,
      'reference_no' => $input['reference_no'] ?? null,
      'addedfrom' => get_staff_user_id(),
      'type' => $input['type'] ?? 'despesa'
    );

    if (!empty($input['recurring']) && $input['recurring'] == 1) {
      if ($input['custom_recurring'] == 1) {
        $data['repeat_every'] = 'custom';
        $data['repeat_every_custom'] = $input['repeat_every'];
        $data['repeat_type_custom'] = $input['recurring_type'];
      } else {
        $data['repeat_every'] = $input['repeat_every'] . '-' . $input['recurring_type'];
      }

      if (isset($input['cycles'])) {
        $data['cycles'] = $input['cycles'];
      }
      if (isset($input['total_cycles'])) {
        $data['total_cycles'] = $input['total_cycles'];
      }
    }

    log_activity('Creating expense with data: ' . json_encode($data));

    $expense_id = $this->Expenses_model->add($data);

    if ($expense_id) {
      $expense = $this->Expenses_model->get($expense_id);

      log_activity('Created expense: ' . json_encode($expense));

      $message = array(
        'status' => TRUE,
        'message' => 'Despesa criada com sucesso',
        'data' => $expense
      );
      $this->response($message, REST_Controller::HTTP_CREATED);
    } else {
      log_activity('Failed to create expense. DB Error: ' . $this->db->error()['message']);

      $message = array(
        'status' => FALSE,
        'message' => 'Falha ao criar despesa'
      );
      $this->response($message, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function data_delete($id = '')
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $id = $this->security->xss_clean($id);

    if (empty($id) || !is_numeric($id)) {
      $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $this->load->model('Expenses_model');
    $output = $this->Expenses_model->delete($id);

    if (!$output) {
      $message = array('status' => FALSE, 'message' => 'Expense Delete Failed');
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $message = array('status' => TRUE, 'message' => 'Expense Deleted Successfully');
    $this->response($message, REST_Controller::HTTP_OK);
  }


  public function data_put()
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    if (empty($_POST) || !isset($_POST)) {
      $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
      $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
      return;
    }

    $this->form_validation->set_data($_POST);

    if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
      $message = array('status' => FALSE, 'message' => 'Invalid Expense ID');
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $update_data = $this->input->post();
    $expense_id = $_POST['id'];

    $this->load->model('Expenses_model');
    $output = $this->Expenses_model->update($update_data, $expense_id);

    if (!$output || empty($output)) {
      $message = array('status' => FALSE, 'message' => 'Expenses Update Fail.');
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $message = array(
      'status' => TRUE,
      'message' => 'Expenses Update Successful.',
      'data' => $this->Expenses_model->get($expense_id)
    );
    $this->response($message, REST_Controller::HTTP_OK);
  }

  public function totals_by_period_get()
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $start_date = $this->get('start_date');
    $end_date = $this->get('end_date');

    $this->db->select('SUM(amount) as total_amount, COUNT(*) as total_expenses');
    $this->db->from(db_prefix() . 'expenses');

    if (!empty($start_date)) {
      $this->db->where('date >=', $start_date);
    }

    if (!empty($end_date)) {
      $this->db->where('date <=', $end_date);
    }

    $result = $this->db->get()->row();

    if (empty($result) || $result->total_expenses == 0) {
      $this->response([
        'status' => FALSE,
        'message' => 'Nenhuma despesa encontrada no período'
      ], REST_Controller::HTTP_NOT_FOUND);
      return;
    }

    $this->response([
      'status' => TRUE,
      'data' => [
        'total_amount' => $result->total_amount,
        'total_expenses' => $result->total_expenses,
        'period' => [
          'start' => $start_date ?? 'all',
          'end' => $end_date ?? 'all'
        ]
      ]
    ], REST_Controller::HTTP_OK);
  }


  public function categories_get()
  {

    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $this->load->model('expenses_model');

    try {
      $categories = $this->expenses_model->get_category();

      if (empty($categories)) {
        $this->response([
          'status' => FALSE,
          'message' => 'Nenhuma categoria encontrada'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
      }

      $this->response([
        'status' => TRUE,
        'data' => $categories
      ], REST_Controller::HTTP_OK);
    } catch (Exception $e) {
      $this->response([
        'status' => FALSE,
        'message' => 'Erro ao buscar categorias',
        'error' => $e->getMessage()
      ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
      return;
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

      $output = $this->Expenses_model->delete($id);
      if ($output === TRUE) {
        $success_count++;
      } else {
        $failed_ids[] = $id;
      }
    }

    if ($success_count > 0) {
      $message = array(
        'status' => TRUE,
        'message' => $success_count . ' expense(s) deleted successfully'
      );
      if (!empty($failed_ids)) {
        $message['failed_ids'] = $failed_ids;
      }
      $this->response($message, REST_Controller::HTTP_OK);
    } else {
      $message = array(
        'status' => FALSE,
        'message' => 'Failed to delete expenses',
        'failed_ids' => $failed_ids
      );
      $this->response($message, REST_Controller::HTTP_NOT_FOUND);
    }
  }


  public function payment_post()
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    $input = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);

    if (empty($input['id']) || empty($input['status'])) {
        $this->response([
            'status' => FALSE,
            'message' => 'Missing required fields: id and status'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    if (!in_array($input['status'], ['pending', 'paid'])) {
        $this->response([
            'status' => FALSE,
            'message' => 'Invalid status value. Must be "pending" or "paid"'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $expense = $this->db->get_where(db_prefix() . 'expenses', ['id' => $input['id']])->row();
    if (!$expense) {
        $this->response([
            'status' => FALSE,
            'message' => 'Expense not found'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
    }

    $data = [];

    if ($input['status'] === 'paid') {
        if (empty($input['payment_date'])) {
            $this->response([
                'status' => FALSE,
                'message' => 'Payment date is required when marking as paid'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $data['last_recurring_date'] = $input['payment_date'];

        if ($expense->recurring == 1) {
            $new_cycles = (int)$expense->cycles + 1;
            $data['cycles'] = $new_cycles;

            if ($expense->total_cycles > 0 && $new_cycles >= $expense->total_cycles) {
                $data['status'] = 'paid';
            } else {
                $data['status'] = 'pending';
            }
        } else {
            $data['status'] = 'paid';
        }
    } else {
        $data['status'] = 'pending';
    }

    $this->db->where('id', $input['id']);
    $success = $this->db->update(db_prefix() . 'expenses', $data);

    if ($success) {
        $updated_expense = $this->db->get_where(db_prefix() . 'expenses', ['id' => $input['id']])->row();
        
        $this->response([
            'status' => TRUE,
            'message' => 'Payment status updated successfully',
            'data' => $updated_expense
        ], REST_Controller::HTTP_OK);
    } else {
        $this->response([
            'status' => FALSE,
            'message' => 'Failed to update payment status'
        ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function data_get($id = '')
  {
    \modules\api\core\Apiinit::the_da_vinci_code('api');

    if (empty($id)) {
        $this->response([
            'status' => FALSE,
            'message' => 'ID is required'
        ], REST_Controller::HTTP_BAD_REQUEST);
        return;
    }

    $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . 
        db_prefix() . 'expenses_categories.name as category_name,' . 
        db_prefix() . 'payment_modes.name as payment_mode_name,' . 
        db_prefix() . 'taxes.name as tax_name, ' . 
        db_prefix() . 'taxes.taxrate as taxrate,' . 
        db_prefix() . 'taxes_2.name as tax_name2, ' . 
        db_prefix() . 'taxes_2.taxrate as taxrate2');

    $this->db->from(db_prefix() . 'expenses');
    $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
    $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
    $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
    $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
    $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');
    
    $this->db->where(db_prefix() . 'expenses.id', $id);
    
    $expense = $this->db->get()->row();

    if (!$expense) {
        $this->response([
            'status' => FALSE,
            'message' => 'Expense not found'
        ], REST_Controller::HTTP_NOT_FOUND);
        return;
    }

    if ($expense->recurring == 1) {
        $expense->recurring_info = array(
            'recurring' => true,
            'recurring_type' => $expense->recurring_type,
            'repeat_every' => $expense->repeat_every,
            'cycles_completed' => $expense->cycles,
            'total_cycles' => $expense->total_cycles,
            'custom_recurring' => $expense->custom_recurring == 1,
            'last_recurring_date' => $expense->last_recurring_date,
        );
    } else {
        $expense->recurring_info = null;
    }

    $this->response([
        'status' => TRUE,
        'data' => $expense
    ], REST_Controller::HTTP_OK);
  }
}