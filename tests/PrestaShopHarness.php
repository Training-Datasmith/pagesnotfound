<?php

class CharacterCleaner
{
    public function cleanNonUnicodeSupport($pattern)
    {
        if (!defined('PREG_BAD_UTF8_OFFSET')) {
            return $pattern;
        }

        return preg_replace('/\\\[px]\{[a-z]{1,2}\}|(\/[a-z]*)u([a-z]*)$/i', '$1$2', $pattern);
    }
}

class Module
{
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $context;
    public $_path = '/modules/pagesnotfound/';

    private static $parentInstallResult = true;
    private static $parentUninstallResult = true;
    private static $registerHookResult = true;
    private static $registerHookFailOn = null;
    private static $registeredHooks = [];

    public function __construct()
    {
        $this->context = Context::getContext();
    }

    public static function resetHarness(): void
    {
        self::$parentInstallResult = true;
        self::$parentUninstallResult = true;
        self::$registerHookResult = true;
        self::$registerHookFailOn = null;
        self::$registeredHooks = [];
    }

    public static function setRegisterHookFailOn($hookName): void
    {
        self::$registerHookFailOn = $hookName;
    }

    public static function setParentInstallResult(bool $value): void
    {
        self::$parentInstallResult = $value;
    }

    public static function setParentUninstallResult(bool $value): void
    {
        self::$parentUninstallResult = $value;
    }

    public static function setRegisterHookResult(bool $value): void
    {
        self::$registerHookResult = $value;
    }

    public static function getRegisteredHooks(): array
    {
        return self::$registeredHooks;
    }

    public function install()
    {
        return self::$parentInstallResult;
    }

    public function uninstall()
    {
        return self::$parentUninstallResult;
    }

    public function registerHook($hookName)
    {
        if (!self::$registerHookResult || self::$registerHookFailOn === $hookName) {
            return false;
        }
        self::$registeredHooks[] = $hookName;

        return true;
    }

    public function unregisterHook($hookName)
    {
        self::$registeredHooks[] = 'unregister:' . $hookName;

        return true;
    }

    public function trans($string, $parameters = [], $domain = null, $locale = null)
    {
        return $string;
    }
}

class Db
{
    /** @var Db|null */
    private static $instance;

    /** @var PDO */
    private $pdo;

    private function __construct()
    {
        $dsn = getenv('PNF_DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=pagesnotfound_test;charset=utf8';
        $user = getenv('PNF_DB_USER') ?: 'pnf';
        $password = getenv('PNF_DB_PASSWORD') ?: 'pnf';
        $this->pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public static function getInstance($slave = false)
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function execute($sql, $use_cache = true)
    {
        return $this->pdo->exec($sql) !== false;
    }

    public function executeS($sql, $array = true, $use_cache = true)
    {
        $statement = $this->pdo->query($sql);
        if ($statement === false) {
            return false;
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function escape($string, $html_ok = false, $bq_sql = false)
    {
        if (_PS_MAGIC_QUOTES_GPC_) {
            $string = stripslashes($string);
        }

        if (!is_numeric($string)) {
            $quoted = $this->pdo->quote((string) $string);
            $string = substr($quoted, 1, -1);

            if (!$html_ok) {
                $string = strip_tags($string);
            }

            if ($bq_sql === true) {
                $string = str_replace('`', '\`', $string);
            }
        }

        return $string;
    }
}

function pSQL($string, $html_ok = false)
{
    return Db::getInstance()->escape($string, $html_ok);
}

class ModuleGraph
{
    public static function getDateBetween($employee = null)
    {
        return ' \'2020-06-01 00:00:00\' AND \'2020-06-30 23:59:59\' ';
    }
}

class Shop
{
    private static $contextShopIds = [1];

    public static function resetHarness(): void
    {
        self::$contextShopIds = [1];
    }

    public static function setContextShopIds(array $ids): void
    {
        self::$contextShopIds = $ids;
    }

    public static function addSqlRestriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }

        if (is_string($alias)) {
            $alias = Db::getInstance()->escape($alias);
        }

        $ids = implode(', ', array_map('intval', self::$contextShopIds));

        return ' AND ' . $alias . 'id_shop IN (' . $ids . ') ';
    }
}

class Tools
{
    public static function isSubmit($submit)
    {
        return isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y'])
            || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y']);
    }

    public static function htmlentitiesUTF8($string, $type = ENT_QUOTES)
    {
        if (is_array($string)) {
            return array_map(['Tools', 'htmlentitiesUTF8'], $string);
        }

        return htmlentities((string) $string, $type, 'utf-8');
    }

    public static function getProtocol($use_ssl = null)
    {
        return null !== $use_ssl && $use_ssl ? 'https://' : 'http://';
    }

    public static function cleanNonUnicodeSupport($pattern)
    {
        $characterCleaner = new CharacterCleaner();

        return $characterCleaner->cleanNonUnicodeSupport($pattern);
    }
}

class Validate
{
    public static function isUrl($url)
    {
        return preg_match(Tools::cleanNonUnicodeSupport('/^[~:#,$%&_=\(\)\.\? \+\-@\/a-zA-Z0-9\pL\pS-]+$/u'), $url);
    }

    public static function isAbsoluteUrl($url)
    {
        if (!empty($url)) {
            return preg_match('/^(https?:)?\/\/[$~:;#,%&_=\(\)\[\]\.\? \+\-@\/a-zA-Z0-9]+$/', $url);
        }

        return true;
    }
}

class AdminStatsController
{
    public $cssFiles = [];

    public function addCSS($path)
    {
        $this->cssFiles[] = $path;
    }
}

class PageNotFoundController
{
}

class ProductController
{
}

class Context
{
    /** @var Context|null */
    private static $instance;

    /** @var object */
    public $controller;

    /** @var object */
    public $shop;

    private function __construct()
    {
        $this->shop = (object) [
            'id' => 1,
            'id_shop_group' => 1,
        ];
        $this->controller = new ProductController();
    }

    public static function getContext()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetHarness(): void
    {
        self::$instance = null;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    public function setShopIds($shopId, $shopGroupId)
    {
        $this->shop->id = $shopId;
        $this->shop->id_shop_group = $shopGroupId;
    }
}
