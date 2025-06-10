<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_items_model extends App_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Copy invoice item
     * @param array $data Invoice item data
     * @return boolean
     */
    public function copy($_data) {
        $custom_fields_items = get_custom_fields('items');

        $data = [
            'description' => $_data['description'] . ' - Copy',
            'rate' => $_data['rate'],
            'tax' => $_data['taxid'],
            'tax2' => $_data['taxid_2'],
            'group_id' => $_data['group_id'],
            'unit' => $_data['unit'],
            'long_description' => $_data['long_description'],
        ];

        foreach ($_data as $column => $value) {
            if (strpos($column, 'rate_currency_') !== false) {
                $data[$column] = $value;
            }
        }

        $columns = $this->db->list_fields(db_prefix() . 'items');
        $this->load->dbforge();
        foreach ($data as $column) {
            if (!in_array($column, $columns) && strpos($column, 'rate_currency_') !== false) {
                $field = [
                    $column => [
                        'type' => 'decimal(15,' . get_decimal_places() . ')',
                        'null' => true,
                    ],
                ];
                $this->dbforge->add_column('items', $field);
            }
        }

        foreach ($custom_fields_items as $cf) {
            $data['custom_fields']['items'][$cf['id']] = get_custom_field_value($_data['itemid'], $cf['id'], 'items_pr', false);
            if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
            }
        }

        $insert_id = $this->add($data);

        if ($insert_id) {
            hooks()->do_action('item_coppied', $insert_id);

            log_activity('Copied Item  [ID:' . $_data['itemid'] . ', ' . $data['description'] . ']');

            return $insert_id;
        }

        return false;
    }
    
public function get_by_type($type = '') {
    $items_table = db_prefix() . 'items';
    $warehouses_table = db_prefix() . 'warehouse';

    // First, find the warehouse ID that matches the specified type
    $this->db->select('warehouse_id');
    $this->db->from($warehouses_table);
    $this->db->where('type', $type);
    $this->db->limit(1); // Assuming you want just one warehouse of a specific type
    $warehouse = $this->db->get()->row();

    if ($warehouse) {
        $this->db->select([
            "$items_table.id as id",
            "$items_table.rate",
            "$items_table.description",
            "$items_table.long_description",
            "$items_table.group_id",
            "$items_table.unit",
            "$items_table.sku_code",
            "$items_table.image",
            "$items_table.commodity_barcode",
            "$items_table.status",
            "$items_table.cost",
            "$items_table.maxDiscount",
            "$items_table.promoPrice",
            "$items_table.promoStart",
            "$items_table.promoEnd",
            "$items_table.stock",
            "$items_table.minStock",
            "$items_table.product_unit",
            "$items_table.createdAt",
            "$items_table.updatedAt",
            "$items_table.warehouse_id as warehouse_id"
        ]);

        $this->db->from($items_table);
        $this->db->where("$items_table.warehouse_id", $warehouse->warehouse_id);
        $this->db->order_by("$items_table.id", 'desc');

        return $this->db->get()->result();
    }

    return []; // Return empty array if no warehouse matches the type
}

 
   public function get($id = '') {
       
        $items_table = db_prefix() . 'items';

        $this->db->select([
            "$items_table.id as id",
            "$items_table.rate",
            "$items_table.description",
            "$items_table.long_description",
            "$items_table.group_id",
            "$items_table.unit",
            "$items_table.sku_code",
            "$items_table.image",
            "$items_table.commodity_barcode",
            "$items_table.status",
            "$items_table.cost",
            "$items_table.maxDiscount",
            "$items_table.promoPrice",
            "$items_table.promoStart",
            "$items_table.promoEnd",
            "$items_table.stock",
            "$items_table.minStock",
            "$items_table.product_unit",
            "$items_table.createdAt",
            "$items_table.updatedAt",
            "$items_table.warehouse_id as warehouse_id"
        ]);

        $this->db->from(db_prefix() . 'items');
        $this->db->join('' . db_prefix() . 'taxes t1', 't1.id = ' . db_prefix() . 'items.tax', 'left');
        $this->db->join('' . db_prefix() . 'taxes t2', 't2.id = ' . db_prefix() . 'items.tax2', 'left');
        $this->db->join(db_prefix() . 'items_groups', '' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');
        $this->db->order_by('description', 'asc');
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'items.id', $id);

            return $this->db->get()->row();
        }

        return $this->db->get()->result_array();
    }

   
  
  
    public function get_item($id = '') {
        $this->db->select(db_prefix() . 'items.*, ' . db_prefix() . 'clients.company');
        $this->db->from(db_prefix() . 'items');
        $this->db->where(db_prefix() . 'items.id', $id);
        $this->db->join(db_prefix() . 'clients', 'clients.userid = ' . db_prefix() . 'items.userid', 'left');

        return $this->db->get()->row();
    }

    public function get_by_sku($id = '') {
        $this->db->from(db_prefix() . 'items');
        $this->db->where(db_prefix() . 'items.sku_code', $id);

        return $this->db->get()->row();
    }

    public function get_category_id_by_name($name) {

        $this->db->select('id ');
        $this->db->from(db_prefix() . 'items_groups');
        $this->db->where('name', $name);
        return $this->db->get()->row();
    }

    public function get_unit_id_by_name($name) {


        $this->db->select('unit_type_id as id');
        $this->db->from(db_prefix() . 'ware_unit_type');
        $this->db->where('unit_code', $name);

        return $this->db->get()->row();
    }
    /**
     * Get items with API formatting
     */
  
  public function get_api(
            $id = '',
            $page = 1,
            $limit = 10,
            $search = '',
            $sortField = 'id',
            $sortOrder = 'DESC',
            $statusFilter = null,
            $startDate = null,
            $endDate = null,
            $category = null,
            $subcategory = null,
            $warehouse_id = null,
            $send = null
    ) {




        $items_table = db_prefix() . 'items';
        $groups_table = db_prefix() . 'items_groups';
        $subgroups_table = db_prefix() . 'wh_sub_group';
        $supplier_table = db_prefix() . 'clients';

        if ($id != '') {



            $this->db->select([
                "$items_table.*",
                "$supplier_table.company as supplier",
                "$groups_table.name as group_name",
                "$subgroups_table.sub_group_name",
                "$subgroups_table.id as sub_group_id"
            ]);

            $this->db->from($items_table)
                    ->join($groups_table, "$groups_table.id = $items_table.group_id", 'left')
                    ->join($subgroups_table, "$subgroups_table.id = $items_table.sub_group", 'left')
                    ->join($supplier_table, "$supplier_table.userid = $items_table.userid", 'left');

            if ($warehouse_id) {
                $this->db->where("$items_table.warehouse_id", $warehouse_id);
            }

            if ($send == 'pdv') {
                $this->db->where("$items_table.id", $id);
                $this->db->or_where("$items_table.commodity_barcode", $id)->limit(1);
                $item = $this->db->get()->row();
            } else {

                $this->db->or_where("$items_table.commodity_barcode", $id);
                $item = $this->db->get()->row_array();
            }



            if ($item) {

                if (is_object($item)) {
                    $this->db->where('product_id', $item->id);
                    $packaging = $this->db->get(db_prefix() . 'product_packaging')->result_array();
                    $item->packaging = $packaging;
                } else {
                    $this->db->where('product_id', $item['id']);
                    $packaging = $this->db->get(db_prefix() . 'product_packaging')->result_array();
                    $item['packaging'] = $packaging;
                }

                if ($send == 'pdv') {
                    return ['data' => $item, 'total' => 1];
                } else {
                    return ['data' => [$item], 'total' => 1];
                }
            }
            return ['data' => [], 'total' => 0];
        }


        $this->db->select([
            "$items_table.id as id",
            "$supplier_table.company as supplier",
            "$items_table.rate",
            't1.taxrate as taxrate',
            't1.id as taxid',
            't1.name as taxname',
            't2.taxrate as taxrate_2',
            't2.id as taxid_2',
            't2.name as taxname_2',
            "$items_table.description",
            "$items_table.long_description",
            "$items_table.group_id",
            "$groups_table.name as group_name",
            "$items_table.unit",
            "$items_table.sku_code",
            "$items_table.image",
            "$items_table.commodity_barcode",
            "$items_table.status",
            "$items_table.cost",
            "$items_table.maxDiscount",
            "$items_table.promoPrice",
            "$items_table.promoStart",
            "$items_table.promoEnd",
            "$items_table.stock",
            "$items_table.minStock",
            "$items_table.product_unit",
            "$items_table.createdAt",
            "$items_table.updatedAt",
            "$subgroups_table.sub_group_name",
            "$subgroups_table.id as sub_group_id",
            "$items_table.warehouse_id as warehouse_id"
        ]);

        $this->db->from($items_table)
                ->join(db_prefix() . 'taxes t1', "t1.id = $items_table.tax", 'left')
                ->join(db_prefix() . 'taxes t2', "t2.id = $items_table.tax2", 'left')
                ->join($groups_table, "$groups_table.id = $items_table.group_id", 'left')
                ->join($subgroups_table, "$subgroups_table.id = $items_table.sub_group", 'left')
                ->join($supplier_table, "$supplier_table.userid = $items_table.userid", 'left');

        if ($warehouse_id) {
            $this->db->where("$items_table.warehouse_id", $warehouse_id);
        }

        if (!empty($statusFilter) && is_array($statusFilter)) {
            $this->db->where_in("$items_table.status", $statusFilter);
        }

        if (!empty($startDate)) {
            $this->db->where("DATE($items_table.createdAt) >=", (new DateTime($startDate))->format('Y-m-d'));
        }
        if (!empty($endDate)) {
            $this->db->where("DATE($items_table.createdAt) <=", (new DateTime($endDate))->format('Y-m-d'));
        }

        if (!empty($category)) {
            $this->db->where("$items_table.group_id", $category);
        }

        if (!empty($subcategory)) {
            $this->db->where("$items_table.sub_group", $subcategory);
        }


        if (!empty($search)) {
            if (is_numeric($search)) {
                $this->db->group_start()
                        ->where('sku_code', $search)
                        ->or_where("$items_table.commodity_barcode", $search)
                        ->group_end(); // Esto agrupa la condición de búsqueda que incluye OR
            } else {

                $this->db->group_start()
                        ->like("$items_table.description", $search)
                        ->or_like("$items_table.long_description", $search)
                        ->or_like("$items_table.rate", $search)
                        ->or_like("$items_table.sku_code", $search)
                        ->or_like("$items_table.commodity_barcode", $search)
                        ->or_like("$items_table.id", $search)
                        ->group_end();
            }
        }

        $total = $this->db->count_all_results('', false);

        $allowedSortFields = ['id', 'description', 'rate', 'sku_code', 'createdAt', 'updatedAt'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'id';

        $this->db->order_by("$items_table.$sortField", $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $items = $this->db->get()->result_array();

        //  var_dump($this->db->last_query());

        return ['data' => $items, 'total' => $total];
    }
  
  
  
  

    public function totalItens($warehouse_id) {

        $this->db->where(db_prefix() . 'items.warehouse_id', $warehouse_id);
        $this->db->from(db_prefix() . 'items');
        return $this->db->count_all_results('', true);
    }

    public function get_api2(
            $id = '',
            $page = 1,
            $limit = 10,
            $search = '',
            $sortField = 'id',
            $sortOrder = 'DESC',
            $statusFilter = null,
            $startDate = null,
            $endDate = null,
            $category = null,
            $subcategory = null,
            $warehouse_id = null,
            $send = null
    ) {
        $items_table = db_prefix() . 'items';
        $groups_table = db_prefix() . 'items_groups';
        $subgroups_table = db_prefix() . 'wh_sub_group';
        $suppliers_table = db_prefix() . 'clients';

        if ($id != '') {

            $this->db->select([
                "$items_table.*",
                "$groups_table.name as group_name",
                "$subgroups_table.sub_group_name",
                "$subgroups_table.id as sub_group_id",
                "$suppliers_table.userid",
                "$suppliers_table.company"
            ]);

            $this->db->from($items_table)
                    ->join($groups_table, "$groups_table.id = $items_table.group_id", 'left')
                    ->join($subgroups_table, "$subgroups_table.id = $items_table.sub_group", 'left')
                    ->join($suppliers_table, "$suppliers_table.userid = $items_table.userid", 'left');

            if ($warehouse_id) {
                $this->db->where("$items_table.warehouse_id", $warehouse_id);
            }

            if ($send == 'pdv') {
                $this->db->where("$items_table.id", $id);
                $this->db->or_where("$items_table.commodity_barcode", $id)->limit(1);
                $item = $this->db->get()->row();
            } else {

                $this->db->or_where("$items_table.commodity_barcode", $id);
                $item = $this->db->get()->row_array();
            }




            if ($item) {

                if ($send == 'pdv') {
                    return ['data' => $item, 'total' => 1];
                } else {
                    return ['data' => [$item], 'total' => 1];
                }
            }
            return ['data' => [], 'total' => 0];
        }

        $this->db->select([
            "$items_table.id as id",
            "$items_table.rate",
            't1.taxrate as taxrate',
            't1.id as taxid',
            't1.name as taxname',
            't2.taxrate as taxrate_2',
            't2.id as taxid_2',
            't2.name as taxname_2',
            "$items_table.description",
            "$items_table.long_description",
            "$items_table.group_id",
            "$groups_table.name as group_name",
            "$items_table.unit",
            "$items_table.ncm",
            "$items_table.nfci",
            "$items_table.cest",
            "$items_table.origin",
            "$items_table.cfop",
            "$items_table.length",
            "$items_table.width",
            "$items_table.height",
            "$items_table.cubage",
            "$items_table.maxDiscount",
            "$items_table.product_unit",
            "$items_table.sku_code",
            "$items_table.image",
            "$items_table.commodity_barcode",
            "$items_table.status",
            "$items_table.cost",
            "$items_table.promoPrice",
            "$items_table.promoStart",
            "$items_table.promoEnd",
            "$items_table.userid",
            "$items_table.stock",
            "$items_table.minStock",
            "$items_table.product_unit",
            "$items_table.createdAt",
            "$items_table.updatedAt",
            "$subgroups_table.sub_group_name",
            "$subgroups_table.id as sub_group_id",
            "$items_table.warehouse_id as warehouse_id",
            "$suppliers_table.userid",
            "$suppliers_table.company"
        ]);

        $this->db->from($items_table)
                ->join(db_prefix() . 'taxes t1', "t1.id = $items_table.tax", 'left')
                ->join(db_prefix() . 'taxes t2', "t2.id = $items_table.tax2", 'left')
                ->join($groups_table, "$groups_table.id = $items_table.group_id", 'left')
                ->join($subgroups_table, "$subgroups_table.id = $items_table.sub_group", 'left')
                ->join($suppliers_table, "$suppliers_table.userid = $items_table.userid", 'left');

        if ($warehouse_id) {
            $this->db->where("$items_table.warehouse_id", $warehouse_id);
        }
        if (!empty($statusFilter) && is_array($statusFilter)) {
            $this->db->where_in("$items_table.status", $statusFilter);
        }
        if (!empty($startDate)) {
            $this->db->where("DATE($items_table.createdAt) >=", (new DateTime($startDate))->format('Y-m-d'));
        }
        if (!empty($endDate)) {
            $this->db->where("DATE($items_table.createdAt) <=", (new DateTime($endDate))->format('Y-m-d'));
        }
        if (!empty($category)) {
            $this->db->where("$items_table.group_id", $category);
        }

        if (!empty($subcategory)) {
            $this->db->where("$items_table.sub_group", $subcategory);
        }

        if (!empty($search)) {
            $this->db->group_start()
                    ->like("$items_table.description", $search)
                    ->or_like("$items_table.long_description", $search)
                    // ->or_like("$items_table.rate", $search)
                    // ->or_like("$items_table.sku_code", $search)
                    ->or_like("$items_table.commodity_barcode", $search)
                    // ->or_like("$items_table.id", $search)
                    ->group_end();
        }

        $total = $this->db->count_all_results('', false);

        $allowedSortFields = ['id', 'description', 'rate', 'sku_code', 'createdAt', 'updatedAt'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'id';

        $this->db->order_by("$items_table.$sortField", $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $items = $this->db->get()->result_array();

        return ['data' => $items, 'total' => $total];
    }

    public function get_grouped() {
        $items = [];
        $this->db->order_by('name', 'asc');
        $groups = $this->db->get(db_prefix() . 'items_groups')->result_array();

        array_unshift($groups, [
            'id' => 0,
            'name' => '',
        ]);

        foreach ($groups as $group) {
            $this->db->select('*,' . db_prefix() . 'items_groups.name as group_name,' . db_prefix() . 'items.id as id');
            $this->db->where('group_id', $group['id']);
            $this->db->join(db_prefix() . 'items_groups', '' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');
            $this->db->order_by('description', 'asc');
            $_items = $this->db->get(db_prefix() . 'items')->result_array();
            if (count($_items) > 0) {
                $items[$group['id']] = [];
                foreach ($_items as $i) {
                    array_push($items[$group['id']], $i);
                }
            }
        }

        return $items;
    }

    /**
     * Add new invoice item
     * @param array $data Invoice item data
     * @return boolean
     */
    public function add($data) {
        unset($data['itemid']);
        if (isset($data['tax']) && $data['tax'] == '') {
            unset($data['tax']);
        }

        if (isset($data['tax2']) && $data['tax2'] == '') {
            unset($data['tax2']);
        }

        if (isset($data['group_id']) && $data['group_id'] == '') {
            $data['group_id'] = 0;
        }

        $columns = $this->db->list_fields(db_prefix() . 'items');

        $this->load->dbforge();

        foreach ($data as $column => $itemData) {
            if (!in_array($column, $columns) && strpos($column, 'rate_currency_') !== false) {
                $field = [
                    $column => [
                        'type' => 'decimal(15,' . get_decimal_places() . ')',
                        'null' => true,
                    ],
                ];
                $this->dbforge->add_column('items', $field);
            }
        }

        $data = hooks()->apply_filters('before_item_created', $data);
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        $this->db->insert('items', $data);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            handle_custom_fields_post($insert_id, $custom_fields, true);

            hooks()->do_action('item_created', $insert_id);

            log_activity('New Invoice Item Added [ID:' . $insert_id . ', ' . $data['description'] . ']');

            return $insert_id;
        }

        return false;
    }

    public function add_products_nf($data)
    {
        // Remover campos desnecessários
        unset($data['itemid']);

        // Tratar campos vazios
        if (isset($data['tax']) && $data['tax'] == '') {
            unset($data['tax']);
        }
        if (isset($data['tax2']) && $data['tax2'] == '') {
            unset($data['tax2']);
        }
        if (isset($data['group_id']) && $data['group_id'] == '') {
            $data['group_id'] = 0;
        }

        // Garantir que campos numéricos sejam números
        $numericFields = ['rate', 'stock', 'minStock', 'cost', 'promoPrice'];
        foreach ($numericFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = floatval($data[$field]);
            }
        }

        // Aplicar hooks e filtros
        $data = hooks()->apply_filters('before_item_created', $data);
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        // Inserir no banco
        $this->db->insert('items', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            handle_custom_fields_post($insert_id, $custom_fields, true);
            hooks()->do_action('item_created', $insert_id);
            log_activity('New Product Added from NF [ID:' . $insert_id . ', ' . $data['description'] . ']');
            return $insert_id;
        }

        return false;
    }

    /**
     * Update invoiec item
     * @param  array $data Invoice data to update
     * @return boolean
     */
    public function edit($data, $id) {
        $itemid = $id;

        if (isset($data['group_id']) && $data['group_id'] == '') {
            $data['group_id'] = 0;
        }

        if (isset($data['tax']) && $data['tax'] == '') {
            $data['tax'] = null;
        }

        if (isset($data['tax2']) && $data['tax2'] == '') {
            $data['tax2'] = null;
        }

        $columns = $this->db->list_fields(db_prefix() . 'items');
        $this->load->dbforge();

        foreach ($data as $column => $itemData) {
            if (!in_array($column, $columns) && strpos($column, 'rate_currency_') !== false) {
                $field = [
                    $column => [
                        'type' => 'decimal(15,' . get_decimal_places() . ')',
                        'null' => true,
                    ],
                ];
                $this->dbforge->add_column('items', $field);
            }
        }

        $updated = false;
        $data = hooks()->apply_filters('before_update_item', $data, $itemid);
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        $this->db->where('id', $itemid);
        $this->db->update('items', $data);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }

        if (handle_custom_fields_post($itemid, $custom_fields, true)) {
            $updated = true;
        }

        do_action_deprecated('item_updated', [$itemid], '2.9.4', 'after_item_updated');

        hooks()->do_action('after_item_updated', [
            'id' => $itemid,
            'data' => $data,
            'custom_fields' => $custom_fields,
            'updated' => &$updated,
        ]);

        if ($updated) {
            log_activity('Invoice Item Updated [ID: ' . $itemid . ', ' . $data['description'] . ']');
        }

        return $updated;
    }

    /**
     * Update invoiec item
     * @param  array $data Invoice data to update
     * @return boolean
     */
    public function edit_by_sku($data, $id, $warehouse_id) {
        $itemid = $id;

        if (isset($data['group_id']) && $data['group_id'] == '') {
            $data['group_id'] = 0;
        }

        if (isset($data['tax']) && $data['tax'] == '') {
            $data['tax'] = null;
        }

        if (isset($data['tax2']) && $data['tax2'] == '') {
            $data['tax2'] = null;
        }

        $columns = $this->db->list_fields(db_prefix() . 'items');
        $this->load->dbforge();

        foreach ($data as $column => $itemData) {
            if (!in_array($column, $columns) && strpos($column, 'rate_currency_') !== false) {
                $field = [
                    $column => [
                        'type' => 'decimal(15,' . get_decimal_places() . ')',
                        'null' => true,
                    ],
                ];
                $this->dbforge->add_column('items', $field);
            }
        }

        $updated = false;
        $data = hooks()->apply_filters('before_update_item', $data, $itemid);
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        $this->db->where('sku_code', $itemid);
        $this->db->where('warehouse_id', $warehouse_id);
        $this->db->update('items', $data);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }

        if (handle_custom_fields_post($itemid, $custom_fields, true)) {
            $updated = true;
        }

        do_action_deprecated('item_updated', [$itemid], '2.9.4', 'after_item_updated');

        hooks()->do_action('after_item_updated', [
            'id' => $itemid,
            'data' => $data,
            'custom_fields' => $custom_fields,
            'updated' => &$updated,
        ]);

        if ($updated) {
            log_activity('Invoice Item Updated [ID: ' . $itemid . ', ' . $data['description'] . ']');
        }

        return $updated;
    }

    public function search($q) {
        $this->db->select('rate, id, description as name, long_description as subtext');
        $this->db->like('description', $q);
        $this->db->or_like('long_description', $q);

        $items = $this->db->get(db_prefix() . 'items')->result_array();

        foreach ($items as $key => $item) {
            $items[$key]['subtext'] = strip_tags(mb_substr($item['subtext'], 0, 200)) . '...';
            $items[$key]['name'] = '(' . app_format_number($item['rate']) . ') ' . $item['name'];
        }

        return $items;
    }

    /**
     * Delete invoice item
     * @param  mixed $id
     * @return boolean
     */
    public function delete($id) {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'items');
        if ($this->db->affected_rows() > 0) {
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'items_pr');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            log_activity('Invoice Item Deleted [ID: ' . $id . ']');

            hooks()->do_action('item_deleted', $id);

            return true;
        }

        return false;
    }

  
   public function delete_by_sku($sku) {
        // Deleta todos os itens com o SKU fornecido
        $this->db->where('sku_code', $sku);
        $this->db->delete(db_prefix() . 'items');

        /*
          // Verifica se alguma linha foi afetada (ou seja, deletada)
          if ($this->db->affected_rows() > 0) {
          // Deleta registros associados na tabela de valores de campos personalizados
          $this->db->where('relid', $sku);
          $this->db->where('fieldto', 'items_pr');
          $this->db->delete(db_prefix() . 'customfieldsvalues');

          // Loga a atividade
          log_activity('Itens com SKU: ' . $sku . ' foram deletados.');

          // Aciona evento hook
          hooks()->do_action('items_deleted', $sku);

          return true;
          }
         * 
         */

        return true;
    }

    public function get_groups($page = 1, $limit = 10, $search = '', $sortOrder = 'ASC') {
        $this->db->select('id, name');
        $this->db->from(db_prefix() . 'items_groups');

        if (!empty($search)) {
            $this->db->like('name', $search);
        }

        $total = $this->db->count_all_results('', false);

        $this->db->order_by('name', $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);

        $groups = $this->db->get()->result_array();

        return ['data' => $groups, 'total' => $total];
    }

    public function add_group($data) {
        $this->db->insert(db_prefix() . 'items_groups', $data);
        log_activity('Items Group Created [Name: ' . $data['name'] . ']');

        return $this->db->insert_id();
    }

    public function edit_group($data, $id) {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'items_groups', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Items Group Updated [Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_group($id) {
        $this->db->where('id', $id);
        $group = $this->db->get(db_prefix() . 'items_groups')->row();

        if ($group) {
            $this->db->where('group_id', $id);
            $this->db->update(db_prefix() . 'items', [
                'group_id' => 0,
            ]);

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'items_groups');

            log_activity('Item Group Deleted [Name: ' . $group->name . ']');

            return true;
        }

        return false;
    }

    public function add_subgroup($data) {
        $this->db->insert(db_prefix() . 'wh_sub_group', $data);
        log_activity('Sub Group Created [Name: ' . $data['sub_group_name'] . ']');
        return $this->db->insert_id();
    }

    public function edit_subgroup($data, $id) {
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'wh_sub_group', $data);
    }

    public function delete_subgroup($id) {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'wh_sub_group');
    }

    
    public function bulk_create_products($products, $warehouse_id, $edit_product_id = null)
    {
        if ((empty($warehouse_id) && !$edit_product_id) || empty($products) || !is_array($products)) {
            return [
                'status' => false,
                'message' => 'Invalid input data',
                'created' => 0,
                'failed' => 0
            ];
        }

        $this->db->trans_begin();
        
        $success_count = 0;
        $failed_products = [];
        $created_products = [];
        
        try {
            foreach ($products as $index => $product_data) {
                
                $input_image_singular_b64 = $product_data['image_base64'] ?? null;
                $input_images_array_b64 = $product_data['images_base64'] ?? [];
                $input_primary_index = isset($product_data['primary_image_index']) ? (int)$product_data['primary_image_index'] : null;

                unset($product_data['image_base64'], $product_data['images_base64'], $product_data['primary_image_index']);
                
                $packaging_data = null;
                if (isset($product_data['packagings'])) {
                    $packaging_data = $product_data['packagings'];
                    unset($product_data['packagings']);
                } else if (isset($product_data['packaging'])) {
                    $packaging_data = $product_data['packaging'];
                    unset($product_data['packaging']);
                }
                
                $field_mappings = [
                    'productName' => 'description',
                    'sku' => 'sku_code',
                    'category' => 'group_id',
                    'subcategory' => 'sub_group',
                    'unit_id' => 'unit_id',
                    'unitsPerBox' => 'defaultPurchaseQuantity',
                    'barcode' => 'commodity_barcode',
                    'status' => 'status',
                    
                    'cost' => 'cost',
                    'costAC' => 'cost_ac',
                    'costAO' => 'cost_ao',
                    'franchiseProfitPercent' => 'franchise_profit_percent',
                    'franchisePrice' => 'franchise_price',
                    'franchiseNfePrice' => 'franchise_nfe_price',
                    'promoPrice' => 'promoPrice', 
                    'maxDiscount' => 'maxDiscount',
                    'promoStart' => 'promoStart',
                    'promoEnd' => 'promoEnd',
                    
                    'stock' => 'stock',
                    'minStock' => 'minStock',
                    'reservedStock' => 'reserved_stock',
                    'defaultPurchaseQuantity' => 'defaultPurchaseQuantity',
                    'location' => 'location', 
                    'rpaEnabled' => 'rpaEnabled', 
                    'show_on_pdv' => 'show_on_pdv',
                    
                    'length' => 'length',
                    'width' => 'width',
                    'height' => 'height',
                    'cubage' => 'cubage',
                    'weight' => 'net_weight',
                    'grossWeight' => 'gross_weight',
                    
                    'tags' => 'tags',
                    'origin' => 'origin',
                    'itemType' => 'item_type',
                    'ncm' => 'ncm',
                    'cest' => 'cest',
                    'cfop' => 'cfop',
                    'tax_percent' => 'tax_percent',
                    'tax_group' => 'tax_group',
                    'tax_icms_base' => 'tax_icms_base',
                    'tax_icms_st' => 'tax_icms_st',
                    'tax_icms_proprio' => 'tax_icms_proprio',
                    'tax_ipi_exception' => 'tax_ipi_exception',
                    'tax_pis' => 'tax_pis',
                    'tax_cofins' => 'tax_cofins',
                    'tax_additional_info' => 'tax_additional_info'
                ];
                
                $cleaned_product_data = [];
                foreach ($field_mappings as $form_field => $db_field) {
                    if (isset($product_data[$form_field])) {
                        $value = $product_data[$form_field];
                        
                        if ($form_field === 'status' || $form_field === 'active') {
                            $value = ($value == '1' || $value === true) ? 'active' : 'inactive';
                        }
                        
                        if (in_array($form_field, ['rpaEnabled', 'show_on_pdv'])) {
                            $value = ($value == '1' || $value === true) ? 1 : 0;
                        }
                        
                        if (in_array($form_field, ['promoStart', 'promoEnd']) && !empty($value)) {
                            $date = new DateTime($value);
                            $value = $date->format('Y-m-d');
                        }
                        
                        if (in_array($form_field, [
                            'cost', 'costAC', 'costAO', 'franchiseProfitPercent', 'franchisePrice', 'franchiseNfePrice', 
                            'promoPrice', 'maxDiscount', 'stock', 'minStock', 'reservedStock', 'defaultPurchaseQuantity',
                            'length', 'width', 'height', 'cubage', 'weight', 'grossWeight'
                        ])) {
                            if ($value !== null && $value !== '') {
                                $value = is_numeric($value) ? (float)$value : 0;
                            }
                        }
                        
                        if ($value !== null) {
                            $cleaned_product_data[$db_field] = $this->security->xss_clean($value);
                        }
                        
                        unset($product_data[$form_field]);
                    }
                }
                
                foreach ($product_data as $key => $value) {
                    if ($value !== null && !is_array($value)) {
                        $cleaned_product_data[$key] = $this->security->xss_clean($value);
                    }
                }
                
                if (!$edit_product_id) {
                    $cleaned_product_data['warehouse_id'] = $warehouse_id;
                }
                
                if (!isset($cleaned_product_data['status'])) {
                    $cleaned_product_data['status'] = 'active';
                }
                
                if (!isset($cleaned_product_data['rate']) && isset($cleaned_product_data['franchise_price'])) {
                    $cleaned_product_data['rate'] = $cleaned_product_data['franchise_price'];
                }
                
                if (!isset($cleaned_product_data['cubage']) && 
                    isset($cleaned_product_data['length']) && 
                    isset($cleaned_product_data['width']) && 
                    isset($cleaned_product_data['height'])) {
                    $cleaned_product_data['cubage'] = 
                        ($cleaned_product_data['length'] * 
                         $cleaned_product_data['width'] * 
                         $cleaned_product_data['height']) / 1000000;
                }
                
                if ($edit_product_id) {
                    $cleaned_product_data['updatedAt'] = date('Y-m-d H:i:s');
                    
                    if (!isset($cleaned_product_data['description'])) {
                        $existing = $this->get_item($edit_product_id);
                        if ($existing && $existing->description) {
                            $cleaned_product_data['description'] = $existing->description;
                        }
                    }
                    
                    $result = $this->edit($cleaned_product_data, $edit_product_id);
                    if (!$result) {
                        $failed_products[] = [
                            'index' => $index,
                            'error' => 'Failed to update product'
                        ];
                        continue;
                    }
                    $product_id = $edit_product_id;
                } else {
                    if (empty($cleaned_product_data['description'])) {
                        $failed_products[] = [
                            'index' => $index,
                            'error' => 'Description is required'
                        ];
                        continue;
                    }
                    
                    $cleaned_product_data['createdAt'] = date('Y-m-d H:i:s');
                    $cleaned_product_data['updatedAt'] = date('Y-m-d H:i:s');

                    $product_id = $this->add($cleaned_product_data);

                    if (!$product_id) {
                        $failed_products[] = [
                            'index' => $index,
                            'error' => 'Failed to create product'
                        ];
                        continue;
                    }
                }
                
                if ($packaging_data && is_array($packaging_data) && !empty($packaging_data)) {
                    $this->db->where('product_id', $product_id);
                    $this->db->delete(db_prefix() . 'product_packaging');
                    
                    foreach ($packaging_data as $package) {
                        $package_data = [
                            'product_id' => $product_id,
                            'name' => $this->security->xss_clean($package['name'] ?? ''),
                            'units' => (int)($package['units'] ?? 0),
                            'length' => (float)($package['length'] ?? 0),
                            'width' => (float)($package['width'] ?? 0),
                            'height' => (float)($package['height'] ?? 0),
                            'cubage' => (float)($package['cubage'] ?? 0),
                            'is_open' => isset($package['isOpen']) ? (int)$package['isOpen'] : 0,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        if (empty($package_data['cubage']) && 
                            !empty($package_data['length']) && 
                            !empty($package_data['width']) && 
                            !empty($package_data['height'])) {
                            $package_data['cubage'] = 
                                ($package_data['length'] * 
                                 $package_data['width'] * 
                                 $package_data['height']) / 1000000;
                        }
                        
                        $this->db->insert(db_prefix() . 'product_packaging', $package_data);
                    }
                }

                $final_image_db_updates = [];
                if ($product_id) { 
                        $upload_dir = './uploads/items/' . $product_id . '/';

                    if ((!empty($input_images_array_b64) && is_array($input_images_array_b64)) || !empty($input_image_singular_b64)) {
                        if (!file_exists($upload_dir)) {
                            if (!mkdir($upload_dir, 0777, true)) {
                                log_activity('Failed to create upload directory for product ID ' . $product_id);
                            }
                        }
                    }

                    $uploaded_primary_url = null;
                    $images_for_other_slots_b64 = [];
                    if (is_array($input_images_array_b64)) {
                        $images_for_other_slots_b64 = $input_images_array_b64;
                    }


                    if ($input_primary_index !== null && isset($input_images_array_b64[$input_primary_index]) && !empty($input_images_array_b64[$input_primary_index])) {
                        $b64 = $input_images_array_b64[$input_primary_index];
                        $url = null; $img_data_h=null; $img_type_h='jpg'; if(preg_match('/^data:image\/(\w+);base64,(.*)$/s',$b64,$m)){$img_type_h=strtolower($m[1]);$img_data_h=base64_decode($m[2],true);}else{$img_data_h=base64_decode($b64,true);} if($img_data_h && file_exists($upload_dir)){$fn_h=uniqid().'.'.$img_type_h;$fp_h=$upload_dir.$fn_h;if(file_put_contents($fp_h,$img_data_h)){$s_h=base_url();$r_h=str_replace('./','',$fp_h);$url=rtrim($s_h,'/').'/'.$r_h;}}
                        if ($url) {
                            $final_image_db_updates['image'] = $url;
                            $uploaded_primary_url = $url;
                            if(isset($images_for_other_slots_b64[$input_primary_index])) {
                                unset($images_for_other_slots_b64[$input_primary_index]);
                            }
                            }
                        }
                        
                    if (!$uploaded_primary_url && !empty($images_for_other_slots_b64)) {
                        foreach ($images_for_other_slots_b64 as $key => $b64_val) {
                            if (!empty($b64_val)) {
                                $url = null; $img_data_h=null; $img_type_h='jpg'; if(preg_match('/^data:image\/(\w+);base64,(.*)$/s',$b64_val,$m)){$img_type_h=strtolower($m[1]);$img_data_h=base64_decode($m[2],true);}else{$img_data_h=base64_decode($b64_val,true);} if($img_data_h && file_exists($upload_dir)){$fn_h=uniqid().'.'.$img_type_h;$fp_h=$upload_dir.$fn_h;if(file_put_contents($fp_h,$img_data_h)){$s_h=base_url();$r_h=str_replace('./','',$fp_h);$url=rtrim($s_h,'/').'/'.$r_h;}}
                                if ($url) {
                                    $final_image_db_updates['image'] = $url;
                                    $uploaded_primary_url = $url;
                                    unset($images_for_other_slots_b64[$key]);
                                }
                                break; 
                            }
                    }
                }

                    if (!$uploaded_primary_url && !empty($input_image_singular_b64)) {
                        $url = null; $img_data_h=null; $img_type_h='jpg'; if(preg_match('/^data:image\/(\w+);base64,(.*)$/s',$input_image_singular_b64,$m)){$img_type_h=strtolower($m[1]);$img_data_h=base64_decode($m[2],true);}else{$img_data_h=base64_decode($input_image_singular_b64,true);} if($img_data_h && file_exists($upload_dir)){$fn_h=uniqid().'.'.$img_type_h;$fp_h=$upload_dir.$fn_h;if(file_put_contents($fp_h,$img_data_h)){$s_h=base_url();$r_h=str_replace('./','',$fp_h);$url=rtrim($s_h,'/').'/'.$r_h;}}
                        if ($url) {
                            $final_image_db_updates['image'] = $url;
                        }
                        }

                    $other_image_fields_map = ['image2', 'image3', 'image4', 'image5'];
                    $current_other_idx = 0;
                    foreach ($images_for_other_slots_b64 as $b64) {
                        if (empty($b64)) continue;
                        if ($current_other_idx >= count($other_image_fields_map)) break;

                        $url = null; $img_data_h=null; $img_type_h='jpg'; if(preg_match('/^data:image\/(\w+);base64,(.*)$/s',$b64,$m)){$img_type_h=strtolower($m[1]);$img_data_h=base64_decode($m[2],true);}else{$img_data_h=base64_decode($b64,true);} if($img_data_h && file_exists($upload_dir)){$fn_h=uniqid().'.'.$img_type_h;$fp_h=$upload_dir.$fn_h;if(file_put_contents($fp_h,$img_data_h)){$s_h=base_url();$r_h=str_replace('./','',$fp_h);$url=rtrim($s_h,'/').'/'.$r_h;}}
                        if ($url) {
                            $final_image_db_updates[$other_image_fields_map[$current_other_idx]] = $url;
                            $current_other_idx++;
                        }
                    }
                    
                    if (!empty($final_image_db_updates)) {
                        $this->db->where('id', $product_id);
                        $this->db->update(db_prefix() . 'items', $final_image_db_updates);
                    }
                }

                $success_count++;
                $created_product = $this->get_item($product_id);
                
                if (!empty($packaging_data)) {
                    $this->db->where('product_id', $product_id);
                    $packaging_result = $this->db->get(db_prefix() . 'product_packaging')->result_array();
                    $created_product->packaging = $packaging_result;
                }
                
                $created_products[] = $created_product;
            }

            if ($success_count > 0) {
                $this->db->trans_commit();
                
                $message = $edit_product_id ? 
                    $success_count . ' products updated successfully' : 
                    $success_count . ' products created successfully';
                
                return [
                    'status' => true,
                    'message' => $message,
                    'created' => $success_count,
                    'failed' => count($failed_products),
                    'failed_products' => $failed_products,
                    'data' => $created_products
                ];
            } else {
                $this->db->trans_rollback();
                $message = $edit_product_id ? 
                    'Failed to update any products' : 
                    'Failed to create any products';
                
                return [
                    'status' => false,
                    'message' => $message,
                    'created' => 0,
                    'failed' => count($failed_products),
                    'failed_products' => $failed_products
                ];
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return [
                'status' => false,
                'message' => 'Error during bulk operation: ' . $e->getMessage(),
                'created' => $success_count,
                'failed' => count($products) - $success_count
            ];
        }
    }

    /**
     * Get NCM (Nomenclatura Comum do Mercosul) data with filtering and pagination
     * 
     * @param string $search Search term
     * @param int $page Page number
     * @param int $limit Items per page
     * @param string $category Category filter
     * @param string $sortField Field to sort by
     * @param string $sortOrder Sort direction (ASC or DESC)
     * @return array Data with NCM records, total count, and categories
     */
    public function get_ncm($search = '', $page = 1, $limit = 10, $category = '', $sortField = 'code', $sortOrder = 'ASC')
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'ncm');
        
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('code', $search);
            $this->db->or_like('description', $search);
            $this->db->or_like('category', $search);
            $this->db->or_like('subcategory', $search);
            $this->db->group_end();
        }
        
        if (!empty($category)) {
            $this->db->where('category', $category);
        }
        
        $total = $this->db->count_all_results('', false);
        
        $allowedSortFields = ['code', 'description', 'category', 'subcategory'];
        $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'code';
        $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
        
        $this->db->order_by($sortField, $sortOrder);
        $this->db->limit($limit, ($page - 1) * $limit);
        
        $ncms = $this->db->get()->result_array();
        
        $this->db->select('DISTINCT(category)');
        $this->db->from(db_prefix() . 'ncm');
        $this->db->order_by('category', 'ASC');
        $categories = $this->db->get()->result_array();
        $categories = array_column($categories, 'category');
        
        return [
            'total' => $total,
            'data' => $ncms,
            'categories' => $categories,
            'page' => $page,
            'pageSize' => $limit
        ];
    }
}
