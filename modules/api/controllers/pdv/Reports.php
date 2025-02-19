<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Reports extends REST_Controller
{
  function __construct()
  {
    parent::__construct();
    $this->load->model('Invoice_items_model');
    $this->load->model('Cashs_model');
  }

  public function product_sales_post()
  {
    $period = $this->post('period') ?: '6months';
    $category = $this->post('category');
    $warehouse = $this->post('warehouse');
    $stockStatus = $this->post('stockStatus');
    $topProductsLimit = $this->post('topProductsLimit') ?: 10;
    $orderBy = $this->post('orderBy') ?: 'quantidade';
    $customStartDate = $this->post('customStartDate');
    $customEndDate = $this->post('customEndDate');

    $page = max(0, $this->post('page') ? (int)$this->post('page') - 1 : 0);
    $pageSize = max(1, $this->post('pageSize') ? (int)$this->post('pageSize') : 10);

    $startDate = null;
    $endDate = date('Y-m-d 23:59:59');

    switch ($period) {
      case '1month':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
        break;
      case '3months':
        $startDate = date('Y-m-d H:i:s', strtotime('-3 months'));
        break;
      case '6months':
        $startDate = date('Y-m-d H:i:s', strtotime('-6 months'));
        break;
      case '12months':
        $startDate = date('Y-m-d H:i:s', strtotime('-12 months'));
        break;
      case 'thisMonth':
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-t 23:59:59');
        break;
      case 'custom':
        if ($customStartDate && $customEndDate) {
          $startDate = date('Y-m-d 00:00:00', strtotime($customStartDate));
          $endDate = date('Y-m-d 23:59:59', strtotime($customEndDate));
        }
        break;
    }

    $this->db->select('
            i.id,
            i.description,
            ig.name as category,
            SUM(ic.qty) as total_qty,
            AVG(ic.rate) as avg_price,
            SUM(ic.qty * ic.rate) as total_value,
            i.stock as current_stock,
            i.minStock,
            DATE_FORMAT(ic.data, "%Y-%m") as month_year,
            MIN(ic.data) as first_sale,
            MAX(ic.data) as last_sale
        ');
    $this->db->from(db_prefix() . 'items i');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.item_id = i.id');
    $this->db->join(db_prefix() . 'items_groups ig', 'ig.id = i.group_id', 'left');
    
    $this->db->where('ic.data BETWEEN "' . $startDate . '" AND "' . $endDate . '"');

    if ($category) {
      $this->db->where('ig.id', $category);
    }
    if ($warehouse) {
      $this->db->join(db_prefix() . 'cashs c', 'c.id = ic.cash_id');
      $this->db->where('c.warehouse_id', $warehouse);
    }
    if ($stockStatus) {
      switch ($stockStatus) {
        case 'baixo':
          $this->db->where('i.stock <= i.minStock');
          break;
        case 'esgotado':
          $this->db->where('i.stock', 0);
          break;
        case 'disponivel':
          $this->db->where('i.stock > i.minStock');
          break;
      }
    }

    $this->db->group_by('i.id, month_year');
    $this->db->order_by('total_qty', 'DESC');
    $this->db->limit(10);

    $chart_results = $this->db->get()->result_array();

    $monthlyData = [];
    foreach ($chart_results as $row) {
      $monthYear = $row['month_year'];

      if (!isset($monthlyData[$monthYear])) {
        $monthlyData[$monthYear] = [
          'month' => date('M', strtotime($monthYear . '-01')),
          'total_qty' => 0,
          'products' => []
        ];
      }

      $monthlyData[$monthYear]['total_qty'] += $row['total_qty'];
      $monthlyData[$monthYear]['products'][] = [
        'id' => $row['id'],
        'name' => $row['description'],
        'category' => $row['category'],
        'qty' => floatval($row['total_qty']),
        'price' => floatval($row['avg_price']),
        'total_value' => floatval($row['total_value']),
        'current_stock' => $row['current_stock'],
        'min_stock' => $row['minStock']
      ];
    }

    $this->db->select('COUNT(DISTINCT i.id) as total_count');
    $this->db->from(db_prefix() . 'items i');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.item_id = i.id');
    $this->db->join(db_prefix() . 'items_groups ig', 'ig.id = i.group_id', 'left');
    $this->db->where('ic.data >=', $startDate);
    $this->db->where('ic.data <=', $endDate);

    if ($category) {
      $this->db->where('ig.id', $category);
    }
    if ($warehouse) {
      $this->db->join(db_prefix() . 'cashs c', 'c.id = ic.cash_id');
      $this->db->where('c.warehouse_id', $warehouse);
    }
    if ($stockStatus) {
      switch ($stockStatus) {
        case 'baixo':
          $this->db->where('i.stock <= i.minStock');
          break;
        case 'esgotado':
          $this->db->where('i.stock', 0);
          break;
        case 'disponivel':
          $this->db->where('i.stock > i.minStock');
          break;
      }
    }

    $total_items = $this->db->get()->row()->total_count;

    $this->db->select('
            i.id,
            i.description,
            ig.name as category,
            SUM(ic.qty) as total_qty,
            AVG(ic.rate) as avg_price,
            SUM(ic.qty * ic.rate) as total_value,
            i.stock as current_stock,
            i.minStock
        ');
    $this->db->from(db_prefix() . 'items i');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.item_id = i.id');
    $this->db->join(db_prefix() . 'items_groups ig', 'ig.id = i.group_id', 'left');
    $this->db->where('ic.data >=', $startDate);
    $this->db->where('ic.data <=', $endDate);

    if ($category) {
      $this->db->where('ig.id', $category);
    }
    if ($warehouse) {
      $this->db->join(db_prefix() . 'cashs c', 'c.id = ic.cash_id');
      $this->db->where('c.warehouse_id', $warehouse);
    }
    if ($stockStatus) {
      switch ($stockStatus) {
        case 'baixo':
          $this->db->where('i.stock <= i.minStock');
          break;
        case 'esgotado':
          $this->db->where('i.stock', 0);
          break;
        case 'disponivel':
          $this->db->where('i.stock > i.minStock');
          break;
      }
    }

    $this->db->group_by('i.id');

    switch ($orderBy) {
      case 'quantidade':
        $this->db->order_by('total_qty', 'DESC');
        break;
      case 'valor':
        $this->db->order_by('total_value', 'DESC');
        break;
      case 'recente':
        $this->db->order_by('ic.data', 'DESC');
        break;
    }

    $this->db->limit($pageSize, $page * $pageSize);
    $table_results = $this->db->get()->result_array();

    $total_qty = array_sum(array_column($table_results, 'total_qty'));

    $productData = [];
    foreach ($table_results as $row) {
      $productData[] = [
        'id' => $row['id'],
        'name' => $row['description'],
        'category' => $row['category'],
        'total_qty' => floatval($row['total_qty']),
        'avg_price' => floatval($row['avg_price']),
        'total_value' => floatval($row['total_value']),
        'current_stock' => $row['current_stock'],
        'min_stock' => $row['minStock'],
        'sales_percentage' => $total_qty > 0 ? ($row['total_qty'] / $total_qty) * 100 : 0
      ];
    }

    ksort($monthlyData);

    $response = [
      'status' => true,
      'monthly_data' => array_values($monthlyData),
      'product_totals' => $productData,
      'pagination' => [
        'total' => (int)$total_items,
        'page' => $page + 1,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total_items / $pageSize)
      ]
    ];

    $this->response($response, REST_Controller::HTTP_OK);
  }

  public function cash_report_post()
  {
    $period = $this->post('period') ?: '6months';
    $franchise = $this->post('franchise');
    $cashier = $this->post('cashier');
    $status = $this->post('status');
    $paymentMethod = $this->post('paymentMethod');
    $customStartDate = $this->post('startDate');
    $customEndDate = $this->post('endDate');
    
    $page = max(0, $this->post('page') ? (int)$this->post('page') - 1 : 0);
    $pageSize = max(1, $this->post('pageSize') ? (int)$this->post('pageSize') : 10);

    $startDate = null;
    $endDate = date('Y-m-d 23:59:59');

    switch ($period) {
      case '1month':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
        break;
      case '3months':
        $startDate = date('Y-m-d H:i:s', strtotime('-3 months'));
        break;
      case '6months':
        $startDate = date('Y-m-d H:i:s', strtotime('-6 months'));
        break;
      case '12months':
        $startDate = date('Y-m-d H:i:s', strtotime('-12 months'));
        break;
      case 'thisMonth':
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-t 23:59:59');
        break;
      case 'custom':
        if ($customStartDate && $customEndDate) {
          $startDate = date('Y-m-d 00:00:00', strtotime($customStartDate));
          $endDate = date('Y-m-d 23:59:59', strtotime($customEndDate));
        }
        break;
    }

    $this->db->select('
        c.id,
        c.data,
        c.user_id,
        c.status,
        c.open_cash,
        c.close_cash,
        c.balance,
        c.balance_dinheiro,
        c.number as cash_number,
        c.sangria,
        c.observation,
        CONCAT(s.firstname, " ", s.lastname) as operator_name,
        TIMESTAMPDIFF(SECOND, c.data, NOW()) as processing_time
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->join(db_prefix() . 'staff s', 's.staffid = c.user_id', 'left');
    $this->db->where('c.data BETWEEN "' . $startDate . '" AND "' . $endDate . '"');

    if ($franchise) {
      $this->db->where('c.franchise_id', $franchise);
    }
    if ($cashier) {
      $this->db->where('c.number', $cashier);
    }
    if ($status) {
      $this->db->where('c.status', $status);
    }

    $total_count = $this->db->count_all_results('', false);

    $this->db->select('
        HOUR(ch.data) as hour,
        COUNT(*) as transactions,
        SUM(ch.balance) as total_value,
        AVG(ch.balance) as avg_ticket
    ');
    $this->db->from(db_prefix() . 'cashs ch');
    $this->db->join(db_prefix() . 'staff sh', 'sh.staffid = ch.user_id', 'left');
    $this->db->where('ch.data BETWEEN "' . $startDate . '" AND "' . $endDate . '"');
    if ($franchise) {
      $this->db->where('ch.franchise_id', $franchise);
    }
    if ($cashier) {
      $this->db->where('ch.number', $cashier);
    }
    if ($status) {
      $this->db->where('ch.status', $status);
    }
    $this->db->group_by('HOUR(ch.data)');
    $this->db->order_by('HOUR(ch.data)', 'ASC');
    $hourly_query = $this->db->get_compiled_select();
    $this->db->reset_query();

    $this->db->select('
        cc.number as cash_id,
        COUNT(*) as transactions,
        SUM(cc.balance) as total_value,
        AVG(cc.balance) as avg_value
    ');
    $this->db->from(db_prefix() . 'cashs cc');
    $this->db->join(db_prefix() . 'staff sc', 'sc.staffid = cc.user_id', 'left');
    $this->db->where('cc.data BETWEEN "' . $startDate . '" AND "' . $endDate . '"');
    if ($franchise) {
      $this->db->where('cc.franchise_id', $franchise);
    }
    if ($cashier) {
      $this->db->where('cc.number', $cashier);
    }
    if ($status) {
      $this->db->where('cc.status', $status);
    }
    $this->db->group_by('cc.number');
    $cashier_query = $this->db->get_compiled_select();
    $this->db->reset_query();

    $this->db->select('
        c.id,
        c.data,
        c.user_id,
        c.status,
        c.open_cash,
        c.close_cash,
        c.balance,
        c.balance_dinheiro,
        c.number as cash_number,
        c.sangria,
        c.observation,
        CONCAT(s.firstname, " ", s.lastname) as operator_name
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->join(db_prefix() . 'staff s', 's.staffid = c.user_id', 'left');
    $this->db->where('c.data BETWEEN "' . $startDate . '" AND "' . $endDate . '"');

    if ($franchise) {
      $this->db->where('c.franchise_id', $franchise);
    }
    if ($cashier) {
      $this->db->where('c.number', $cashier);
    }
    if ($status) {
      $this->db->where('c.status', $status);
    }

    $this->db->order_by('c.data', 'DESC');
    $this->db->limit($pageSize, $page * $pageSize);
    
    $transactions = $this->db->get()->result_array();

    $this->db->select('
        COUNT(*) as total_transactions,
        SUM(c.balance) as total_value,
        AVG(c.balance) as avg_ticket
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->where('c.data BETWEEN "' . $startDate . '" AND "' . $endDate . '"');
    if ($franchise) {
        $this->db->where('c.franchise_id', $franchise);
    }
    if ($cashier) {
        $this->db->where('c.number', $cashier);
    }
    if ($status) {
        $this->db->where('c.status', $status);
    }
    $metrics = $this->db->get()->row_array();

    $hourly_analysis = $this->db->query($hourly_query)->result_array();
    $cashier_analysis = $this->db->query($cashier_query)->result_array();

    $response = [
        'status' => true,
        'metrics' => [
            'total_value' => floatval($metrics['total_value']),
            'total_transactions' => (int)$metrics['total_transactions'],
            'avg_ticket' => floatval($metrics['avg_ticket']),
            'peak_hour' => $this->get_peak_hour($hourly_analysis)
        ],
        'hourly_analysis' => array_values(array_map(function($hour) {
            return [
                'hour' => (int)$hour['hour'],
                'transactions' => (int)$hour['transactions'],
                'total_value' => floatval($hour['total_value']),
                'avg_ticket' => floatval($hour['avg_ticket'])
            ];
        }, $hourly_analysis)),
        'cashier_analysis' => array_map(function($cashier) {
            return [
                'cash_id' => $cashier['cash_id'],
                'transactions' => (int)$cashier['transactions'],
                'total_value' => floatval($cashier['total_value']),
                'avg_value' => floatval($cashier['avg_value'])
            ];
        }, $cashier_analysis),
        'transactions' => array_map(function($transaction) {
            return [
                'id' => $transaction['id'],
                'date' => $transaction['data'],
                'cashier_id' => $transaction['cash_number'],
                'status' => $transaction['status'],
                'operator_name' => $transaction['operator_name'],
                'open_amount' => floatval($transaction['open_cash']),
                'close_amount' => floatval($transaction['close_cash']),
                'balance' => floatval($transaction['balance']),
                'cash_balance' => floatval($transaction['balance_dinheiro']),
                'withdrawals' => floatval($transaction['sangria']),
                'observation' => $transaction['observation']
            ];
        }, $transactions),
        'pagination' => [
            'total' => $total_count,
            'page' => $page + 1,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total_count / $pageSize)
        ]
    ];

    $this->response($response, REST_Controller::HTTP_OK);
  }

  private function get_peak_hour($hourly_analysis)
  {
    $peak = ['hour' => 0, 'value' => 0];
    foreach ($hourly_analysis as $hour) {
      if ($hour['total_value'] > $peak['value']) {
        $peak = [
          'hour' => $hour['hour'],
          'value' => $hour['total_value']
        ];
      }
    }
    return $peak['hour'];
  }

  public function franchises_get()
  {
    $this->db->select('id, name');
    $this->db->from(db_prefix() . 'franchises');
    $this->db->order_by('name', 'ASC');
    
    $query = $this->db->get();
    $franchises = $query->result_array();

    $this->response([
      'status' => true,
      'data' => $franchises
    ], REST_Controller::HTTP_OK);
  }

  public function cashiers_get($franchise_id = null)
  {
    $this->db->select('id, name, franchise_id');
    $this->db->from(db_prefix() . 'cash_registers');
    if ($franchise_id) {
      $this->db->where('franchise_id', $franchise_id);
    }
    $this->db->order_by('name', 'ASC');
    
    $query = $this->db->get();
    $cashiers = $query->result_array();

    $this->response([
      'status' => true,
      'data' => $cashiers
    ], REST_Controller::HTTP_OK);
  }
}
