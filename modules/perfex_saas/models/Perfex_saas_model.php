<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Perfex_saas_model extends App_Model
{
    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get data from a table.
     *
     * @param string $table The name of the table.
     * @param string $id The ID of the data to retrieve. If empty, retrieve all data.
     * @return mixed The retrieved data.
     */
    function get($table, $id = '')
    {
        $this->db->select();
        $this->db->from($table);
        $this->db->order_by('id', 'DESC');

        if (!empty($id)) {
            $this->db->where('id', $id);
        }

        $query = $this->db->get();

        return empty($id) ? $query->result() : $query->row();
    }

    /**
     * Get an entity by slug.
     *
     * @param string $entity The entity name.
     * @param string $slug The slug of the entity.
     * @param string $parse_method The slef method to use for parsing the entity.
     * @return mixed The retrieved entity.
     */
    function get_entity_by_slug($entity, $slug, $parse_method = '')
    {
        $this->db->select();
        $this->db->from(perfex_saas_table($entity));
        $this->db->where('slug', $slug);

        $row = $this->db->get()->row();

        if (!empty($parse_method) && !empty($row)) {
            $row = $this->{$parse_method}($row);
        }

        return $row;
    }

    /**
     * Add or update an entity.
     *
     * @param string $entity The entity name.
     * @param array $data The data to add or update.
     * @return int|bool The ID of the added or updated entity, or false on failure.
     */
    public function add_or_update(string $entity, array $data)
    {
        return $this->add_or_update_raw(perfex_saas_table($entity), $data);
    }

    /**
     * Add or update an entity using raw table name.
     *
     * @param string $table The name of the table.
     * @param array $data The data to add or update.
     * @return int|bool The ID of the added or updated entity, or false on failure.
     */
    public function add_or_update_raw(string $table, array $data)
    {
        $id = false;

        if (isset($data['id']) && !empty($data['id'])) {
            $this->db->where('id', $data['id']);
            if ($this->db->update($table, $data)) {
                $id = $data['id'];
            }
        } else {
            $this->db->insert($table, $data);
            $id = $this->db->insert_id();
        }

        return $id;
    }

    /**
     * Delete an entity by ID.
     *
     * @param string $entity The entity name.
     * @param mixed $id The ID of the entity to delete.
     * @return bool True on success, false on failure.
     */
    public function delete(string $entity, $id)
    {
        $this->db->where('id', $id);
        return $this->db->delete(perfex_saas_table($entity));
    }

    /**
     * Clone an entity by ID.
     *
     * @param string $entity The entity name.
     * @param mixed $id The ID of the entity to clone.
     * @return int|bool The ID of the cloned entity, or false on failure.
     */
    public function clone(string $entity, $id)
    {
        $table = perfex_saas_table($entity);

        $entity_data = $this->get($table, $id);
        if (!$entity_data) {
            return false;
        }

        $total = count($this->get($table));

        if (isset($entity_data->name)) {
            $entity_data->name = $entity_data->name . '#' . $total + 1;
        }

        if (isset($entity_data->slug)) {
            $entity_data->slug = slug_it($entity_data->name);
        }

        if (isset($entity_data->is_default)) {
            $entity_data->is_default = 0;
        }

        unset($entity_data->id);

        return $this->add_or_update($entity, (array)$entity_data);
    }

    /**
     * Check if the database user has create privilege
     * @return bool
     */
    public function db_user_has_create_privilege()
    {
        try {
            $db = perfex_saas_db('testdb');
            if ($this->db->query('CREATE DATABASE ' . $db)) {
                if (!$this->db->query('DROP DATABASE `' . $db . '`'))
                    throw new \Exception("Error dropping test db $db", 1);
            } else {
                throw new \Exception("Error creating database", 1);
            }
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Database user dont have permission to create new db:' . $e->getMessage());
        }
        return false;
    }

    /**
     * Create a database
     * @param string $db The name of the database to create
     * @return bool|string True on success, error message on failure
     */
    public function create_database($db)
    {
        try {
            if (!$this->db->query("CREATE DATABASE IF NOT EXISTS `$db`")) {
                throw new \Exception("Error creating database $db", 1);
            }
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Database user dont have permission to create new db:' . $e->getMessage());
            return $e->getMessage();
        }
        return false;
    }

    /**
     * Get database pools population by package ID
     * @param int $package_id The ID of the package
     * @return array Array containing population map and pools map
     */
    public function get_db_pools_population_by_packgeid($package_id)
    {
        $packages = $this->packages($package_id);
        $packages = !empty($package_id) ? [$packages] : $packages;

        $query = 'SELECT COUNT(DISTINCT(' . PERFEX_SAAS_TENANT_COLUMN . ')) as total FROM `' . db_prefix() . 'migrations`';
        $population_map = [];
        $pools_map = [];

        foreach ($packages as $p) {
            $pools = $p->db_pools;

            if ($pools) {
                foreach ($pools as $pool) {
                    $pool = (array)$pool;
                    $key = perfex_saas_dsn_to_string($pool, false);

                    if (!isset($pools_map[$key])) {
                        $query = perfex_saas_raw_query_row($query, $pool, true);
                        $population = $query->total ?? 0;
                        $population_map[$key] = (int)$population;
                        $pools_map[$key] = $pool;
                    }
                }
            }
        }

        return [$population_map, $pools_map];
    }

    /**
     * Get database pools population
     * @param array $pools Array of database pools
     * @return array Array containing population map and pools map
     */
    public function get_db_pools_population($pools)
    {
        $query = 'SELECT COUNT(DISTINCT(' . PERFEX_SAAS_TENANT_COLUMN . ')) as total FROM `' . db_prefix() . 'staff`';
        $population_map = [];
        $pools_map = [];

        foreach ($pools as $pool) {
            $pool = (array)$pool;
            $key = perfex_saas_dsn_to_string($pool, false);

            if (!isset($pools_map[$key])) {

                try {
                    $resp = perfex_saas_raw_query_row($query, $pool, true);
                } catch (\Throwable $th) {
                    if (stripos($th->getMessage(), 'table or view not found')) //table not set on the db
                        $resp = (object)['total' => 0];
                    else
                        throw $th;
                }
                $population = $resp->total ?? 0;
                $population_map[$key] = (int)$population;
                $pools_map[$key] = $pool;
            }
        }
        return [$population_map, $pools_map];
    }


    /**
     * Get the list of database schemes for tenant management.
     *
     * @return array The array of database schemes.
     */
    public function db_schemes()
    {
        // Define an array of database schemes for tenant management
        $schemes = [
            ['key' => 'multitenancy', 'label' => _l('perfex_saas_use_the_current_active_database_for_all_tenants')], // Option for using the current active database for all tenants
            ['key' => 'single', 'label' => _l('perfex_saas_use_single_database_for_each_company_instance.')], // Option for using a single database for each company instance
            ['key' => 'single_pool', 'label' => _l('perfex_saas_single_pool_db_scheme')], // Option for using a single pool database scheme
            ['key' => 'shard', 'label' => _l('perfex_saas_distribute_companies_data_among_the_provided_databases_in_the_pool')], // Option for distributing companies' data among provided databases in the pool
        ];

        // Check if the current database user has the privilege to create a new database
        if (!$this->db_user_has_create_privilege()) {
            unset($schemes[1]); // Remove the option for single database per company if the privilege is not available
        }

        return $schemes;
    }

    /**
     * Get the alternative list of database schemes for tenant management.
     *
     * @return array The array of alternative database schemes.
     */
    public function db_schemes_alt()
    {
        // Define an array of alternative database schemes for tenant management
        $schemes = [
            ['key' => 'package', 'label' => _l('perfex_saas_auto_detect_from_client_subcribed_package')], // Option for auto-detecting database scheme from client subscribed package
            ['key' => 'multitenancy', 'label' => _l('perfex_saas_use_the_current_active_database')], // Option for using the current active database
            ['key' => 'single', 'label' => _l('perfex_saas_create_a_separate_database')], // Option for creating a separate database
            ['key' => 'shard', 'label' => _l('perfex_saas_i_will_provide_database_credential')], // Option for providing database credentials
        ];

        // Check if the current database user has the privilege to create a new database
        if (!$this->db_user_has_create_privilege()) {
            unset($schemes[1]); // Remove the option for using the current active database if the privilege is not available
        }

        return $schemes;
    }

    /**
     * Get the list of company status options.
     *
     * @return array The array of company status options.
     */
    public function company_status_list()
    {
        // Return an array of company status options
        return [
            ['key' => 'active', 'label' => _l('perfex_saas_active')], // Option for active company
            ['key' => 'inactive', 'label' => _l('perfex_saas_inactive')], // Option for inactive company
            ['key' => 'banned', 'label' => _l('perfex_saas_banned')] // Option for banned company
        ];
    }

    /**
     * Get the list of yes/no options.
     *
     * @return array The array of yes/no options.
     */
    public function yes_no_options()
    {
        // Return an array of yes/no options
        return [
            ['key' => 'no', 'label' => _l('perfex_saas_no')], // Option for "No"
            ['key' => 'yes', 'label' => _l('perfex_saas_yes')] // Option for "Yes"
        ];
    }

    /**
     * Get the shared options from the database.
     *
     * @return array The array of shared options.
     */
    public function shared_options()
    {
        // Retrieve the options from the database
        $this->db->select("name as key, REPLACE(name,'_',' ') as name");
        $results = $this->db->get(db_prefix() . 'options')->result();
        return $results;
    }


    /**
     * Get the list of modules installed on perfex.
     *
     * @param bool $exclude_self Flag to exclude the perfex saas module.
     * @return array The array of modules.
     */
    public function modules($exclude_self = true)
    {
        // Get the list of modules
        $modules = $this->app_modules->get();

        // Retrieve the custom module names from the options
        $custom_modules_name = get_option('perfex_saas_custom_modules_name');
        $custom_modules_name = empty($custom_modules_name) ? [] : json_decode($custom_modules_name, true);

        foreach ($modules as $key => $value) {
            // Check if the module is the self module and exclude it if necessary
            if ($value['system_name'] == PERFEX_SAAS_MODULE_NAME && $exclude_self) {
                unset($modules[$key]);
                continue;
            }

            // Assign the custom name to the module if available, otherwise use the default module name
            $modules[$key]['custom_name'] = isset($custom_modules_name[$value['system_name']]) ? $custom_modules_name[$value['system_name']] : $modules[$key]['headers']['module_name'];
        }

        return $modules;
    }

    /**
     * Get the custom name of a module.
     *
     * @param string $module_system_name The system name of the module.
     * @return string The custom name of the module.
     */
    public function get_module_custom_name($module_system_name)
    {
        // Retrieve the custom module names from the options
        $custom_modules_name = get_option('perfex_saas_custom_modules_name');
        $custom_modules_name = empty($custom_modules_name) ? [] : json_decode($custom_modules_name, true);

        // Return the custom name if available, otherwise return the module system name
        return isset($custom_modules_name[$module_system_name]) ? $custom_modules_name[$module_system_name] : $module_system_name;
    }

    /**
     * Mark a package as default.
     *
     * @param int $package_id The ID of the package to mark as default.
     * @return bool True on success, false on failure.
     */
    public function mark_package_as_default($package_id)
    {
        $table = perfex_saas_table('packages');
        $this->db->update($table, ['is_default' => 0]);

        $this->db->where('id', $package_id);
        return $this->db->update($table, ['is_default' => 1]);
    }

    /**
     * Get all packages or single package by id.
     *
     * @param mixed $id The ID of the package to retrieve. If empty, retrieve all packages.
     * @return array|object The retrieved packages.
     */
    function packages($id = '')
    {
        $packages = $this->get(perfex_saas_table('packages'), $id);

        if (!empty($id) && !empty($packages)) {
            $packages = [$packages];
        }

        foreach ($packages as $key => $package) {
            $packages[$key] = $this->parse_package($package);
        }

        return !empty($id) ? $packages[0] : $packages;
    }

    /**
     * Get all companies or single company speicifc by id.
     *
     * @param mixed $id The ID of the company to retrieve. If empty, retrieve all companies.
     * @return array|object The retrieved companies.
     */
    public function companies($id = '')
    {
        if (is_client_logged_in()) {
            $this->db->where('clientid', get_client_user_id());
        }

        $companies = $this->get(perfex_saas_table('companies'), $id);
        if (!empty($id)) {
            $companies = [$companies];
        }

        foreach ($companies as $key => $company) {
            $companies[$key] = $this->parse_company($company);
        }

        return !empty($id) ? $companies[0] : $companies;
    }



    /**
     * Parse a package object.
     *
     * @param object $package The package object to parse.
     * @return object The parsed package object.
     */
    public function parse_package(object $package)
    {
        if (isset($package->metadata)) {
            $package->metadata = (object)json_decode($package->metadata);
        }
        if (isset($package->db_pools)) {
            $package->db_pools = (array)json_decode($this->encryption->decrypt($package->db_pools));
        }
        if (isset($package->modules)) {
            $package->modules = (array)json_decode($package->modules);
        }

        return $package;
    }

    /**
     * Parse a company object.
     *
     * @param object $company The company object to parse.
     * @return object The parsed company object.
     */
    public function parse_company(object $company)
    {
        if (isset($company->metadata)) {
            $company->metadata = (object)json_decode($company->metadata);
        }

        if (isset($company->dsn)) {
            $company->dsn = $this->encryption->decrypt($company->dsn);
        }

        return $company;
    }

    /**
     * Create or update a company.
     *
     * @param mixed $data The company data.
     * @param mixed $invoice The invoice data.
     * @return mixed The ID of the created or updated company.
     * @throws \Exception When the company payload is malformed or certain conditions are not met.
     */
    public function create_or_update_company($data, $invoice)
    {

        $company = null;
        if (!empty($data['id'])) {
            $company = $this->companies($data['id']);
        }

        $creating_new = empty($company->id);

        if ($creating_new || empty($data['id'])) {
            if (empty($data['clientid']) || empty($data['name'])) {
                throw new \Exception(_l('perfex_saas_malformed_company_payload'), 1);
            }
        }


        $creating_as_admin = !is_client_logged_in() && (has_permission('perfex_saas_companies', '', 'create') && has_permission('perfex_saas_companies', '', 'edit'));

        $data['metadata'] = isset($data['metadata']) ? (array)$data['metadata'] : [];


        // Handle custom domain - updating or create
        $custom_domain = $data['custom_domain'] ?? '';
        if (!empty($custom_domain)) {
            if (!filter_var($custom_domain, FILTER_VALIDATE_DOMAIN)) {
                $custom_domain = parse_url($custom_domain, PHP_URL_HOST);
                if (!empty($custom_domain))
                    throw new Exception(_l('perfex_saas_invalid_custom_domain', $custom_domain));
            }

            $autoapprove = (int)($invoice->metadata->autoapprove_custom_domain ?? 0);
            if (!$creating_as_admin && !$autoapprove) { // Make pending
                $data['metadata']['pending_custom_domain'] = $custom_domain;
                unset($data['custom_domain']);
            }
        }


        // Create actions
        if ($creating_new) {

            // Check limit for the owner
            $max = (int)(isset($invoice->metadata->max_instance_limit) ? $invoice->metadata->max_instance_limit : 1);
            $this->db->where('clientid', $data['clientid']);
            $count = count($this->companies());
            if ($max > 0 && $count >= $max) {
                throw new \Exception(_l('perfex_saas_max_instance_reached' . ($creating_as_admin ? '_admin' : ''), $max), 1);
            }

            // Handle slug
            $slug = isset($data['slug']) && !empty($data['slug']) ? $data['slug'] : explode(' ', $data['name'])[0];
            $data['slug'] = perfex_saas_generate_unique_slug($slug, 'companies', $data['id'] ?? '');
            if (strlen($data['slug']) > 50)
                throw new \Exception("Invalid slug: slug should not be more than 50 characters.", 1);


            // Set default to empty for client and leave for admin.
            $data['dsn'] = $creating_as_admin ? $data['dsn'] : '';

            // Determine the dsn if none is provided so far
            if (empty($data['dsn'])) {

                // If invoice is single db, set the dbname. This prevents saving the master db credential to the database.
                if ($invoice->db_scheme == 'single') {

                    if (!$this->db_user_has_create_privilege()) {
                        throw new \Exception(_l('perfex_saas_db_scheme_single_not_supported'), 1);
                    }

                    $dbname = perfex_saas_db($data['slug']);
                    $create_db = $this->create_database($dbname);
                    if ($create_db !== true) {
                        throw new \Exception(_l('Error creating database: ' . $create_db), 1);
                    }

                    $data['dsn'] = perfex_saas_dsn_to_string([
                        'dbname' => $dbname
                    ]);
                }

                if ($invoice->db_scheme == 'multitenancy') {
                    $data['dsn'] = perfex_saas_dsn_to_string([
                        'dbname' => APP_DB_NAME,
                    ]);
                }

                if ($invoice->db_scheme == 'single_pool' || $invoice->db_scheme == 'shard') {

                    $dsn = perfex_saas_get_company_dsn($company, $invoice);
                    if (!perfex_saas_is_valid_dsn($dsn, true)) {
                        throw new \Exception(_l('perfex_saas_invalid_datacenter'), 1);
                    }

                    $data['dsn'] = perfex_saas_dsn_to_string($dsn);
                }
            }

            if (isset($data['dsn']) && !is_string($data['dsn'])) {
                throw new \Exception("DSN must be provided in string format", 1);
            }


            if (empty($company->id)) { // Create
                // Make pending by default. Only pending will be picked up by deployer.
                $data['status'] = 'pending';
            }

            // Seed from file
            if (!empty($_FILES['sql_file']['name'])) {

                if (empty($data['dsn']) || !in_array($invoice->db_scheme, ['single_pool', 'single'])) {
                    throw new \Exception(_l('perfex_saas_sql_import_not_supported'));
                }

                // Will throw if missing requested key
                $dbname = perfex_saas_parse_dsn($data['dsn'], ['dbname'])['dbname'];

                $allowed = $creating_as_admin;

                if (isset($invoice->metadata->allow_create_from_dump) && $invoice->metadata->allow_create_from_dump == 'yes') {
                    $allowed = true;
                }

                if (!$allowed) {
                    throw new \Exception(_l('perfex_saas_create_from_dump_not_supported_on_your_subscription'), 1);
                }

                $sql_file_path = perfex_saas_save_company_dump_seed_file($dbname, 'sql_file');
                $data['metadata']['sql_file'] = $sql_file_path;
            }

            // Make pending by default. Only pending will be picked up by deployer.
            $data['status'] = 'pending';
        }

        // Updating
        if (!$creating_new) {
            if (!$creating_as_admin) {
                unset($data['status']);
            }

            // Ensure slug is not updated
            if (isset($data['slug'])) {
                unset($data['slug']);
            }
        }

        if (isset($data['sql_file'])) {
            unset($data['sql_file']);
        }

        // Admin options
        if (isset($data['db_scheme'])) {
            unset($data['db_scheme']);
        }

        if (isset($data['db_pools'])) {
            unset($data['db_pools']);
        }

        // Encrypt any DSN info to be saved to the DB
        if (empty($company->id) && isset($data['dsn']) && !empty($data['dsn'])) {
            $data['dsn'] = $this->encryption->encrypt($data['dsn']);
        }

        $old_metadata = (array)(isset($company->metadata) ? $company->metadata : []);
        if (isset($data['metadata'])) {
            $data['metadata'] = json_encode(array_merge($old_metadata, $data['metadata']));
        }

        // Save and make deployment another job
        $_id = $this->add_or_update('companies', $data);
        return $_id;
    }


    /**
     * Generate a client invoice.
     *
     * @param mixed $clientid The client ID.
     * @param mixed $packageid The package ID.
     * @return mixed The generated company invoice.
     * @throws \Exception When certain conditions are not met.
     */
    public function generate_company_invoice($clientid, $packageid)
    {
        $package = $this->packages($packageid);

        $metadata = $package->metadata;
        $old_invoice = $this->get_company_invoice($clientid);

        $date = date('Y-m-d');
        $duedate =  $date;

        if ($old_invoice) {

            // Ensure user pays for the active subscription before upgrading.
            if ($old_invoice->status == Invoices_model::STATUS_OVERDUE) {
                throw new \Exception(_l('perfex_saas_clear_overdue_invoice_note'), 1);
            }

            $date = !empty($old_invoice->date) ? $old_invoice->date : $date;
            $duedate = !empty($old_invoice->duedate) ? $old_invoice->duedate : $duedate;
        } else if ($package->trial_period > 0) {
            $duedate = date('Y-m-d', strtotime("+$package->trial_period days"));
        }

        $next_invoice_number = get_option('next_invoice_number');
        $invoice_number      = str_pad($next_invoice_number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);

        // Payments options
        $payment_modes = [];
        $all_payment_modes = $this->payment_modes_model->get();
        foreach ($all_payment_modes as $pmode) {
            $payment_modes[] = $pmode['id'];
        }

        $data = [
            "clientid" => $clientid,
            "number" => $invoice_number,
            "date" => $date,
            "duedate" => $duedate,
            "tags" => PERFEX_SAAS_FILTER_TAG,
            "allowed_payment_modes" => $payment_modes,
            "currency" => get_base_currency()->id,
            "sale_agent" => $metadata->invoice->sale_agent ?? "",
            "recurring" => $metadata->invoice->recurring ?? "1",
            "repeat_every_custom" => $metadata->invoice->repeat_every_custom ?? "",
            "repeat_type_custom" => $metadata->invoice->repeat_type_custom ?? "",
            "show_quantity_as" => "1",
            "newitems" => [
                "2" => [
                    "order" => "1",
                    "description" => _l('perfex_saas_invoice_desc_subscription', $package->name),
                    "long_description" => "",
                    "qty" => "1",
                    "unit" => "",
                    "rate" => $package->price
                ]
            ],
            "subtotal" => $package->price,
            "discount_percent" => "0",
            "discount_total" => "0.00",
            "adjustment" => "0",
            "total" => $package->price,
            "billing_street" => "",
        ];

        // Important
        $data[perfex_saas_column('packageid')] = $packageid;

        // Remove old items
        if ($old_invoice) {
            $items = get_items_by_type('invoice', $old_invoice->id);
            $items_id = [];
            foreach ($items as $item) {
                $items_id[] = $item['id'];
            }
            $data['removed_items'] = $items_id;
        }

        if (!$this->add_company_invoice($clientid, $data)) {
            throw new Exception($this->db->error()->message, 1);
        }

        $invoice = $this->get_company_invoice($clientid);

        return $invoice;
    }

    /**
     * Get a company by its slug.
     *
     * @param string $slug The company slug.
     * @param string $clientid The client ID.
     * @return mixed The company with the given slug.
     */
    public function get_company_by_slug($slug, $clientid = '')
    {
        if ($clientid) {
            $this->db->where('clientid', $clientid);
        }
        return $this->get_entity_by_slug('companies', $slug, 'parse_company');
    }

    /**
     * Get a company invoice.
     *
     * @param mixed $clientid The client ID.
     * @return mixed The company invoice.
     */
    public function get_company_invoice($clientid)
    {
        $packageTable = perfex_saas_table('packages');
        $invoiceTable = db_prefix() . 'invoices';

        $this->db->where('recurring >', '0'); // Must be recuring
        $this->db->where(perfex_saas_column('packageid') . ' IS NOT NULL'); //must have packageid
        $this->db->where('clientid', $clientid);
        $this->db->select("$invoiceTable.*, clientid, name, description, slug, price, bill_interval, is_default, is_private, db_scheme, db_pools, $packageTable.status as package_status, modules, metadata, trial_period");
        $this->db->join($packageTable, $packageTable . '.id = ' . perfex_saas_column('packageid'), 'inner');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . $invoiceTable . '.clientid', 'inner');
        $this->db->order_by($invoiceTable . '.datecreated', 'DESC');
        $invoice = $this->db->from($invoiceTable)->get()->row();
        if (!$invoice) return $invoice;
        return $this->parse_package($invoice);
    }

    /**
     * Add or update a company invoice.
     *
     * @param int $clientid The client ID for which the invoice is being added or updated.
     * @param array $data The data for the invoice.
     * @return mixed The result of the add or update operation.
     */
    public function add_company_invoice($clientid, $data)
    {
        // Retrieve the existing invoice for the client
        $invoice = $this->get_company_invoice($clientid);

        if ($invoice) {
            // If an invoice exists, update it with the new data
            $data['id'] = $invoice->id;
            return $this->invoices_model->update($data, $invoice->id);
        } else {
            // If no invoice exists, add a new invoice with the provided data
            return $this->invoices_model->add($data);
        }
    }
}
