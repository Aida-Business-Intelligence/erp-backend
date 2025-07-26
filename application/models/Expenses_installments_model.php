<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Expenses_installments_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adicionar parcelas para uma despesa
     * @param int $expense_id ID da despesa
     * @param array $installments Array com os dados das parcelas
     * @return bool
     */
    public function add_installments($expense_id, $installments)
    {
        if (empty($expense_id) || empty($installments)) {
            return false;
        }

        $this->db->trans_start();

        foreach ($installments as $installment) {
            $data = [
                'expenses_id' => $expense_id,
                'numero_parcela' => $installment['numero_parcela'],
                'data_vencimento' => $installment['data_vencimento'],
                'valor_parcela' => $installment['valor_parcela'],
                'valor_com_juros' => $installment['valor_com_juros'],
                'juros' => $installment['juros'] ?? 0,
                'percentual_juros' => $installment['percentual_juros'] ?? 0,
                'status' => $installment['status'] ?? 'Pendente',
                'paymentmode_id' => $installment['paymentmode_id'] ?? null,
                'documento_parcela' => $installment['documento_parcela'] ?? null,
                'observacoes' => $installment['observacoes'] ?? null,
            ];

            $this->db->insert(db_prefix() . 'account_installments', $data);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Obter parcelas de uma despesa
     * @param int $expense_id ID da despesa
     * @return array
     */
    public function get_installments_by_expense($expense_id)
    {
        $this->db->where('expenses_id', $expense_id);
        $this->db->order_by('numero_parcela', 'ASC');
        return $this->db->get(db_prefix() . 'account_installments')->result_array();
    }

    /**
     * Obter uma parcela específica
     * @param int $installment_id ID da parcela
     * @return object|null
     */
    public function get_installment($installment_id)
    {
        $this->db->where('id', $installment_id);
        return $this->db->get(db_prefix() . 'account_installments')->row();
    }

    /**
     * Atualizar uma parcela
     * @param int $installment_id ID da parcela
     * @param array $data Dados para atualizar
     * @return bool
     */
    public function update_installment($installment_id, $data)
    {
        $this->db->where('id', $installment_id);
        $this->db->update(db_prefix() . 'account_installments', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Pagar uma parcela
     * @param int $installment_id ID da parcela
     * @param array $payment_data Dados do pagamento
     * @return bool
     */
    public function pay_installment($installment_id, $payment_data)
    {
        $data = [
            'data_pagamento' => $payment_data['data_pagamento'] ?? date('Y-m-d'),
            'valor_pago' => $payment_data['valor_pago'],
            'status' => 'Pago',
            'banco_id' => $payment_data['banco_id'] ?? null,
            'observacoes' => $payment_data['observacoes'] ?? null,
        ];

        return $this->update_installment($installment_id, $data);
    }

    /**
     * Pagar parcela parcial
     * @param int $installment_id ID da parcela
     * @param array $payment_data Dados do pagamento
     * @return bool
     */
    public function pay_installment_partial($installment_id, $payment_data)
    {
        $installment = $this->get_installment($installment_id);
        if (!$installment) {
            return false;
        }

        $valor_pago_atual = $installment->valor_pago ?? 0;
        $novo_valor_pago = $valor_pago_atual + $payment_data['valor_pago'];

        $data = [
            'data_pagamento' => $payment_data['data_pagamento'] ?? date('Y-m-d'),
            'valor_pago' => $novo_valor_pago,
            'banco_id' => $payment_data['banco_id'] ?? null,
            'observacoes' => $payment_data['observacoes'] ?? null,
        ];

        // Se pagou o valor total, marca como pago
        if ($novo_valor_pago >= $installment->valor_com_juros) {
            $data['status'] = 'Pago';
        } else {
            $data['status'] = 'Parcial';
        }

        return $this->update_installment($installment_id, $data);
    }

    /**
     * Deletar parcelas de uma despesa
     * @param int $expense_id ID da despesa
     * @return bool
     */
    public function delete_installments_by_expense($expense_id)
    {
        $this->db->where('expenses_id', $expense_id);
        $this->db->delete(db_prefix() . 'account_installments');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Verificar se uma despesa tem parcelas
     * @param int $expense_id ID da despesa
     * @return bool
     */
    public function has_installments($expense_id)
    {
        $this->db->where('expenses_id', $expense_id);
        $this->db->limit(1);
        $result = $this->db->get(db_prefix() . 'account_installments');
        return $result->num_rows() > 0;
    }

    /**
     * Verificar se uma despesa é parcelada (mais de uma parcela)
     * @param int $expense_id ID da despesa
     * @return bool
     */
    public function is_installment_expense($expense_id)
    {
        $this->db->where('expenses_id', $expense_id);
        $count = $this->db->count_all_results(db_prefix() . 'account_installments');
        return $count > 1;
    }

    /**
     * Verificar se uma despesa é única (apenas uma parcela)
     * @param int $expense_id ID da despesa
     * @return bool
     */
    public function is_single_expense($expense_id)
    {
        $this->db->where('expenses_id', $expense_id);
        $count = $this->db->count_all_results(db_prefix() . 'account_installments');
        return $count == 1;
    }

    /**
     * Obter resumo das parcelas de uma despesa
     * @param int $expense_id ID da despesa
     * @return array
     */
    public function get_installments_summary($expense_id)
    {
        $this->db->select('
            COUNT(*) as total_parcelas,
            SUM(valor_parcela) as valor_total_original,
            SUM(valor_com_juros) as valor_total_com_juros,
            SUM(juros) as total_juros,
            SUM(CASE WHEN status = "Pago" THEN valor_pago ELSE 0 END) as valor_total_pago,
            SUM(CASE WHEN status = "Pendente" THEN valor_com_juros ELSE 0 END) as valor_pendente,
            COUNT(CASE WHEN status = "Pago" THEN 1 END) as parcelas_pagas,
            COUNT(CASE WHEN status = "Pendente" THEN 1 END) as parcelas_pendentes,
            COUNT(CASE WHEN status = "Parcial" THEN 1 END) as parcelas_parciais
        ');
        $this->db->where('expenses_id', $expense_id);
        return $this->db->get(db_prefix() . 'account_installments')->row_array();
    }

    /**
     * Obter parcelas vencidas
     * @param string $date Data de referência (padrão: hoje)
     * @return array
     */
    public function get_overdue_installments($date = null)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $this->db->select('
            ai.*,
            e.expense_identifier,
            e.note,
            c.company as client_name,
            pm.name as payment_mode_name
        ');
        $this->db->from(db_prefix() . 'account_installments ai');
        $this->db->join(db_prefix() . 'expenses e', 'e.id = ai.expenses_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = e.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes pm', 'pm.id = ai.paymentmode_id', 'left');
        $this->db->where('ai.data_vencimento <', $date);
        $this->db->where('ai.status !=', 'Pago');
        $this->db->order_by('ai.data_vencimento', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Obter parcelas por período
     * @param string $start_date Data inicial
     * @param string $end_date Data final
     * @param int $warehouse_id ID do warehouse (opcional)
     * @return array
     */
    public function get_installments_by_period($start_date, $end_date, $warehouse_id = null)
    {
        $this->db->select('
            ai.*,
            e.expense_identifier,
            e.note,
            e.warehouse_id,
            c.company as client_name,
            pm.name as payment_mode_name
        ');
        $this->db->from(db_prefix() . 'account_installments ai');
        $this->db->join(db_prefix() . 'expenses e', 'e.id = ai.expenses_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = e.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes pm', 'pm.id = ai.paymentmode_id', 'left');
        $this->db->where('ai.data_vencimento >=', $start_date);
        $this->db->where('ai.data_vencimento <=', $end_date);

        if ($warehouse_id) {
            $this->db->where('e.warehouse_id', $warehouse_id);
        }

        $this->db->order_by('ai.data_vencimento', 'ASC');

        return $this->db->get()->result_array();
    }
} 