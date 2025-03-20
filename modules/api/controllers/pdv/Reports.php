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
    $warehouse_id = $this->post('warehouse_id');
    $stockStatus = $this->post('stockStatus');
    $topProductsLimit = $this->post('topProductsLimit') ?: 10;
    $orderBy = $this->post('orderBy') ?: 'quantidade';
    $customStartDate = $this->post('customStartDate');
    $customEndDate = $this->post('customEndDate');

    if (empty($warehouse_id)) {
      $this->response([
        'status' => FALSE,
        'message' => 'Warehouse ID is required'
      ], REST_Controller::HTTP_BAD_REQUEST);
      return;
    }

    $page = max(0, $this->post('page') ? (int) $this->post('page') - 1 : 0);
    $pageSize = max(1, $this->post('pageSize') ? (int) $this->post('pageSize') : 10);

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

    if (!$startDate) {
      $startDate = date('Y-m-d H:i:s', strtotime('-6 months'));
    }

    $this->db->select('
            i.id,
            i.description,
            i.stock as current_stock,
            i.minStock,
            i.cost,
            i.rate as price,
            i.sku_code,
            i.barcode,
            ig.name as category,
            COALESCE(SUM(ic.qty), 0) as total_qty,
            COALESCE(AVG(ic.rate), i.rate) as avg_price,
            COALESCE(SUM(ic.qty * ic.rate), 0) as total_value,
            DATE_FORMAT(ic.data, "%Y-%m") as month_year,
            MIN(ic.data) as first_sale,
            MAX(ic.data) as last_sale
        ');
    $this->db->from(db_prefix() . 'items i');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.item_id = i.id', 'left');
    $this->db->join(db_prefix() . 'items_groups ig', 'ig.id = i.group_id', 'left');
    $this->db->join(db_prefix() . 'cashs c', 'c.id = ic.cash_id', 'left');
    $this->db->where('i.warehouse_id', $warehouse_id);
    $this->db->where('i.active', 1);
    $this->db->where('c.status', 1); 
    $this->db->where('ic.data >=', $startDate);
    $this->db->where('ic.data <=', $endDate);

    if ($category) {
      $this->db->where('ig.id', $category);
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
    $this->db->having('total_qty > 0');  
    $this->db->order_by('total_qty', 'DESC');
    $this->db->limit(10);

    $chart_results = $this->db->get()->result_array();

    $monthlyData = [];
    foreach ($chart_results as $row) {
      $monthYear = $row['month_year'] ?: date('Y-m', strtotime($startDate));

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
        'min_stock' => $row['minStock'],
        'cost' => floatval($row['cost']),
        'sku_code' => $row['sku_code'],
        'barcode' => $row['barcode']
      ];
    }

    // Count query
    $this->db->select('COUNT(DISTINCT i.id) as total_count');
    $this->db->from(db_prefix() . 'items i');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.item_id = i.id', 'left');
    $this->db->join(db_prefix() . 'items_groups ig', 'ig.id = i.group_id', 'left');
    $this->db->join(db_prefix() . 'cashs c', 'c.id = ic.cash_id', 'left');
    $this->db->where('i.warehouse_id', $warehouse_id);
    $this->db->where('i.active', 1);
    $this->db->where('c.status', 1); 
    $this->db->where('ic.data >=', $startDate);
    $this->db->where('ic.data <=', $endDate);

    if ($category) {
      $this->db->where('ig.id', $category);
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
    $this->db->having('SUM(COALESCE(ic.qty, 0)) > 0');  
    $count_result = $this->db->get()->row();
    $total_items = $count_result ? $count_result->total_count : 0;

    $this->db->select('
            i.id,
            i.description,
            i.stock as current_stock,
            i.minStock,
            i.cost,
            i.rate as price,
            i.sku_code,
            i.barcode,
            ig.name as category,
            COALESCE(SUM(ic.qty), 0) as total_qty,
            COALESCE(AVG(ic.rate), i.rate) as avg_price,
            COALESCE(SUM(ic.qty * ic.rate), 0) as total_value
        ');
    $this->db->from(db_prefix() . 'items i');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.item_id = i.id', 'left');
    $this->db->join(db_prefix() . 'items_groups ig', 'ig.id = i.group_id', 'left');
    $this->db->join(db_prefix() . 'cashs c', 'c.id = ic.cash_id', 'left');
    $this->db->where('i.warehouse_id', $warehouse_id);
    $this->db->where('i.active', 1);
    $this->db->where('c.status', 1);
    $this->db->where('ic.data >=', $startDate);
    $this->db->where('ic.data <=', $endDate);

    if ($category) {
      $this->db->where('ig.id', $category);
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
    $this->db->having('total_qty > 0');  

    switch ($orderBy) {
      case 'quantidade':
        $this->db->order_by('total_qty', 'DESC');
        break;
      case 'valor':
        $this->db->order_by('total_value', 'DESC');
        break;
      case 'recente':
        $this->db->order_by('MAX(ic.data)', 'DESC');
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
        'total' => (int) $total_items,
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

    $page = max(0, $this->post('page') ? (int) $this->post('page') - 1 : 0);
    $pageSize = max(1, $this->post('pageSize') ? (int) $this->post('pageSize') : 10);

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
        'total_transactions' => (int) $metrics['total_transactions'],
        'avg_ticket' => floatval($metrics['avg_ticket']),
        'peak_hour' => $this->get_peak_hour($hourly_analysis)
      ],
      'hourly_analysis' => array_values(array_map(function ($hour) {
        return [
          'hour' => (int) $hour['hour'],
          'transactions' => (int) $hour['transactions'],
          'total_value' => floatval($hour['total_value']),
          'avg_ticket' => floatval($hour['avg_ticket'])
        ];
      }, $hourly_analysis)),
      'cashier_analysis' => array_map(function ($cashier) {
        return [
          'cash_id' => $cashier['cash_id'],
          'transactions' => (int) $cashier['transactions'],
          'total_value' => floatval($cashier['total_value']),
          'avg_value' => floatval($cashier['avg_value'])
        ];
      }, $cashier_analysis),
      'transactions' => array_map(function ($transaction) {
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

  public function stock_report_post()
  {
    $period = $this->post('period') ?: '6months';
    $franchise = $this->post('franchise');
    $category = $this->post('category');
    $stockStatus = $this->post('stockStatus');
    $performanceFilter = $this->post('performanceFilter');
    $customStartDate = $this->post('startDate');
    $customEndDate = $this->post('endDate');
    $orderBy = $this->post('orderBy') ?: 'depletion';
    // $warehouse_id = $this->post('warehouse_id') ?: 0;

    $page = max(0, $this->post('page') ? (int) $this->post('page') - 1 : 0);
    $pageSize = max(1, $this->post('pageSize') ? (int) $this->post('pageSize') : 10);

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

    if (!$startDate) {
      $startDate = date('Y-m-d H:i:s', strtotime('-6 months'));
    }

    $this->db->select(
      db_prefix() . 'items.id,
        ' . db_prefix() . 'items.description as name,
        ' . db_prefix() . 'items.stock as currentStock,
        ' . db_prefix() . 'items.minStock,
        ' . db_prefix() . 'items.rate as price,
        ' . db_prefix() . 'items.purchase_price as cost,
        ' . db_prefix() . 'items_groups.name as category,
        COUNT(' . db_prefix() . 'itemcash.id) as totalSales,
        COALESCE(SUM(' . db_prefix() . 'itemcash.qty), 0) as total_qty_sold,
        MIN(' . db_prefix() . 'itemcash.data) as first_sale,
        MAX(' . db_prefix() . 'itemcash.data) as last_sale,
        DATEDIFF(NOW(), MIN(' . db_prefix() . 'itemcash.data)) as daysInStock'
    );

    $this->db->from(db_prefix() . 'items');
    $this->db->join(db_prefix() . 'items_groups', db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');
    $this->db->join(db_prefix() . 'itemcash', db_prefix() . 'itemcash.item_id = ' . db_prefix() . 'items.id', 'left');

    $this->db->where(db_prefix() . 'itemcash.data >=', $startDate);
    $this->db->where(db_prefix() . 'itemcash.data <=', $endDate);

    // // Adicionando a condição do warehouse_id
    // if ($warehouse_id) {
    //   $this->db->where(db_prefix() . 'items.warehouse_id', $warehouse_id);
    // }


    if ($franchise) {
      $this->db->join(db_prefix() . 'cashs', db_prefix() . 'cashs.id = ' . db_prefix() . 'itemcash.cash_id');
      $this->db->where(db_prefix() . 'cashs.franchise_id', $franchise);
    }

    if ($category) {
      $this->db->where(db_prefix() . 'items.group_id', $category);
    }

    if ($stockStatus) {
      switch ($stockStatus) {
        case 'critical':
          $this->db->where(db_prefix() . 'items.stock <= ' . db_prefix() . 'items.minStock');
          break;
        case 'warning':
          $this->db->where(db_prefix() . 'items.stock > ' . db_prefix() . 'items.minStock');
          $this->db->where(db_prefix() . 'items.stock <= (' . db_prefix() . 'items.minStock * 1.5)');
          break;
        case 'ok':
          $this->db->where(db_prefix() . 'items.stock > (' . db_prefix() . 'items.minStock * 1.5)');
          break;
      }
    }

    $this->db->group_by(db_prefix() . 'items.id');

    $total_count = $this->db->count_all_results('', false);

    switch ($orderBy) {
      case 'depletion':
        $this->db->order_by(db_prefix() . 'items.stock', 'ASC');
        break;
      case 'turnover':
        $this->db->order_by('total_qty_sold', 'DESC');
        break;
      case 'stock':
        $this->db->order_by(db_prefix() . 'items.stock', 'ASC');
        break;
      default:
        $this->db->order_by(db_prefix() . 'items.stock', 'ASC');
    }

    $this->db->limit($pageSize, $page * $pageSize);
    $items = $this->db->get()->result_array();

    $metrics = [
      'critical_count' => 0,
      'warning_count' => 0,
      'high_turnover_count' => 0,
      'excess_stock_count' => 0
    ];

    $processed_items = [];


    foreach ($items as $item) {
      $days_analyzed = max(1, (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24));

      $total_qty_sold = floatval($item['total_qty_sold']) ?: 0;
      $daily_sales_rate = $days_analyzed > 0 ? $total_qty_sold / $days_analyzed : 0;

      $current_stock = max(0, (int) $item['currentStock']);
      $days_to_depletion = $daily_sales_rate > 0 ? $current_stock / $daily_sales_rate : ($current_stock > 0 ? 999999 : 0);

      $turnover_rate = $current_stock > 0 ? ($total_qty_sold / $current_stock) * 100 : 0;

      $price = floatval($item['price']) ?: 0;
      $cost = floatval($item['cost']) ?: 0;
      $profit_margin = $cost > 0 ? ($price - $cost) / $cost : 0;

      $status = 'ok';
      $min_stock = max(0, (int) $item['minStock']);

      if ($current_stock <= $min_stock) {
        $status = 'critical';
        $metrics['critical_count']++;
      } elseif ($current_stock <= ($min_stock * 1.5)) {
        $status = 'warning';
        $metrics['warning_count']++;
      }

      if ($turnover_rate > 50) {
        $metrics['high_turnover_count']++;
      }

      if ($days_to_depletion > 180 && $current_stock > ($min_stock * 2)) {
        $metrics['excess_stock_count']++;
      }

      $suggestion = [];
      if ($days_to_depletion < 30 && $turnover_rate > 30 && $profit_margin > 0.3) {
        $suggestion = [
          'action' => 'increase',
          'suggestion' => 'Aumentar estoque em 50%. Produto com alta rotatividade e boa margem de lucro.',
          'priority' => 'high'
        ];
      } elseif ($days_to_depletion > 90 && $turnover_rate < 10) {
        $suggestion = [
          'action' => 'decrease',
          'suggestion' => 'Reduzir estoque em 30%. Baixa rotatividade e capital parado.',
          'priority' => 'medium'
        ];
      } else {
        $suggestion = [
          'action' => 'maintain',
          'suggestion' => 'Manter níveis atuais de estoque.',
          'priority' => 'low'
        ];
      }

      $processed_items[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'category' => $item['category'],
        'currentStock' => $current_stock,
        'minStock' => $min_stock,
        'depletionInfo' => [
          'days' => round($days_to_depletion),
          'rate' => round($daily_sales_rate, 2),
          'isQuickDepleting' => $days_to_depletion < 30,
          'isSlowDepleting' => $days_to_depletion > 90
        ],
        'turnoverRate' => round($turnover_rate, 2),
        'totalSales' => (int) $total_qty_sold,
        'daysInStock' => (int) ($item['daysInStock'] ?: $days_analyzed),
        'status' => $status,
        'suggestion' => $suggestion
      ];
    }

    $response = [
      'status' => true,
      'metrics' => $metrics,
      'items' => $processed_items,
      'pagination' => [
        'total' => (int) $total_count,
        'page' => $page + 1,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total_count / $pageSize)
      ]
    ];

    $this->response($response, REST_Controller::HTTP_OK);
  }

  public function sales_report_post()
  {
    $period = $this->post('period') ?: '6months';
    $pdvBox = $this->post('pdvBox');
    $status = $this->post('status');
    $customStartDate = $this->post('customStartDate');
    $customEndDate = $this->post('customEndDate');

    $page = max(0, $this->post('page') ? (int) $this->post('page') - 1 : 0);
    $pageSize = max(1, $this->post('pageSize') ? (int) $this->post('pageSize') : 10);
    $warehouse_id = $this->post('warehouse_id') ?: 0;

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

    if (!$startDate) {
      $startDate = date('Y-m-d H:i:s', strtotime('-6 months'));
    }

    $this->db->select('
      DATE_FORMAT(c.data, "%Y-%m") as month_year,
      DATE_FORMAT(c.data, "%b") as month,
      COUNT(DISTINCT c.id) as transactions,
      SUM(c.balance) as total_value,
      COUNT(DISTINCT ic.id) as total_items
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->join(db_prefix() . 'itemcash ic', 'ic.cash_id = c.id', 'left');
    $this->db->where('c.data >=', $startDate);
    $this->db->where('c.data <=', $endDate);
    $this->db->where('c.warehouse_id', $warehouse_id);

    if ($pdvBox) {
      $this->db->where('c.number', $pdvBox);
    }
    if ($status) {
      $this->db->where('c.status', $status);
    }

    $this->db->group_by('month_year');
    $this->db->order_by('month_year', 'ASC');
    $monthly_data = $this->db->get()->result_array();

    $this->db->select('
      c.id,
      c.data as date,
      c.number as pdvBoxId,
      CONCAT("Caixa ", c.number) as pdvBoxName,
      c.status,
      c.balance as total
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->where('c.data >=', $startDate);
    $this->db->where('c.data <=', $endDate);
    $this->db->where('c.warehouse_id', $warehouse_id);

    if ($pdvBox) {
      $this->db->where('c.number', $pdvBox);
    }
    if ($status) {
      $this->db->where('c.status', $status);
    }

    $total_count = $this->db->count_all_results('', false);

    $this->db->order_by('c.data', 'DESC');
    $this->db->limit($pageSize, $page * $pageSize);
    $sales = $this->db->get()->result_array();

    $this->db->select('
      HOUR(c.data) as hour,
      COUNT(*) as transactions,
      SUM(c.balance) as total_value,
      AVG(c.balance) as avg_ticket
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->where('c.data >=', $startDate);
    $this->db->where('c.data <=', $endDate);
    $this->db->where('c.warehouse_id', $warehouse_id);

    if ($pdvBox) {
      $this->db->where('c.number', $pdvBox);
    }
    if ($status) {
      $this->db->where('c.status', $status);
    }

    $this->db->group_by('HOUR(c.data)');
    $this->db->order_by('HOUR(c.data)', 'ASC');
    $hourly_data = $this->db->get()->result_array();

    $filled_hourly_data = [];
    for ($i = 0; $i < 24; $i++) {
      $hour_exists = false;
      foreach ($hourly_data as $hour) {
        if ((int) $hour['hour'] === $i) {
          $filled_hourly_data[] = $hour;
          $hour_exists = true;
          break;
        }
      }
      if (!$hour_exists) {
        $filled_hourly_data[] = [
          'hour' => $i,
          'transactions' => 0,
          'total_value' => 0,
          'avg_ticket' => 0
        ];
      }
    }

    $response = [
      'status' => true,
      'monthly_data' => array_map(function ($month) {
        return [
          'month' => $month['month'],
          'total_value' => floatval($month['total_value']),
          'transactions' => (int) $month['transactions'],
          'total_items' => (int) $month['total_items']
        ];
      }, $monthly_data),
      'hourly_data' => array_map(function ($hour) {
        return [
          'hour' => sprintf('%02d:00', $hour['hour']),
          'transactions' => (int) $hour['transactions'],
          'total_value' => floatval($hour['total_value']),
          'avg_ticket' => floatval($hour['avg_ticket'] ?? 0)
        ];
      }, $filled_hourly_data),
      'sales' => array_map(function ($sale) {
        return [
          'id' => $sale['id'],
          'date' => $sale['date'],
          'pdvBoxId' => $sale['pdvBoxId'],
          'pdvBoxName' => $sale['pdvBoxName'],
          'status' => $sale['status'],
          'total' => floatval($sale['total'])
        ];
      }, $sales),
      'pagination' => [
        'total' => (int) $total_count,
        'page' => $page + 1,
        'pageSize' => $pageSize,
        'totalPages' => ceil($total_count / $pageSize)
      ]
    ];

    $this->response($response, REST_Controller::HTTP_OK);
  }

  public function financial_report_post()
  {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $current_month = date('Y-m');
    $previous_month = date('Y-m', strtotime('-1 month'));

    $this->db->select('
      COALESCE(SUM(c.balance), 0) as total_sales,
      COALESCE(SUM(CASE WHEN e.operacao = "dinheiro" THEN e.total ELSE 0 END), 0) as cash_sales,
      COALESCE(SUM(CASE WHEN e.operacao = "cartao" THEN e.total ELSE 0 END), 0) as card_sales,
      COUNT(DISTINCT c.id) as transaction_count
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->join(db_prefix() . 'cashextracts e', 'e.cash_id = c.id', 'left');
    $this->db->where('DATE(c.data)', $today);
    $today_data = $this->db->get()->row_array();

    $this->db->select('
      COALESCE(SUM(c.balance), 0) as total_sales,
      COALESCE(SUM(CASE WHEN e.operacao = "dinheiro" THEN e.total ELSE 0 END), 0) as cash_sales,
      COALESCE(SUM(CASE WHEN e.operacao = "cartao" THEN e.total ELSE 0 END), 0) as card_sales,
      COUNT(DISTINCT c.id) as transaction_count
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->join(db_prefix() . 'cashextracts e', 'e.cash_id = c.id', 'left');
    $this->db->where('DATE(c.data)', $yesterday);
    $yesterday_data = $this->db->get()->row_array();

    $this->db->select('
      COALESCE(SUM(c.balance), 0) as total_sales,
      COUNT(DISTINCT c.id) as transaction_count
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->where('DATE_FORMAT(c.data, "%Y-%m") =', $current_month);
    $current_month_data = $this->db->get()->row_array();

    $this->db->select('
      COALESCE(SUM(c.balance), 0) as total_sales,
      COUNT(DISTINCT c.id) as transaction_count
    ');
    $this->db->from(db_prefix() . 'cashs c');
    $this->db->where('DATE_FORMAT(c.data, "%Y-%m") =', $previous_month);
    $previous_month_data = $this->db->get()->row_array();

    $total_change_percent = $yesterday_data['total_sales'] > 0
      ? (($today_data['total_sales'] - $yesterday_data['total_sales']) / $yesterday_data['total_sales']) * 100
      : 0;

    $cash_change_percent = $yesterday_data['cash_sales'] > 0
      ? (($today_data['cash_sales'] - $yesterday_data['cash_sales']) / $yesterday_data['cash_sales']) * 100
      : 0;

    $card_change_percent = $yesterday_data['card_sales'] > 0
      ? (($today_data['card_sales'] - $yesterday_data['card_sales']) / $yesterday_data['card_sales']) * 100
      : 0;

    /**
      PRECISA ALTERAR ESSE VALOR E PUXAR PELO
      ID DA WAREHOUSE OU PELO ID DA FRANQUIA
      POR ENQUANTO, O VALOR DE MONTLY GOAL ESTÁ
      DEFINIDO DE FORMA ESTÁTICA
     */
    $monthly_goal = 100000;
    $goal_progress = $current_month_data['total_sales'] > 0
      ? ($current_month_data['total_sales'] / $monthly_goal) * 100
      : 0;

    $response = [
      'status' => true,
      'daily_performance' => [
        'total_sales' => [
          'current' => floatval($today_data['total_sales']),
          'previous' => floatval($yesterday_data['total_sales']),
          'change_percent' => round($total_change_percent, 1)
        ],
        'cash_sales' => [
          'current' => floatval($today_data['cash_sales']),
          'previous' => floatval($yesterday_data['cash_sales']),
          'change_percent' => round($cash_change_percent, 1)
        ],
        'card_sales' => [
          'current' => floatval($today_data['card_sales']),
          'previous' => floatval($yesterday_data['card_sales']),
          'change_percent' => round($card_change_percent, 1)
        ]
      ],
      'monthly_performance' => [
        'current_month' => [
          'total_sales' => floatval($current_month_data['total_sales']),
          'transaction_count' => (int) $current_month_data['transaction_count']
        ],
        'previous_month' => [
          'total_sales' => floatval($previous_month_data['total_sales']),
          'transaction_count' => (int) $previous_month_data['transaction_count']
        ],
        'goal' => [
          'target' => $monthly_goal,
          'progress' => round($goal_progress, 1),
          'remaining' => max(0, $monthly_goal - $current_month_data['total_sales'])
        ]
      ]
    ];

    $this->response($response, REST_Controller::HTTP_OK);
  }
}
