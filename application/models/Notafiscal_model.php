<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Notafiscal_model extends App_Model
{
    private $table_nfe;

    public function __construct()
    {
        parent::__construct();
        $this->table_nfe = db_prefix() . 'nfe'; // -> 'tblnfe'
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

        public function nfe_get($id)
    {
        $row = $this->db->get_where($this->table_nfe, ['id' => (int)$id])->row_array();
        if (!$row) return null;

        // se quiser decodificar snapshots
        if (isset($row['payload_snapshot']) && $row['payload_snapshot'] !== null && $row['payload_snapshot'] !== '') {
            $decoded = json_decode($row['payload_snapshot'], true);
            $row['payload_snapshot'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
        } else {
            $row['payload_snapshot'] = [];
        }
        return $row;
    }

    public function nfe_list($params)
    {
        $page         = (int)$params['page'];
        $limit        = (int)$params['limit'];
        $offset       = ($page - 1) * $limit;
        $warehouse_id = (int)$params['warehouse_id'];
        $search       = $params['search'];
        $sortField    = $this->nfe_sanitizeSort($params['sortField']);
        $sortOrder    = $params['sortOrder'];
        $status       = $params['status'];
        $start_date   = $params['start_date'];
        $end_date     = $params['end_date'];
        $invoice_id   = $params['invoice_id'];

        $this->db->from($this->table_nfe);
        $this->db->where('warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('`key`', $search);      // coluna do banco
            $this->db->or_like('nf_number', $search);
            $this->db->or_like('protocol', $search);
            $this->db->or_like('serie', $search);
            $this->db->or_like('status', $search);
            $this->db->group_end();
        }

        if (!empty($invoice_id)) {
            $this->db->where('invoice_id', $invoice_id);
        }

        if (!empty($status)) {
            if (is_array($status)) $this->db->where_in('status', $status);
            else $this->db->where('status', $status);
        }

        if (!empty($start_date)) $this->db->where('DATE(created_at) >=', $start_date);
        if (!empty($end_date))   $this->db->where('DATE(created_at) <=', $end_date);

        // Count
        $count = clone $this->db;
        $total = $count->count_all_results();

        // Data
        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, $offset);
        $rows = $this->db->get()->result_array();

        return ['total' => $total, 'rows' => $rows];
    }

    private function nfe_sanitizeSort($field)
    {
        $allowed = [
            'id','created_at','updated_at','status',
            'nf_number','serie','key','protocol','authorization_date','total_value'
        ];
        // mapear 'key' com crase na hora do order_by
        $field = in_array($field, $allowed, true) ? $field : 'created_at';
        return $field === 'key' ? '`key`' : $field;
    }

    public function nfe_validate_invoice($invoice_id, $warehouse_id, $user_id)
    {
        $invoice = $this->db->get_where(db_prefix().'invoices', [
            'id' => $invoice_id,
            'warehouse_id' => $warehouse_id
        ])->row();
        if (!$invoice) return ['status' => false, 'message' => 'Fatura não encontrada para este depósito'];

        $already = $this->db->get_where($this->table_nfe, ['invoice_id' => $invoice_id])->row();
        if ($already) return ['status' => true, 'nfe_id' => (int)$already->id, 'message' => 'NFe já validada/criada'];

        $data = [
            'invoice_id'        => $invoice_id,
            'warehouse_id'      => $warehouse_id,
            'status'            => 'VALIDADO',               // seu fluxo
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
            'payload_snapshot'  => json_encode(['validated_by' => $user_id])
        ];

        $this->db->insert($this->table_nfe, $data);
        $nfe_id = $this->db->insert_id();
        if (!$nfe_id) return ['status' => false, 'message' => 'Falha ao criar registro de NFe'];

        return ['status' => true, 'nfe_id' => (int)$nfe_id];
    }

    public function nfe_generate_nfe(array $payload, $user_id)
    {
        if (!empty($payload['nfe_id'])) {
            $nfe = $this->nfe_get((int)$payload['nfe_id']);
            if (!$nfe) return ['status' => false, 'message' => 'NFe não encontrada'];
            $nfe_id = (int)$nfe['id'];
        } else {
            if (empty($payload['invoice_id']) || empty($payload['warehouse_id'])) {
                return ['status' => false, 'message' => 'invoice_id e warehouse_id são obrigatórios'];
            }
            $validated = $this->nfe_validate_invoice((int)$payload['invoice_id'], (int)$payload['warehouse_id'], $user_id);
            if (!$validated['status']) return $validated;
            $nfe_id = (int)$validated['nfe_id'];
        }

        // gerar chave/xml (stub)
        $chave = $this->nfe_build_fake_chave($nfe_id);
        $xml   = $this->nfe_build_fake_xml($nfe_id, $chave);

        // Atualizar usando **nomes de coluna do banco**
        $this->db->where('id', $nfe_id)->update($this->table_nfe, [
            'status'             => 'EMITIDO',
            '`key`'              => $chave,                         // coluna `key`
            'authorization_date' => date('Y-m-d H:i:s'),
            'payload_snapshot'   => json_encode([
                'generated_by' => $user_id,
                'xml_preview'  => substr($xml, 0, 200)
            ]),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() <= 0) {
            return ['status' => false, 'message' => 'Não foi possível atualizar a NFe'];
        }

        return [
            'status'      => true,
            'nfe_id'      => $nfe_id,
            'chave'       => $chave,
            'xml_preview' => substr($xml, 0, 200) . '...',
        ];
    }

    private function nfe_build_fake_chave($nfe_id)
    {
        $base = str_pad($nfe_id, 44, '0', STR_PAD_LEFT);
        return substr($base, 0, 44);
    }

    private function nfe_build_fake_xml($nfe_id, $chave)
    {
        $dh = date('c');
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<NFe>
  <infNFe Id="NFe{$chave}" versao="4.00">
    <ide><mod>55</mod><nNF>{$nfe_id}</nNF><dhEmi>{$dh}</dhEmi></ide>
  </infNFe>
</NFe>
XML;
    }

    public function nfe_remove_many(array $ids)
    {
        // bloqueio só se quiser manter regra; sua tabela não tem 'AUTHORIZED', mas deixo genérico
        $this->db->where_in('id', $ids)->where('status', 'AUTHORIZED');
        $authorized = $this->db->count_all_results($this->table_nfe);
        if ($authorized > 0) {
            return ['status' => false, 'message' => 'Há NF-e autorizadas; não é permitido remover.'];
        }

        $this->db->where_in('id', $ids)->delete($this->table_nfe);
        $affected = $this->db->affected_rows();
        return ['status' => $affected > 0, 'message' => $affected.' registro(s) removido(s)'];
    }

    public function get_nfce($id = '', $where = [])
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'nfce');

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

    public function get_api_nfce($id = '', $page = 1, $limit = 10, $search = '', $sortField = 'id', $sortOrder = 'DESC', $warehouse_id = 0, $status = null, $start_date = null, $end_date = null, $invoice_id = '')
    {
        $allowedSortFields = [
            'id',
            'documento',
            'nfe',
            'recibo',
            'qrcode',
            'serie',
            'protocolo',
            'data_autorizacao',
            'chave',
            'status',
            'created_at'
        ];

        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'id';
        }

        $this->db->select('*');
        $this->db->from(db_prefix() . 'nfce');

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
                $this->db->where_in('status', $status);
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
                $this->db->where('status', $numericStatus);
            }
        }

        // Filtro por data (aceita apenas start_date ou apenas end_date)
        if ($start_date) {
            $this->db->where('created_at >=', date('Y-m-d 00:00:00', strtotime($start_date)));
        }
        if ($end_date) {
            $this->db->where('created_at <=', date('Y-m-d 23:59:59', strtotime($end_date)));
        }

        // Filtro de busca
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('documento', $search);
            $this->db->or_like('nfe', $search);
            $this->db->or_like('recibo', $search);
            $this->db->or_like('qrcode', $search);
            $this->db->or_like('serie', $search);
            $this->db->or_like('protocolo', $search);
            $this->db->or_like('data_autorizacao', $search);
            $this->db->or_like('chave', $search);
            $this->db->group_end();
        }

        // Filtro de numero invoice
        if (!empty($invoice_id)) {
            $this->db->group_start();
            $this->db->like('chave', $invoice_id);
            $this->db->or_like('id', $invoice_id);
            $this->db->group_end();
        }

        // Ordenação
        $this->db->order_by($sortField, $sortOrder);

        // Paginação
        $offset = ($page - 1) * $limit;
        $this->db->limit($limit, $offset);

        $invoices = $this->db->get()->result();


        // Contagem total (com os mesmos filtros)
        $this->db->reset_query();
        $this->db->from(db_prefix() . 'nfce');

        // Aplica os mesmos filtros da query principal
        if ($warehouse_id > 0) {
            $this->db->where('warehouse_id', $warehouse_id);
        }

        if ($status !== null) {
            if (is_array($status)) {
                $this->db->where_in('status', $status);
            } else {
                $this->db->where('status', $status);
            }
        }

        if ($start_date) {
            $this->db->where('created_at >=', date('Y-m-d 00:00:00', strtotime($start_date)));
        }
        if ($end_date) {
            $this->db->where('created_at <=', date('Y-m-d 23:59:59', strtotime($end_date)));
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('invoice_number', $search);
            $this->db->or_like('invoice_key', $search);
            $this->db->or_like('recibo', $search);
            $this->db->or_like('qrcode', $search);
            $this->db->or_like('serie', $search);
            $this->db->or_like('protocolo', $search);
            $this->db->or_like('data_autorizacao', $search);
            $this->db->or_like('chave', $search);
            $this->db->group_end();
        }

        // Filtro de numero invoice
        if (!empty($invoice_id)) {
            $this->db->group_start();
            $this->db->like('chave', $invoice_id);
            $this->db->or_like('id', $invoice_id);
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