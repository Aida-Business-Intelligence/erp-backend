<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__ . '/../REST_Controller.php';

class Financial extends REST_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('financial_model');
    }

    public function summary_get() {
        $payments = $this->financial_model->get_payment_summary();
        
        // Calcular totais do dia atual
        $dinheiro_dia = 0;
        $dinheiro_ontem = 0;
        $credito_dia = 0;
        $credito_ontem = 0;
        
        // Processando vendas em dinheiro
        foreach($payments['cash'] as $p) {
            if($p['payment_date'] == date('Y-m-d')) {
                $dinheiro_dia = floatval($p['total_sum']);
            }
            if($p['payment_date'] == date('Y-m-d', strtotime('-1 day'))) {
                $dinheiro_ontem = floatval($p['total_sum']);
            }
        }

        // Processando vendas em crédito
        foreach($payments['credit'] as $p) {
            if($p['payment_date'] == date('Y-m-d')) {
                $credito_dia = floatval($p['total_sum']);
            }
            if($p['payment_date'] == date('Y-m-d', strtotime('-1 day'))) {
                $credito_ontem = floatval($p['total_sum']);
            }
        }

        // Calculando percentuais
        $percent_dinheiro = $dinheiro_ontem > 0 ? (($dinheiro_dia/$dinheiro_ontem)*100)-100 : 0;
        $percent_credito = $credito_ontem > 0 ? (($credito_dia/$credito_ontem)*100)-100 : 0;

        $data = [
            "sales" => [
                "total" => $dinheiro_dia + $credito_dia,
                "percent" => ($dinheiro_ontem + $credito_ontem) > 0 ? 
                    ((($dinheiro_dia + $credito_dia)/($dinheiro_ontem + $credito_ontem))*100)-100 : 0,
                "comparison" => "vs yesterday"
            ],
            "cash_sales" => [
                "total" => $dinheiro_dia,
                "percent" => $percent_dinheiro,
                "comparison" => "vs yesterday"
            ],
            "card_sales" => [
                "total" => $credito_dia,
                "percent" => $percent_credito,
                "comparison" => "vs yesterday"
            ],
            "monthly_goal" => [
                "total" => 82,
                "value" => 89543.21,
                "comparison" => "vs yesterday"
            ]
        ];

        $this->response($data, REST_Controller::HTTP_OK);
    }

    public function list_get() {
        $page = $this->get('page') ? (int) $this->get('page') : 1;
        $limit = $this->get('pageSize') ? (int) $this->get('pageSize') : 10;
        $search = $this->get('search') ?: '';
        $sortField = $this->get('sortField') ?: 'id';
        $sortOrder = $this->get('sortOrder') === 'desc' ? 'DESC' : 'ASC';

        $data = $this->cashs_model->get_api('', $page, $limit, $search, $sortField, $sortOrder);

        if ($data) {
            $this->response(['total' => $data['total'], 'data' => $data['data']], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => FALSE, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }
    }
} 