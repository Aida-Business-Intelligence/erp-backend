<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<script>
    "use strict";

    const PERFEX_SAAS_MODULE_NAME = '<?= PERFEX_SAAS_MODULE_NAME ?>';
    const PERFEX_SAAS_FILTER_TAG = '<?= PERFEX_SAAS_FILTER_TAG; ?>';
    const PERFEX_SAAS_IS_TENANT = <?= perfex_saas_is_tenant() ? 'true' : 'false'; ?>;
    const PERFEX_SAAS_ENFORCED_SHARED_FIELDS = <?= json_encode(PERFEX_SAAS_ENFORCED_SHARED_FIELDS); ?>;
    const PERFEX_SAAS_IFRAME_MODE = window.self !== window.top;
</script>

<?php
// @todo Only load this when using preview iframe. Find way to detect iframe and react.
if (perfex_saas_is_tenant()) : ?>
    <!-- Add NProgress to spice loading in iframe-->
    <script src='https://unpkg.com/nprogress@0.2.0/nprogress.js'></script>
    <link rel='stylesheet' href='https://unpkg.com/nprogress@0.2.0/nprogress.css' />
<?php endif; ?>

<!-- Module custom admin script -->
<script src="<?= module_dir_url(PERFEX_SAAS_MODULE_NAME, 'assets/js/admin.js'); ?>"></script>