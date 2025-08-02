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

        // Primeiro, remover parcelas existentes para evitar duplicação
        $this->delete_installments_by_expense($expense_id);

        foreach ($installments as $installment) {
            $data = [
                'expenses_id' => $expense_id,
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

        // Atualizar o due_date da despesa com a data da primeira parcela
        if (!empty($installments)) {
            $first_installment = $installments[0];
            $this->db->where('id', $expense_id);
            $this->db->update(db_prefix() . 'expenses', [
                'due_date' => $first_installment['data_vencimento']
            ]);
            
            log_message('debug', 'Due date inicializado para expense_id ' . $expense_id . ': ' . $first_installment['data_vencimento']);
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
        // Verificar se a tabela existe
        if (!$this->db->table_exists(db_prefix() . 'account_installments')) {
            log_message('error', 'Tabela ' . db_prefix() . 'account_installments não existe');
            return [];
        }

        $this->db->select('
            id,
            expenses_id,
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
            data_pagamento,
            valor_pago,
            banco_id,
            id_cheque,
            id_boleto
        ');
        $this->db->where('expenses_id', $expense_id);
        $this->db->order_by('numero_parcela', 'ASC');
        
        $result = $this->db->get(db_prefix() . 'account_installments')->result_array();
        
        // Log para debug
        log_message('debug', 'Parcelas encontradas para expense_id ' . $expense_id . ': ' . count($result));
        
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
            'juros_adicional' => $payment_data['juros_adicional'] ?? 0,
            'desconto' => $payment_data['desconto'] ?? 0,
            'multa' => $payment_data['multa'] ?? 0,
            'id_cheque' => $payment_data['id_cheque'] ?? null,
            'id_boleto' => $payment_data['id_boleto'] ?? null,
        ];

        $result = $this->update_installment($installment_id, $data);
        
        // Se o pagamento foi bem-sucedido, atualizar o due_date da despesa
        if ($result) {
            $installment = $this->get_installment($installment_id);
            if ($installment) {
                $this->update_expense_due_date($installment->expenses_id);
            }
        }
        
        return $result;
    }

    /**
     * Método de debug para verificar o estado das parcelas
     * @param int $expense_id ID da despesa
     * @return array
     */
    public function debug_installments_status($expense_id)
    {
        log_message('debug', '=== DEBUG INSTALLMENTS STATUS para expense_id: ' . $expense_id . ' ===');
        
        // Buscar todas as parcelas
        $this->db->select('id, numero_parcela, data_vencimento, data_pagamento, status, valor_pago');
        $this->db->where('expenses_id', $expense_id);
        $this->db->order_by('numero_parcela', 'ASC');
        
        $installments = $this->db->get(db_prefix() . 'account_installments')->result_array();
        
        log_message('debug', 'Total de parcelas encontradas: ' . count($installments));
        
        foreach ($installments as $inst) {
            log_message('debug', 'Parcela ' . $inst['numero_parcela'] . ': Status=' . $inst['status'] . ', Vencimento=' . $inst['data_vencimento'] . ', Pagamento=' . $inst['data_pagamento'] . ', Valor Pago=' . $inst['valor_pago']);
        }
        
        // Verificar parcelas pendentes
        $this->db->select('COUNT(*) as total_pendentes');
        $this->db->where('expenses_id', $expense_id);
        $this->db->where('status !=', 'Pago');
        $pendentes = $this->db->get(db_prefix() . 'account_installments')->row();
        
        log_message('debug', 'Parcelas pendentes: ' . $pendentes->total_pendentes);
        
        // Verificar último pagamento
        $this->db->select('data_pagamento, numero_parcela');
        $this->db->where('expenses_id', $expense_id);
        $this->db->where('status', 'Pago');
        $this->db->where('data_pagamento IS NOT NULL');
        $this->db->order_by('data_pagamento', 'DESC');
        $this->db->limit(1);
        
        $ultimo_pagamento = $this->db->get(db_prefix() . 'account_installments')->row();
        
        if ($ultimo_pagamento) {
            log_message('debug', 'Último pagamento: Parcela ' . $ultimo_pagamento->numero_parcela . ' em ' . $ultimo_pagamento->data_pagamento);
        } else {
            log_message('debug', 'Nenhum pagamento encontrado');
        }
        
        return [
            'installments' => $installments,
            'pendentes' => $pendentes->total_pendentes,
            'ultimo_pagamento' => $ultimo_pagamento
        ];
    }

    /**
     * Atualizar o due_date da despesa com a data da próxima parcela não paga
     * @param int $expense_id ID da despesa
     * @return bool
     */
    public function update_expense_due_date($expense_id)
    {
        log_message('debug', '=== INICIANDO update_expense_due_date para expense_id: ' . $expense_id . ' ===');
        
        // Debug: verificar estado atual das parcelas
        $this->debug_installments_status($expense_id);
        
        // Buscar a próxima parcela não paga
        $this->db->select('data_vencimento');
        $this->db->where('expenses_id', $expense_id);
        $this->db->where('status !=', 'Pago');
        $this->db->order_by('data_vencimento', 'ASC');
        $this->db->limit(1);
        
        $next_installment = $this->db->get(db_prefix() . 'account_installments')->row();
        
        if ($next_installment) {
            // Atualizar o due_date da despesa com a data da próxima parcela
            $this->db->where('id', $expense_id);
            $this->db->update(db_prefix() . 'expenses', [
                'due_date' => $next_installment->data_vencimento
            ]);
            
            log_message('debug', 'Due date atualizado para expense_id ' . $expense_id . ': ' . $next_installment->data_vencimento . ' (próxima parcela pendente)');
            return true;
        } else {
            log_message('debug', 'Nenhuma parcela pendente encontrada para expense_id: ' . $expense_id . '. Buscando data do pagamento mais recente...');
            
            // Se não há mais parcelas pendentes, buscar a data do pagamento mais recente
            // Primeiro, vamos buscar todas as parcelas pagas para debug
            $this->db->select('data_pagamento, numero_parcela, status');
            $this->db->where('expenses_id', $expense_id);
            $this->db->where('status', 'Pago');
            $this->db->where('data_pagamento IS NOT NULL');
            $this->db->order_by('data_pagamento', 'ASC');
            
            $all_paid_installments = $this->db->get(db_prefix() . 'account_installments')->result();
            
            log_message('debug', 'Todas as parcelas pagas encontradas para expense_id ' . $expense_id . ':');
            foreach ($all_paid_installments as $inst) {
                log_message('debug', '  - Parcela ' . $inst->numero_parcela . ': data_pagamento = ' . $inst->data_pagamento . ', status = ' . $inst->status);
            }
            
            // Agora buscar especificamente a mais recente
            $this->db->select('data_pagamento, numero_parcela');
            $this->db->where('expenses_id', $expense_id);
            $this->db->where('status', 'Pago');
            $this->db->where('data_pagamento IS NOT NULL');
            $this->db->order_by('data_pagamento', 'DESC');
            $this->db->limit(1);
            
            $last_payment = $this->db->get(db_prefix() . 'account_installments')->row();
            
            // Debug: verificar se a consulta retornou o resultado esperado
            log_message('debug', 'Consulta SQL executada: ' . $this->db->last_query());
            
            if ($last_payment) {
                log_message('debug', 'Último pagamento encontrado: parcela ' . $last_payment->numero_parcela . ' com data ' . $last_payment->data_pagamento);
                
                // Verificação adicional: encontrar a data mais recente programaticamente
                $latest_date = null;
                $latest_installment = null;
                foreach ($all_paid_installments as $inst) {
                    if ($latest_date === null || strtotime($inst->data_pagamento) > strtotime($latest_date)) {
                        $latest_date = $inst->data_pagamento;
                        $latest_installment = $inst->numero_parcela;
                    }
                }
                
                log_message('debug', 'Data mais recente encontrada programaticamente: ' . $latest_date . ' (parcela ' . $latest_installment . ')');
                log_message('debug', 'Data retornada pela consulta SQL: ' . $last_payment->data_pagamento . ' (parcela ' . $last_payment->numero_parcela . ')');
                
                // Usar a data mais recente encontrada
                $final_date = $latest_date ?: $last_payment->data_pagamento;
                
                // Atualizar o due_date da despesa com a data do pagamento mais recente
                $this->db->where('id', $expense_id);
                $this->db->update(db_prefix() . 'expenses', [
                    'due_date' => $final_date
                ]);
                
                log_message('debug', 'Due date atualizado para expense_id ' . $expense_id . ' com data do último pagamento: ' . $final_date);
            } else {
                log_message('debug', 'Nenhuma data de pagamento encontrada para expense_id: ' . $expense_id . '. Usando data atual como fallback.');
                
                // Fallback: se não encontrar data de pagamento, manter a data atual
                $this->db->where('id', $expense_id);
                $this->db->update(db_prefix() . 'expenses', [
                    'due_date' => date('Y-m-d')
                ]);
                
                log_message('debug', 'Due date mantido com data atual para expense_id ' . $expense_id . ' (todas as parcelas pagas)');
            }
            
            return true;
        }
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

        // Se pagou o valor total, marca como pago
        if ($novo_valor_pago >= $valor_total_parcela) {
            $data['status'] = 'Pago';
        } else {
            $data['status'] = 'Parcial';
        }

        $result = $this->update_installment($installment_id, $data);
        
        // Se a atualização foi bem-sucedida, atualizar o due_date da despesa
        if ($result) {
            $this->update_expense_due_date($installment->expenses_id);
        }
        
        return $result;
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
            SUM(juros_adicional) as total_juros_adicional,
            SUM(desconto) as total_desconto,
            SUM(multa) as total_multa,
            SUM(CASE WHEN status = "Pago" THEN valor_pago ELSE 0 END) as valor_total_pago,
            SUM(CASE WHEN status = "Pendente" THEN (valor_com_juros + juros_adicional + multa - desconto) ELSE 0 END) as valor_pendente,
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
            ai.id,
            ai.expenses_id,
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
            ai.id,
            ai.expenses_id,
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

    /**
     * Atualizar due_date de todas as despesas que têm parcelas
     * Método utilitário para corrigir despesas existentes
     * @return array Array com estatísticas da atualização
     */
    public function update_all_expenses_due_dates()
    {
        $stats = [
            'total_expenses' => 0,
            'updated' => 0,
            'errors' => 0,
            'errors_list' => []
        ];

        // Buscar todas as despesas que têm parcelas
        $this->db->select('DISTINCT(expenses_id) as expense_id');
        $this->db->from(db_prefix() . 'account_installments');
        $expenses_with_installments = $this->db->get()->result_array();

        $stats['total_expenses'] = count($expenses_with_installments);

        foreach ($expenses_with_installments as $expense) {
            try {
                $success = $this->update_expense_due_date($expense['expense_id']);
                if ($success) {
                    $stats['updated']++;
                } else {
                    $stats['errors']++;
                    $stats['errors_list'][] = 'Falha ao atualizar expense_id: ' . $expense['expense_id'];
                }
            } catch (Exception $e) {
                $stats['errors']++;
                $stats['errors_list'][] = 'Erro ao atualizar expense_id ' . $expense['expense_id'] . ': ' . $e->getMessage();
            }
        }

        log_message('info', 'Atualização em massa de due_dates concluída. Total: ' . $stats['total_expenses'] . ', Atualizadas: ' . $stats['updated'] . ', Erros: ' . $stats['errors']);

        return $stats;
    }
} 