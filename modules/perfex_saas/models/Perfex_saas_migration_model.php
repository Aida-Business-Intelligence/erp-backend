<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * This class describes a migration model.
 */
class Perfex_saas_migration_model extends App_Model
{

    /** @var object */
    private $MASTER_DB;

    /** @var object */
    private $SLAVE_DB;

    /** @var string */
    protected $CHARACTER_SET;

    /**
     * @inheritDoc
     */
    function __construct()
    {
        parent::__construct();
        $this->CHARACTER_SET = $this->db->char_set ?? "utf8 COLLATE utf8_general_ci";
    }

    function loadDB(array $dsn)
    {

        $base_config = [
            'dbdriver'     => APP_DB_DRIVER,
            'dbprefix'     => db_prefix(),
            'char_set'     => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8',
            'dbcollat'     => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8_general_ci',
        ];

        $config = array_merge($base_config, [
            'hostname'     => $dsn['host'],
            'username'     => $dsn['user'],
            'password'     => $dsn['password'],
            'database'     => $dsn['dbname']
        ]);

        return $this->load->database($config, TRUE);
    }

    function prepareDB($slaveDsn, $masterDsn = [])
    {

        $this->MASTER_DB = $this->db; // load the source/default database

        if (!empty($masterDsn)) {

            $this->MASTER_DB = $this->loadDB($masterDsn);
        }

        $this->SLAVE_DB = $this->loadDB($slaveDsn); // load the slave database
    }

    /**
     * Manage tables, create or drop them
     * @param array $tables
     * @param string $action
     * @return array $sql_commands_to_run
     */
    function manage_tables($tables, $action)
    {
        $sql_commands_to_run = array();

        if ($action == 'create') {
            foreach ($tables as $table) {
                $query = $this->MASTER_DB->query("SHOW CREATE TABLE `$table` -- create tables");
                $table_structure = $query->row_array();
                $sql_commands_to_run[] = $table_structure["Create Table"] . ";";
            }
        }

        if ($action == 'drop') {
            foreach ($tables as $table) {
                $sql_commands_to_run[] = "DROP TABLE $table;";
            }
        }

        return $sql_commands_to_run;
    }

    /**
     * Go through each table, compare their sql structure
     * @param array $default_tables The list of the default master tables
     * @param array $instance_tables The list of the instance tables
     */
    function compare_table_structures($default_tables, $instance_tables)
    {
        $tables_need_updating = array();

        $instance_table_structures = $default_table_structures = array();

        /*
         * generate the sql for each table in the default database
         */
        foreach ($default_tables as $table) {
            $query = $this->MASTER_DB->query("SHOW CREATE TABLE `$table` -- dev");
            $table_structure = $query->row_array();
            $sql = $table_structure["Create Table"];
            //@clean-query
            $sql = str_ireplace(["DEFAULT '0000-00-00 00:00:00'", 'DEFAULT "0000-00-00 00:00:00"'], 'DEFAULT CURRENT_TIMESTAMP', $sql);
            $default_table_structures[$table] = $sql;
        }

        /*
         * generate the sql for each table in the instance database
         */
        foreach ($instance_tables as $table) {
            $query = $this->SLAVE_DB->query("SHOW CREATE TABLE `$table` -- instance");
            $table_structure = $query->row_array();
            $sql = $table_structure["Create Table"];
            //@clean-query
            $sql = str_ireplace(["DEFAULT '0000-00-00 00:00:00'", 'DEFAULT "0000-00-00 00:00:00"'], 'DEFAULT CURRENT_TIMESTAMP', $sql);
            $instance_table_structures[$table] = $sql;
        }

        /*
         * compare the default sql to the instance sql
         */
        foreach ($default_tables as $table) {
            $default_table = $default_table_structures[$table];
            $instance_table = (isset($instance_table_structures[$table])) ? $instance_table_structures[$table] : '';

            if ($this->count_differences($default_table, $instance_table) > 0) {
                $tables_need_updating[] = $table;
            }
        }
        return $tables_need_updating;
    }

    /**
     * Count differences in 2 sql statements
     * @param string $old
     * @param string $new
     * @return int $differences
     */
    function count_differences($old, $new)
    {
        $differences = 0;
        $old = trim(preg_replace('/\s+/', '', $old));
        $new = trim(preg_replace('/\s+/', '', $new));

        if ($old == $new) {
            return $differences;
        }

        $old = explode(" ", $old);
        $new = explode(" ", $new);
        $length = max(count($old), count($new));

        for ($i = 0; $i < $length; $i++) {
            if ($old[$i] != $new[$i]) {
                $differences++;
            }
        }

        return $differences;
    }

    /**
     * Given an array of tables that differ from MASTER_DB to SLAVE_DB, update SLAVE_DB
     * @param array $tables
     */
    function update_existing_tables($tables)
    {
        $sql_commands_to_run = array();
        $table_structure_default = array();
        $table_structure_instance = array();

        if (is_array($tables) && !empty($tables)) {
            foreach ($tables as $table) {
                $table_structure_default[$table] = $this->table_field_data($this->MASTER_DB, $table);
                $table_structure_instance[$table] = $this->table_field_data($this->SLAVE_DB, $table);
            }
        }

        /*
         * add, remove or update any fields in $table_structure_instance
         */
        $sql_commands_to_run = array_merge($sql_commands_to_run, $this->determine_field_changes($table_structure_default, $table_structure_instance));

        return $sql_commands_to_run;
    }

    /**
     * Given a database and a table, compile an array of field meta data
     * @param DB $database
     * @param string $table
     * @return array $fields
     */
    function table_field_data($database, $table)
    {
        $fields = $database->query("SHOW COLUMNS FROM `$table`")->result_array();
        return $fields;
    }

    /**
     * Given to arrays of table fields, add/edit/remove fields
     * @param type $source_field_structures
     * @param type $destination_field_structures
     */
    function determine_field_changes($source_field_structures, $destination_field_structures)
    {
        $sql_commands_to_run = array();

        /**
         * loop through the source (usually default) database
         */
        foreach ($source_field_structures as $table => $fields) {
            foreach ($fields as $field) {
                if ($this->in_array_recursive($field["Field"], $destination_field_structures[$table])) {
                    $modify_field = '';
                    /*
                     * Check for required modifications
                     */
                    for ($n = 0; $n < count($fields); $n++) {
                        if (isset($fields[$n]) && isset($destination_field_structures[$table][$n]) && ($fields[$n]["Field"] == $destination_field_structures[$table][$n]["Field"])) {
                            $differences = array_diff($fields[$n], $destination_field_structures[$table][$n]);

                            if (is_array($differences) && !empty($differences)) {
                                $modify_field = "ALTER TABLE $table MODIFY COLUMN `" . $fields[$n]["Field"] . "` " . $fields[$n]["Type"];

                                if (strpos(strtoupper($fields[$n]["Type"]), 'VARCHAR') !== false && strpos(strtoupper($fields[$n]["Type"]), 'TEXT') !== false)
                                    $modify_field .= ' CHARACTER SET ' . $this->CHARACTER_SET;

                                if (!(in_array(strtoupper($fields[$n]["Type"]), ['BLOB', 'TEXT', 'GEOMETRY', 'JSON'])))
                                    $modify_field .= (isset($fields[$n]["Default"]) && $fields[$n]["Default"] != '') ? ' DEFAULT \'' . $fields[$n]["Default"] . '\'' : '';

                                $modify_field .= (isset($fields[$n]["Null"]) && $fields[$n]["Null"] == 'YES') ? ' NULL' : ' NOT NULL';
                                $modify_field .= (isset($fields[$n]["Extra"]) && $fields[$n]["Extra"] != '') ? ' ' . $fields[$n]["Extra"] : '';
                                $modify_field .= (isset($previous_field) && $previous_field != '') ? ' AFTER `' . $previous_field . '`' : '';
                                $modify_field .= ';';
                            }
                            $previous_field = $fields[$n]["Field"];
                        }

                        if ($modify_field != '' && !in_array($modify_field, $sql_commands_to_run))
                            $sql_commands_to_run[] = $modify_field;
                    }
                } else {
                    /*
                     * Add 
                     */
                    $add_field = "ALTER TABLE $table ADD COLUMN `" . $field["Field"] . "` " . $field["Type"];

                    if (strpos(strtoupper($field["Type"]), 'VARCHAR') !== false && strpos(strtoupper($field["Type"]), 'TEXT') !== false)
                        $add_field .= " CHARACTER SET " . $this->CHARACTER_SET;

                    $add_field .= (isset($field["Null"]) && $field["Null"] == 'YES') ? ' Null' : '';

                    //SQLSTATE[42000]: Syntax error or access violation: 1101 BLOB, TEXT, GEOMETRY or JSON can't have a default value
                    if (!(in_array(strtoupper($field["Type"]), ['BLOB', 'TEXT', 'GEOMETRY', 'JSON'])))
                        $add_field .= is_null($field["Default"]) || (isset($field["Default"]) && trim($field["Default"]) == '') ? " DEFAULT NULL" : " DEFAULT '" . $field["Default"] . "'";

                    $add_field .= (isset($field["Extra"]) && $field["Extra"] != '') ? ' ' . $field["Extra"] : '';
                    $add_field .= ';';
                    $sql_commands_to_run[] = $add_field;
                }
            }
        }

        return $sql_commands_to_run;
    }

    /**
     * Recursive version of in_array
     * @param type $needle
     * @param type $haystack
     * @param type $strict
     * @return boolean
     */
    function in_array_recursive($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $array => $item) {
            $item = $item["Field"]; // look in the name field only
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_recursive($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }


    /**
     * Generate outstanding migration needed for the slave dsn.
     *
     * @param array $slaveDsn
     * @param array $options
     * @return array Array of sql statements need to be run to make slave have all what the master db have.
     */
    function migrations($slaveDsn, $options = [])
    {

        //get the default db and a tenant as blueprint db
        $this->prepareDB($slaveDsn);

        /** 
         * This will become a list of SQL Commands to run on the instance database to bring it up to date
         */
        $sql_commands_to_run = array();

        /** 
         * list the tables from both databases
         */
        $default_tables = $this->MASTER_DB->list_tables();
        $instance_tables = $this->SLAVE_DB->list_tables();


        //remove saas table from the list of tables to sync.
        $total_default_tables = count($default_tables);
        $total_instance_tables = count($instance_tables);
        $max_length = max($total_default_tables, $total_instance_tables);

        $saas_table_prefix = perfex_saas_table('');
        $saas_table_prefix_length = strlen($saas_table_prefix);
        for ($i = 0; $i < $max_length; $i++) {
            if ($i < $total_default_tables) {

                $table = $default_tables[$i];
                if (!str_starts_with($table, db_prefix()) || substr($table, 0, $saas_table_prefix_length) === $saas_table_prefix) {
                    unset($default_tables[$i]);
                }
            }

            if ($i < $total_instance_tables) {
                $table = $instance_tables[$i];
                if (!str_starts_with($table, db_prefix()) || substr($table, 0, $saas_table_prefix_length) === $saas_table_prefix) {
                    unset($instance_tables[$i]);
                }
            }
        }

        /** 
         * list any tables that need to be created or dropped
         */
        $tables_to_create = array_diff($default_tables, $instance_tables);
        $tables_to_drop = array_diff($instance_tables, $default_tables);

        /**
         * Create/Drop any tables that are not in the sample instance database
         */
        $sql_commands_to_run = (is_array($tables_to_create) && !empty($tables_to_create)) ? array_merge($sql_commands_to_run, $this->manage_tables($tables_to_create, 'create')) : $sql_commands_to_run;

        if (isset($options['allowTableDrop'])  && $options['allowTableDrop'] === true)
            $sql_commands_to_run = (is_array($tables_to_drop) && !empty($tables_to_drop)) ? array_merge($sql_commands_to_run, $this->manage_tables($tables_to_drop, 'drop')) : $sql_commands_to_run;

        $tables_to_update = $this->compare_table_structures($default_tables, $instance_tables);

        /**
         * Before comparing tables, remove any tables from the list that will be created in the $tables_to_create array
         */
        $tables_to_update = array_diff($tables_to_update, $tables_to_create);

        /**
         * update tables, add/update/emove columns
         */
        $sql_commands_to_run = (is_array($tables_to_update) && !empty($tables_to_update)) ? array_merge($sql_commands_to_run, $this->update_existing_tables($tables_to_update)) : $sql_commands_to_run;

        return $sql_commands_to_run;
    }

    /**
     * Generate and run sql migration queries for the dsn.
     *
     * @param array $dsn
     * @param array $options
     * @param array $sql_commands_to_run
     * @return mixed
     */
    function run($dsn, $options = ['allowTableDrop' => false], $sql_commands_to_run = [])
    {
        if (empty($sql_commands_to_run))
            $sql_commands_to_run = $this->migrations($dsn, $options);

        if (is_array($sql_commands_to_run) && !empty($sql_commands_to_run)) {

            return perfex_saas_raw_query($sql_commands_to_run, $dsn, false, true, null, true);
        }

        return false;
    }
}
