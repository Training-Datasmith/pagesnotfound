<?php

if (!defined('_PS_VERSION_')) {
    define('_PS_VERSION_', '1.7.7.8');
}
if (!defined('_DB_PREFIX_')) {
    define('_DB_PREFIX_', 'ps_');
}
if (!defined('_MYSQL_ENGINE_')) {
    define('_MYSQL_ENGINE_', 'InnoDB');
}
if (!defined('_PS_USE_SQL_SLAVE_')) {
    define('_PS_USE_SQL_SLAVE_', false);
}
if (!defined('_PS_MAGIC_QUOTES_GPC_')) {
    define('_PS_MAGIC_QUOTES_GPC_', false);
}
if (!defined('__PS_BASE_URI__')) {
    define('__PS_BASE_URI__', '/shop/');
}

$rootDir = sys_get_temp_dir() . '/pnf_ps_root';
if (!is_dir($rootDir)) {
    mkdir($rootDir, 0777, true);
}
if (!defined('_PS_ROOT_DIR_')) {
    define('_PS_ROOT_DIR_', $rootDir);
}

require_once dirname(__DIR__) . '/tests/PrestaShopHarness.php';
require_once dirname(__DIR__) . '/tests/Support/PagesNotFoundTestCase.php';

require_once dirname(__DIR__) . '/upgrade/upgrade-2.0.1.php';
require_once dirname(__DIR__) . '/upgrade/upgrade-2.0.2.php';
require_once dirname(__DIR__) . '/pagesnotfound.php';
