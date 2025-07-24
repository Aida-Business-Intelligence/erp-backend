<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_321 extends CI_Migration
{
    public function up()
    {
        $dbPrefix    = db_prefix();
        $dbCharset   = $this->db->char_set;
        $dbCollation = $this->db->dbcollat;

        // Adicionar campos de parcelamento na tabela receivables
        if (! $this->db->field_exists('num_parcelas', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` ADD `num_parcelas` INT(11) NOT NULL DEFAULT 1 AFTER `is_staff`;');
        }

        if (! $this->db->field_exists('juros', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` ADD `juros` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `num_parcelas`;');
        }

        if (! $this->db->field_exists('juros_apartir', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` ADD `juros_apartir` INT(11) NOT NULL DEFAULT 1 AFTER `juros`;');
        }

        if (! $this->db->field_exists('total_parcelado', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` ADD `total_parcelado` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `juros_apartir`;');
        }
    }

    public function down()
    {
        $dbPrefix = db_prefix();

        // Remover campos de parcelamento da tabela receivables
        if ($this->db->field_exists('total_parcelado', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` DROP COLUMN `total_parcelado`;');
        }

        if ($this->db->field_exists('juros_apartir', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` DROP COLUMN `juros_apartir`;');
        }

        if ($this->db->field_exists('juros', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` DROP COLUMN `juros`;');
        }

        if ($this->db->field_exists('num_parcelas', $dbPrefix . 'receivables')) {
            $this->db->query('ALTER TABLE `' . $dbPrefix . 'receivables` DROP COLUMN `num_parcelas`;');
        }
    }
}
