<?php
defined('BASEPATH') or exit('No direct script access allowed');

require(__DIR__ . '/../vendor/autoload.php');

use PHPSQLParser\PHPSQLParser;

/**
 * Saves the company dump seed file after successful validations.
 *
 * @param string $database_name The name of the database.
 * @param string $index_name The index name of the uploaded file.
 * @throws Exception Throws an exception if the file type is invalid, prohibited statements are detected, comments are found, or if there's an error uploading the file.
 * @return string The path of the saved file.
 */
function perfex_saas_save_company_dump_seed_file($database_name, $index_name = 'sql_file')
{
    // Validate the file type
    $allowed_extensions = ['sql'];
    $file_extension = strtolower(pathinfo($_FILES[$index_name]['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception(_l('perfex_saas_invalid_file_type_only_sql_files_are_allowed'));
    }

    $file_name = $_FILES[$index_name]['tmp_name'];

    // Sanitize the input
    $sql = file_get_contents($file_name);
    $sql = preg_replace('/(\/\*|\*\/)/', '', $sql);
    $sql = preg_replace('/\s+/', ' ', $sql);
    $sql = trim($sql);

    $prohibited_statements = ['CREATE DATABASE', 'DROP DATABASE', 'DROP TABLE', 'TRUNCATE TABLE', 'GRANT ', 'REVOKE ', PERFEX_SAAS_TENANT_COLUMN];
    foreach ($prohibited_statements as $statement) {
        if (stripos($sql, $statement) !== false) {
            throw new Exception(_l("perfex_saas_prohibited_statement_detected", $statement));
        }
    }

    // Check for the presence of comments in the SQL query
    if (preg_match('/\/\*.*\*\//', $sql) || preg_match('/--.*\n/', $sql)) {
        throw new Exception(_l('perfex_saas_comments_detected_in_sql_query'));
    }

    // Validate the SQL syntax using PHPSQLParser
    $verified_queries = perfex_saas_read_safe_sql_query_lines($file_name, $database_name);

    if (!empty($verified_queries)) {
        $path = get_upload_path_by_type('customer');
        $filename = unique_filename($path, random_int(1110, PHP_INT_MAX) . '_' . time() . $_FILES[$index_name]['name']);
        $newFilePath = $path . $filename;

        if (write_file($newFilePath, get_instance()->encryption->encrypt(json_encode($verified_queries)))) {
            return $newFilePath;
        }
    }

    throw new Exception(_l('perfex_saas_error_uploading_file'), 1);
}

/**
 * Reads safe SQL query lines from a file or an array of lines.
 *
 * @param string|array $filename The file name or an array of lines.
 * @param string $current_database_name The name of the current active database.
 * @return array An array of verified SQL query lines.
 */
function perfex_saas_read_safe_sql_query_lines($filename, $current_database_name)
{
    $lines = is_array($filename) ? $filename : file($filename);
    $templine = "";
    $queries = [];
    foreach ($lines as $line) {
        // Skip it if it's a comment
        $sub = substr($line, 0, 2);
        if ($sub == '--' || $line == '' || $sub == '/*') {
            continue;
        }

        // Add this line to the current segment
        $templine .= $line;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            //@clean-query
            $templine = str_ireplace(["DEFAULT '0000-00-00 00:00:00'", 'DEFAULT "0000-00-00 00:00:00"'], 'DEFAULT CURRENT_TIMESTAMP', $templine);

            $templine = perfex_saas_safe_query($templine, $current_database_name);

            $queries[] = $templine;

            // Reset temp variable to empty
            $templine = '';
        }
    }

    return $queries;
}

/**
 * Validates and executes a safe SQL query.
 *
 * @param string $query The SQL query to validate and execute.
 * @param string $current_database_name The name of the current active database.
 * @throws Exception Throws an exception if the query is empty, contains prohibited statements, has invalid SQL syntax, or if it's not an allowed statement.
 * @return string The validated SQL query.
 */
function perfex_saas_safe_query($query, $current_database_name)
{
    if (trim($query) == '') {
        throw new Exception(_l("perfex_saas_empty_query"));
    }

    // Check if the SQL contains any prohibited statements
    $prohibited_statements = ['CREATE DATABASE', 'DROP DATABASE', 'DROP TABLE', 'TRUNCATE TABLE', 'GRANT ', 'REVOKE ', PERFEX_SAAS_TENANT_COLUMN];
    foreach ($prohibited_statements as $statement) {
        if (stripos($query, $statement) !== false || str_starts_with(strtoupper(trim($query)), 'USE')) {
            throw new Exception(_l("perfex_saas_prohibited_statement_detected", $statement));
        }
    }

    // Validate the SQL syntax using PHPSQLParser
    $parser = new PHPSQLParser($query);
    $parsed = $parser->parsed;
    if (!$parsed) {
        throw new Exception(_l("perfex_saas_invalid_sql_syntax", substr($query, 0, 100)));
    }

    $key = strtoupper(key($parsed));

    if ($key === 'CREATE' || $key === 'ALTER') {
        // Check if the statement is creating or altering a table within the current active database
        if (isset($parsed['TABLE']) && isset($parsed['TABLE']['base_expr'])) {
            $table_name = str_replace('`', '', $parsed['TABLE']['base_expr']);
            if (strpos($table_name, '.') !== false) {
                list($db_name, $table_name) = explode('.', $table_name);
                if ($db_name != $current_database_name) {
                    throw new Exception(_l('perfex_saas_create_alter_table_statement_can_only_be_executed_within_the_current_active_database'));
                }
            }
        }
    }

    // Use a whitelist of allowed statements
    $allowed_statements = ['INSERT', 'SELECT', 'ALTER', 'CREATE'];
    if (!in_array($key, $allowed_statements)) {
        throw new Exception(_l('perfex_saas_only_insert__select__alter_and_create_statements_are_allowed', $key . ': ' . substr($query, 0, 100)));
    }

    return $query;
}


/**
 * Imports and executes the seed SQL file for a company in the Perfex SAAS module.
 *
 * @param object $company The company object.
 * @param string $dsn The database connection DSN.
 * @throws Exception Throws an exception if the seed file is empty.
 * @return void
 */
function perfex_saas_import_seed_sql_file($company, $dsn)
{
    // Read and decrypt the SQL queries from the seed file
    $queries = json_decode(get_instance()->encryption->decrypt(file_get_contents($company->metadata->sql_file)));

    if (empty($queries)) {
        throw new Exception(_l("perfex_saas_empty_seed_file", $company->name), 1);
    }

    // Get the current active database name
    $current_database = perfex_saas_raw_query_row('SELECT DATABASE()', $dsn, true);

    // Verify and sanitize the SQL queries
    $verified_queries = perfex_saas_read_safe_sql_query_lines($queries, $current_database);

    // Add transaction statements and disable foreign key checks
    $verified_queries = array_merge(['START TRANSACTION;', 'SET foreign_key_checks = 0;'], $verified_queries, ['COMMIT;', 'SET foreign_key_checks = 1;']);

    // Disable autocommit mode and execute the SQL queries within a transaction
    perfex_saas_raw_query($verified_queries, $dsn, false, true);

    // Remove the seed file
    unlink($company->metadata->sql_file);
}
