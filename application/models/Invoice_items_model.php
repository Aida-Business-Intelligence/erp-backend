<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_items_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Copy invoice item
     * @param array $data Invoice item data
     * @return boolean
     */
    public function copy($_data)
    {
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

    /**
     * Get invoice item by ID
     * @param  mixed $id
     * @return mixed - array if not passed id, object if id passed
     */
    public function get($id = '')
    {
        $columns = $this->db->list_fields(db_prefix() . 'items');
        $rateCurrencyColumns = '';
        foreach ($columns as $column) {
            if (strpos($column, 'rate_currency_') !== false) {
                $rateCurrencyColumns .= $column . ',';
            }
        }
        $this->db->select($rateCurrencyColumns . '' . db_prefix() . 'items.id as itemid,rate,
            t1.taxrate as taxrate,t1.id as taxid,t1.name as taxname,
            t2.taxrate as taxrate_2,t2.id as taxid_2,t2.name as taxname_2,
            description,long_description,group_id,' . db_prefix() . 'items_groups.name as group_name,unit');

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

    public function get_item($id = '')
    {
        $this->db->select(db_prefix() . 'items.*, ' . db_prefix() . 'clients.company');
        $this->db->from(db_prefix() . 'items');
        $this->db->where(db_prefix() . 'items.id', $id);
        $this->db->join(db_prefix() . 'clients', 'clients.userid = ' . db_prefix() . 'items.userid', 'left');

        return $this->db->get()->row();
    }

    public function get_by_sku($id = '')
    {
        $this->db->from(db_prefix() . 'items');
        $this->db->where(db_prefix() . 'items.sku_code', $id);

        return $this->db->get()->row();
    }

    public function get_category_id_by_name($name)
    {

        $this->db->select('id ');
        $this->db->from(db_prefix() . 'items_groups');
        $this->db->where('name', $name);
        return $this->db->get()->row();

    }

    public function get_unit_id_by_name($name)
    {


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
            $this->db->group_start()
                ->like("$items_table.description", $search)
                ->or_like("$items_table.long_description", $search)
                ->or_like("$items_table.rate", $search)
                ->or_like("$items_table.sku_code", $search)
                ->or_like("$items_table.commodity_barcode", $search)
                ->or_like("$items_table.id", $search)
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

    public function totalItens($warehouse_id)
    {

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

    // public function get_api2(
    //     $id = '',
    //     $page = 1,
    //     $limit = 10,
    //     // $search = '',
    //     $sortField = 'id',
    //     $sortOrder = 'DESC',
    //     // $statusFilter = null,
    //     // $startDate = null,
    //     // $endDate = null,
    //     // $category = null,
    //     // $subcategory = null,
    //     // $send = null,
    //     $warehouse_id = null
    // ) {


    //     $items_table = db_prefix() . 'items';
    //     $groups_table = db_prefix() . 'items_groups';
    //     $subgroups_table = db_prefix() . 'wh_sub_group';



    //     if ($id != '') {
    //         $this->db->select([
    //             "$items_table.*",
    //             "$groups_table.name as group_name",
    //             "$subgroups_table.sub_group_name",
    //             "$subgroups_table.id as sub_group_id"
    //         ]);

    //         $this->db->from($items_table)
    //             ->join($groups_table, "$groups_table.id = $items_table.group_id", 'left')
    //             ->join($subgroups_table, "$subgroups_table.id = $items_table.sub_group", 'left');

    //         if ($warehouse_id) {
    //             $this->db->where("$items_table.warehouse_id", $warehouse_id);
    //         }

    //         if ($send == 'pdv') {
    //             $this->db->where("$items_table.id", $id);
    //             $this->db->or_where("$items_table.commodity_barcode", $id)->limit(1);
    //             $item = $this->db->get()->row();
    //         } else {

    //             $this->db->or_where("$items_table.commodity_barcode", $id);
    //             $item = $this->db->get()->row_array();
    //         }




    //         if ($item) {

    //             if ($send == 'pdv') {
    //                 return ['data' => $item, 'total' => 1];
    //             } else {
    //                 return ['data' => [$item], 'total' => 1];
    //             }

    //         }
    //         return ['data' => [], 'total' => 0];
    //     }

    //     $this->db->select([
    //         "$items_table.id as id",
    //         "$items_table.rate",
    //         't1.taxrate as taxrate',
    //         't1.id as taxid',
    //         't1.name as taxname',
    //         't2.taxrate as taxrate_2',
    //         't2.id as taxid_2',
    //         't2.name as taxname_2',
    //         "$items_table.description",
    //         "$items_table.long_description",
    //         "$items_table.group_id",
    //         "$groups_table.name as group_name",
    //         "$items_table.unit",
    //         "$items_table.sku_code",
    //         "$items_table.image",
    //         "$items_table.commodity_barcode",
    //         "$items_table.status",
    //         "$items_table.cost",
    //         "$items_table.promoPrice",
    //         "$items_table.promoStart",
    //         "$items_table.promoEnd",
    //         "$items_table.stock",
    //         "$items_table.minStock",
    //         "$items_table.product_unit",
    //         "$items_table.createdAt",
    //         "$items_table.updatedAt",
    //         "$subgroups_table.sub_group_name",
    //         "$subgroups_table.id as sub_group_id",
    //         "$items_table.warehouse_id as warehouse_id"
    //     ]);

    //     $this->db->from($items_table)
    //         ->join(db_prefix() . 'taxes t1', "t1.id = $items_table.tax", 'left')
    //         ->join(db_prefix() . 'taxes t2', "t2.id = $items_table.tax2", 'left')
    //         ->join($groups_table, "$groups_table.id = $items_table.group_id", 'left')
    //         ->join($subgroups_table, "$subgroups_table.id = $items_table.sub_group", 'left');

    //     if ($warehouse_id) {
    //         $this->db->where("$items_table.warehouse_id", $warehouse_id);
    //     }

    //     if (!empty($statusFilter) && is_array($statusFilter)) {
    //         $this->db->where_in("$items_table.status", $statusFilter);
    //     }

    //     if (!empty($startDate)) {
    //         $this->db->where("DATE($items_table.createdAt) >=", (new DateTime($startDate))->format('Y-m-d'));
    //     }
    //     if (!empty($endDate)) {
    //         $this->db->where("DATE($items_table.createdAt) <=", (new DateTime($endDate))->format('Y-m-d'));
    //     }

    //     if (!empty($category)) {
    //         $this->db->where("$items_table.group_id", $category);
    //     }

    //     if (!empty($subcategory)) {
    //         $this->db->where("$items_table.sub_group", $subcategory);
    //     }

    //     if (!empty($search)) {
    //         $this->db->group_start()
    //             ->like("$items_table.description", $search)
    //             ->or_like("$items_table.long_description", $search)
    //             ->or_like("$items_table.rate", $search)
    //             ->or_like("$items_table.sku_code", $search)
    //             ->or_like("$items_table.commodity_barcode", $search)
    //             ->or_like("$items_table.id", $search)
    //             ->group_end();
    //     }

    //     $total = $this->db->count_all_results('', false);

    //     $allowedSortFields = ['id', 'description', 'rate', 'sku_code', 'createdAt', 'updatedAt'];
    //     $sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'id';

    //     $this->db->order_by("$items_table.$sortField", $sortOrder);
    //     $this->db->limit($limit, ($page - 1) * $limit);

    //     $items = $this->db->get()->result_array();

    //     return ['data' => $items, 'total' => $total];
    // }

    public function get_grouped()
    {
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
    public function add($data)
    {
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
    public function edit($data, $id)
    {
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
    public function edit_by_sku($data, $id, $warehouse_id)
    {
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

    public function search($q)
    {
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
    public function delete($id)
    {
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

    public function delete_by_sku($sku)
    {
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

    public function get_groups($page = 1, $limit = 10, $search = '', $sortOrder = 'ASC')
    {
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

    public function add_group($data)
    {
        $this->db->insert(db_prefix() . 'items_groups', $data);
        log_activity('Items Group Created [Name: ' . $data['name'] . ']');

        return $this->db->insert_id();
    }

    public function edit_group($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'items_groups', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Items Group Updated [Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function delete_group($id)
    {
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

    public function add_subgroup($data)
    {
        $this->db->insert(db_prefix() . 'wh_sub_group', $data);
        log_activity('Sub Group Created [Name: ' . $data['sub_group_name'] . ']');
        return $this->db->insert_id();
    }

    public function edit_subgroup($data, $id)
    {
        $this->db->where('id', $id);
        return $this->db->update(db_prefix() . 'wh_sub_group', $data);
    }

    public function delete_subgroup($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'wh_sub_group');
    }
}
