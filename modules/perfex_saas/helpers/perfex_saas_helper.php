<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get the base URL for a tenant or instance.
 *
 * This function constructs the base URL for a tenant/ It uses the `perfex_saas_tenant_url_signature()` function
 * to generate the URL signature or use subdomain or custom domain base on the method passed.
 *
 * @param object $tenant     The tenant object.
 * @param string $endpoint Optional. The endpoint to append to the base URL. Default is an empty string.
 * @param string $method Optional. The type of url needed. 'path' to use req_uri scheme, 
 * 'auto' to autodetect base on settings and custom domain and all to get all possible addresses
 *
 * @return string|array The base URL for the tenant or array of all possible path when method === 'all'
 */
function perfex_saas_tenant_base_url($tenant, $endpoint = '', $method = 'auto')
{
    $slug = $tenant->slug;
    $default_url = base_url(perfex_saas_tenant_url_signature($slug) . "/$endpoint");
    $subdomain = "";
    $custom_domain = "";

    if ($method == 'path') {
        return $default_url;
    }

    $CI = &get_instance();
    $package = $CI->perfex_saas_model->get_company_invoice($tenant->clientid);
    $can_use_custom_domain = $package->metadata->enable_custom_domain ?? false;

    // If has custom domain, and available for use
    if (!empty($tenant->custom_domain) && $can_use_custom_domain) {
        $custom_domain =  perfex_saas_prep_url($tenant->custom_domain . '/' . $endpoint);
        if ($method === 'auto') return $custom_domain;
    }

    // If subdomain is enabled on package, use subdomain
    $can_use_subdomain = $package->metadata->enable_subdomain ?? false;
    if ($can_use_subdomain) {
        $subdomain = perfex_saas_prep_url($slug . '.' . perfex_saas_get_saas_default_host() . '/' . $endpoint);
        if ($method === 'auto') return $subdomain;
    }

    if ($method === 'all') return [
        'path' => $default_url,
        'custom_domain' => $custom_domain,
        'subdomain' => $subdomain
    ];

    return $default_url;
}

/**
 * Get the admin URL for a tenant or instance.
 *
 * This function constructs the admin URL for a tenant by appending It uses the `perfex_saas_tenant_base_url()` function
 * to generate the base URL for the tenant.
 *
 * @param object $tenant     The  tenant object
 * @param string $endpoint Optional. The endpoint to append to the admin URL. Default is an empty string.
 * @param string $method Optional. The type of url needed. 'path' to use req_uri scheme, 'auto' to autodetect base on settings and custom domain. 
 * @return string The admin URL for the tenant.
 */
function perfex_saas_tenant_admin_url($tenant, $endpoint = '', $method = 'auto')
{
    return perfex_saas_tenant_base_url($tenant, "admin/$endpoint", $method);
}

/**
 * Custom CI Prep URL
 *
 * Simply adds the https:// part if running on https
 *
 * @param	string	the URL
 * @return	string
 */
function perfex_saas_prep_url($str = '')
{
    $url = prep_url($str);

    if (str_starts_with($url, 'http://') && is_https())
        $url = str_ireplace('http://', 'https://', $url);

    return $url;
}

/**
 * Generate a unique slug.
 *
 * This function generates a unique slug based on the provided string. It ensures that the slug
 * is not already used in the specified table and is not in the reserved list of slugs. If the
 * generated slug is not unique or is reserved, it appends a random number and recursively calls
 * itself to generate a new slug until a unique one is found.
 *
 * @param string $str    The string to generate the slug from.
 * @param string $table  The table name to check for existing slugs.
 * @param string $id     Optional. The ID of the record to exclude from the check. Default is an empty string.
 *
 * @return string The generated unique slug.
 */
function perfex_saas_generate_unique_slug(string $str, string $table, string $id = '')
{
    $str = strtolower($str);
    $reserved_list = explode(',', strtolower(get_option('perfex_saas_reserved_slugs')));
    $reserved_list = array_merge([perfex_saas_master_tenant_slug(), 'app', 'main', 'www', 'ww3', 'mail', 'cname', 'web', 'admin', 'customer', 'base', 'contact'], $reserved_list);

    $CI = &get_instance();
    if ($id != '') {
        $CI->db->where('id !=', $id);
    }

    // Ensure uniqueness
    if ($CI->db->where('slug', $str)->get(perfex_saas_table($table), 1)->num_rows() > 0 || in_array($str, $reserved_list)) {
        return perfex_saas_generate_unique_slug($str . random_int(10, 999), $table, $id);
    }

    return slug_it($str);
}

/**
 * Get the DSN (Database Source Name) for a company.
 *
 * This function retrieves the DSN for a company, which is used to establish a database connection.
 * It checks if the company already has a DSN assigned. If not, it checks the company's invoice and
 * package details to determine the appropriate DSN. The function handles different database
 * deployment schemes, such as multitenancy, single database per company, and sharding.
 *
 * @param object|null $company The company object for which to get the DSN. Can be null.
 * @param object|null $invoice The invoice object associated with the company. Can be null.
 *
 * @return array The DSN details as an associative array.
 *
 * @throws \Exception When a valid data center cannot be found.
 */
function perfex_saas_get_company_dsn($company = null, $invoice = null)
{
    $default_dsn = perfex_saas_master_dsn();
    $CI = &get_instance();

    if (!empty($company->dsn)) {
        $dsn = perfex_saas_parse_dsn($company->dsn);

        if (perfex_saas_is_valid_dsn($dsn) === true) {
            return $dsn;
        }

        if (isset($dsn['dbname']) && $dsn['dbname'] == APP_DB_NAME) {
            return $default_dsn;
        }

        if (isset($dsn['dbname']) && $dsn['dbname'] == perfex_saas_db($company->slug)) {
            $default_dsn['dbname'] = $dsn['dbname'];
            return $default_dsn;
        }
    }

    if (empty($company->dsn) && !empty($invoice)) {
        $invoice = is_null($invoice) ? $CI->perfex_saas_model->get_company_invoice($company->clientid) : $invoice;

        if (isset($invoice->db_scheme) && !empty($invoice->db_scheme)) {
            $db_scheme = $invoice->db_scheme;

            if ($db_scheme == 'multitenancy') {
                return $default_dsn;
            }

            if ($db_scheme == 'single') {
                $default_dsn['dbname'] = perfex_saas_db($company->slug);
                return $default_dsn;
            }

            $packageid = $invoice->{perfex_saas_column('packageid')};

            list($populations, $pools) = !empty($invoice->db_pools) && !is_string($invoice->db_pools) ?  $CI->perfex_saas_model->get_db_pools_population((array)$invoice->db_pools) :
                $CI->perfex_saas_model->get_db_pools_population_by_packgeid($packageid);

            asort($populations);

            $selected_pool = [];
            if ($db_scheme == 'single_pool') {
                if (((int)array_values($populations)[0]) != 0) {
                    $admin = perfex_saas_get_super_admin();
                    $staffid = $admin->staffid;

                    // Notify the super admin about the database exhaustion.
                    if (add_notification([
                        'touserid' => $staffid,
                        'description' => 'perfex_saas_not_package_db_list_exhausted',
                        'link' => PERFEX_SAAS_MODULE_NAME . '/packages/edit/' . $packageid,
                        'additional_data' => serialize([$invoice->name])
                    ])) {
                        pusher_trigger_notification([$staffid]);
                    }
                } else {
                    $selected_pool = $pools[array_keys($populations)[0]];
                }
            }

            if ($db_scheme == 'shard') {
                $selected_pool = $pools[array_keys($populations)[0]];
            }

            if (!empty($selected_pool)) {
                $selected_pool['source'] = 'pool';
                return $selected_pool;
            }
        }
    }

    throw new \Exception(_l('perfex_saas_error_finding_valid_datacenter'), 1);
}

/**
 * Deploy companies.
 *
 * This function deploys companies by updating their status to 'inactive' and then attempting to deploy
 * each company using the `perfex_saas_deploy_company()` function. If the deployment is successful, the
 * company's status is updated to 'active'. If any errors occur during deployment, they are logged and
 * the company is removed and deleted from the database.
 *
 * @param string $company_id The ID of the company to deploy. Can be empty.
 * @param string $clientid The ID of the client associated with the company. Can be empty.
 * @param int $limit The maximum number of companies to deploy at a time.
 *
 * @return array An array containing the total number of companies, the errors encountered during deployment,
 *               and the total number of successfully deployed companies.
 */
function perfex_saas_deployer($company_id = '', $clientid = '', $limit = 5)
{
    $CI = &get_instance();
    $CI->db->where('status', 'pending');
    $CI->db->limit($limit);

    if (!empty($clientid)) {
        $CI->db->where('clientid', $clientid);
    }

    $pending_companies = $CI->perfex_saas_model->companies($company_id);

    if (!empty($company_id)) {
        $pending_companies = [$pending_companies];
    }

    $errors = [];
    $total_deployed = 0;

    foreach ($pending_companies as $company) {
        try {

            // Set to invactive
            $CI->perfex_saas_model->add_or_update('companies', ['id' => $company->id, 'status' => 'inactive']);

            // Attempt deploy
            $deploy = perfex_saas_deploy_company($company);

            if ($deploy !== true) throw new \Exception($deploy, 1);

            $CI->perfex_saas_model->add_or_update('companies', ['id' => $company->id, 'status' => 'active']);
            $total_deployed += 1;
        } catch (\Exception $e) {

            // Rollback deployment and creation of the instance
            $error = "$company->name deploy error: " . $e->getMessage();
            $errors[] = $error;
            log_message('error', $error);

            $dsn = perfex_saas_get_company_dsn($company);
            try {
                perfex_saas_remove_company($company);
            } catch (\Throwable $th) {
            }

            $CI->perfex_saas_model->delete('companies', $company->id);

            // Remove the dump file if provided
            try {
                if (!empty($company->metadata->sql_file)) {
                    unlink($company->metadata->sql_file);
                    perfex_saas_raw_query('DROP DATABASE IF EXISTS `' . $dsn['dbname'] . '`');
                }
            } catch (\Throwable $th) {
            }
        }
    }

    set_alert('danger', implode("\n", $errors));

    return ['total' => count($pending_companies), 'errors' => $errors, 'total_success' => $total_deployed];
}




/**
 * Deploy a company.
 *
 * This function is responsible for deploying a company by performing various steps
 * such as detecting the appropriate data center, validating the data center, importing
 * SQL seed file, setting up the data center, securing installation settings, registering
 * the first administrative user, and sending notifications.
 *
 * @param object $company   The company object containing information about the company to be deployed.
 * @return bool|string      Returns true if the deployment is successful, otherwise returns an error message.
 * @throws \Throwable       Throws an exception if there is an error during the deployment process.
 */
function perfex_saas_deploy_company($company)
{

    try {

        $CI = &get_instance();
        $invoice = $CI->perfex_saas_model->get_company_invoice($company->clientid);

        // Get data center
        perfex_saas_deploy_step(_l('perfex_saas_detecting_appropriate_datacenter'));
        $dsn = perfex_saas_get_company_dsn($company, $invoice);

        perfex_saas_deploy_step(_l('perfex_saas_validating_datacenter'));
        if (!perfex_saas_is_valid_dsn($dsn, true))
            throw new \Exception(_l('perfex_saas_invalid_datacenter'), 1);

        // Save the DSN if it is obtained from the package pool
        // This is necessary to keep company data intact in case of a package update on database pools
        if (isset($dsn['source']) && $dsn['source'] == 'pool') {

            $data = ['id' => $company->id, 'dsn' => $CI->encryption->encrypt(perfex_saas_dsn_to_string($dsn))];
            $CI->perfex_saas_model->add_or_update('companies', $data);
        }

        $skip_manual_seed = false;

        // Import from SQL dump file
        if (isset($company->metadata->sql_file) && !empty($company->metadata->sql_file) && file_exists($company->metadata->sql_file)) {

            $skip_manual_seed = true;
            perfex_saas_deploy_step(_l('perfex_saas_importing_seed_file'));
            perfex_saas_import_seed_sql_file($company, $dsn);


            // Add saas column to all table and set value to the company slug
            $setup_queries = perfex_saas_impersonate_instance($company, function () use ($company) {

                return perfex_saas_setup_master_db(true, true, function ($queries, $table) use ($company) {

                    $queries[] = "UPDATE `$table` SET `" . PERFEX_SAAS_TENANT_COLUMN . "` = '$company->slug'";
                    return $queries;
                });
            });

            if (!empty($setup_queries))
                perfex_saas_raw_query($setup_queries, $dsn);
        }

        perfex_saas_deploy_step(_l('perfex_saas_preparing_datacenter_for_installation'));
        perfex_saas_setup_dsn($dsn);

        if (!$skip_manual_seed) {
            perfex_saas_deploy_step(_l('perfex_saas_deploying_seed_to_datacenter'));
            perfex_saas_setup_seed($company, $dsn);

            perfex_saas_deploy_step(_l('perfex_saas_securing_installation_settings'));
            perfex_saas_clear_sensitive_data($company, $dsn, $invoice);


            perfex_saas_deploy_step(_l('perfex_saas_registering_first_administrative_user'));
            perfex_saas_setup_tenant_admin($company, $dsn);
        }

        perfex_saas_deploy_step(_l('perfex_saas_preparing_push_notifications'));

        $notifiedUsers = [];

        // Notify supper admin
        $admin = perfex_saas_get_super_admin();
        $staffid = $admin->staffid;
        if (add_notification([
            'touserid' => $staffid,
            'description' => 'perfex_saas_not_customer_create_instance',
            'link' => 'clients/client/' . $company->clientid,
            'additional_data' => serialize([$company->name])
        ])) {
            array_push($notifiedUsers, $staffid);
        }

        perfex_saas_deploy_step(_l('perfex_saas_sending_push_notification_to_the_company_and_superadmin'));
        pusher_trigger_notification($notifiedUsers);

        perfex_saas_deploy_step(_l('perfex_saas_sending_email_notification_to_the_company_contact_and_superadmin'));

        // Send email to customer about deployment
        $contact = perfex_saas_get_primary_contact($company->clientid);

        if (!empty($contact->email)) {
            send_mail_template('customer_deployed_instance', PERFEX_SAAS_MODULE_NAME, $contact->email, $company->clientid, $contact->id, $company);
        }

        // Send email to admin about the removal
        if (!empty($admin->email)) {
            send_mail_template('customer_deployed_instance_for_admin', PERFEX_SAAS_MODULE_NAME, $admin->email, $company->clientid, $contact->id, $company);
        }

        perfex_saas_deploy_step(_l('perfex_saas_complete'));

        return true;
    } catch (\Throwable $th) {

        try {
            // Noify supper admin
            $admin = perfex_saas_get_super_admin();
            $staffid = $admin->staffid;

            $notifiedUsers = [];
            if (add_notification([
                'touserid' => $staffid,
                'description' => 'perfex_saas_not_customer_create_instance_failed',
                'link' => 'clients/client/' . $company->clientid,
                'additional_data' => serialize([$company->name, $th->getMessage()])
            ])) {
                array_push($notifiedUsers, $staffid);
            }

            pusher_trigger_notification($notifiedUsers);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
        }

        log_message("error", $th->getMessage());

        if (ENVIRONMENT == 'development') throw $th;

        return $th->getMessage();
    }
    return false;
}

/**
 * Set session for the active step in company deployment.
 * 
 * This will be helpful to improve user UX by showing progress of the deployment.
 *
 * @param string $step The description text for the step.
 * @return void
 */
function perfex_saas_deploy_step(string $step)
{
    $_SESSION['perfex_saas_deploy_step'] = $step;
}

/**
 * Remove crm instance for a company.
 * 
 * This function attempt to remove the instance data and delete the instance from the database.
 * It send email notification to both the admin and user about the removal of the instance.
 *
 * @param object $company The company instance to delete
 * @return boolean|string True when successful or string stating the error encountered.
 */
function perfex_saas_remove_company($company)
{

    try {

        if (!isset($company->slug) || empty($company->slug))
            throw new \Exception(_l('perfex_saas_company_slug_is_missing_!'), 1);

        $slug = $company->slug;

        $CI = &get_instance();
        $invoice = $CI->perfex_saas_model->get_company_invoice($company->clientid);

        // Get the data center
        perfex_saas_deploy_step(_l('perfex_saas_detecting_appropriate_datacenter'));
        $dsn = perfex_saas_get_company_dsn($company, $invoice);

        perfex_saas_deploy_step(_l('perfex_saas_validating_datacenter'));
        if (!perfex_saas_is_valid_dsn($dsn, true))
            throw new \Exception(_l('perfex_saas_invalid_datacenter'), 1);

        perfex_saas_deploy_step(_l('perfex_saas_removing_data_from_datacenter'));
        $db = $CI->perfex_saas_migration_model->loadDB($dsn);

        // Get all table list
        $tables = $db->list_tables(); //neutral global query, wont fail

        // Loop through all and remove all data with tenant column of the company slug
        foreach ($tables as $table) {
            if (str_starts_with($table, db_prefix()) && in_array(PERFEX_SAAS_TENANT_COLUMN, $db->list_fields($table)))
                perfex_saas_raw_query("DELETE FROM $table WHERE  `" . PERFEX_SAAS_TENANT_COLUMN . "`='$slug'", $dsn);
        }

        // Check if options table and staffs table or any other seeds table is empty
        if (
            in_array(db_prefix() . "staff", $tables) &&
            empty(perfex_saas_raw_query_row("SELECT * FROM " . db_prefix() . "staff", $dsn, true)) &&
            empty(perfex_saas_raw_query_row("SELECT * FROM " . db_prefix() . "clients", $dsn, true)) &&
            empty(perfex_saas_raw_query_row("SELECT * FROM " . db_prefix() . "contacts", $dsn, true)) &&
            $dsn['dbname'] === perfex_saas_db($slug)
        ) {
            // Drop database if using single and not other tenant on the db
            perfex_saas_raw_query("DROP DATABASE `" . $dsn['dbname'] . '`', $dsn);

            // Clear the tenant media folder
            $master_media_folder = $CI->app->get_media_folder();
            $master_slug = perfex_saas_master_tenant_slug();
            if (str_ends_with($master_media_folder, $master_slug)) {
                $tenant_media_folder = str_ireplace($master_slug, $slug, $master_media_folder);
                if ($tenant_media_folder !== $master_media_folder && is_dir($tenant_media_folder))
                    perfex_saas_remove_dir($tenant_media_folder);
            }
        }

        perfex_saas_deploy_step(_l('perfex_saas_preparing_push_notifications'));

        $notifiedUsers = [];

        // Notify supper admin
        $admin = perfex_saas_get_super_admin();
        $staffid = $admin->staffid;
        if (add_notification([
            'touserid' => $staffid,
            'description' => 'perfex_saas_not_customer_instance_removed',
            'link' => 'clients/client/' . $company->clientid,
            'additional_data' => serialize([$company->name])
        ])) {
            array_push($notifiedUsers, $staffid);
        }

        perfex_saas_deploy_step(_l('perfex_saas_sending_push_notification_to_the_company_and_superadmin'));
        pusher_trigger_notification($notifiedUsers);

        perfex_saas_deploy_step(_l('perfex_saas_sending_email_notification_to_the_company_contact_and_superadmin'));
        // Send email to customer about removal
        $contact = perfex_saas_get_primary_contact($company->clientid);
        if (!empty($contact->email)) {
            send_mail_template('customer_removed_instance', PERFEX_SAAS_MODULE_NAME, $contact->email, $company->clientid, $contact->id, $company);
        }

        // Send email to admin about the removal
        if (!empty($contact->id) && !empty($admin->email)) {
            send_mail_template('customer_removed_instance_for_admin', PERFEX_SAAS_MODULE_NAME, $admin->email, $company->clientid, $contact->id, $company);
        }

        perfex_saas_deploy_step(_l('perfex_saas_complete'));

        return true;
    } catch (\Throwable $th) {

        try {
            // Notify supper admin
            $admin = perfex_saas_get_super_admin();
            $staffid = $admin->staffid;

            $notifiedUsers = [];
            if (add_notification([
                'touserid' => $staffid,
                'description' => 'perfex_saas_not_customer_create_instance_failed',
                'link' => 'clients/client/' . $company->clientid,
                'additional_data' => serialize([$company->name, $th->getMessage()])
            ])) {
                array_push($notifiedUsers, $staffid);
            }

            pusher_trigger_notification($notifiedUsers);
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
        }

        log_message("error", $th->getMessage());

        if (ENVIRONMENT == 'development') throw $th;

        return $th->getMessage();
    }
    return false;
}

/**
 * Retrieves the primary contact associated with a user ID.
 *
 * @param int $userid The ID of the user
 * @return mixed The primary contact row object if found, otherwise false
 */
function perfex_saas_get_primary_contact($userid)
{
    $CI = &get_instance();
    $CI->db->where('userid', $userid);
    $CI->db->where('is_primary', 1);
    $row = $CI->db->get(db_prefix() . 'contacts')->row();

    if ($row) {
        return $row;
    }

    return false;
}

/**
 * Sets up tables and columns for a given data source name (DSN).
 * 
 * The function detect missing DB schema and generate the SQL statements.
 * The statement are run on the DSN. THis is important for single DB scheme packages
 *
 * @param array $dsn The data source name (DSN) configuration
 * @return void
 */
function perfex_saas_setup_dsn($dsn)
{
    // Setup tables and columns
    $CI = &get_instance();
    $CI->perfex_saas_migration_model->run($dsn);
}

/**
 * Sets up seed data for a company by populating specific tables with default values.
 *
 * @param object $company The company object containing information about the company
 * @param array $dsn The data source name (DSN) configuration for the company's database
 * @return void
 */
function perfex_saas_setup_seed($company, $dsn)
{
    $CI = &get_instance();

    // Define the table selectors that specify the columns to be selected from each table
    $table_selectors = [
        'emailtemplates' => ['type', 'slug', 'language'],
        'leads_email_integration' => [PERFEX_SAAS_TENANT_COLUMN],
        'leads_sources' => ['name'],
        'leads_status' => ['name'],
        'options' => ['name'],
        'payment_modes' => ['name'],
        'roles' => ['name'],
        'tickets_priorities' => ['name'],
        'tickets_status' => ['name'],
        'countries' => ['short_name', 'calling_code'],
        'currencies' => ['name', 'symbol'],
        'migrations' => ['version'],
    ];

    $slug = $company->slug;

    $queries = [];

    // Loop through each table selector
    foreach ($table_selectors as $table_name => $selectors) {

        $table = db_prefix() . $table_name;
        $q = "SELECT * FROM $table where `" . PERFEX_SAAS_TENANT_COLUMN . "`='" . perfex_saas_master_tenant_slug() . "';";

        $primary_key = '';

        // Retrieve rows from the master database
        $rows = perfex_saas_raw_query($q, [], true, false);

        foreach ($rows as $row) {

            $row = (array)$row;

            if (empty($primary_key)) {
                $primary_key = array_keys($row)[0];
            }

            $row[PERFEX_SAAS_TENANT_COLUMN] = $slug;

            if ($table_name != 'migrations') {
                $row[$primary_key] = NULL;
            }

            $where = ["`" . PERFEX_SAAS_TENANT_COLUMN . "`='$slug'"];
            foreach ($selectors as $selector) {
                $value = $row[$selector];
                $where[] = "`$selector`=" . $CI->db->escape($value);
            }

            // Check if the row already exists in the company's database
            $result = perfex_saas_raw_query("SELECT * FROM $table WHERE " . implode(' AND ', $where) . ' LIMIT 1', $dsn, true);
            if (!$result || count($result) == 0) {
                // Insert the row into the company's database
                $queries[] = $CI->db->insert_string($table, $row);
            }
        }

        $primary_key = '';
    }

    // Execute the queries to insert the seed data into the company's database
    perfex_saas_raw_query($queries, $dsn);
}

/**
 * Retrieves the super admin for a specific company or the master tenant.
 *
 * @param string $slug (Optional) The slug of the company. Defaults to the master tenant slug.
 * @param array $dsn (Optional) The data source name (DSN) configuration. Defaults to an empty array.
 * @return object|false The super admin object if found, false otherwise.
 */
function perfex_saas_get_super_admin($slug = '', $dsn = [])
{
    $table = db_prefix() . "staff";
    $slug = empty($slug) ? perfex_saas_master_tenant_slug() : $slug;

    // Retrieve the super admin from the database
    return perfex_saas_raw_query_row("SELECT * FROM $table WHERE `" . PERFEX_SAAS_TENANT_COLUMN . "`='$slug' AND admin='1' AND active='1' LIMIT 1", $dsn, true);
}

/**
 * Function to add admin login credential to an instance setup.
 * 
 * Will not run if admin already exist on the DB.
 *
 * @param object $tenant The company instance object containing information about the company.
 * @param array $dsn
 * @return void
 */
function perfex_saas_setup_tenant_admin($tenant, $dsn)
{
    $CI = &get_instance();

    $table = db_prefix() . "staff";

    $result = perfex_saas_get_super_admin($tenant->slug, $dsn);
    if (isset($result->email)) return true; //already exist

    // Get contact from customer
    $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($tenant->clientid));

    // Fallback to the active staff
    if (!$contact && is_staff_logged_in())
        $contact = get_staff(get_staff_user_id());

    if (!$contact) throw new \Exception(_l('perfex_saas_error_getting_contact_to_be_used_as_administrator_on_the_new_instance'), 1);

    // Insert admin login to the instance
    $data = [];
    $data['firstname']   = $contact->firstname;
    $data['lastname']    = $contact->lastname;
    $data['email']       = $contact->email;
    $data['phonenumber'] = $contact->phonenumber;
    $data['password'] = $contact->password;
    $data['admin']  = 1;
    $data['active'] = 1;
    $data[PERFEX_SAAS_TENANT_COLUMN] = $tenant->slug;
    $data['datecreated'] = date('Y-m-d H:i:s');
    $admin_insert_query = $CI->db->insert_string($table, $data);
    return perfex_saas_raw_query($admin_insert_query, $dsn);
}

/**
 * Clears sensitive data and sahred data from the company instance.
 *
 * @param object $company The company object containing information about the company.
 * @param array $dsn_array The data source name (DSN) configuration.
 * @param object|null $invoice (Optional) The invoice object. Defaults to null.
 * @return void
 */
function perfex_saas_clear_sensitive_data($company, $dsn_array, $invoice = null)
{
    $slug = $company->slug;
    $options_table = db_prefix() . "options";
    $emailtemplate_table = db_prefix() . "emailtemplates";

    // Check if installation has already been secured
    $r = perfex_saas_raw_query("SELECT `value` FROM $options_table WHERE `name`='perfex_saas_installation_secured' AND `" . PERFEX_SAAS_TENANT_COLUMN . "`='$slug'", $dsn_array, true);
    if (count($r) > 0) {
        return;
    }

    $where = "WHERE `" . PERFEX_SAAS_TENANT_COLUMN . "`='$slug'";

    // Clean mask and shared fields
    if ($invoice) {
        if (!empty($invoice->metadata->shared_settings)) {
            $shared_settings = $invoice->metadata->shared_settings;
            $_secret_fields = (array)(empty($shared_settings->masked) ? [] : $shared_settings->masked);
            $_shared_fields = (array)(empty($shared_settings->shared) ? [] : $shared_settings->shared);

            $shared_fields = "'" . implode("','", $_shared_fields) . "'";
            $mask_fields = "'" . implode("','", $_secret_fields) . "'";

            $queries = [];
            if (!empty($_shared_fields)) {
                // Empty shared options so they can always be taken from the master tenant
                $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where AND (`name` IN ($shared_fields))";
            }

            if (!empty($_secret_fields)) {
                // Empty secret fields
                $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where AND (`name` IN ($mask_fields))";
            }
        }
    }

    // Reset general sensitive options in the new template
    $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where AND (`name` LIKE '%password%' OR `name` LIKE '%key%' OR `name` LIKE '%secret%' OR `name` LIKE '%_id' OR `name` LIKE '%token')";
    $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where AND (`name` LIKE '%company_logo%' OR `name` ='favicon' OR `name` ='main_domain')";
    $queries[] = "UPDATE `" . $options_table . "` SET `value`='' $where AND (`name` LIKE 'invoice_company_%' OR `name` ='company_vat' OR `name` ='company_state')";

    // Update company name
    $queries[] = "UPDATE `" . $options_table . "` SET `value`='$company->name' $where AND `name` = 'companyname'";

    // Insert installation secured flag
    $queries[] = "INSERT INTO `" . $options_table . "` (`id`, `name`, `value`, `autoload`, `" . PERFEX_SAAS_TENANT_COLUMN . "`) VALUES (NULL, 'perfex_saas_installation_secured', 'true', '0', '$slug')";

    // Remove SAAS email templates
    $queries[] = "DELETE FROM $emailtemplate_table $where AND `slug` LIKE 'company-instance%'";

    // Run all queries in a single transaction
    perfex_saas_raw_query($queries, $dsn_array, false, true);
}

/**
 * Share package shared settings with the active current tenant.
 * 
 * This method will get master shared settings and inject into app instance.
 * It replaces the settings when it is empty on the instance or the instance has the masked value.
 *
 * @return void
 */
function perfex_saas_init_shared_options()
{
    if (perfex_saas_is_tenant()) {

        $CI = &get_instance();

        $tenant = perfex_saas_tenant();
        if (empty($tenant->package_invoice)) return; // wont share any settings

        $instance_settings = $CI->app->get_options();

        $package_shared_fields = [];
        $enforced_shared_fields = PERFEX_SAAS_ENFORCED_SHARED_FIELDS;

        //return if no shared fields
        if (!empty($tenant->package_invoice->metadata->shared_settings->shared)) {

            $package_shared_fields = (array)$tenant->package_invoice->metadata->shared_settings->shared;
        }

        $shared_fields = array_merge($package_shared_fields, $enforced_shared_fields);
        $shared_master_settings = perfex_saas_master_shared_settings($shared_fields);

        foreach ($shared_master_settings as $setting) {

            $current_value = $instance_settings[$setting->name];

            // Override if empty or value is the masked value of the master settings
            if (empty($current_value) || perfex_saas_get_starred_string($setting->value) == $current_value || in_array($setting->name, $enforced_shared_fields)) {

                $instance_settings[$setting->name] = $setting->value;
            }
        }

        // Always set this to 0 to hide menu from users
        $instance_settings['show_help_on_setup_menu'] = 0;

        // Ensure the language is always set.
        if (!isset($instance_settings['active_language']) || empty($instance_settings['active_language'])) {
            $instance_settings['active_language'] = 'english';
        }

        // Use ReflectionClass to update the private app property
        $reflectionClass = new ReflectionClass($CI->app);
        $property = $reflectionClass->getProperty('options');
        $property->setAccessible(true);
        $property->setValue($CI->app, $instance_settings);
    }
}

/**
 * Mask secret values in the contents.
 *
 * * This function mask the field value marked as secret on shared setting list.
 * It attempt to prevent revealing of the share fields with sensitive value.
 * 
 * @param string $contents   The input contents.
 * @return string            The contents with masked secret values.
 */
function perfex_saas_mask_secret_values(string $contents)
{
    $tenant = perfex_saas_tenant();
    $CI = &get_instance();

    // If masked fields are not specified in the package metadata, return the contents as-is
    if (empty($tenant->package_invoice->metadata->shared_settings->masked)) {
        return $contents;
    }

    $package = $tenant->package_invoice;
    $masked_fields = (array) $package->metadata->shared_settings->masked;

    // Get shared secret master settings based on the masked fields
    $shared_secret_master_settings = perfex_saas_master_shared_settings($masked_fields);

    foreach ($shared_secret_master_settings as $row) {
        if (($decrypted_value = $CI->encryption->decrypt($row->value)) !== false) {
            // Replace the decrypted value with a starred version in the contents
            $contents = str_ireplace($decrypted_value, perfex_saas_get_starred_string($decrypted_value), $contents);
        } else {
            // Replace the value with a starred version in the contents
            $contents = str_ireplace($row->value, perfex_saas_get_starred_string($row->value), $contents);
        }
    }

    return $contents;
}

/**
 * Get shared secret master settings.
 *
 * @param array $fields     The masked fields.
 * @return array            The shared secret master settings.
 */
function perfex_saas_master_shared_settings(array $fields)
{
    $fields = "'" . implode("','", $fields) . "'";
    $option_query = 'SELECT name, value FROM ' . db_prefix() . 'options WHERE `' . PERFEX_SAAS_TENANT_COLUMN . "`='" . perfex_saas_master_tenant_slug() . "' AND `name` IN ($fields)";

    // Perform a raw query to fetch the shared secret master settings
    return perfex_saas_raw_query($option_query, [], true);
}

/**
 * Get a starred version of a string.
 * 
 * Masked part of string with the provided mask
 *
 * @param string $str          The input string.
 * @param int    $prefix_len   The length of the prefix to keep as-is.
 * @param int    $suffix_len   The length of the suffix to keep as-is.
 * @param string $mask         The character to use for stars.
 * @return string              The starred version of the string.
 */
function perfex_saas_get_starred_string($str, $prefix_len = 1, $suffix_len = 1, $mask = '*')
{
    if (empty($str)) {
        return $str;
    }

    $len = strlen($str);

    // Ensure prefix length is within a reasonable range
    if ($prefix_len > ($len / 2)) {
        $prefix_len = (int) $len / 3;
    }

    // Ensure suffix length is within a reasonable range
    if ($suffix_len > ($len / 2)) {
        $suffix_len = (int) $len / 3;
    }

    // Get the prefix and suffix substrings
    $prefix = substr($str, 0, $prefix_len);
    $suffix = $suffix_len > 0 ? substr($str, -1 * $suffix_len) : '';

    $repeat = $len - ($prefix_len + $suffix_len);

    // Create the starred string by repeating the star character
    return $prefix . str_repeat($mask, $repeat) . $suffix;
}


/**
 * Impersonate a tenant instance.
 *
 * This function give you the ability to run some come (callback) in the context of the company instance.
 * Its advice to call this function at the end of the flow to ensure safety.
 * 
 * @param object   $company   The company object to impersonate.
 * @param callable $callback  The callback function to execute while impersonating the instance.
 * @return mixed              The result of the callback function.
 * @throws Exception         Throws an exception if there are any errors during impersonation.
 */
function perfex_saas_impersonate_instance($company, $callback)
{
    // Only allow impersonation from the master instance
    if (perfex_saas_is_tenant()) {
        throw new \Exception(_l('perfex_saas_can_not_impersonate_within_another_slave_instnace'), 1);
    }

    if (!is_callable($callback)) {
        throw new \Exception(_l('perfex_saas_invalid_callback_passed_to_impersonate'), 1);
    }

    $CI = &get_instance();
    $OLD_DB = $CI->db;
    $slug = $company->slug;

    // Attempt to define necessary variables to imitate a normal tenant instance context

    // Check if impersonation in the current session is unique to a company
    if (defined('PERFEX_SAAS_TENANT_SLUG') && PERFEX_SAAS_TENANT_SLUG !== $slug) {
        throw new \Exception("Error Processing Request: impersonation in a session must be unique i.e for only a company only", 1);
    }

    defined('PERFEX_SAAS_TENANT_BASE_URL') or define('PERFEX_SAAS_TENANT_BASE_URL', perfex_saas_tenant_base_url($company));
    defined('PERFEX_SAAS_TENANT_SLUG') or define('PERFEX_SAAS_TENANT_SLUG', $slug);
    $GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant'] = $company;

    $dsn = perfex_saas_get_company_dsn($company);
    $db = $CI->perfex_saas_migration_model->loadDB($dsn);
    if ($db === FALSE) {
        throw new \Exception(_l('perfex_saas_error_loading_instance_datacenter_during_impersonate'), 1);
    }
    $CI->db = $db;

    // Test if impersonation works by running a query
    $test_sql = $CI->db->select()->from(db_prefix() . 'staff')->get_compiled_select();
    $test_sql = perfex_saas_db_query($test_sql);

    if (
        perfex_saas_tenant()->slug !== $slug ||
        !stripos($test_sql, PERFEX_SAAS_TENANT_COLUMN) ||
        !stripos($test_sql, $slug) ||
        !stripos($test_sql, 'WHERE')
    ) {
        throw new \Exception(_l('perfex_saas_error_ensuring_impersonation_works'), 1);
    }

    // Call user callback
    $callback_result = call_user_func($callback);

    // End impersonation by unsetting the tenant constant
    unset($GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant']);

    // Confirm the end of impersonation
    if (perfex_saas_tenant_slug()) {
        throw new \Exception(_l('perfex_saas_error_ending_tenant_impersonation'), 1);
    }

    $CI->db = $OLD_DB;

    return $callback_result;
}

/**
 * Perform cron tasks for the Saas application.
 * This method should only be run from the master instance.
 * 
 * Run cron for each instance in a resumeable way so that it can be resumed from where it left off when timeout occurs
 */
function perfex_saas_cron()
{
    // Ensure this method is not run for child instances, only from the master
    if (perfex_saas_is_tenant()) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model(PERFEX_SAAS_MODULE_NAME . '/' . PERFEX_SAAS_MODULE_NAME . '_cron_model');

    try {
        $cron_cache = (object) json_decode(get_option('perfex_saas_cron_cache'));
        $start_from_id = (int) @$cron_cache->last_proccessed_instance_id;

        $CI->perfex_saas_model->db->where('id >', $start_from_id)->where('status', 'active');
        $companies = $CI->perfex_saas_model->companies();

        // Run general migration check and update the last run time
        $CI->perfex_saas_cron_model->saas_cron($companies);
        update_option('perfex_saas_cron_last_success_runtime', time());

        // Run cron for each instance and return the last processed instance id
        $last_proccessed_instance_id = $CI->perfex_saas_cron_model->tenants_cron($companies);
        $cron_cache->last_proccessed_instance_id = $last_proccessed_instance_id;

        // Update cron cache
        update_option('perfex_saas_cron_cache', json_encode($cron_cache));
    } catch (\Throwable $th) {
        log_message('error', $th->getMessage());
    }
}

/**
 * Perform auto-subscription for clients.
 * This method is triggered when a client is logged in and has not subscription or company.
 */
function perfex_saas_autosubscribe()
{
    if (is_client_logged_in()) {
        if (get_option('perfex_saas_enable_auto_trial') == '1') {

            // Get invoice
            $CI = &get_instance();

            if (!str_starts_with($CI->uri->uri_string(), 'clients/packages/')) {
                $invoice = $CI->perfex_saas_model->get_company_invoice(get_client_user_id());
                if (!isset($invoice->id)) {

                    // Check if we have selected plan in session
                    if (empty($package_slug = $CI->session->ps_plan)) {
                        // Get default package
                        $CI->db->where('is_default', 1);
                        $default_package = $CI->perfex_saas_model->packages();
                        $package_slug = empty($default_package) ? '' : $default_package[0]->slug;
                    };

                    // Subscribe
                    if (!empty($package_slug)) {
                        redirect(site_url("clients/packages/$package_slug/select"));
                        exit();
                    }
                }
            }
        }
    }
}

/**
 * Generate a form label hint.
 *
 * @param string $hint_lang_key  The language key for the hint text.
 * @param string|string[] $params The language key sprint_f variables.
 * @return string                The HTML code for the form label hint.
 */
function perfex_saas_form_label_hint($hint_lang_key, $params = null)
{
    return '<span class="tw-ml-2" data-toggle="tooltip" data-title="' . _l($hint_lang_key, $params) . '"><i class="fa fa-question-circle"></i></span>';
}

/**
 * Remove directory recursively including hidder directories and files.
 * This is preferable to perfex delete_dir function as that does not handle hidden directories well.
 *
 * @param      string  $target  The directory to remove
 * @return     bool
 */
function perfex_saas_remove_dir($target)
{
    try {
        if (is_dir($target)) {
            $dir = new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS);
            foreach (new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST) as $filename => $file) {
                if (is_file($filename)) {
                    unlink($filename);
                } else {
                    perfex_saas_remove_dir($filename);
                }
            }
            return rmdir($target); // Now remove target folder
        }
    } catch (\Exception $e) {
    }
    return false;
}
