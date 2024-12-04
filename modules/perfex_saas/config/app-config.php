<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Require the root helper file.
 * This helper is independent on the CI class and early required functions by saas are loaded from here.
 */
if (!function_exists('perfex_saas_init'))
    require_once(APP_MODULES_PATH . '/perfex_saas/helpers/perfex_saas_core_helper.php');

// Init perfex saas and detect the active tenant if any
// This method call with set $_GLOBAL[PERFEX_SAAS_MODULE_NAME . '_tenant'] and can be used henceforth as session is not ready for use here.
perfex_saas_init();
