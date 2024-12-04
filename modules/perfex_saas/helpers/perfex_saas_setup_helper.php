<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Generates a regular expression pattern to match the signature for requiring a file.
 *
 * The signature pattern is in the format:
 *     #//perfex-saas:start:<filename>([\s\S]*)//perfex-saas:end:<filename>#
 * where <filename> is the basename of the file.
 *
 * @param string $file The path to the file.
 *
 * @return string The regular expression pattern for the file signature.
 */
function perfex_saas_require_signature($file)
{
    $basename = basename($file);
    return '#//perfex-saas:start:' . $basename . '([\s\S]*)//perfex-saas:end:' . $basename . '#';
}

/**
 * Generates the template for requiring a file in Perfex SAAS.
 *
 * This function generates the template for requiring a file in Perfex SAAS. The template includes comments that mark
 * the start and end of the required file. The template is in the following format:
 *     //perfex-saas:start:#filename
 *     //dont remove/change above line
 *     require_once('#path');
 *     //dont remove/change below line
 *     //perfex-saas:end:#filename
 * where #filename is replaced with the basename of the file, and #path is replaced with the actual path to the file.
 *
 * @param string $path The path to the file.
 *
 * @return string The template for requiring the file.
 */
function perfex_saas_require_in_file_template($path)
{
    $template = "//perfex-saas:start:#filename\n//dont remove/change above line\nrequire_once('#path');\n//dont remove/change below line\n//perfex-saas:end:#filename";

    $template = str_ireplace('#filename', basename($path), $template);
    $template = str_ireplace('#path', $path, $template);
    return $template;
}

/**
 * Writes content to a file.
 *
 * It sets the appropriate file permissions, opens the file,
 * writes the content, and closes the file.
 *
 * @param string $path    The path to the file.
 * @param string $content The content to write to the file.
 *
 * @return bool True if the write operation was successful, false otherwise.
 */
function perfex_saas_file_put_contents($path, $content)
{
    @chmod($path, FILE_WRITE_MODE);
    if (!$fp = fopen($path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
        return false;
    }
    flock($fp, LOCK_EX);
    fwrite($fp, $content, strlen($content));
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($path, FILE_READ_MODE);
    return true;
}

/**
 * Requires a file into another file.
 *
 * The function uses a template to generate the require statement and inserts it at the specified
 * position in the destination file. If no position is specified, the require statement is appended to the end of the
 * file.
 *
 * @param string  $dest        The path to the destination file.
 * @param string  $requirePath The path to the file to require.
 * @param bool    $force       Whether to force the insertion even if it already exists.
 * @param int|bool $position    The position to insert the require statement. False to append to the end of the file.
 *
 * @return void
 */
function perfex_saas_require_in_file($dest, $requirePath, $force = false, $position = false)
{
    if (!file_exists($dest)) {
        perfex_saas_file_put_contents($dest, "<?php defined('BASEPATH') or exit('No direct script access allowed');\n");
    }

    if (file_exists($dest) && file_exists($requirePath)) {
        $content = file_get_contents($dest);  // Fetch the content inside the file

        $template = perfex_saas_require_in_file_template($requirePath);

        $exist = preg_match(perfex_saas_require_signature($requirePath), $content);
        if ($exist && !$force) { // Check if this process has run once or not
            return;
        }
        $content = perfex_saas_unrequire_in_file($dest, $requirePath);

        if ($position !== false) {
            $content = substr_replace($content, $template . "\n", $position, 0);
        } else {
            $content = $content . $template;
        }

        perfex_saas_file_put_contents($dest, $content);
    }
}

/**
 * Removes the require statement of a file.
 *
 * This function removes the require statement from a file in Perfex SAAS.
 * It fetches the content inside the destination file, replaces the require statement with an
 * empty string using a regular expression, and then updates the destination file with the modified content.
 *
 * @param string $dest        The path to the destination file.
 * @param string $requirePath The path to the file to be removed from the require statement.
 *
 * @return string The modified content of the destination file.
 */
function perfex_saas_unrequire_in_file($dest, $requirePath)
{
    if (file_exists($dest) && file_exists($requirePath)) {
        $content = file_get_contents($dest);  // Fetch the content inside the file
        $content = preg_replace(perfex_saas_require_signature($requirePath), '', $content);
        perfex_saas_file_put_contents($dest, $content);
        return $content;
    }
}

/**
 * Hooks the DB driver method in Codeigniter.
 *
 * This function hooks the DB driver method in Perfex SAAS by modifying a core framework file. 
 * It allows for custom handling of SQL queries within the SAAS environment. 
 * We acknowledge that modifying core framework files should be done with caution and is an advanced customization option.
 * However, its essential in this case to ensuring safety and control of malacious query in SAAS environment and especially for 
 * the multitenancy DB scheme.
 *
 * @param bool $forward Indicates whether to perform the forward hook or the reverse hook.
 *
 * @return void
 *
 * @throws Exception If an error occurs during the modification process.
 * @todo Conduct more research on better solution for multitenancy scheme
 */
function perfex_saas_db_driver_hook($forward)
{
    $path = BASEPATH . 'database/DB_driver.php';
    $find = '$this->_execute($sql)';
    $replace = '$this->_execute(perfex_saas_db_query($sql))';

    try {
        // Perform the modification based on the `$forward` parameter
        if ($forward) {
            replace_in_file($path, $find, $replace);
        } else {
            replace_in_file($path, $replace, $find);
        }
    } catch (Exception $e) {
        throw new Exception('Error modifying DB driver method: ' . $e->getMessage());
    }
}

/**
 * Hooks the configuration constants in Perfex SAAS.
 *
 * This function hooks the configuration constants in Perfex SAAS. It takes a boolean parameter `$forward` to determine
 * the direction of the hook. If `$forward` is true, it replaces the original constants with their corresponding
 * "_DEFAULT" versions in the specified configuration file. If `$forward` is false, it reverts the replacements by
 * replacing the "_DEFAULT" constants with the original ones.
 *
 * @param bool $forward Indicates whether to perform the forward hook or the reverse hook.
 *
 * @return void
 */
function perfex_saas_config_constant_hook($forward)
{
    $path = APPPATH . 'config/app-config.php';

    $constants_to_override = ['APP_BASE_URL', 'APP_DB_HOSTNAME', 'APP_DB_USERNAME', 'APP_DB_PASSWORD', 'APP_DB_NAME'];
    foreach ($constants_to_override as $key) {
        $find = "'$key'";
        $replace = "'$key" . "_DEFAULT'";
        if ($forward) {
            replace_in_file($path, $find, $replace);
        } else {
            replace_in_file($path, $replace, $find);
        }
    }
}


/**
 * Setups the master database for Perfex SAAS.
 *
 * This function is responsible for setting up the master database for Perfex SAAS. It performs the following actions:
 * - Retrieves the list of tables in the database.
 * - Constructs queries to add or remove the Perfex SAAS tenant column in the tables.
 * - Executes the queries to alter the tables accordingly.
 * - Optionally executes a callback function on the queries before executing them.
 * - Optionally wipes all SAAS databases when performing a backward setup.
 *
 * @param bool    $forward         Determines if the setup is forward or backward.
 * @param bool    $return_queries  Determines if the queries should be returned instead of executed.
 * @param Closure $callback        Optional callback function to modify the queries before execution.
 *
 * @return void|array  If $return_queries is true, an array of queries is returned; otherwise, void is returned.
 */
function perfex_saas_setup_master_db($forward, $return_queries = false, $callback = null)
{
    $CI = &get_instance();
    $db = $CI->db;
    $tables = $db->list_tables();
    $saas_table_prefix = perfex_saas_table('');
    $saas_table_prefix_length = strlen($saas_table_prefix);
    $queries = [];

    $valid_callback = is_callable($callback);

    foreach ($tables as $table) {
        // Exempt SAAS app tables if any
        if (substr($table, 0, $saas_table_prefix_length) === $saas_table_prefix) {
            continue;
        }

        // Exempt tables not starting with the database prefix (e.g., 'tbl')
        if (substr($table, 0, strlen(db_prefix())) !== db_prefix()) {
            continue;
        }

        $column_exist = $db->field_exists(PERFEX_SAAS_TENANT_COLUMN, $table);
        if ($forward) {
            if (!$column_exist) {
                $queries[] = 'ALTER TABLE `' . $table . '` ADD `' . PERFEX_SAAS_TENANT_COLUMN . "` VARCHAR(50) NOT NULL DEFAULT '" . perfex_saas_master_tenant_slug() . "'";
            }
        } else {
            if ($column_exist) {
                $queries[] = 'DELETE  FROM `' . $table . '` WHERE `' . PERFEX_SAAS_TENANT_COLUMN . "` != '" . perfex_saas_master_tenant_slug() . "' ";
                $queries[] = 'ALTER TABLE `' . $table . '` DROP `' . PERFEX_SAAS_TENANT_COLUMN . '`;';
            }
        }

        if ($valid_callback) {
            $queries = call_user_func($callback, $queries, $table);
        }
    }

    if ($forward === false) {
        // Get all SAAS databases and wipe them
        $ci = &get_instance();
        $ci->load->dbutil();
        $dbs = $ci->dbutil->list_databases();
        if (!empty($dbs)) {
            $tenants = perfex_saas_raw_query('SELECT * from ' . perfex_saas_table('companies'), [], true);
            foreach ($tenants as $tenant) {
                $db_name = perfex_saas_db($tenant->slug);
                if (in_array($db_name, $dbs)) {
                    // Drop the database
                    $queries[] = 'DROP DATABASE `' . $db_name . '`';
                }
            }
        }
    }

    if ($return_queries) {
        return $queries;
    }

    if (!empty($queries)) {
        perfex_saas_raw_query($queries, [], false, false, null, true, false);
    }
}

/**
 * Installs Perfex SAAS.
 *
 * The function setup saas for any perfex installation in a way that does not block/breack future updates
 * from perfex author by using custom files excluded in perfex updates.
 * The files are meant for customization by perfex. Exception to this is the DB driver hook. See: perfex_saas_db_driver_hook()
 * 
 * Rerunning the function after a module install or outrageous broken of SAAS after an update.
 * 
 * This function is responsible for installing Perfex SAAS. It performs the following actions:
 * - Runs the database setups for the master database.
 * - Requires the base config and middleware files and injects SAAS configurations.
 * - Requires the routes file and injects SAAS routes.
 * - Sets the driver hook for the database.
 * - Performs config constant replacements.
 *
 * @return void
 */
function perfex_saas_install()
{
    // Run database setups
    perfex_saas_setup_master_db(true);

    // Require the base config
    perfex_saas_require_in_file(APPPATH . 'config/app-config.php', module_dir_path(PERFEX_SAAS_MODULE_NAME, 'config/app-config.php'));

    // Require the SAAS routes and hooks
    perfex_saas_require_in_file(APPPATH . 'config/my_routes.php', module_dir_path(PERFEX_SAAS_MODULE_NAME, 'config/my_routes.php'));

    // Set driver hook
    perfex_saas_db_driver_hook(true);

    // Config constant replacements
    perfex_saas_config_constant_hook(true);
}


/**
 * Uninstalls Perfex SAAS.
 *
 * This function is responsible for uninstalling Perfex SAAS. It performs the following actions:
 * - Unrequires the base config and middleware files.
 * - Unrequires the routes file and removes the injected SAAS routes.
 * - Disables the database driver hook for every query if multitenant option is enabled.
 * - Removes all config constant shadwowing.
 * - Optionally, removes all data in the current active table and destroys all company databases in development mode.
 *
 * @param bool $clean (Optional) Determines whether to perform a clean uninstall by removing all data. Defaults to false.
 * @return void
 */
function perfex_saas_uninstall($clean = false)
{
    // Remove the base config and middleware
    perfex_saas_unrequire_in_file(APPPATH . 'config/app-config.php', module_dir_path(PERFEX_SAAS_MODULE_NAME, 'config/app-config.php'));

    // Remove the SAAS routes and hooks
    perfex_saas_unrequire_in_file(APPPATH . 'config/my_routes.php', module_dir_path(PERFEX_SAAS_MODULE_NAME, 'config/my_routes.php'));

    // Now set driver hook, inject database hook to every query if multitenant option is enabled
    perfex_saas_db_driver_hook(false);

    // Remove all config constant replacements
    perfex_saas_config_constant_hook(false);

    if ($clean === true) {
        // Remove all data in the current active table and destroy all created company databases (if running super DB user)
        perfex_saas_setup_master_db(false);
    }
}
