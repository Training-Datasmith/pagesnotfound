<?php

class PagesNotFoundDisplayTopTest extends PagesNotFoundTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->dropTableIfExists();
        $this->module->install();
    }

    public function testLogsAbsoluteRefererAndShopIds()
    {
        $this->setPageNotFoundFrontContext(4, 9);
        $_SERVER['REQUEST_URI'] = '/missing-page';
        $_SERVER['HTTP_REFERER'] = 'http://example.com/from?q=1';

        $this->assertNull($this->module->hookDisplayTop([]));

        $rows = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertCount(1, $rows);
        $this->assertSame('/missing-page', $rows[0]['request_uri']);
        $this->assertSame('http://example.com/from?q=1', $rows[0]['http_referer']);
        $this->assertSame(4, (int) $rows[0]['id_shop']);
        $this->assertSame(9, (int) $rows[0]['id_shop_group']);
        $this->assertLessThanOrEqual(5, (int) Db::getInstance()->executeS(
            'SELECT TIMESTAMPDIFF(SECOND, date_add, NOW()) AS age FROM `' . _DB_PREFIX_ . 'pagenotfound`'
        )[0]['age']);
    }

    public function testLogsProtocolRelativeReferer()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/missing-page';
        $_SERVER['HTTP_REFERER'] = '//cdn.example/a';

        $this->module->hookDisplayTop([]);

        $rows = Db::getInstance()->executeS('SELECT http_referer FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertSame('//cdn.example/a', $rows[0]['http_referer']);
    }

    public function testRewritesRequestUriFromRedirectUrl()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/404.php';
        $_SERVER['REDIRECT_URL'] = '/original-missing';

        $this->module->hookDisplayTop([]);

        $this->assertSame('/original-missing', $_SERVER['REQUEST_URI']);
        $rows = Db::getInstance()->executeS('SELECT request_uri FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertSame('/original-missing', $rows[0]['request_uri']);
    }

    public function testLeavesUnrelatedUriWhenRedirectUrlIsSet()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/missing-page';
        $_SERVER['REDIRECT_URL'] = '/other';

        $this->module->hookDisplayTop([]);

        $rows = Db::getInstance()->executeS('SELECT request_uri FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertSame('/missing-page', $rows[0]['request_uri']);
    }

    public function testLogs404PhpWhenRedirectUrlIsAbsent()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/404.php';

        $this->module->hookDisplayTop([]);

        $rows = Db::getInstance()->executeS('SELECT request_uri FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertSame('/404.php', $rows[0]['request_uri']);
    }

    public function testSkipsAdmin404Suffix()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/missing-admin404';

        $this->module->hookDisplayTop([]);

        $this->assertSame(0, $this->rowCount());
    }

    public function testSkipsUriThatFailsIsUrl()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/bad"quote';

        $this->module->hookDisplayTop([]);

        $this->assertSame(0, $this->rowCount());
    }

    public function testSkipsRelativeReferer()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/missing-page';
        $_SERVER['HTTP_REFERER'] = '/local/path';

        $this->module->hookDisplayTop([]);

        $this->assertSame(0, $this->rowCount());
    }

    public function testLogsEmptyRefererAsEmptyString()
    {
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/missing-page';

        $this->module->hookDisplayTop([]);

        $rows = Db::getInstance()->executeS('SELECT http_referer FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertSame('', $rows[0]['http_referer']);
    }

    public function testSkipsControllersOtherThanPageNotFound()
    {
        $_SERVER['REQUEST_URI'] = '/missing-page';

        $this->module->hookDisplayTop([]);

        $this->assertSame(0, $this->rowCount());
    }
}
