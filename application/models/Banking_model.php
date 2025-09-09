<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Banking_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ============= CONTAS ============= */

    public function accounts_list($page=1,$limit=10,$search='',$sortField='id',$sortOrder='DESC',$warehouse_id=0)
    {
        $allowed = ['id','bank_name','account_number','account_type','opening_balance','created_at'];
        if (!in_array($sortField,$allowed)) $sortField='id';

        $this->db->from(db_prefix().'banking_accounts')->where('warehouse_id',$warehouse_id);
        if ($search) {
            $this->db->group_start()
                ->like('bank_name',$search)
                ->or_like('account_number',$search)
            ->group_end();
        }
        $total = $this->db->count_all_results('', false);

        $offset = ($page-1)*$limit;
        $this->db->order_by($sortField,$sortOrder)->limit($limit,$offset);
        $rows = $this->db->get()->result();

        return ['total'=>$total,'data'=>$rows];
    }

    public function account_create($data)
    {
        $insert = [
            'warehouse_id'  => (int)$data['warehouse_id'],
            'bank_name'     => $data['bank_name'],
            'agency'        => $data['agency'] ?? null,
            'account_number'=> $data['account_number'],
            'account_type'  => $data['account_type'] ?? null,
            'opening_balance' => isset($data['opening_balance']) ? (float)$data['opening_balance'] : 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix().'banking_accounts',$insert);
        return $this->db->affected_rows() ? $this->db->insert_id() : false;
    }

    public function account_update($id,$data)
    {
        if (!$id) return false;
        $update = array_intersect_key($data, array_flip([
            'bank_name','agency','account_number','account_type','opening_balance'
        ]));
        if (!$update) return false;
        $update['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id',$id)->update(db_prefix().'banking_accounts',$update);
        return $this->db->affected_rows() >= 0;
    }

    public function account_delete_many($ids)
    {
        $this->db->where_in('id',$ids)->delete(db_prefix().'banking_accounts');
        return $this->db->affected_rows();
    }

    /* ============= TAXAS ============= */

    // Busca: tenta conta; se não existir, cai no default do warehouse; se nada, retorna zeros
    public function fees_get($warehouse_id, $account_id=null)
    {
        if ($account_id) {
            $q = $this->db->get_where(db_prefix().'banking_fee_settings', [
                'warehouse_id'=>$warehouse_id,
                'account_id'=>$account_id
            ])->row_array();
            if ($q) return $q;
        }

        $q = $this->db->get_where(db_prefix().'banking_fee_settings', [
            'warehouse_id'=>$warehouse_id,
            'account_id'=>null
        ])->row_array();

        if ($q) return $q;

        // default zerado
        return [
            'id'=>null,'warehouse_id'=>$warehouse_id,'account_id'=>null,
            'credit_fee_percent'=>0,'debit_fee_percent'=>0,'pix_fee_percent'=>0,
            'anticipation_enabled'=>0,'anticipation_percent'=>0
        ];
    }

    public function fees_upsert($data)
    {
        $row = [
            'warehouse_id' => (int)$data['warehouse_id'],
            'account_id'   => isset($data['account_id']) && $data['account_id']!=='' ? (int)$data['account_id'] : null,
            'credit_fee_percent' => isset($data['credit_fee_percent']) ? (float)$data['credit_fee_percent'] : 0,
            'debit_fee_percent'  => isset($data['debit_fee_percent']) ? (float)$data['debit_fee_percent'] : 0,
            'pix_fee_percent'    => isset($data['pix_fee_percent']) ? (float)$data['pix_fee_percent'] : 0,
            'anticipation_enabled' => !empty($data['anticipation_enabled']) ? 1 : 0,
            'anticipation_percent' => isset($data['anticipation_percent']) ? (float)$data['anticipation_percent'] : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // existe?
        $exists = $this->db->get_where(db_prefix().'banking_fee_settings', [
            'warehouse_id'=>$row['warehouse_id'],
            'account_id'=>$row['account_id']
        ])->row_array();

        if ($exists) {
            $this->db->where('id', $exists['id'])->update(db_prefix().'banking_fee_settings', $row);
            return $this->db->affected_rows() >= 0;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix().'banking_fee_settings', $row);
        return $this->db->affected_rows() > 0;
    }

    /* ============= CÁLCULO ============= */

    // Calcula líquido a partir do método + antecipação
    public function calc_net_amount($gross, $method, $anticipation, $warehouse_id, $account_id, $anticipation_override=null)
    {
        $fees = $this->fees_get($warehouse_id, $account_id);

        // taxa base por método
        $map = [
            'credit' => (float)$fees['credit_fee_percent'],
            'debit'  => (float)$fees['debit_fee_percent'],
            'pix'    => (float)$fees['pix_fee_percent'],
            'other'  => 0.0,
        ];
        $feePercent = isset($map[$method]) ? $map[$method] : 0.0;

        // antecipação
        $anticipationApplied = 0;
        $anticipationPercent = 0.0;

        if ($anticipation && ((int)$fees['anticipation_enabled']) === 1) {
            $anticipationApplied = 1;
            $anticipationPercent = $anticipation_override !== null
                ? (float)$anticipation_override
                : (float)$fees['anticipation_percent'];
        }

        $totalPercent = $feePercent + $anticipationPercent;
        $net = $gross * (1 - ($totalPercent/100));

        return [
            'fee_percent_applied' => $feePercent,
            'anticipation_applied' => $anticipationApplied,
            'anticipation_percent_applied' => $anticipationPercent,
            'net_amount' => round($net, 2),
        ];
    }

    /* ============= TRANSAÇÕES ============= */

    public function transaction_create($data)
    {
        $row = [
            'warehouse_id' => (int)$data['warehouse_id'],
            'account_id'   => (int)$data['account_id'],
            'type'         => $data['type'],           // income/expense
            'method'       => $data['method'],         // credit/debit/pix/other
            'gross_amount' => (float)$data['gross_amount'],
            'fee_percent_applied' => (float)$data['fee_percent_applied'],
            'anticipation_applied' => !empty($data['anticipation_applied']) ? 1 : 0,
            'anticipation_percent_applied' => (float)$data['anticipation_percent_applied'],
            'net_amount'   => (float)$data['net_amount'],
            'description'  => $data['description'] ?? null,
            'status'       => $data['status'] ?? 'confirmed',
            'transacted_at'=> date('Y-m-d H:i:s', strtotime($data['transacted_at'])),
            'created_by'   => $data['created_by'] ?? 0,
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix().'banking_transactions', $row);
        return $this->db->affected_rows() ? $this->db->insert_id() : false;
    }

    public function transactions_list($page,$limit,$search,$sortField,$sortOrder,$warehouse_id,$account_id,$start_date,$end_date,$method,$type)
    {
        $allowed = ['id','transacted_at','gross_amount','net_amount','method','type','status','created_at'];
        if (!in_array($sortField,$allowed)) $sortField='transacted_at';

        $this->db->from(db_prefix().'banking_transactions')
            ->where('warehouse_id',$warehouse_id)
            ->where('account_id',$account_id);

        if ($start_date) $this->db->where('transacted_at >=', date('Y-m-d 00:00:00', strtotime($start_date)));
        if ($end_date)   $this->db->where('transacted_at <=', date('Y-m-d 23:59:59', strtotime($end_date)));
        if ($method)     $this->db->where('method', $method);
        if ($type)       $this->db->where('type', $type);

        if ($search) {
            $this->db->group_start()
                ->like('description',$search)
                ->or_like('status',$search)
            ->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $offset = ($page-1)*$limit;
        $this->db->order_by($sortField,$sortOrder)->limit($limit,$offset);
        $rows = $this->db->get()->result();

        return ['total'=>$total,'data'=>$rows];
    }

    /* ============= SALDO ============= */

    public function balance_summary($warehouse_id,$account_id,$start_date=null,$end_date=null)
    {
        // saldo inicial
        $account = $this->db->get_where(db_prefix().'banking_accounts',['id'=>$account_id,'warehouse_id'=>$warehouse_id])->row();
        $opening = $account ? (float)$account->opening_balance : 0.0;

        $this->db->select('
            SUM(CASE WHEN type="income"  THEN net_amount ELSE 0 END) as incomes_net,
            SUM(CASE WHEN type="expense" THEN net_amount ELSE 0 END) as expenses_net,
            SUM(net_amount) as net_total,
            SUM(gross_amount) as gross_total
        ');
        $this->db->from(db_prefix().'banking_transactions')
            ->where('warehouse_id',$warehouse_id)
            ->where('account_id',$account_id)
            ->where('status','confirmed');
        if ($start_date) $this->db->where('transacted_at >=', date('Y-m-d 00:00:00', strtotime($start_date)));
        if ($end_date)   $this->db->where('transacted_at <=', date('Y-m-d 23:59:59', strtotime($end_date)));

        $sum = $this->db->get()->row_array();

        // detalhamento por método (útil pro gráfico do seu front)
        $byMethod = $this->db->query("
            SELECT method,
                   SUM(gross_amount) as gross_sum,
                   SUM(net_amount)   as net_sum
            FROM ".db_prefix()."banking_transactions
            WHERE warehouse_id=? AND account_id=? AND status='confirmed'
              ".($start_date ? " AND transacted_at >= '".$this->db->escape_str(date('Y-m-d 00:00:00', strtotime($start_date)))."'" : "")."
              ".($end_date   ? " AND transacted_at <= '".$this->db->escape_str(date('Y-m-d 23:59:59', strtotime($end_date)))."'" : "")."
            GROUP BY method
        ", [$warehouse_id,$account_id])->result();

        return [
            'opening_balance' => $opening,
            'gross_total' => (float)($sum['gross_total'] ?? 0),
            'net_total'   => (float)($sum['net_total'] ?? 0),
            'incomes_net' => (float)($sum['incomes_net'] ?? 0),
            'expenses_net'=> (float)($sum['expenses_net'] ?? 0),
            'current_balance_net' => round($opening + (float)($sum['net_total'] ?? 0), 2),
            'by_method' => $byMethod,
        ];
    }
}
