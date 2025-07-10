<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Receivables_ao_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_receivables($filters = [], $page = 0, $pageSize = 10, $sortField = 'date', $sortOrder = 'DESC')
    {
        $this->db->select('
        e.*,
        c.company as company,
        cat.name as category_name,
        pm.name as payment_mode_name
    ');
        $this->db->from(db_prefix() . 'expenses_ao as e');
        $this->db->join(db_prefix() . 'clients as c', 'e.clientid = c.userid', 'left');
        $this->db->join(db_prefix() . 'expenses_categories  as cat', 'e.category = cat.id', 'left');
        $this->db->join(db_prefix() . 'payment_modes as pm', 'e.paymentmode = pm.id', 'left');

        $this->db->where('e.type', 'receita');

        if (!empty($filters['warehouse_id'])) {
            $this->db->where('e.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('e.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('e.category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('e.expense_name', $filters['search']);
            $this->db->or_like('e.reference_no', $filters['search']);
            $this->db->or_like('e.note', $filters['search']); // <-- Adicione esta linha
            $this->db->group_end();
        }

        if (
            !empty($filters['startDate']) &&
            !empty($filters['endDate']) &&
            $filters['startDate'] === $filters['endDate']
        ) {
            $this->db->where('e.date', $filters['startDate']);
        } else {
            if (!empty($filters['startDate'])) {
                $this->db->where('e.date >=', $filters['startDate']);
            }
            if (!empty($filters['endDate'])) {
                $this->db->where('e.date <=', $filters['endDate']);
            }
        }

        $allowedSortFields = [
            'id' => 'e.id',
            'date' => 'e.date',
            'amount' => 'e.amount',
            'status' => 'e.status',
            'company' => 'c.company',
            'category_name' => 'cat.name',
            'payment_mode_name' => 'pm.name',
        ];

        $sortField = $sortField ?? 'id';
        $sortOrder = strtolower($sortOrder) === 'desc' ? 'DESC' : 'ASC';

        $sortFieldSql = isset($allowedSortFields[$sortField]) ? $allowedSortFields[$sortField] : 'e.id';

        $this->db->order_by($sortFieldSql, $sortOrder);

        $this->db->limit($pageSize, $page * $pageSize);

        return $this->db->get()->result();
    }



    public function count_receivables($filters = [])
    {
        $this->db->from(db_prefix() . 'expenses_ao as e');
        $this->db->where('e.type', 'receita');

        if (!empty($filters['warehouse_id'])) {
            $this->db->where('e.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('e.status', $filters['status']);
        }
        if (!empty($filters['category'])) {
            $this->db->where('e.category', $filters['category']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('e.expense_name', $filters['search']);
            $this->db->or_like('e.reference_no', $filters['search']);
            $this->db->group_end();
        }

        if (
            !empty($filters['startDate']) &&
            !empty($filters['endDate']) &&
            $filters['startDate'] === $filters['endDate']
        ) {
            $this->db->where('e.date', $filters['startDate']);
        } else {
            if (!empty($filters['startDate'])) {
                $this->db->where('e.date >=', $filters['startDate']);
            }
            if (!empty($filters['endDate'])) {
                $this->db->where('e.date <=', $filters['endDate']);
            }
        }

        return $this->db->count_all_results();
    }

    public function get_receivables_summary($warehouse_id)
    {
        $today = date('Y-m-d');
        $currentMonth = date('m');
        $currentYear = date('Y');

        $received_today = $this->sum_receivables_amount('paid', $warehouse_id, '=', $today);
        $received_today_count = $this->count_receivables_by_status('paid', $warehouse_id, '=', $today);

        $received = $this->sum_receivables_amount('paid', $warehouse_id);
        $received_count = $this->count_receivables_by_status('paid', $warehouse_id);

        $to_receive_month = $this->sum_receivables_in_month('pending', $warehouse_id, $currentMonth, $currentYear);
        $to_receive_month_count = $this->count_receivables_in_month('pending', $warehouse_id, $currentMonth, $currentYear);

        $to_receive = $this->sum_receivables_amount('pending', $warehouse_id, '>=');
        $to_receive_count = $this->count_receivables_by_status('pending', $warehouse_id, '>=');

        $overdue = $this->sum_receivables_amount('pending', $warehouse_id, '<');
        $overdue_count = $this->count_receivables_by_status('pending', $warehouse_id, '<');

        return [
            'received' => $received,
            'received_count' => $received_count,
            'received_today' => $received_today,
            'received_today_count' => $received_today_count,
            'to_receive' => $to_receive,
            'to_receive_count' => $to_receive_count,
            'to_receive_month' => $to_receive_month,
            'to_receive_month_count' => $to_receive_month_count,
            'overdue' => $overdue,
            'overdue_count' => $overdue_count,
        ];
    }

    private function sum_receivables_amount($status, $warehouse_id, $date_operator = null, $specific_date = null)
    {
        $this->db->select_sum('amount');
        $this->db->from(db_prefix() . 'expenses_ao');
        $this->db->where('type', 'receita');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);

        if ($date_operator && !$specific_date) {
            $this->db->where('date ' . $date_operator, date('Y-m-d'));
        }

        if ($specific_date) {
            $this->db->where('date', $specific_date);
        }

        return (float) $this->db->get()->row()->amount;
    }

    private function count_receivables_by_status($status, $warehouse_id, $date_operator = null, $specific_date = null)
    {
        $this->db->from(db_prefix() . 'expenses_ao');
        $this->db->where('type', 'receita');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);

        if ($date_operator && !$specific_date) {
            $this->db->where('date ' . $date_operator, date('Y-m-d'));
        }

        if ($specific_date) {
            $this->db->where('date', $specific_date);
        }

        return (int) $this->db->count_all_results();
    }

    private function sum_receivables_in_month($status, $warehouse_id, $month, $year)
    {
        $this->db->select_sum('amount');
        $this->db->from(db_prefix() . 'expenses_ao');
        $this->db->where('type', 'receita');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);
        $this->db->where('MONTH(date)', $month);
        $this->db->where('YEAR(date)', $year);

        return (float) $this->db->get()->row()->amount;
    }

    private function count_receivables_in_month($status, $warehouse_id, $month, $year)
    {
        $this->db->from(db_prefix() . 'expenses_ao');
        $this->db->where('type', 'receita');
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->where('status', $status);
        $this->db->where('MONTH(date)', $month);
        $this->db->where('YEAR(date)', $year);

        return (int) $this->db->count_all_results();
    }

}