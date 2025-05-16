<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ItemStocksMov_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all movements for a specific item
     *
     * @param int $item_id
     * @return array
     */
    public function get_movements_by_item($item_id)
    {
        $this->db->select('
        m.id AS movimento_id,
        m.qtde,
        m.obs AS obs_movimentacao,
        m.date AS data_movimentacao,
        m.type_transaction AS origem_transacao,
        m.user_id,
        CONCAT(staff.firstname, " ", staff.lastname) AS user_name,

        m.warehouse_id,
        w.warehouse_name,

        m.cash_id,
        m.transaction_id,

        i.id AS produto_id,
        i.commodity_name AS nome_produto,
        i.description,
        i.rate AS preco_unitario,
        i.code AS codigo_produto,
        i.stock AS estoque_atual,
        i.can_be_sold,
        i.can_be_purchased,
        i.can_be_inventory,
        i.show_on_pdv,
        i.active,
        i.createdAt,
        i.updatedAt
    ');
        $this->db->from(db_prefix() . 'itemstocksmov m');
        $this->db->join(db_prefix() . 'items i', 'i.id = m.item_id');
        $this->db->join(db_prefix() . 'warehouse w', 'w.warehouse_id = m.warehouse_id', 'left');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = m.user_id', 'left');
        $this->db->where('m.item_id', $item_id);
        $this->db->order_by('m.date', 'DESC');

        return $this->db->get()->result_array();
    }


}