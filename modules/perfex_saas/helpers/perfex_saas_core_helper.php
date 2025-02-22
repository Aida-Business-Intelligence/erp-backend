<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This file contain core helper functions for the module.
 * All core function for boostraping are defined here along side important constant.
 * CI get_instance() or any other core function of codeigniter as app are not fully loaded and should be avoided in this helper
 */

require(__DIR__ . '/../config/constants.php');
require(__DIR__ . '/../vendor/autoload.php');

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

/**
 * Initializes the Perfex SAAS module.
 * Sets up the SAAS environment based on the requested tenant.
 *
 * @return void
 */
function perfex_saas_init()
{
    try {
        if (isset($_SERVER['REQUEST_URI'])) {

            $request_uri = $_SERVER['REQUEST_URI'];
            $host  = $_SERVER['HTTP_HOST'];

            // Can identify tenant by url segment and or host (subdomain or cname/custom domain)
            $tenant_info = perfex_saas_get_tenant_info_by_http($request_uri, $host);

            if ($tenant_info) {
                $tenant_path_id = $tenant_info['path_id'];
                $tenant_id = $tenant_info['slug'];
                $tenancy_access_mode = $tenant_info['mode'];
                $field =  $tenancy_access_mode == PERFEX_SAAS_TENANT_MODE_DOMAIN ? 'custom_domain' : 'slug'; // path and subdomain mode use slug for search

                if ($field == 'custom_domain') {
                    $tenant_id = $tenant_info['custom_domain'];
                    $field = 'custom_domain';
                }

                // Determine the tenant base URL
                $tenant_base_url = perfex_saas_url_origin($_SERVER) . '/';
                if (!empty($tenant_path_id)) {
                    $tenant_base_url .= "$tenant_path_id/";
                }

                if (!$tenant_id)
                    perfex_saas_show_tenant_error("Invalid Tenant", "We could not find the requested instance.", 404);

                // Check if tenant exists
                $tenant = perfex_saas_search_tenant_by_field($field, $tenant_id);
                if (!$tenant) {
                    perfex_saas_show_tenant_error("Invalid Tenant", "The requested tenant does not exist.", 404);
                }

                // Get package and invoice details
                $package_invoice = perfex_saas_get_client_package_invoice($tenant->clientid);

                // Decode metadata and package/invoice details
                $tenant->metadata = json_decode($tenant->metadata);
                if ($package_invoice) {

                    $tenant->package_invoice = $package_invoice;
                }

                // Add the identity mode
                $tenant->http_identification_type = $tenancy_access_mode;

                // @todo Determine if we should check package for permission to use custom domain if the tenant is recognized by custom domain.

                // Set global variable for the tenant
                $GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant'] = $tenant;

                // Tenant is gotten from request uri match i.e tenant_slug/ps
                if (!empty($tenant_path_id)) {
                    // Replace repeated $id segment
                    if (stripos($request_uri, "/$tenant_path_id/$tenant_path_id") !== false) {
                        $_SERVER['REQUEST_URI'] = str_ireplace("/$tenant_path_id/$tenant_path_id", "/$tenant_path_id", $request_uri);
                        if (empty($_POST)) {
                            $url = perfex_saas_full_url($_SERVER);
                            header("Location: $url");
                            exit;
                        }
                    }

                    // Serve static files
                    if (stripos($request_uri, ".") && stripos($request_uri, ".php") === false) {
                        $url = str_ireplace("/$tenant_path_id", '', perfex_saas_full_url($_SERVER));
                        header("Location: $url");
                        exit;
                    }
                }

                // Define constants for the tenants. If any of these have been defined earlier, an error should be thrown,
                // as it is important that the user of the module has not defined these custom constants.
                define('PERFEX_SAAS_TENANT_SLUG', $tenant->slug);
                define('APP_SESSION_COOKIE_NAME', $tenant->slug . '_sp_session');
                define('APP_COOKIE_PREFIX', $tenant->slug);
                define('APP_CSRF_TOKEN_NAME', $tenant->slug . '_csrf_token_name');
                define('APP_CSRF_COOKIE_NAME', $tenant->slug . '_csrf_cookie_name');
                define('PERFEX_SAAS_TENANT_BASE_URL', $tenant_base_url);
            }
        }
    } catch (\Exception $e) {
        // Handle any exceptions that occur during initialization
        perfex_saas_show_tenant_error("Initialization Error", $e->getMessage(), 500);
    }

    // Define APP_BASE_URL based on the tenant's base URL, fallback to the default base URL if not available
    define('APP_BASE_URL', defined('PERFEX_SAAS_TENANT_BASE_URL') ? PERFEX_SAAS_TENANT_BASE_URL : APP_BASE_URL_DEFAULT);
}


/**
 * Get tenant information based on the provided HTTP request URI and host.
 * If the returned array contain non empty slug, then tenant match/search should be made by 'slug' field otherwise 'custom_domain'.
 * The returned array also contain 'mode' key which can either be 'domain' - custom domain, 'subdomain' or 'path', depending on how the 
 * tenant is recognized.
 *
 * @param string $request_uri The request URI.
 * @param string $host The HTTP host.
 * @return array|false Tenant information if found, otherwise false.
 * @throws Exception If invalid input is provided or no tenant is found.
 */
function perfex_saas_get_tenant_info_by_http($request_uri, $host)
{
    // Validate and sanitize input
    if (!is_string($request_uri) || !is_string($host)) {
        throw new Exception('Invalid input provided.');
    }

    $tenant_info = false;
    $mode = PERFEX_SAAS_TENANT_MODE_DOMAIN;

    // Try by subdomain or domain first before url match
    if (!empty($host)) {
        $tenant_info = perfex_saas_get_tenant_info_by_host($host);
        if (!empty($tenant_info['slug']))
            $mode = PERFEX_SAAS_TENANT_MODE_SUBDOMAIN;
    }

    if (!$tenant_info) {
        // Get tenant information from request URI
        $tenant_info = perfex_saas_get_tenant_info_by_request_uri($request_uri);
        if ($tenant_info)
            $mode = PERFEX_SAAS_TENANT_MODE_PATH;
    }

    if (!$tenant_info) {
        return false;
    }

    return [
        'path_id' => $tenant_info['path_id'] ?? '',
        'slug' => $tenant_info['slug'] ?? '',
        'custom_domain' => $tenant_info['custom_domain'] ?? '',
        'mode' => $mode
    ];
}


/**
 * Extracts the tenant information from the request URI.
 *
 * @param string $request_uri The request URI.
 * @return array|false The tenant information array or false if not found.
 * @todo Support subdirectory installation of perfex
 */
function perfex_saas_get_tenant_info_by_request_uri($request_uri)
{
    $saas_url_marker = '/' . PERFEX_SAAS_ROUTE_ID;
    // Should match either /tenant/ps/* or /tenant/ps
    $saas_url_id_pos = stripos($request_uri, $saas_url_marker . '/');

    if ($saas_url_id_pos === false && str_ends_with($request_uri, $saas_url_marker))
        $saas_url_id_pos = stripos($request_uri, $saas_url_marker);

    if ($saas_url_id_pos !== false) {

        // Extract tenant slug and id
        $tenant_slug = substr($request_uri, 1, $saas_url_id_pos - 1);
        // Find the position of the last slash
        $lastSlashPos = strrpos($tenant_slug, '/');
        // Extract the substring after the last slash
        if ($lastSlashPos !== false)
            $tenant_slug = substr($tenant_slug, $lastSlashPos + 1);

        // Get the directory in case the perfex is installed in subfolder
        $base_url_path = parse_url(APP_BASE_URL_DEFAULT);
        if (!isset($base_url_path['path'])) {
            throw new \Exception("Your base url in app/app-config.php should end with trailing slash !", 1);
        }

        $base_url_path = $base_url_path['path'];

        if (!empty($tenant_slug) && str_starts_with($request_uri, $base_url_path . $tenant_slug . $saas_url_marker)) {

            $id = trim($base_url_path . $tenant_slug . $saas_url_marker, '/'); // i.e. tenantslug/ps or dir/tenantslug/ps

            return [
                'slug' => $tenant_slug,
                'path_id' => $id,
            ];
        }
    }

    return false;
}

/**
 * Get tenant information based on the provided host.
 * Returned array contain either of non empty 'custom_domain' and 'slug' but not both.
 *
 * @param string $http_host The HTTP host.
 * @return array|false Tenant information if found or false is on same domain with saas base domain
 * @throws Exception If no tenant is found or an invalid subdomain is detected.
 */
function perfex_saas_get_tenant_info_by_host($http_host)
{
    // Validate input
    if (!filter_var($http_host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || stripos($http_host, '/') !== false) {
        throw new Exception('Invalid HTTP host provided: ' . $http_host);
    }

    // Get the default host and the URL host
    $app_host = perfex_saas_get_saas_default_host();
    $host = $http_host;
    $tenant_slug = '';

    if (str_starts_with($app_host, 'www.')) {
        $app_host = str_ireplace('www.', '', $app_host);
    }

    if (str_starts_with($host, 'www.')) {
        $host = str_ireplace('www.', '', $host);
    }

    if ($app_host === $host) {
        //throw new Exception('No tenant found for the provided host.');
        return false;
    }

    // Check for subdomain
    if (str_ends_with($host, $app_host)) {
        $subdomain = trim(str_ireplace($app_host, '', $host), '.');

        if (empty($subdomain) || stripos($subdomain, '.') !== false) {
            throw new Exception('Invalid subdomain detected.');
        }

        $tenant_slug = $subdomain; // Assign the subdomain as the tenant slug
        $host = ""; // Reset the host value
    }

    return [
        'custom_domain' => $host, // Custom domain (without "www")
        'slug' => $tenant_slug // Tenant slug
    ];
}

/**
 * Get the default app base url host. Use the address for installation before setting up SaaS.
 *
 * @return string
 */
function perfex_saas_get_saas_default_host()
{
    return parse_url(APP_BASE_URL_DEFAULT, PHP_URL_HOST);
}

/**
 * Retrieve a tenant by a certain db column/field in companies table
 *
 * @param string $field The db column name
 * @param string $value The value to search for
 * @return object|null The tenant object if found, null otherwise
 */
function perfex_saas_search_tenant_by_field($field, $value)
{
    $tenant_table = perfex_saas_table('companies');
    $query = "SELECT `slug`, `name`, `dsn`, `clientid`, `metadata`, `status` FROM `$tenant_table` WHERE `$field` = :value";
    $parameters = [':value' => $value];

    return perfex_saas_raw_query_row($query, [], true, true, $parameters);
}

/**
 * Retrieve the package invoice for a client
 *
 * @param int $clientid The client ID
 * @return object|null The package invoice object if found, null otherwise
 */
function perfex_saas_get_client_package_invoice($clientid)
{

    $invoice_table = db_prefix_perfex_saas_custom() . 'invoices';
    $package_table = perfex_saas_table('packages');
    $client_table = db_prefix_perfex_saas_custom() . 'clients';
    $package_column = perfex_saas_column('packageid');

    $q = "SELECT 
            `$invoice_table`.*,
            `$package_table`.`status` as `package_status`, `slug`, 
            `clientid`, `name`, `description`, `price`, `bill_interval`, `is_default`, 
            `is_private`, `db_scheme`, `db_pools`, `modules`, `metadata`, `trial_period` 
        FROM `$invoice_table` 
            INNER JOIN `$package_table` ON `$package_table`.`id` = `$package_column` 
            INNER JOIN `$client_table` ON `$client_table`.`userid` = `$invoice_table`.`clientid` 
        WHERE `recurring` > '0' AND `$package_column` IS NOT NULL AND `clientid`=:clientid;";

    $parameters = [':clientid' => (int)$clientid];

    $package_invoice = perfex_saas_raw_query_row($q, [], true, true, $parameters);
    // Decode package/invoice details
    if ($package_invoice) {
        if (!empty($package_invoice->metadata)) {
            $package_invoice->metadata = json_decode($package_invoice->metadata);
        }

        if (!empty($package_invoice->modules)) {
            $package_invoice->modules = json_decode($package_invoice->modules, true);
        }
    }
    return $package_invoice;
}




/**##################################################################################################################***
 *                                                                                                                      *
 *                                               Common tenant helpers methods                                          *
 *                                                                                                                      *
 ***##################################################################################################################**/

/**
 * Function to generate name for instance db.
 * Add unique signature to DB created by the saas
 *
 * @param string $db
 * @throws Exception    When the length of the db will be higher than 64 characters.
 * @return string
 */
function perfex_saas_db($db)
{
    $db = PERFEX_SAAS_MODULE_NAME . '_db_' . $db;

    // Convert slug to lowercase
    $db = strtolower($db);

    // Replace non-alphanumeric characters with underscore
    $db = preg_replace('/[^a-z0-9]+/', '_', $db);

    // Remove leading and trailing underscores
    $db = trim($db, '_');

    // throw error when length is above 64 characters (database name limit)
    if (strlen($db) > 64) throw new \Exception("Database name provided has exceed the 64 character limit: $db", 1);

    return $db;
}

/**
 * Method to prefix saas table names
 *
 * @param string $table
 * @return string
 */
function perfex_saas_table($table)
{
    $db_prefix = db_prefix_perfex_saas_custom();

    return $db_prefix . PERFEX_SAAS_MODULE_NAME . '_' . $table;
}


/**
 * Method to generate perfex saas column name for perfex tables
 *
 * @param string $column
 * @return string
 */
function perfex_saas_column($column)
{
    return PERFEX_SAAS_MODULE_NAME . '_' . $column;
}


/**
 * Function to get master slug
 *
 * @return string
 */
function perfex_saas_master_tenant_slug()
{
    return 'master';
}

/**
 * check is request is instance request or saas module
 *
 * @return     bool
 */
function perfex_saas_is_tenant()
{
    return defined('PERFEX_SAAS_TENANT_BASE_URL') && defined('PERFEX_SAAS_TENANT_SLUG') && !empty($GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant']);
}


/**
 * Get the active tenant
 * 
 * @return     object|false
 * 
 * Returned object can have 'package_invoice' and object contain bot property of invoice and the package together.
 */
function perfex_saas_tenant()
{
    if (!perfex_saas_is_tenant()) return false;

    $tenant = (object)$GLOBALS[PERFEX_SAAS_MODULE_NAME . '_tenant'];

    return $tenant;
}


/**
 * Get the active tenant slug
 *
 * @return     string|false
 */
function perfex_saas_tenant_slug()
{
    if (!perfex_saas_is_tenant()) return false;

    return defined('PERFEX_SAAS_TENANT_SLUG') ? PERFEX_SAAS_TENANT_SLUG : false;
}

/**
 * Get the database prefix for Perfex SAAS custom tables.
 *
 * If the function `db_prefix()` exists, it will be used to retrieve the database prefix.
 * Otherwise, it will fallback to the value defined in the constant `APP_DB_PREFIX`.
 * If the constant is not defined, the default prefix 'tbl' will be used.
 *
 * @return string The database prefix for Perfex SAAS custom tables.
 */
function db_prefix_perfex_saas_custom()
{
    $db_prefix = function_exists('db_prefix') ? db_prefix() : (defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl');
    return $db_prefix;
}




/**##################################################################################################################***
 *                                                                                                                      *
 *                                               Raw Database helpers                                                   *
 *                                                                                                                      *
 ***##################################################################################################################**/

/**
 * Retrieves the master database connection details.
 *
 * @return array  The master database connection details
 */
function perfex_saas_master_dsn()
{
    return array(
        'driver' => APP_DB_DRIVER,
        'host' => defined('APP_DB_HOSTNAME_DEFAULT') ? APP_DB_HOSTNAME_DEFAULT : APP_DB_HOSTNAME,
        'user' => defined('APP_DB_USERNAME_DEFAULT') ? APP_DB_USERNAME_DEFAULT : APP_DB_USERNAME,
        'password' => defined('APP_DB_PASSWORD_DEFAULT') ? APP_DB_PASSWORD_DEFAULT : APP_DB_PASSWORD,
        'dbname' => defined('APP_DB_NAME_DEFAULT') ? APP_DB_NAME_DEFAULT : APP_DB_NAME
    );
}

/**
 * Execute a raw SQL query using PDO and prevent SQL vulnerabilities.
 *
 * The query is executed using the provided PDO connection and can contain placeholders for query parameters.
 * The function supports single queries and multiple queries in an array.
 * The function is expected to be used internally and should be use with parameters when running input from the user/public.
 *
 * @param string|string[] $query              The SQL query or array of queries to execute.
 * @param array           $dsn                The database connection details. Defaults to an empty array.
 * @param bool            $return             Whether to return the query results. Defaults to false.
 * @param bool            $atomic             Whether to run the queries in a transaction. Defaults to true.
 * @param callable|null   $callback           Optional callback function to execute on each result row.
 * @param bool            $disable_foreign_key Whether to disable foreign key checks. Defaults to false.
 * @param bool            $stop_on_error      Whether to stop execution on the first query error. Defaults to true.
 * @param array           $query_params       Array of query parameters to bind to the prepared statement. Defaults to an empty array.
 *
 * @return mixed|null The query result or null if there was an error.
 *
 * @throws \PDOException If there is a database error and the environment is set to development.
 * @throws \Exception    If there is a non-database-related error and the environment is set to development.
 */
function perfex_saas_raw_query($query, $dsn = [], $return = false, $atomic = true, $callback = null, $disable_foreign_key = false, $stop_on_error = true, $query_params = [])
{

    if (empty($dsn)) {

        $dsn = perfex_saas_master_dsn();
    }

    if (is_string($dsn)) { //conn is dsn sting
        $dsn = perfex_saas_parse_dsn($dsn);
    }

    // Get PDO
    $pdo = perfex_saas_get_pdo_conn($dsn, true);

    $is_multi_query = is_array($query);
    $resultList = array();

    try {

        if ($is_multi_query && !empty($query_params))
            throw new \Exception("Query parameter binding is not supported for multiple query", 1);

        $pre_queries = [];
        $post_queries = [];
        $queries = $is_multi_query ? $query : [$query];

        if ($disable_foreign_key) {
            $pre_queries[] = "SET foreign_key_checks = 0;";
            $post_queries[] = "SET foreign_key_checks = 1;"; // add to end
        }

        if ($atomic) {
            $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
            $pre_queries[] = "START TRANSACTION";
            $post_queries[] = "COMMIT"; //add to end
        }

        // Run prequeries. These are safe to run without binding
        foreach ($pre_queries as $pr_q) {
            $pdo->query($pr_q);
        }

        foreach ($queries as $q) {

            $stmt = false;

            if (!$stop_on_error) {
                try {
                    $stmt = perfex_saas_pdo_safe_query($pdo, $q, $query_params);
                } catch (\Throwable $th) {
                    log_message("error", "Database Error: " . $th->getMessage());
                    $stmt = false;
                }
            } else {
                $stmt = perfex_saas_pdo_safe_query($pdo, $q, $query_params);
            }


            $results = [false];

            if ($stmt) {

                $results = [true];

                if ($return) {
                    $results = [];
                    while ($row = $stmt->fetchObject()) {
                        $results[] = $row;
                        if ($callback && is_callable($callback)) {
                            call_user_func($callback, $row);
                        }
                    }
                    $stmt->closeCursor();
                }
            }

            $resultList[] = $results;
        }

        // Safe queries
        foreach ($pre_queries as $po_q) {
            $pdo->query($po_q);
        }
    } catch (\PDOException $e) {

        log_message("error", "Database Error: " . $e->getMessage() . ': ' . @$q);

        if ($atomic) $pdo->rollBack();

        if (ENVIRONMENT == 'development') throw $e;

        return null;
    } catch (\Exception $e) {

        log_message("error", $e->getMessage() . ': ' . @$q);

        if ($atomic) $pdo->rollBack();

        if (ENVIRONMENT == 'development') throw $e;

        return null;
    }

    return $is_multi_query ? $resultList : $resultList[0];
}

/**
 * Executes a safe query using prepared statements with PDO.
 *
 * @param PDO $pdo The PDO instance.
 * @param string $query The SQL query.
 * @param array $parameters The query parameters to bind.
 * @return PDOStatement|false The PDOStatement object if successful, false otherwise.
 */
function perfex_saas_pdo_safe_query($pdo, $query, $parameters)
{
    $statement = $pdo->prepare($query);

    foreach ($parameters as $key => $value) {
        $statement->bindParam($key, $value);
    }

    return $statement->execute() ? $statement : false;
}

/**
 * Executes a raw query and returns the first row of the result set.
 *
 * @param string|string[] $query The SQL query.
 * @param array $dsn The connection details.
 * @param bool $return Whether to return the result set or not.
 * @param bool $atomic Whether to run the query in a transaction or not.
 * @param array $query_params The query parameters.
 * @return mixed|null The first row of the result set if successful, null otherwise.
 */
function perfex_saas_raw_query_row($query, $dsn = [], $return = false, $atomic = true, $query_params = [])
{
    $result = perfex_saas_raw_query($query, $dsn, $return, $atomic, null, false, true, $query_params);
    if (!$result) {
        return $result;
    }
    return $result[0];
}


/**
 * Executes a database query based on the provided SQL statement in context of the current instance.
 *
 * @param string $sql The SQL query.
 * @return mixed The result of the query execution.
 */
function perfex_saas_db_query($sql)
{
    $slug = perfex_saas_tenant_slug();

    if (!$slug) {
        // Default saas panel.
        if (
            stripos($sql, perfex_saas_table('')) !== false //saas table queries
        )
            return $sql;

        // Always set this for security. This ensure other tenant data is not loaded for master instance on multitenant singledb
        $slug = perfex_saas_master_tenant_slug();
    }
    return perfex_saas_simple_query($slug, $sql);
}


/**
 * Function to parse a tenant SQL query.
 * This function restricts the tenant by adding the tenant ID where clause in read and writes statements.
 *
 * @param string $slug The tenant slug.
 * @param string $sql The SQL query.
 * @param bool $return_parsed Flag to indicate whether to return the parsed SQL tree.
 * @param bool $parsed Flag to indicate whether the SQL query is already parsed.
 * @throws \Exception
 * @return mixed|string The parsed SQL query or the result of the query execution.
 */
function perfex_saas_simple_query($slug, $sql, $return_parsed = false, $parsed = false)
{
    if (!$parsed) {
        $parser = new PHPSQLParser($sql);
        $parsed  = $parser->parsed;
    }

    $key = strtoupper(key($parsed));

    $will_change_db_struct = in_array($key, ['CREATE', 'BRACKET', 'TRUNCATE', 'RENAME', 'DROP', 'ALTER']);

    if ($slug === perfex_saas_master_tenant_slug()) {

        if ($will_change_db_struct) {

            // If CREATE, add column if not in the columns
            if ($key === 'CREATE' && isset($parsed['TABLE'])) {
                // @todo Limit this to when a module is being activated only.
                return perfex_saas_add_tenant_column_to_create_query($parsed);
            }

            return $sql; // Allow for installing new modules from master instance or deploying new instance.
        }
    }

    // Deny, unsupported, tenant shouldnt be able to do any of this query
    if ($will_change_db_struct) {
        throw new \Exception("Unsupported query for tenant: $sql", 1);
    }


    if (strtoupper(trim($sql)) == "SELECT FOUND_ROWS()" || $sql == 'SELECT VERSION() as version' || $sql == 'SELECT @@sql_mode as mode') {
        return $sql;
    }


    if (in_array($key, ['SHOW', 'LOCK', 'UNLOCK'])) {
        if (stripos($sql, 'DROP') == false && stripos($sql, 'DELETE') == false)
            return $sql;
    }

    if (stripos($sql, 'SELECT GET_LOCK') !== false || stripos($sql, 'IS_FREE_LOCK') !== false || stripos($sql, 'RELEASE_LOCK') !== false) {
        return $sql;
    }

    if ($key == 'SET') {

        if (
            stripos($sql, 'set session sql_mode') !== false ||
            str_starts_with(strtolower($sql), 'set sql_mode = ') ||
            strtoupper($sql) == 'SET SQL_BIG_SELECTS=1'
        )
            return $sql;
    }


    $table = null;
    $filterColumn = PERFEX_SAAS_TENANT_COLUMN;
    $slug_string = "'" . $slug . "'";
    $column_string = "`" . PERFEX_SAAS_TENANT_COLUMN . "`";

    try {
        // If write query already have the column i.e importation of files, we want to remove the column and the value
        if (stripos($sql, PERFEX_SAAS_TENANT_COLUMN) !== false) {
            $expr_index = 2; //the cols expr
            $_sub_trees = isset($parsed[$key][$expr_index]['sub_tree']) ? $parsed[$key][$expr_index]['sub_tree'] : [];
            $tenant_column_index = null;

            // Find the posistion of the tenant col
            if (!empty($_sub_trees) && is_array($_sub_trees)) {
                foreach ($_sub_trees as $_k => $_v) {
                    if ($_v['no_quotes']['parts'][0] === PERFEX_SAAS_TENANT_COLUMN) {
                        $tenant_column_index = $_k;
                        break;
                    }
                }
            }

            // If position is matched
            if (!is_null($tenant_column_index)) {

                // Remove the col
                unset($parsed[$key][$expr_index]['sub_tree'][$tenant_column_index]);

                // Remove the col values
                $totalRows = count($parsed["VALUES"]);
                for ($i = 0; $i < $totalRows; $i++) {
                    unset($parsed["VALUES"][$i]['data'][$tenant_column_index]);
                }
            }
        }


        $canHasSubtree = false;
        $table = isset($parsed['FROM'][0]['table']) ?
            ($parsed['FROM'][0]['alias']['name'] ?? $parsed['FROM'][0]['table']) : (isset($parsed[$key][0]['table']) ?
                ($parsed[$key][0]['alias']['name'] ?? $parsed[$key][0]['table']) : (isset($parsed[$key][1]['table']) ? ($parsed[$key][1]['alias']['name'] ?? $parsed[$key][1]['table']) : ''
                )
            );

        // Exclude pivot from global tenant where clause
        $isPvQuery = in_array(trim(strtolower($sql)), ['select @pv := "0"', "select @pv := '0'"]);
        $excludeGlobalWhere = $isPvQuery;

        if ($table) {

            $filterColumn = $table . '.' . $filterColumn;
        } else {

            $canHasSubtree = true;
            $searchKey = ($key == 'SELECT' || isset($parsed['FROM'])) ? 'FROM' : $key;
            $searchKeyArray = perfex_saas_find_key($parsed, $searchKey, 'any');
            if (is_array($searchKeyArray)) {
                $table = perfex_saas_find_key($searchKeyArray, 'table');
            }

            if (!$table) {
                $msg = "\n Table Extraction: Error extracting table name from this query: ";
                $allowedWithoutTable = $isPvQuery;

                if (!$allowedWithoutTable)
                    throw new \Exception($msg . $sql); // throw exception
            } else {
                $filterColumn = $table . '.' . $filterColumn;
            }
        }


        $globalWhereAnd = [
            ['expr_type' => 'operator', 'base_expr' => 'and']
        ];

        $globalWhere = [
            [
                'expr_type' => 'colref',
                'base_expr' => $filterColumn,
            ],
            [
                'expr_type' => 'operator',
                'base_expr' => '=',
            ],
            [
                'expr_type' => 'const',
                'base_expr' => $slug_string,
            ]
        ];

        if (!$excludeGlobalWhere) {

            if ($key == 'SELECT') {
                // Parse subtrees
                $hasSubtree = false;
                if ($canHasSubtree) {
                    $hasSubtree = perfex_saas_find_key($parsed['FROM'], 'expr_type', 'subquery');
                    if ($hasSubtree) {
                        foreach ($parsed['FROM'] as $fIndex => $fromL) {
                            if ($fromL['expr_type'] == 'subquery') {
                                //parse subquery differently
                                $sub = perfex_saas_simple_query($slug, $fromL['base_expr'], true, $fromL['sub_tree']);
                                $parsed['FROM'][$fIndex]['sub_tree'] = $sub;
                            }
                        }
                    }
                }

                if (!$hasSubtree) {
                    if (isset($parsed['WHERE'])) {
                        $parsed['WHERE'] = array_merge($parsed['WHERE'], $globalWhereAnd, $globalWhere);
                    } else {
                        $parsed['WHERE'] = $globalWhere;
                    }
                }
            }

            // Common queries to add where
            if (in_array($key, ['DELETE', 'UPDATE'])) {
                if (isset($parsed['WHERE'])) {
                    $parsed['WHERE'] = array_merge($parsed['WHERE'], $globalWhereAnd, $globalWhere);
                } else {
                    $parsed['WHERE'] = $globalWhere;
                }
            }


            if (in_array($key, ['INSERT'])) {
                // Add field
                array_push($parsed[$key][2]['sub_tree'], ['expr_type' => 'colref', 'base_expr' => $column_string, 'no_quotes' => ["delim" => false, "parts" => [PERFEX_SAAS_TENANT_COLUMN]]]);

                // Add tenant field for all rows for multipe single insertion
                $totalInsertRows = count($parsed["VALUES"]);
                for ($i = 0; $i < $totalInsertRows; $i++) {

                    array_push($parsed["VALUES"][$i]['data'], ['expr_type' => 'const', 'base_expr' => $slug_string, 'sub_tree' => false]);
                }
            }

            if (in_array($key, ['UPDATE'])) {

                array_push(
                    $parsed["SET"],
                    [
                        "expr_type" => "expression", "base_expr" => $column_string . '=' . $slug_string, "sub_tree" => [
                            ['expr_type' => 'colref', 'base_expr' => $column_string, 'no_quotes' => ['delim' => false, 'parts' => [PERFEX_SAAS_TENANT_COLUMN]], 'sub_tree' => false],
                            ['expr_type' => 'operator', 'base_expr' => '=', 'sub_tree' => false],
                            ['expr_type' => 'const', 'base_expr' => $slug_string, 'sub_tree' => false]
                        ]
                    ]
                );
            }

            // Note: leads_email_integration table has been programmed by author to always filter by id=1
            // We need to remove this where clause for case of multiple tenant in single db.
            if (stripos($sql, 'leads_email_integration') > 0) {

                $startIndex = -1;
                foreach ($parsed["WHERE"] as $key => $value) {

                    if ($startIndex >= 0 && $value['expr_type'] == 'colref') { //meet with another colref, stop
                        break;
                    }

                    // Detect start of id where clause
                    if ($value['expr_type'] == 'colref' && in_array($value['base_expr'], ["`id`", "'id'", "id"])) {
                        $startIndex = $key;
                    }

                    // Remove every key until new colref is found
                    if ($startIndex >= 0) {
                        unset($parsed["WHERE"][$key]);
                    }
                }

                // Limit to one
                $parsed["LIMIT"] = ['offset' => '', 'rowcount' => 1];
            }
        }

        // Return tree if requested.
        if ($return_parsed) {
            return $parsed;
        }

        // Create new instance of sql creator and compile
        $creator = new PHPSQLCreator($parsed);
        $newSql = $creator->created;

        return $newSql;
    } catch (\Exception $e) {
        throw new \Exception($e->getMessage(), 1);
    }
}

/**
 * Checks if a query is a write query.
 *
 * @param string $query The SQL query.
 * @return bool True if the query is a write query, false otherwise.
 */
/**
 * Checks if a query is a write query.
 *
 * @param string $query The SQL query.
 * @return bool True if the query is a write query, false otherwise.
 */
function perfex_saas_is_write_query($query)
{
    // Parse the query using a SQL parser library
    $parser = new PHPSQLParser();
    $parsedQuery = $parser->parse($query);

    // Check if the query contains write-related statements in the string form
    $containsStringCheck = (stripos($query, 'DELETE FROM') !== false ||
        stripos($query, 'DROP DATABASE') !== false ||
        stripos($query, 'INSERT INTO') !== false ||
        (stripos($query, 'UPDATE') !== false && stripos($query, 'SET') !== false)
    );

    // Check if the parsed query contains write-related statements using the parsed data
    $containsParsedCheck = (isset($parsedQuery['DELETE']) || // Check if DELETE statement is present
        isset($parsedQuery['DROP']) || // Check if DROP statement is present
        isset($parsedQuery['INSERT']) || // Check if INSERT statement is present
        isset($parsedQuery['UPDATE'])
    );

    // Check if the parsed query contains write-related statements
    $isWriteQuery = $containsStringCheck && $containsParsedCheck;

    return $isWriteQuery;
}

// return the value of a key in the supplied array
function perfex_saas_find_key($arr, $tracker, $return = 'string')
{
    foreach ($arr as $key => $value) {
        if ($key === $tracker) {
            if ($return == 'string' && is_string($value)) {
                return $value;
            } else {
                return $value;
            }
        }
        if (is_array($value)) {
            $ret = perfex_saas_find_key($value, $tracker, $return);
            if ($ret) {
                return $ret;
            }
        }
    }
    return false;
}

/**
 * Add the 'tenant_id' column to the CREATE TABLE SQL statement.
 *
 * @param string|PHPSQLParser $sql The original SQL statement or Parsed instance.
 * @return string The modified SQL statement with the 'tenant_id' column added.
 */
function perfex_saas_add_tenant_column_to_create_query($sql)
{
    $parsed = $sql;

    if (is_string($parsed)) {

        // Parse the SQL statement
        $parser = new PHPSQLParser();
        $parsed = $parser->parse($sql);
    }

    // Define the column structure for 'tenant_id'
    $newCol = '{
        "expr_type": "column-def",
        "base_expr": "`' . PERFEX_SAAS_TENANT_COLUMN . '` VARCHAR(50) NOT NULL",
        "sub_tree": [
            {
                "expr_type": "colref",
                "base_expr": "`' . PERFEX_SAAS_TENANT_COLUMN . '`",
                "no_quotes": {
                    "delim": false,
                    "parts": [
                        "' . PERFEX_SAAS_TENANT_COLUMN . '"
                    ]
                }
            },
            {
                "expr_type": "column-type",
                "base_expr": "VARCHAR(50) NOT NULL",
                "sub_tree": [
                    {
                        "expr_type": "data-type",
                        "base_expr": "VARCHAR",
                        "length": "50"
                    },
                    {
                        "expr_type": "bracket_expression",
                        "base_expr": "(50)",
                        "sub_tree": [
                            {
                                "expr_type": "const",
                                "base_expr": "50"
                            }
                        ]
                    },
                    {
                        "expr_type": "reserved",
                        "base_expr": "NOT"
                    },
                    {
                        "expr_type": "reserved",
                        "base_expr": "NULL"
                    }
                ],
                "unique": false,
                "nullable": false,
                "auto_inc": false,
                "primary": false
            }
        ]
    }';

    // Add the 'tenant_id' column to the parsed SQL structure
    $parsed['TABLE']['create-def']['sub_tree'][] = json_decode($newCol, true);

    // Generate the modified SQL statement
    $creator = new PHPSQLCreator($parsed);
    $newSql = $creator->created;

    return $newSql;
}




/**##################################################################################################################***
 *                                                                                                                      *
 *                                               Database locator and DSN                                               *
 *                                                                                                                      *
 ***##################################################################################################################**/


/**
 * Convert an array DSN to a string representation.
 * 
 * @param array $dsn The array DSN containing driver, host, dbname, user, and password.
 * @param bool $with_auth Whether to include authentication details in the DSN.
 * @return string The DSN string representation.
 */
function perfex_saas_dsn_to_string(array $dsn, $with_auth = true)
{
    // Extract the individual components from the DSN array
    $driver = $dsn['driver'] ?? APP_DB_DRIVER;
    $host = $dsn['host'] ?? APP_DB_HOSTNAME_DEFAULT;
    $dbname = $dsn['dbname'] ?? '';
    $user = $dsn['user'] ?? '';
    $password = $dsn['password'] ?? '';

    // Build the basic DSN string
    $dsn_string = $driver . ':host=' . $host . ';dbname=' . $dbname;

    // If 'with_auth' is false, return the basic DSN string without authentication details
    if (!$with_auth) {
        return $dsn_string;
    }

    // Append the authentication details to the DSN string
    $dsn_string = $dsn_string . ';user=' . $user . ';password=' . $password . ';';

    return $dsn_string;
}



/**
 * Parse a DSN string and return the parsed components.
 *
 * Example dsn string: mysql:host=127.0.0.1;dbname=demodb;user=demouser;password=diewo;eg@j$l!;
 * DSN should follow above pattern and should ends with ";".
 * 
 * @param string $dsn The DSN string to parse.
 * @param array $returnKeys The specific keys to return from the parsed DSN.
 * @return array The parsed DSN components.
 * @throws Exception When the DSN string is empty or invalid.
 */
function perfex_saas_parse_dsn($dsn, $returnKeys = [])
{
    // Define the default indexes for parsing
    $indexes = ['host', 'dbname', 'user', 'password'];

    // Check if specific keys are requested for return
    $returnSet = is_array($returnKeys) && !empty($returnKeys);
    if ($returnSet) {
        $indexes = $returnKeys;
    }

    // Check if the DSN string is empty or invalid
    if (empty($dsn) || (false === ($pos = stripos($dsn, ":")))) {
        $error = "Empty or Invalid DSN string";
        log_message("error", "$error: $dsn");
        throw new Exception($error);
    }

    // Extract the driver from the DSN string
    $driver = strtolower(substr($dsn, 0, $pos)); // always returns a string

    // Check if the driver is empty
    if (empty($driver)) {
        throw new Exception(_l("perfex_saas_invalid_dsn_no_driver"));
    }

    // Initialize the parsed DSN array with the driver
    $parsedDsn = ['driver' => $driver];

    // Define the keys used for mapping and their order in the DSN string
    $mapKeys = [':host=', ';dbname=', ';user=', ';password='];

    // Iterate through the map keys to extract values from the DSN string
    foreach ($mapKeys as $i => $key) {
        $position = stripos($dsn, $key);
        $nextPosition = ($i + 1) >= count($mapKeys) ? stripos($dsn, ';', -1) : stripos($dsn, $mapKeys[$i + 1]);

        // Get the length of the value using the next position minus the key position
        $valueLength = $nextPosition - $position;
        $value = substr($dsn, $position, $valueLength);

        // Remove the key from the captured value
        $value = str_ireplace($key, '', $value);

        // Clean the DSN key
        $key = str_ireplace([':', '=', ';'], '', $key);

        $parsedDsn[$key] = $value;
    }

    // Set the return value based on the requested keys
    $r = $parsedDsn;

    if ($returnSet) {
        $r = [];
        foreach ($indexes as $key) {
            // Check if the parsed DSN contains the requested key
            if (!isset($parsedDsn[$key])) {
                throw new RuntimeException(_l('perfex_saas_dsn_missing_key', $key));
            }

            $r[$key] = $parsedDsn[$key];
        }
    }

    return $r;
}

/**
 * Check if a DSN is valid by testing the database connection.
 *
 * @param array $dsn The DSN array to validate.
 * @param bool $use_cache Flag to indicate whether to use the cached connection.
 * @return bool|string Returns true if the DSN is valid, otherwise returns an error message.
 */
function perfex_saas_is_valid_dsn(array $dsn, $use_cache = true)
{
    try {
        // Check if the required DSN components (host, user, dbname) are present
        if (empty($dsn['host'] ?? '') || empty($dsn['user'] ?? '') || empty($dsn['dbname'] ?? '')) {
            throw new \Exception(_l('perfex_saas_host__user_and_dbname_is_required_for_valid_dsn'), 1);
        }

        // Test the database connection
        $conn = perfex_saas_get_pdo_conn($dsn, $use_cache);

        if (!$conn) {
            throw new \Exception("Error establishing connection", 1);
        }

        return true;
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
}

/**
 * Get a PDO database connection based on the provided DSN.
 *
 * @param array $dsn The DSN array containing driver, host, dbname, user, and password.
 * @param bool $use_cache Flag to indicate whether to use the cached connection.
 * @return PDO The PDO database connection.
 */
function perfex_saas_get_pdo_conn($dsn, $use_cache = true)
{
    // PDO uses 'mysql' instead of 'mysqli'
    if (!isset($dsn['driver']) || (isset($dsn['driver']) && $dsn['driver'] == 'mysqli')) {
        $dsn['driver'] = 'mysql';
    }

    $dsn_string = perfex_saas_dsn_to_string($dsn, false);

    $cached = isset($GLOBALS[$dsn_string]);

    if ($cached && $use_cache) {
        $pdo = $GLOBALS[$dsn_string];
        if ($pdo instanceof PDO) {
            return $pdo;
        }
    }

    $pdo = new PDO(
        $dsn_string,
        $dsn['user'],
        $dsn['password'],
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    $GLOBALS[$dsn_string] = $pdo;
    return $pdo;
}




/**##################################################################################################################/**
 *                                                                                                                      *
 *                                                    UI and Http helpers                                               *
 *                                                                                                                      *
 ***##################################################################################################################**/

/**
 * Show a custom error page for the tenant (middleware).
 *
 * @param string $heading The heading of the error page.
 * @param string $message The error message to display.
 * @param int $error_code The error code to display (default: 403).
 * @param string $template The error template file to use (default: '404').
 */
function perfex_saas_show_tenant_error($heading, $message, $error_code = 403, $template = '404')
{
    $error_file = APPPATH . 'views/errors/html/error_' . $template . '.php';

    $message = "
        $message 
        <script>
            let tag = document.querySelector('h2');
            tag.innerHTML = tag.innerHTML.replace('404', '$error_code');
        </script>
    ";

    if (file_exists($error_file)) {
        require_once($error_file);
        exit();
    }

    echo ($heading . '<br/><br/>' . $message);
    exit();
}




/**
 * Generate the URL signature for the tenant.
 *
 * @param string $slug The slug of the tenant.
 * @return string The URL signature.
 */
function perfex_saas_tenant_url_signature($slug)
{
    $path_prefix = PERFEX_SAAS_ROUTE_ID;
    return "$slug/$path_prefix";
}




/**
 * Get the URL origin based on the server variables.
 *
 * @param array $server The server variables.
 * @param bool $use_forwarded_host Whether to use the forwarded host.
 * @return string The URL origin.
 */
function perfex_saas_url_origin($server, $use_forwarded_host = true)
{
    $ssl = (!empty($server['HTTPS']) && $server['HTTPS'] == 'on');
    $sp = strtolower($server['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $server['SERVER_PORT'];
    $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
    $host = ($use_forwarded_host && isset($server['HTTP_X_FORWARDED_HOST'])) ? $server['HTTP_X_FORWARDED_HOST'] : (isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $server['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

/**
 * Get the full URL based on the server variables.
 *
 * @param array $server The server variables.
 * @param bool $use_forwarded_host Whether to use the forwarded host.
 * @return string The full URL.
 */
function perfex_saas_full_url($server, $use_forwarded_host = true)
{
    $url_origin = perfex_saas_url_origin($server, $use_forwarded_host);
    $request_uri = $server['REQUEST_URI'];
    return $url_origin . $request_uri;
}

/**
 * Redirect the user back to the previous page or a default page.
 */
function perfex_saas_redirect_back()
{
    if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
        redirect($_SERVER['HTTP_REFERER']);
    } else {
        // If HTTP_REFERER is not set or empty, redirect to a default page
        redirect(admin_url(PERFEX_SAAS_MODULE_NAME));
    }
}


/**
 * Perform an HTTP request using cURL.
 *
 * @param string $url     The URL to send the request to.
 * @param array  $options An array of options for the request.
 *
 * @return array An array containing the 'error' and 'response' from the request.
 */
function perfex_saas_http_request($url, $options)
{
    // Initialize cURL
    $curl = curl_init($url);

    // Set SSL verification and timeout options
    $verify_ssl = (int) ($options['sslverify'] ?? 0);
    $timeout = (int) ($options['timeout'] ?? 30);

    if ($options) {
        // Get request method
        $method = strtoupper($options["method"] ?? "GET");

        // Get request data and headers
        $data = @$options["data"];
        $headers = (array) @$options["headers"];

        // Set JSON data and headers for POST requests
        if ($method === "POST") {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        // Set custom headers if provided
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
    }

    // Set common cURL options
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYHOST => $verify_ssl,
        CURLOPT_TIMEOUT => (int) $timeout,
    ]);

    // Make the request
    $result = curl_exec($curl);

    // Check for errors
    $error = '';
    if (!$curl || !$result) {
        $error = 'Curl Error - "' . curl_error($curl) . '" - Code: ' . curl_errno($curl);
    }

    // Close the cURL session
    curl_close($curl);

    return ['error' => $error, 'response' => $result];
}

/**
 * Get the list of modules available to the tenant.
 *
 * @param object|null $tenant                       The tenant object. If null, the current tenant will be used.
 * @param bool        $include_saas_module           Whether to include the SAAS module in the list.
 * @param bool        $include_tenant_disabled_modules Whether to include tenant-disabled modules.
 * @param bool        $include_admin_disabled_modules  Whether to include admin-disabled modules.
 *
 * @return array The list of tenant modules.
 */
function perfex_saas_tenant_modules(
    object $tenant = null,
    bool $include_saas_module = true,
    bool $include_tenant_disabled_modules = false,
    bool $include_admin_disabled_modules = false
) {
    // Get the tenant object
    $tenant = $tenant ?? perfex_saas_tenant();

    // Get the package and modules
    $package = isset($tenant->package_invoice) ? $tenant->package_invoice : null;
    $modules = (array) ($package->modules ?? []);

    // Get the metadata and approved/disabled modules
    $metadata = (object) $tenant->metadata;
    $admin_approved_modules = isset($metadata->admin_approved_modules) ? (array) $metadata->admin_approved_modules : [];
    $admin_disabled_modules = isset($metadata->admin_disabled_modules) ? (array) $metadata->admin_disabled_modules : [];
    $disabled_modules = isset($metadata->disabled_modules) ? (array) $metadata->disabled_modules : [];


    // Merge package modules and admin-approved modules
    $tenant_modules = array_merge($modules, $admin_approved_modules);

    // Include SAAS module if required
    if ($include_saas_module) {
        $tenant_modules[] = PERFEX_SAAS_MODULE_NAME;
    }

    // Make the package and assigned modules unique
    $tenant_modules = array_unique($tenant_modules);

    // Remove disabled modules if not included
    if (!$include_tenant_disabled_modules) {
        $tenant_modules = array_diff($tenant_modules, $disabled_modules);
    }

    // Remove admin-disabled modules if not included
    if (!$include_admin_disabled_modules) {
        $tenant_modules = array_diff($tenant_modules, $admin_disabled_modules);
    }

    return (array) $tenant_modules;
}
