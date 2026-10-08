<?php

use PHPUnit\Framework\TestCase;

abstract class PagesNotFoundTestCase extends TestCase
{
    /** @var PagesNotFound */
    protected $module;

    /** @var AdminStatsController */
    protected $adminController;

    protected function setUp(): void
    {
        parent::setUp();
        Module::resetHarness();
        Shop::resetHarness();
        Context::resetHarness();
        Db::resetInstance();

        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=pagesnotfound';
        unset($_SERVER['REDIRECT_URL'], $_SERVER['HTTP_REFERER']);

        $this->truncateTable();
        $this->removeHtaccess();

        $this->adminController = new AdminStatsController();
        $context = Context::getContext();
        $context->setController(new ProductController());
        $context->setShopIds(1, 1);
        $context->controller = $this->adminController;

        $this->module = new PagesNotFound();
    }

    protected function tearDown(): void
    {
        $this->dropTableIfExists();
        $this->removeHtaccess();
        parent::tearDown();
    }

    protected function truncateTable(): void
    {
        try {
            Db::getInstance()->execute('TRUNCATE `' . _DB_PREFIX_ . 'pagenotfound`');
        } catch (Throwable $e) {
            // Table may not exist yet.
        }
    }

    protected function dropTableIfExists(): void
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'pagenotfound`');
    }

    protected function removeHtaccess(): void
    {
        $path = _PS_ROOT_DIR_ . '/.htaccess';
        if (is_file($path)) {
            unlink($path);
        }
    }

    protected function createHtaccess(): void
    {
        file_put_contents(_PS_ROOT_DIR_ . '/.htaccess', '# test');
    }

    protected function insertRow($requestUri, $httpReferer, $dateAdd, $shopId = 1, $shopGroupId = 1): void
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'pagenotfound`
            (`request_uri`, `http_referer`, `date_add`, `id_shop`, `id_shop_group`)
            VALUES (
                \'' . pSQL($requestUri) . '\',
                \'' . pSQL($httpReferer) . '\',
                \'' . pSQL($dateAdd) . '\',
                ' . (int) $shopId . ',
                ' . (int) $shopGroupId . '
            )';
        Db::getInstance()->execute($sql);
    }

    protected function insertRowWithoutSqlEscape($requestUri, $httpReferer, $dateAdd, $shopId = 1, $shopGroupId = 1): void
    {
        $statement = Db::getInstance()->getPdo()->prepare(
            'INSERT INTO `' . _DB_PREFIX_ . 'pagenotfound`
            (`request_uri`, `http_referer`, `date_add`, `id_shop`, `id_shop_group`)
            VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$requestUri, $httpReferer, $dateAdd, (int) $shopId, (int) $shopGroupId]);
    }

    protected function rowCount(): int
    {
        $rows = Db::getInstance()->executeS('SELECT COUNT(*) AS c FROM `' . _DB_PREFIX_ . 'pagenotfound`');

        return (int) $rows[0]['c'];
    }

    protected function assertStringContains($needle, $haystack): void
    {
        $this->assertNotFalse(strpos($haystack, $needle), 'Expected substring: ' . $needle);
    }

    protected function assertStringNotContains($needle, $haystack): void
    {
        $this->assertSame(false, strpos($haystack, $needle), 'Unexpected substring: ' . $needle);
    }

    /**
     * @return array<int, array{page: string, referer: string, counter: string}>
     */
    protected function parseStatsRows($html): array
    {
        $rows = [];
        if (!preg_match_all(
            '/<td data-header="Page"><a[^>]*>(.*?)<\/a><\/td>\s*<td data-header="Referrer"><a[^>]*>(.*?)<\/a><\/td>\s*<td data-header="Counter"><span>(\d+)<\/span><\/td>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        )) {
            return $rows;
        }

        foreach ($matches as $match) {
            $rows[] = [
                'page' => trim(strip_tags($match[1])),
                'referer' => trim(strip_tags($match[2])),
                'counter' => $match[3],
            ];
        }

        return $rows;
    }

    protected function setPageNotFoundFrontContext($shopId = 4, $shopGroupId = 9): void
    {
        $context = Context::getContext();
        $context->setController(new PageNotFoundController());
        $context->setShopIds($shopId, $shopGroupId);
    }
}
