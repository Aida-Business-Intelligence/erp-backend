<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Receivables_installments_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Adicionar parcelas para uma receita
     * @param int $receivable_id ID da receita
     * @param array $installments Array com os dados das parcelas
     * @return bool
     */
    public function add_installments($receivable_id, $installments)
    {
        if (empty($receivable_id) || empty($installments)) {
            return false;
        }

        $this->db->trans_start();

        // Primeiro, remover parcelas existentes para evitar duplicação
        $this->delete_installments_by_receivable($receivable_id);

        foreach ($installments as $installment) {
            $data = [
                'receivables_id' => $receivable_id,
                'numero_parcela' => $installment['numero_parcela'],
                'data_vencimento' => $installment['data_vencimento'],
                'valor_parcela' => $installment['valor_parcela'],
                'valor_com_juros' => $installment['valor_com_juros'],
                'juros' => $installment['juros'] ?? 0,
                'juros_adicional' => $installment['juros_adicional'] ?? 0,
                'desconto' => $installment['desconto'] ?? 0,
                'multa' => $installment['multa'] ?? 0,
                'percentual_juros' => $installment['percentual_juros'] ?? 0,
                'tipo_juros' => $installment['tipo_juros'] ?? 'simples',
                'status' => $installment['status'] ?? 'Pendente',
                'paymentmode_id' => $installment['paymentmode_id'] ?? null,
                'documento_parcela' => $installment['documento_parcela'] ?? null,
                'observacoes' => $installment['observacoes'] ?? null,
            ];

            $this->db->insert(db_prefix() . 'account_installments', $data);
        }

        // Atualizar o due_date da receita com a data da primeira parcela
        if (!empty($installments)) {
            $first_installment = $installments[0];
            $this->db->where('id', $receivable_id);
            $this->db->update(db_prefix() . 'receivables', [
                'due_date' => $first_installment['data_vencimento']
            ]);
            
            log_message('debug', 'Due date inicializado para receivable_id ' . $receivable_id . ': ' . $first_installment['data_vencimento']);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Obter parcelas de uma receita
     * @param int $receivable_id ID da receita
     * @return array
     */
    public function get_installments_by_receivable($receivable_id)
    {
        // Verificar se a tabela existe
        if (!$this->db->table_exists(db_prefix() . 'account_installments')) {
            log_message('error', 'Tabela ' . db_prefix() . 'account_installments não existe');
            return [];
        }

        $this->db->select('
            id,
            receivables_id,
            numero_parcela,
            data_vencimento,
            valor_parcela,
            valor_com_juros,
            juros,
            juros_adicional,
            desconto,
            multa,
            percentual_juros,
            tipo_juros,
            status,
            paymentmode_id,
            documento_parcela,
            observacoes,
            comprovante,
            data_pagamento,
            valor_pago,
            banco_id,
            id_cheque,
            id_boleto
        ');
        $this->db->where('receivables_id', $receivable_id);
        $this->db->order_by('numero_parcela', 'ASC');
        
        $result = $this->db->get(db_prefix() . 'account_installments')->result_array();
        
        // Log para debug
        log_message('debug', 'Parcelas encontradas para receivable_id ' . $receivable_id . ': ' . count($result));
        
        return $result;
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
     * Buscar parcela específica por receita e número
     * @param int $receivable_id ID da receita
     * @param int $numero_parcela Número da parcela
     * @return object|null
     */
    public function get_installment_by_receivable_and_number($receivable_id, $numero_parcela)
    {
        if (empty($receivable_id) || empty($numero_parcela)) {
            return null;
        }

        $this->db->where('receivables_id', $receivable_id);
        $this->db->where('numero_parcela', $numero_parcela);
        $query = $this->db->get(db_prefix() . 'account_installments');

        return $query->row();
    }

    /**
     * Atualizar uma parcela
     * @param int $installment_id ID da parcela
     * @param array $data Dados para atualizar
     * @return bool
     */
    public function update_installment($installment_id, $data)
    {
        // Log para debug
        log_message('debug', 'Atualizando parcela ID: ' . $installment_id . ' - Dados: ' . json_encode($data));
        
        $this->db->where('id', $installment_id);
        $this->db->update(db_prefix() . 'account_installments', $data);
        $affected_rows = $this->db->affected_rows();
        
        log_message('debug', 'Parcela ID: ' . $installment_id . ' - Linhas afetadas: ' . $affected_rows);
        
        return $affected_rows > 0;
    }

    /**
     * Receber uma parcela
     * @param int $installment_id ID da parcela
     * @param array $payment_data Dados do recebimento
     * @return bool
     */
    public function receive_installment($installment_id, $payment_data)
    {
        // Log para debug
        log_message('debug', 'Recebendo parcela ID: ' . $installment_id . ' - Valor: ' . $payment_data['valor_pago']);
        
        $data = [
            'data_pagamento' => $payment_data['data_pagamento'] ?? date('Y-m-d'),
            'valor_pago' => $payment_data['valor_pago'],
            'status' => 'Pago',
            'banco_id' => $payment_data['banco_id'] ?? null,
            'observacoes' => $payment_data['observacoes'] ?? null,
            'juros_adicional' => $payment_data['juros_adicional'] ?? 0,
            'desconto' => $payment_data['desconto'] ?? 0,
            'multa' => $payment_data['multa'] ?? 0,
            'id_cheque' => $payment_data['id_cheque'] ?? null,
            'id_boleto' => $payment_data['id_boleto'] ?? null,
            'comprovante' => $payment_data['comprovante'] ?? null,
        ];

        $result = $this->update_installment($installment_id, $data);
        
        // Se o recebimento foi bem-sucedido, atualizar o due_date da receita
        if ($result) {
            $installment = $this->get_installment($installment_id);
            if ($installment) {
                log_message('debug', 'Parcela recebida com sucesso. Atualizando due_date para receivable_id: ' . $installment->receivables_id);
                $this->update_receivable_due_date($installment->receivables_id);
            }
        }
        
        return $result;
    }

    /**
     * Atualizar o due_date da receita com a data da próxima parcela não recebida
     * @param int $receivable_id ID da receita
     * @return bool
     */
    public function update_receivable_due_date($receivable_id)
    {
        // Buscar a próxima parcela não recebida (incluindo parciais)
        $this->db->select('data_vencimento');
        $this->db->where('receivables_id', $receivable_id);
        $this->db->where('status !=', 'Pago');
        $this->db->order_by('data_vencimento', 'ASC');
        $this->db->limit(1);
        
        $next_installment = $this->db->get(db_prefix() . 'account_installments')->row();
        
        if ($next_installment) {
            // Atualizar o due_date da receita com a data da próxima parcela
            $this->db->where('id', $receivable_id);
            $this->db->update(db_prefix() . 'receivables', [
                'due_date' => $next_installment->data_vencimento
            ]);
            
            log_message('debug', 'Due date atualizado para receivable_id ' . $receivable_id . ': ' . $next_installment->data_vencimento);
            return true;
        } else {
            // Se não há mais parcelas pendentes, limpar o due_date ou definir como null
            $this->db->where('id', $receivable_id);
            $this->db->update(db_prefix() . 'receivables', [
                'due_date' => null
            ]);
            
            log_message('debug', 'Due date limpo para receivable_id ' . $receivable_id . ' (todas as parcelas recebidas)');
            return true;
        }
    }

    /**
     * Receber parcela parcial
     * @param int $installment_id ID da parcela
     * @param array $payment_data Dados do recebimento
     * @return bool
     */
    public function receive_installment_partial($installment_id, $payment_data)
    {
        $installment = $this->get_installment($installment_id);
        if (!$installment) {
            return false;
        }

        $valor_pago_atual = $installment->valor_pago ?? 0;
        $novo_valor_pago = $valor_pago_atual + $payment_data['valor_pago'];

        // Calcular valor total da parcela incluindo juros adicional, multa e desconto
        $valor_total_parcela = $installment->valor_com_juros + 
                              ($installment->juros_adicional ?? 0) + 
                              ($installment->multa ?? 0) - 
                              ($installment->desconto ?? 0);

        $data = [
            'data_pagamento' => $payment_data['data_pagamento'] ?? date('Y-m-d'),
            'valor_pago' => $novo_valor_pago,
            'banco_id' => $payment_data['banco_id'] ?? null,
            'observacoes' => $payment_data['observacoes'] ?? null,
            'juros_adicional' => $payment_data['juros_adicional'] ?? 0,
            'desconto' => $payment_data['desconto'] ?? 0,
            'multa' => $payment_data['multa'] ?? 0,
            'id_cheque' => $payment_data['id_cheque'] ?? null,
            'id_boleto' => $payment_data['id_boleto'] ?? null,
        ];

        // Se recebeu o valor total, marca como pago
        if ($novo_valor_pago >= $valor_total_parcela) {
            $data['status'] = 'Pago';
        } else {
            $data['status'] = 'Parcial';
        }

        $result = $this->update_installment($installment_id, $data);
        
        // Se a atualização foi bem-sucedida, atualizar o due_date da receita
        if ($result) {
            $this->update_receivable_due_date($installment->receivables_id);
        }
        
        return $result;
    }

    /**
     * Deletar parcelas de uma receita
     * @param int $receivable_id ID da receita
     * @return bool
     */
    public function delete_installments_by_receivable($receivable_id)
    {
        $this->db->where('receivables_id', $receivable_id);
        $this->db->delete(db_prefix() . 'account_installments');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Verificar se uma receita tem parcelas
     * @param int $receivable_id ID da receita
     * @return bool
     */
    public function has_installments($receivable_id)
    {
        $this->db->where('receivables_id', $receivable_id);
        $this->db->limit(1);
        $result = $this->db->get(db_prefix() . 'account_installments');
        return $result->num_rows() > 0;
    }

    /**
     * Verificar se uma receita é parcelada (mais de uma parcela)
     * @param int $receivable_id ID da receita
     * @return bool
     */
    public function is_installment_receivable($receivable_id)
    {
        $this->db->where('receivables_id', $receivable_id);
        $count = $this->db->count_all_results(db_prefix() . 'account_installments');
        return $count > 1;
    }

    /**
     * Verificar se uma receita é única (apenas uma parcela)
     * @param int $receivable_id ID da receita
     * @return bool
     */
    public function is_single_receivable($receivable_id)
    {
        $this->db->where('receivables_id', $receivable_id);
        $count = $this->db->count_all_results(db_prefix() . 'account_installments');
        return $count == 1;
    }

    /**
     * Obter resumo das parcelas de uma receita
     * @param int $receivable_id ID da receita
     * @return array
     */
    public function get_installments_summary($receivable_id)
    {
        $this->db->select('
            COUNT(*) as total_parcelas,
            SUM(valor_parcela) as valor_total_original,
            SUM(valor_com_juros) as valor_total_com_juros,
            SUM(juros) as total_juros,
            SUM(juros_adicional) as total_juros_adicional,
            SUM(desconto) as total_desconto,
            SUM(multa) as total_multa,
            SUM(CASE WHEN status = "Pago" THEN valor_pago ELSE 0 END) as valor_total_pago,
            SUM(CASE WHEN status IN ("Pendente", "Parcial") THEN (valor_com_juros + juros_adicional + multa - desconto) ELSE 0 END) as valor_pendente,
            COUNT(CASE WHEN status = "Pago" THEN 1 END) as parcelas_pagas,
            COUNT(CASE WHEN status IN ("Pendente", "Parcial") THEN 1 END) as parcelas_pendentes,
            COUNT(CASE WHEN status = "Parcial" THEN 1 END) as parcelas_parciais
        ');
        $this->db->where('receivables_id', $receivable_id);
        $result = $this->db->get(db_prefix() . 'account_installments')->row_array();
        
        // Log para debug
        log_message('debug', 'Summary para receivable_id ' . $receivable_id . ': ' . json_encode($result));
        
        return $result;
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
            ai.id,
            ai.receivables_id,
            ai.numero_parcela,
            ai.data_vencimento,
            ai.valor_parcela,
            ai.valor_com_juros,
            ai.juros,
            ai.juros_adicional,
            ai.desconto,
            ai.multa,
            ai.percentual_juros,
            ai.tipo_juros,
            ai.status,
            ai.paymentmode_id,
            ai.documento_parcela,
            ai.observacoes,
            ai.data_pagamento,
            ai.valor_pago,
            ai.banco_id,
            r.receivable_identifier,
            r.note,
            c.company as client_name,
            pm.name as payment_mode_name
        ');
        $this->db->from(db_prefix() . 'account_installments ai');
        $this->db->join(db_prefix() . 'receivables r', 'r.id = ai.receivables_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = r.clientid', 'left');
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
            ai.id,
            ai.receivables_id,
            ai.numero_parcela,
            ai.data_vencimento,
            ai.valor_parcela,
            ai.valor_com_juros,
            ai.juros,
            ai.juros_adicional,
            ai.desconto,
            ai.multa,
            ai.percentual_juros,
            ai.tipo_juros,
            ai.status,
            ai.paymentmode_id,
            ai.documento_parcela,
            ai.observacoes,
            ai.data_pagamento,
            ai.valor_pago,
            ai.banco_id,
            r.receivable_identifier,
            r.note,
            r.warehouse_id,
            c.company as client_name,
            pm.name as payment_mode_name
        ');
        $this->db->from(db_prefix() . 'account_installments ai');
        $this->db->join(db_prefix() . 'receivables r', 'r.id = ai.receivables_id', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = r.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes pm', 'pm.id = ai.paymentmode_id', 'left');
        $this->db->where('ai.data_vencimento >=', $start_date);
        $this->db->where('ai.data_vencimento <=', $end_date);

        if ($warehouse_id) {
            $this->db->where('r.warehouse_id', $warehouse_id);
        }

        $this->db->order_by('ai.data_vencimento', 'ASC');

        return $this->db->get()->result_array();
    }

    /**
     * Atualizar due_date de todas as receitas que têm parcelas
     * Método utilitário para corrigir receitas existentes
     * @return array Array com estatísticas da atualização
     */
    public function update_all_receivables_due_dates()
    {
        $stats = [
            'total_receivables' => 0,
            'updated' => 0,
            'errors' => 0,
            'errors_list' => []
        ];

        // Buscar todas as receitas que têm parcelas
        $this->db->select('DISTINCT(receivables_id) as receivable_id');
        $this->db->from(db_prefix() . 'account_installments');
        $receivables_with_installments = $this->db->get()->result_array();

        $stats['total_receivables'] = count($receivables_with_installments);

        foreach ($receivables_with_installments as $receivable) {
            try {
                $success = $this->update_receivable_due_date($receivable['receivable_id']);
                if ($success) {
                    $stats['updated']++;
                } else {
                    $stats['errors']++;
                    $stats['errors_list'][] = 'Falha ao atualizar receivable_id: ' . $receivable['receivable_id'];
                }
            } catch (Exception $e) {
                $stats['errors']++;
                $stats['errors_list'][] = 'Erro ao atualizar receivable_id ' . $receivable['receivable_id'] . ': ' . $e->getMessage();
            }
        }

        log_message('info', 'Atualização em massa de due_dates concluída. Total: ' . $stats['total_receivables'] . ', Atualizadas: ' . $stats['updated'] . ', Erros: ' . $stats['errors']);

        return $stats;
    }
} 