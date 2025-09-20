<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Nfe_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'nfe'; // -> tblnfe
    }

    public function get($id)
    {
        $row = $this->db->get_where($this->table, ['id' => (int)$id])->row_array();
        if (!$row) return null;

        // Campos JSON opcionais
        foreach (['items','errors','events'] as $jsonField) {
            if (isset($row[$jsonField]) && $row[$jsonField] !== null && $row[$jsonField] !== '') {
                $decoded = json_decode($row[$jsonField], true);
                $row[$jsonField] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
            } else {
                $row[$jsonField] = [];
            }
        }
        return $row;
    }

    public function list($params)
    {
        $page         = (int)$params['page'];
        $limit        = (int)$params['limit'];
        $offset       = ($page - 1) * $limit;
        $warehouse_id = (int)$params['warehouse_id'];
        $search       = $params['search'];
        $sortField    = $this->sanitizeSortField($params['sortField']);
        $sortOrder    = $params['sortOrder'];
        $status       = $params['status'];
        $start_date   = $params['start_date'];
        $end_date     = $params['end_date'];
        $invoice_id   = $params['invoice_id'];

        $this->db->from($this->table);
        $this->db->where('warehouse_id', $warehouse_id);

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('chave', $search);
            $this->db->or_like('numero', $search);
            $this->db->or_like('protocolo', $search);
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

        if (!empty($start_date)) {
            $this->db->where('DATE(created_at) >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('DATE(created_at) <=', $end_date);
        }

        // Count
        $count = clone $this->db;
        $total = $count->count_all_results();

        // Data
        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, $offset);
        $rows = $this->db->get()->result_array();

        return ['total' => $total, 'rows' => $rows];
    }

    private function sanitizeSortField($field)
    {
        $allowed = [
            'id','created_at','updated_at','status',
            'numero','serie','chave','protocolo','environment'
        ];
        return in_array($field, $allowed, true) ? $field : 'created_at';
    }

    /**
     * Valida se a invoice pode ter NFe e cria um stub em tblnfe com status VALIDATED
     */
    public function validate_invoice($invoice_id, $warehouse_id, $user_id)
    {
        // Confere existência da invoice
        $invoice = $this->db->get_where(db_prefix().'invoices', [
            'id' => $invoice_id,
            'warehouse_id' => $warehouse_id
        ])->row();

        if (!$invoice) {
            return ['status' => false, 'message' => 'Fatura não encontrada para este depósito'];
        }

        // Evita duplicidade
        $already = $this->db->get_where($this->table, [
            'invoice_id' => $invoice_id
        ])->row();

        if ($already) {
            return ['status' => true, 'nfe_id' => (int)$already->id, 'message' => 'NFe já validada/criada'];
        }

        $data = [
            'invoice_id'   => $invoice_id,
            'warehouse_id' => $warehouse_id,
            'status'       => 'VALIDATED',
            'environment'  => 'homologacao', // default
            'model'        => '55',
            'created_by'   => $user_id,
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->table, $data);
        $nfe_id = $this->db->insert_id();

        if (!$nfe_id) {
            return ['status' => false, 'message' => 'Falha ao criar registro de NFe'];
        }

        return ['status' => true, 'nfe_id' => (int)$nfe_id];
    }

    /**
     * Gera (stub) XML/Chave. Aceita:
     * - ['nfe_id' => ...]
     * - ou ['invoice_id' => ..., 'warehouse_id' => ...]
     */
    public function generate_nfe(array $payload, $user_id)
    {
        if (!empty($payload['nfe_id'])) {
            $nfe = $this->get((int)$payload['nfe_id']);
            if (!$nfe) return ['status' => false, 'message' => 'NFe não encontrada'];
            $nfe_id = (int)$nfe['id'];
        } else {
            if (empty($payload['invoice_id']) || empty($payload['warehouse_id'])) {
                return ['status' => false, 'message' => 'invoice_id e warehouse_id são obrigatórios'];
            }
            $validated = $this->validate_invoice((int)$payload['invoice_id'], (int)$payload['warehouse_id'], $user_id);
            if (!$validated['status']) return $validated;
            $nfe_id = (int)$validated['nfe_id'];
            $nfe    = $this->get($nfe_id);
        }

        // Monta um XML fake (para depois trocar pela montagem real)
        $chave = $this->build_fake_chave($nfe_id);
        $xml   = $this->build_fake_xml($nfe_id, $chave);

        $this->db->where('id', $nfe_id)->update($this->table, [
            'status'       => 'GENERATED',
            'chave'        => $chave,
            'xml_content'  => $xml,
            'xml_source'   => 'generated',
            'updated_at'   => date('Y-m-d H:i:s'),
            'generated_by' => $user_id,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() <= 0) {
            return ['status' => false, 'message' => 'Não foi possível atualizar a NFe'];
        }

        return [
            'status'      => true,
            'nfe_id'      => $nfe_id,
            'chave'       => $chave,
            'xml_preview' => substr($xml, 0, 120) . '...'
        ];
    }

    private function build_fake_chave($nfe_id)
    {
        // 44 dígitos fake (não use em produção)
        $base = str_pad($nfe_id, 44, '0', STR_PAD_LEFT);
        return substr($base, 0, 44);
    }

    private function build_fake_xml($nfe_id, $chave)
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<NFe>
  <infNFe Id="NFe{$chave}" versao="4.00">
    <ide>
      <cUF>43</cUF>
      <tpAmb>2</tpAmb>
      <mod>55</mod>
      <serie>1</serie>
      <nNF>{$nfe_id}</nNF>
      <dhEmi>{date('c')}</dhEmi>
    </ide>
    <emit>
      <xNome>EMITENTE FAKE</xNome>
      <CNPJ>00000000000000</CNPJ>
    </emit>
    <dest>
      <xNome>DESTINATÁRIO FAKE</xNome>
      <CPF>00000000000</CPF>
    </dest>
    <total>
      <vNF>0.00</vNF>
    </total>
  </infNFe>
</NFe>
XML;
        return $xml;
    }

    /**
     * Remove várias NF-e — bloqueia remoção se status estiver em 'AUTHORIZED'
     */
    public function remove_many(array $ids)
    {
        // Não permitir apagar NF-e autorizadas
        $this->db->where_in('id', $ids)
                 ->where('status', 'AUTHORIZED');
        $authorized = $this->db->count_all_results($this->table);
        if ($authorized > 0) {
            return ['status' => false, 'message' => 'Há NF-e autorizadas; não é permitido remover.'];
        }

        $this->db->where_in('id', $ids)->delete($this->table);
        $affected = $this->db->affected_rows();

        return [
            'status'  => $affected > 0,
            'message' => $affected . ' registro(s) removido(s)'
        ];
    }
}
