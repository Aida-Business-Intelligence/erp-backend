<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_115 extends App_module_migration
{
    public function up()
    {
        // Adicionar novos campos na tabela expenses
        $this->add_expenses_fields();
        
        // Adicionar novos campos na tabela account_installments
        $this->add_account_installments_fields();
        
        // Criar tabela account_installments_ac
        $this->create_account_installments_ac_table();
    }

    private function add_expenses_fields()
    {
        // Campos AC (Adicional de Contrato) - NOVOS
        if (!$this->db->field_exists('valor_ac', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `valor_ac` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `amount`');
        }

        if (!$this->db->field_exists('valor_total', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `valor_total` DECIMAL(15,2) NULL AFTER `valor_ac`');
        }

        if (!$this->db->field_exists('type', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT "despesa" AFTER `valor_total`');
        }

        // Campos de parcelas AC - NOVOS
        if (!$this->db->field_exists('num_parcelas_ac', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `num_parcelas_ac` INT(11) NOT NULL DEFAULT 1 AFTER `type`');
        }

        if (!$this->db->field_exists('data_vencimento_ac', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `data_vencimento_ac` DATE NULL AFTER `num_parcelas_ac`');
        }

        if (!$this->db->field_exists('observacoes_ac', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `observacoes_ac` TEXT NULL AFTER `data_vencimento_ac`');
        }

        if (!$this->db->field_exists('parcelas_ac', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `parcelas_ac` LONGTEXT NULL AFTER `observacoes_ac`');
        }

        // Formas de pagamento padrão - NOVOS
        if (!$this->db->field_exists('forma_pagamento_padrao_fiscal', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `forma_pagamento_padrao_fiscal` TEXT NULL AFTER `parcelas_ac`');
        }

        if (!$this->db->field_exists('forma_pagamento_padrao_ac', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `forma_pagamento_padrao_ac` TEXT NULL AFTER `forma_pagamento_padrao_fiscal`');
        }

        // Resumo de valores - NOVO
        if (!$this->db->field_exists('resumo_valores', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `resumo_valores` TEXT NULL AFTER `forma_pagamento_padrao_ac`');
        }

        // Campos de parcelas fiscais - NOVOS (para organizar os existentes)
        if (!$this->db->field_exists('num_parcelas_fiscal', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `num_parcelas_fiscal` INT(11) NOT NULL DEFAULT 1 AFTER `resumo_valores`');
        }

        if (!$this->db->field_exists('data_vencimento_fiscal', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `data_vencimento_fiscal` DATE NULL AFTER `num_parcelas_fiscal`');
        }

        if (!$this->db->field_exists('observacoes_fiscal', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `observacoes_fiscal` TEXT NULL AFTER `data_vencimento_fiscal`');
        }

        if (!$this->db->field_exists('parcelas_fiscais', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `parcelas_fiscais` LONGTEXT NULL AFTER `observacoes_fiscal`');
        }

        // Campos de parcelamento - ADICIONAR FALTANTES
        if (!$this->db->field_exists('tipo_juros', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `tipo_juros` VARCHAR(20) NOT NULL DEFAULT "simples" AFTER `juros_apartir`');
        }

        if (!$this->db->field_exists('valor_original', db_prefix() . 'expenses')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'expenses` 
                ADD COLUMN `valor_original` DECIMAL(15,2) NULL AFTER `total_parcelado`');
        }
    }

    private function add_account_installments_fields()
    {
        // Adicionar novos campos para suporte a formas de pagamento múltiplas
        if (!$this->db->field_exists('formas_pagamento', db_prefix() . 'account_installments')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'account_installments` 
                ADD COLUMN `formas_pagamento` TEXT NULL AFTER `observacoes`');
        }

        if (!$this->db->field_exists('valores_formas', db_prefix() . 'account_installments')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'account_installments` 
                ADD COLUMN `valores_formas` TEXT NULL AFTER `formas_pagamento`');
        }

        if (!$this->db->field_exists('diferenca', db_prefix() . 'account_installments')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'account_installments` 
                ADD COLUMN `diferenca` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `valores_formas`');
        }

        if (!$this->db->field_exists('customizada', db_prefix() . 'account_installments')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'account_installments` 
                ADD COLUMN `customizada` TINYINT(1) NOT NULL DEFAULT 0 AFTER `diferenca`');
        }

        if (!$this->db->field_exists('tipo', db_prefix() . 'account_installments')) {
            $this->db->query('ALTER TABLE `' . db_prefix() . 'account_installments` 
                ADD COLUMN `tipo` VARCHAR(20) NOT NULL DEFAULT "fiscal" AFTER `customizada`');
        }
    }

    private function create_account_installments_ac_table()
    {
        if (!$this->db->table_exists(db_prefix() . 'account_installments_ac')) {
            $this->db->query('CREATE TABLE `' . db_prefix() . 'account_installments_ac` (
                `id` int NOT NULL AUTO_INCREMENT,
                `expenses_id` int DEFAULT NULL,
                `receivables_id` int DEFAULT NULL,
                `numero_parcela` int NOT NULL,
                `data_vencimento` date NOT NULL,
                `data_referencia` date NOT NULL,
                `valor_parcela` decimal(10,2) NOT NULL,
                `valor_com_juros` decimal(10,2) NOT NULL,
                `juros` decimal(10,2) NOT NULL DEFAULT "0.00",
                `juros_adicional` decimal(10,2) NOT NULL DEFAULT "0.00",
                `desconto` decimal(10,2) NOT NULL DEFAULT "0.00",
                `multa` decimal(10,2) NOT NULL DEFAULT "0.00",
                `percentual_juros` decimal(5,2) DEFAULT "0.00",
                `tipo_juros` enum("simples","composto") DEFAULT "simples",
                `data_pagamento` date DEFAULT NULL,
                `valor_pago` decimal(10,2) DEFAULT NULL,
                `status` varchar(20) NOT NULL DEFAULT "Pendente",
                `banco_id` int DEFAULT NULL,
                `documento_parcela` varchar(50) DEFAULT NULL,
                `id_boleto` varchar(50) DEFAULT NULL,
                `id_cheque` varchar(50) DEFAULT NULL,
                `observacoes` text,
                `comprovante` varchar(255) DEFAULT NULL,
                `paymentmode_id` int DEFAULT "0",
                `formas_pagamento` text,
                `valores_formas` text,
                `diferenca` decimal(15,2) NOT NULL DEFAULT "0.00",
                `customizada` tinyint(1) NOT NULL DEFAULT "0",
                `tipo` varchar(20) NOT NULL DEFAULT "ac",
                PRIMARY KEY (`id`),
                KEY `idx_expenses_id` (`expenses_id`),
                KEY `idx_status` (`status`),
                KEY `idx_data_vencimento` (`data_vencimento`),
                KEY `idx_tipo` (`tipo`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
        }
    }
}