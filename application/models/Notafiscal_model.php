<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Notafiscal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = '', $where = [])
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'nota_fiscal');

        if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
            $this->db->where($where);
        }

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            $invoice = $this->db->get()->row();

            if ($invoice) {
                // Decodificar campos JSON
                $invoice->orders_id = json_decode($invoice->orders_id, true) ?? [];
                $invoice->items = json_decode($invoice->items, true) ?? [];
                $invoice->installments = json_decode($invoice->installments, true) ?? [];
            }

            return $invoice;
        }

        $this->db->order_by('invoice_date', 'DESC');
        $result = $this->db->get()->result();

        // Decodificar campos JSON para cada registro
        foreach ($result as &$row) {
            $row->orders_id = json_decode($row->orders_id, true) ?? [];
            $row->items = json_decode($row->items, true) ?? [];
            $row->installments = json_decode($row->installments, true) ?? [];
        }

        return $result;
    }

    public function get_api($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'DESC', $warehouse_id = 0, $status = null, $start_date = null, $end_date = null, $invoice_id = '')
    {
        $allowedSortFields = [
            'id',
            'invoice_number',
            'invoice_key',
            'invoice_date',
            'invoice_status',
            'supplier_name',
            'supplier_document',
            'client_name',
            'client_document',
            'total_value',
            'created_at'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }

        $this->db->select('*');
        $this->db->from(db_prefix() . 'nota_fiscal');

        // Filtro por warehouse_id (obrigatório)
        if ($warehouse_id > 0) {
            $this->db->where('warehouse_id', $warehouse_id);
        } else {
            // Se não tiver warehouse_id, não retorna nada
            return ['data' => [], 'total' => 0];
        }

        // Filtro por status (aceita array ou valor único)
        if ($status !== null) {
            if (is_array($status)) {
                // Converte status string para numérico se necessário
                $status = array_map(function ($s) {
                    $statusMap = [
                        'pending' => 0,
                        'processing' => 1,
                        'completed' => 2,
                        'linked' => 3,
                        'canceled' => 4
                    ];
                    return $statusMap[$s] ?? $s;
                }, $status);
                $this->db->where_in('invoice_status', $status);
            } else {
                // Converte status string para numérico se necessário
                $statusMap = [
                    'pending' => 0,
                    'processing' => 1,
                    'completed' => 2,
                    'linked' => 3,
                    'canceled' => 4
                ];
                $numericStatus = $statusMap[$status] ?? $status;
                $this->db->where('invoice_status', $numericStatus);
            }
        }

        // Filtro por data (aceita apenas start_date ou apenas end_date)
        if ($start_date) {
            $this->db->where('invoice_date >=', date('Y-m-d 00:00:00', strtotime($start_date)));
        }
        if ($end_date) {
            $this->db->where('invoice_date <=', date('Y-m-d 23:59:59', strtotime($end_date)));
        }

        // Filtro de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('invoice_number', $search);
            $this->db->or_like('invoice_key', $search);
            $this->db->or_like('supplier_name', $search);
            $this->db->or_like('supplier_document', $search);
            $this->db->or_like('client_name', $search);
            $this->db->or_like('client_document', $search);
            $this->db->group_end();
        }

        // Filtro de numero invoice
        if (!empty($invoice_id)) {
            $this->db->group_start();
            $this->db->like('invoice_number', $invoice_id);
            $this->db->or_like('invoice_key', $invoice_id);
            $this->db->group_end();
        }

        // Ordenação
        $this->db->order_by($sortField, $sortOrder);

        // Paginação
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);

        $invoices = $this->db->get()->result();

        // Decodificar campos JSON para cada registro
        foreach ($invoices as &$invoice) {
            $invoice->orders_id = json_decode($invoice->orders_id, true) ?? [];
            $invoice->items = json_decode($invoice->items, true) ?? [];
            $invoice->installments = json_decode($invoice->installments, true) ?? [];
        }

        // Contagem total (com os mesmos filtros)
        $this->db->reset_query();
        $this->db->from(db_prefix() . 'nota_fiscal');

        // Aplica os mesmos filtros da query principal
        if ($warehouse_id > 0) {
            $this->db->where('warehouse_id', $warehouse_id);
        }

        if ($status !== null) {
            if (is_array($status)) {
                $this->db->where_in('invoice_status', $status);
            } else {
                $this->db->where('invoice_status', $status);
            }
        }

        if ($start_date) {
            $this->db->where('invoice_date >=', date('Y-m-d 00:00:00', strtotime($start_date)));
        }
        if ($end_date) {
            $this->db->where('invoice_date <=', date('Y-m-d 23:59:59', strtotime($end_date)));
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('invoice_number', $search);
            $this->db->or_like('invoice_key', $search);
            $this->db->or_like('supplier_name', $search);
            $this->db->or_like('supplier_document', $search);
            $this->db->or_like('client_name', $search);
            $this->db->or_like('client_document', $search);
            $this->db->group_end();
        }

        // Filtro de numero invoice
        if (!empty($invoice_id)) {
            $this->db->group_start();
            $this->db->like('invoice_number', $invoice_id);
            $this->db->or_like('invoice_key', $invoice_id);
            $this->db->group_end();
        }

        $total = $this->db->count_all_results();

        return [
            'data' => $invoices,
            'total' => $total
        ];
    }

    public function add($data)
    {
        // Certificar que campos JSON estão codificados
        if (isset($data['orders_id']) && is_array($data['orders_id'])) {
            $data['orders_id'] = json_encode($data['orders_id']);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        if (isset($data['installments']) && is_array($data['installments'])) {
            $data['installments'] = json_encode($data['installments']);
        }

        $this->db->insert(db_prefix() . 'nota_fiscal', $data);
        return ($this->db->affected_rows() > 0) ? $this->db->insert_id() : false;
    }

    public function update($data, $id)
    {
        // Certificar que campos JSON estão codificados
        if (isset($data['orders_id']) && is_array($data['orders_id'])) {
            $data['orders_id'] = json_encode($data['orders_id']);
        }

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        if (isset($data['installments']) && is_array($data['installments'])) {
            $data['installments'] = json_encode($data['installments']);
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'nota_fiscal', $data);
        return ($this->db->affected_rows() > 0);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'nota_fiscal');
        return ($this->db->affected_rows() > 0);
    }
}