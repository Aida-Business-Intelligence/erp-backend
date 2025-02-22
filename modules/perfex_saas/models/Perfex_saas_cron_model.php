<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Perfex_saas_cron_model extends App_model
{
    /**
     * Timeout limit in seconds
     *
     * @var integer
     */
    private $available_execution_time = 25;

    /**
     * Monitor of used seconds
     *
     * @var integer
     */
    private $start_time;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        $max_time = (int)ini_get("max_execution_time");
        if ($max_time <= 0)
            $max_time = 60 * 60; //1 hour;

        $this->available_execution_time = $max_time - 5; //minus 5 seconds for cleanup
        $this->start_time = time();
    }


    /**
     * Run saas module maintenance routines.
     * 
     * This exceute patch for master and child instances where neccessary and handle deploy of new instances.
     *
     * @param object $cron_cache
     * @return object
     */
    public function saas_cron($companies)
    {

        // Run migration patch
        try {

            // General repair and patch on the master db incase of new modules installation with db changes
            perfex_saas_setup_master_db(true);

            // Get all companies and filter for those hosted in another DB from master i.e single db single tenant
            $dsn_list = [];
            foreach ($companies as $key => $company) {

                $dsn = perfex_saas_get_company_dsn($company);

                //skip companies running on same db as master
                if ($dsn['host'] == APP_DB_HOSTNAME_DEFAULT && $dsn['dbname'] == APP_DB_NAME_DEFAULT) {
                    continue;
                }

                $dsn_list[$key] = $dsn;
            }

            if (!empty($dsn_list)) {

                // Check last index and see if it requires migration. If it does, probably all others does
                $last_index = array_key_last($dsn_list);
                $last_company_dsn = $dsn_list[$last_index];
                $sql_commands_to_run = $this->perfex_saas_migration_model->migrations($last_company_dsn);

                if (is_array($sql_commands_to_run) && !empty($sql_commands_to_run)) {

                    // Run DB patch for all.
                    foreach ($dsn_list as $dsn) {

                        //run migration patch
                        $this->perfex_saas_migration_model->run($dsn);
                    }
                }
            }
        } catch (\Throwable $th) {

            log_message('error', $th->getMessage());
        }


        // Run deployment of new instances
        perfex_saas_deployer();
    }

    /**
     * Run cron for all tenants.
     * 
     * It uses Timeouter to detect timeout and return last processed id
     *
     * @param integer $start_from_id    The company id to start from.
     * @return integer The last processed company id
     */
    public function tenants_cron($companies)
    {
        $this->load->library(PERFEX_SAAS_MODULE_NAME . '/Timeouter');

        // Get all instance and run cron
        foreach ($companies as $company) {

            $time_elapsed = (time() - $this->start_time);

            try {

                // Start timeout
                Timeouter::limit($this->available_execution_time - $time_elapsed, 'Time out.');

                declare(ticks=1) {

                    try {

                        $url = perfex_saas_tenant_base_url($company, 'cron/index');

                        // Simulate cron command: wget -q -O- http://saasdomain.com/demoinstance/ps/cron/index
                        $cron_result = perfex_saas_http_request($url, ['timeout' => 20]);
                        if (!$cron_result || !empty($cron_result['error'])) {

                            log_message("Error", "Cron: Error running cron on $url :" . $cron_result['error']);
                        }
                    } catch (\Exception $e) {
                        log_message('error', "Cron job failure for $company->slug :" . $e->getMessage());
                    }
                }

                Timeouter::end();
            } catch (\Exception $e) {

                Timeouter::end();
                return $company->id;
            }
        }

        return 0;
    }
}
