-- =====================================================
-- ATUALIZAÇÃO DO BANCO DE DADOS - VERSÃO 115
-- Novos campos para despesas AC (Adicional de Contrato)
-- =====================================================

-- =====================================================
-- 1. ALTERAÇÕES NA TABELA EXPENSES
-- =====================================================

-- Campos AC (Adicional de Contrato) - NOVOS
ALTER TABLE `tblexpenses` 
ADD COLUMN `valor_ac` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `amount`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `valor_total` DECIMAL(15,2) NULL AFTER `valor_ac`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'despesa' AFTER `valor_total`;

-- Campos de parcelas AC - NOVOS
ALTER TABLE `tblexpenses` 
ADD COLUMN `num_parcelas_ac` INT(11) NOT NULL DEFAULT 1 AFTER `type`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `data_vencimento_ac` DATE NULL AFTER `num_parcelas_ac`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `observacoes_ac` TEXT NULL AFTER `data_vencimento_ac`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `parcelas_ac` LONGTEXT NULL AFTER `observacoes_ac`;

-- Formas de pagamento padrão - NOVOS
ALTER TABLE `tblexpenses` 
ADD COLUMN `forma_pagamento_padrao_fiscal` TEXT NULL AFTER `parcelas_ac`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `forma_pagamento_padrao_ac` TEXT NULL AFTER `forma_pagamento_padrao_fiscal`;

-- Resumo de valores - NOVO
ALTER TABLE `tblexpenses` 
ADD COLUMN `resumo_valores` TEXT NULL AFTER `forma_pagamento_padrao_ac`;

-- Campos de parcelamento - RENOMEAR EXISTENTES PARA FISCAL
ALTER TABLE `tblexpenses` 
ADD COLUMN `num_parcelas_fiscal` INT(11) NOT NULL DEFAULT 1 AFTER `resumo_valores`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `data_vencimento_fiscal` DATE NULL AFTER `num_parcelas_fiscal`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `observacoes_fiscal` TEXT NULL AFTER `data_vencimento_fiscal`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `parcelas_fiscais` LONGTEXT NULL AFTER `observacoes_fiscal`;

-- Campos de parcelamento - ADICIONAR CAMPOS FALTANTES
ALTER TABLE `tblexpenses` 
ADD COLUMN `tipo_juros` VARCHAR(20) NOT NULL DEFAULT 'simples' AFTER `juros_apartir`;

ALTER TABLE `tblexpenses` 
ADD COLUMN `valor_original` DECIMAL(15,2) NULL AFTER `total_parcelado`;

-- =====================================================
-- 2. ALTERAÇÕES NA TABELA ACCOUNT_INSTALLMENTS (FISCAL)
-- =====================================================

-- Adicionar novos campos para suporte a formas de pagamento múltiplas
ALTER TABLE `tblaccount_installments` 
ADD COLUMN `formas_pagamento` TEXT NULL AFTER `observacoes`;

ALTER TABLE `tblaccount_installments` 
ADD COLUMN `valores_formas` TEXT NULL AFTER `formas_pagamento`;

ALTER TABLE `tblaccount_installments` 
ADD COLUMN `diferenca` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `valores_formas`;

ALTER TABLE `tblaccount_installments` 
ADD COLUMN `customizada` TINYINT(1) NOT NULL DEFAULT 0 AFTER `diferenca`;

ALTER TABLE `tblaccount_installments` 
ADD COLUMN `tipo` VARCHAR(20) NOT NULL DEFAULT 'fiscal' AFTER `customizada`;


ALTER TABLE `tblexpenses` CHANGE `parcelas_fiscais` `parcelas_fiscais` JSON NULL DEFAULT NULL;


ALTER TABLE `tblexpenses` CHANGE `parcelas_ac` `parcelas_ac` JSON NULL DEFAULT NULL;

ALTER TABLE `tblexpenses` CHANGE `resumo_valores` `resumo_valores` JSON NULL DEFAULT NULL;



-- =====================================================
-- 3. CRIAR TABELA ACCOUNT_INSTALLMENTS_AC
-- =====================================================

CREATE TABLE IF NOT EXISTS `tblaccount_installments_ac` (
    `id` int NOT NULL AUTO_INCREMENT,
    `expenses_id` int DEFAULT NULL,
    `receivables_id` int DEFAULT NULL,
    `numero_parcela` int NOT NULL,
    `data_vencimento` date NOT NULL,
    `data_referencia` date NOT NULL,
    `valor_parcela` decimal(10,2) NOT NULL,
    `valor_com_juros` decimal(10,2) NOT NULL,
    `juros` decimal(10,2) NOT NULL DEFAULT '0.00',
    `juros_adicional` decimal(10,2) NOT NULL DEFAULT '0.00',
    `desconto` decimal(10,2) NOT NULL DEFAULT '0.00',
    `multa` decimal(10,2) NOT NULL DEFAULT '0.00',
    `percentual_juros` decimal(5,2) DEFAULT '0.00',
    `tipo_juros` enum('simples','composto') DEFAULT 'simples',
    `data_pagamento` date DEFAULT NULL,
    `valor_pago` decimal(10,2) DEFAULT NULL,
    `status` varchar(20) NOT NULL DEFAULT 'Pendente',
    `banco_id` int DEFAULT NULL,
    `documento_parcela` varchar(50) DEFAULT NULL,
    `id_boleto` varchar(50) DEFAULT NULL,
    `id_cheque` varchar(50) DEFAULT NULL,
    `observacoes` text,
    `comprovante` varchar(255) DEFAULT NULL,
    `paymentmode_id` int DEFAULT '0',
    `formas_pagamento` text,
    `valores_formas` text,
    `diferenca` decimal(15,2) NOT NULL DEFAULT '0.00',
    `customizada` tinyint(1) NOT NULL DEFAULT '0',
    `tipo` varchar(20) NOT NULL DEFAULT 'ac',
    PRIMARY KEY (`id`),
    KEY `idx_expenses_id` (`expenses_id`),
    KEY `idx_status` (`status`),
    KEY `idx_data_vencimento` (`data_vencimento`),
    KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- 4. COMENTÁRIOS SOBRE OS CAMPOS
-- =====================================================

/*
CAMPOS ADICIONADOS NA TABELA EXPENSES:

1. Campos AC (Adicional de Contrato) - NOVOS:
   - valor_ac: Valor do Adicional de Contrato
   - valor_total: Valor total da despesa (fiscal + AC)
   - type: Tipo da despesa (padrão: "despesa")

2. Campos de Parcelas AC - NOVOS:
   - num_parcelas_ac: Número de parcelas AC
   - data_vencimento_ac: Data de vencimento AC
   - observacoes_ac: Observações específicas AC
   - parcelas_ac: JSON com array de parcelas AC

3. Formas de Pagamento Padrão - NOVOS:
   - forma_pagamento_padrao_fiscal: JSON com formas de pagamento padrão fiscal
   - forma_pagamento_padrao_ac: JSON com formas de pagamento padrão AC

4. Resumo de Valores - NOVO:
   - resumo_valores: JSON com resumo dos valores

5. Campos de Parcelas Fiscais - NOVOS (para organizar os existentes):
   - num_parcelas_fiscal: Número de parcelas fiscais
   - data_vencimento_fiscal: Data de vencimento fiscal
   - observacoes_fiscal: Observações específicas fiscais
   - parcelas_fiscais: JSON com array de parcelas fiscais

6. Campos de Parcelamento - ADICIONAR FALTANTES:
   - tipo_juros: Tipo de juros (simples/composto) - ADICIONADO
   - valor_original: Valor original da despesa - ADICIONADO

CAMPOS ADICIONADOS NA TABELA ACCOUNT_INSTALLMENTS (FISCAL):

1. formas_pagamento: JSON com array de formas de pagamento da parcela
2. valores_formas: JSON com array de valores por forma de pagamento
3. diferenca: Diferença de valores na parcela
4. customizada: Se a parcela foi customizada (0/1)
5. tipo: Tipo da parcela ("fiscal" - padrão)

NOVA TABELA ACCOUNT_INSTALLMENTS_AC:

Tabela específica para armazenar parcelas do tipo AC (Adicional de Contrato),
com estrutura idêntica à account_installments mas focada em parcelas AC.
Inclui todos os campos da tabela fiscal + campos específicos para AC.
*/
