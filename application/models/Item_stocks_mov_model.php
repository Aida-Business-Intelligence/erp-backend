<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Item_stocks_mov_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all movements for a specific item with pagination
     *
     * @param int $item_id
     * @param int $page
     * @param int $pageSize
     * @param string $sortField
     * @param string $sortOrder
     * @return array
     */
    public function get_movements_by_item($item_id, $page = 0, $pageSize = 10, $sortField = 'date', $sortOrder = 'DESC')
    {
        try {
            // Log para debug
            log_message('debug', 'Item_stocks_mov_model::get_movements_by_item - Iniciando método');
            
            // Primeiro, vamos testar uma consulta simples
            $this->db->select('COUNT(*) as total');
            $this->db->from(db_prefix() . 'itemstocksmov');
            $this->db->where('item_id', $item_id);
            $count_result = $this->db->get()->row();
            $total = $count_result ? $count_result->total : 0;
            
            // Log para debug
            log_message('debug', 'Item_stocks_mov_model::get_movements_by_item - Total de registros: ' . $total);

            // Agora vamos fazer a consulta principal de forma mais simples
            $this->db->select('
                m.id AS movimento_id,
                m.qtde,
                m.obs AS obs_movimentacao,
                m.date AS data_movimentacao,
                m.type_transaction AS origem_transacao,
                m.user_id,
                m.warehouse_id,
                m.cash_id,
                m.transaction_id
            ');
            $this->db->from(db_prefix() . 'itemstocksmov m');
            $this->db->where('m.item_id', $item_id);

            // Ordenação simples
            $this->db->order_by('m.date', $sortOrder);

            // Limite
            $this->db->limit($pageSize, $page * $pageSize);

            // Log para debug - mostrar a query SQL
            log_message('debug', 'Item_stocks_mov_model::get_movements_by_item - Query SQL: ' . $this->db->get_compiled_select());

            $data = $this->db->get()->result_array();
            
            // Log para debug
            log_message('debug', 'Item_stocks_mov_model::get_movements_by_item - Dados obtidos com sucesso. Quantidade: ' . count($data));

            return [
                'data' => $data,
                'total' => $total
            ];
        } catch (Exception $e) {
            // Log para debug
            log_message('error', 'Item_stocks_mov_model::get_movements_by_item - Erro: ' . $e->getMessage() . ' - Linha: ' . $e->getLine());
            throw $e;
        }
    }

    public function get_product_info($item_id)
    {
        $this->db->select('
        i.id,
        i.commodity_name AS nome_produto,
        i.description,
        i.rate AS preco_unitario,
        i.code AS codigo_produto,
        i.stock AS estoque_atual,
        i.can_be_sold,
        i.can_be_purchased,
        i.can_be_inventory,
        i.show_on_pdv,
        i.active
    ');
        $this->db->from(db_prefix() . 'items i');
        $this->db->where('i.id', $item_id);

        $result = $this->db->get()->row_array();

        return $result ?: [];
    }
}