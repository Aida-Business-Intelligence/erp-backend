<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Financial_model extends App_Model {
    
    public function __construct() {
        parent::__construct();
    }

    public function get_payment_summary() {
        // Vendas em dinheiro dos últimos 7 dias
        $this->db->select('DATE(daterecorded) as payment_date, COALESCE(SUM(amount), 0) as total_sum');
        $this->db->from('tblcashpaymentrecords');
        $this->db->where('daterecorded >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)', null, false);
        $this->db->where('paymentmethod', 'Dinheiro');
        $this->db->group_by('payment_date');
        $this->db->order_by('payment_date', 'ASC');
        
        $cash_payments = $this->db->get()->result_array();

        // Vendas em crédito dos últimos 7 dias
        $this->db->select('DATE(daterecorded) as payment_date, COALESCE(SUM(amount), 0) as total_sum');
        $this->db->from('tblcashpaymentrecords');
        $this->db->where('daterecorded >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)', null, false);
        $this->db->where('paymentmethod', 'Crédito');
        $this->db->group_by('payment_date');
        $this->db->order_by('payment_date', 'ASC');
        
        $credit_payments = $this->db->get()->result_array();

        // Preencher dias vazios
        $complete_cash = $this->fill_empty_days($cash_payments);
        $complete_credit = $this->fill_empty_days($credit_payments);

        return [
            'cash' => $complete_cash,
            'credit' => $complete_credit
        ];
    }

    private function fill_empty_days($payments) {
        $complete_data = [];
        for($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $found = false;
            
            foreach($payments as $payment) {
                if($payment['payment_date'] == $date) {
                    $complete_data[] = $payment;
                    $found = true;
                    break;
                }
            }
            
            if(!$found) {
                $complete_data[] = [
                    'payment_date' => $date,
                    'total_sum' => '0'
                ];
            }
        }
        return $complete_data;
    }
} 