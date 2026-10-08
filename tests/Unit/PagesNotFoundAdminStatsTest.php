<?php

class PagesNotFoundAdminStatsTest extends PagesNotFoundTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->dropTableIfExists();
        $this->module->install();
    }

    public function testEmptyReportShowsGuideAndHtaccessWarning()
    {
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('No "page not found" issue registered for now.', $html);
        $this->assertStringContains('You must use a .htaccess file to redirect 404 errors to the "404.php" page.', $html);
        $this->assertStringNotContains('name="submitDeletePNF"', $html);
        $this->assertStringNotContains('name="submitTruncatePNF"', $html);
    }

    public function testExistingHtaccessSuppressesWarning()
    {
        $this->createHtaccess();
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringNotContains('You must use a .htaccess file to redirect 404 errors to the "404.php" page.', $html);
    }

    public function testGuideNames404DocumentUnderBaseUri()
    {
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('ErrorDocument 404 /shop/404.php', $html);
        $this->assertStringContains('/shop/404.php', $html);
    }

    public function testStylesheetPathIsTheModuleCss()
    {
        $this->module->hookDisplayAdminStatsModules();

        $this->assertSame(
            ['/modules/pagesnotfound/views/css/stacking_responsive.css'],
            $this->adminController->cssFiles
        );
    }

    public function testRefererDisplayIsHostPlusPath()
    {
        $this->insertRow('/gone', 'http://example.com:8080/path?q=1', '2020-06-15 12:00:00');
        $html = $this->module->hookDisplayAdminStatsModules();

        $rows = $this->parseStatsRows($html);
        $this->assertCount(1, $rows);
        $this->assertSame('example.com/path', $rows[0]['referer']);
        $this->assertStringContains('http://example.com/path', $html);
    }

    public function testEmptyRefererDisplaysPlaceholder()
    {
        $this->insertRow('/gone', '', '2020-06-15 12:00:00');
        $html = $this->module->hookDisplayAdminStatsModules();

        $rows = $this->parseStatsRows($html);
        $this->assertSame('--', $rows[0]['referer']);
    }

    public function testSortsHigherTotalFirst()
    {
        $this->insertRow('/aaa-rare', 'http://example.com/a', '2020-06-10 12:00:00');
        $this->insertRow('/zzz-popular', 'http://example.com/z1', '2020-06-10 12:00:00');
        $this->insertRow('/zzz-popular', 'http://example.com/z2', '2020-06-10 12:00:00');
        $this->insertRow('/zzz-popular', 'http://example.com/z3', '2020-06-10 12:00:00');
        $this->insertRow('/zzz-popular', 'http://example.com/z4', '2020-06-10 12:00:00');

        $html = $this->module->hookDisplayAdminStatsModules();
        $popularPos = strpos($html, '/zzz-popular');
        $rarePos = strpos($html, '/aaa-rare');
        $this->assertNotFalse($popularPos);
        $this->assertNotFalse($rarePos);
        $this->assertLessThan($rarePos, $popularPos);
    }

    public function testHidesOtherShops()
    {
        $this->insertRow('/only-shop-1', 'http://example.com/a', '2020-06-15 12:00:00', 1, 1);
        $this->insertRow('/only-shop-2', 'http://example.com/b', '2020-06-15 12:00:00', 2, 1);

        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('/only-shop-1', $html);
        $this->assertStringNotContains('/only-shop-2', $html);
    }

    public function testDateWindowIsInclusive()
    {
        $this->insertRow('/in-start', 'http://example.com/a', '2020-06-01 00:00:00');
        $this->insertRow('/in-end', 'http://example.com/b', '2020-06-30 23:59:59');
        $this->insertRow('/out-before', 'http://example.com/c', '2020-05-31 23:59:59');
        $this->insertRow('/out-after', 'http://example.com/d', '2020-07-01 00:00:00');

        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('/in-start', $html);
        $this->assertStringContains('/in-end', $html);
        $this->assertStringNotContains('/out-before', $html);
        $this->assertStringNotContains('/out-after', $html);
    }

    public function testCollapsedRefererCountsAreSummed()
    {
        $this->insertRow('/same', 'http://ref.example/landing?a=1', '2020-06-10 12:00:00');
        $this->insertRow('/same', 'http://ref.example/landing?a=1', '2020-06-11 12:00:00');
        $this->insertRow('/same', 'http://ref.example/landing?a=2', '2020-06-12 12:00:00');
        $this->insertRow('/same', 'http://ref.example/landing?a=2', '2020-06-13 12:00:00');
        $this->insertRow('/same', 'http://ref.example/landing?a=2', '2020-06-14 12:00:00');

        $html = $this->module->hookDisplayAdminStatsModules();
        $rows = $this->parseStatsRows($html);

        $this->assertCount(1, $rows);
        $this->assertSame('ref.example/landing', $rows[0]['referer']);
        $this->assertSame('5', $rows[0]['counter']);
    }

    public function testEscapesHookInsertedUriAndRefererInMarkup()
    {
        $this->setPageNotFoundFrontContext(1, 1);
        $_SERVER['REQUEST_URI'] = '/a< b>';
        $_SERVER['HTTP_REFERER'] = 'http://ref.example/a&b';

        $this->module->hookDisplayTop([]);

        $rows = Db::getInstance()->executeS('SELECT request_uri, http_referer FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $this->assertCount(1, $rows);
        $this->assertSame('/a< b>', $rows[0]['request_uri']);
        $this->assertSame('http://ref.example/a&b', $rows[0]['http_referer']);

        Db::getInstance()->execute(
            'UPDATE `' . _DB_PREFIX_ . 'pagenotfound` SET date_add = \'2020-06-15 12:00:00\''
        );

        Context::getContext()->controller = $this->adminController;
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=pagesnotfound';
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertSame(
            1,
            preg_match(
                '/<td data-header="Page"><a href="\/a&lt; b&gt;-admin404">\/a&lt; b&gt;<\/a><\/td>/',
                $html
            )
        );
        $this->assertSame(
            1,
            preg_match(
                '/<td data-header="Referrer"><a href="http:\/\/ref\.example\/a&amp;b">ref\.example\/a&amp;b<\/a><\/td>/',
                $html
            )
        );
        $this->assertSame(
            0,
            preg_match('/<td data-header="Page"><a href="\/a< b>-admin404">\/a< b><\/a><\/td>/', $html)
        );
    }

    public function testEscapesPageUriInMarkup()
    {
        $this->insertRowWithoutSqlEscape('/<b>pnfmarker</b>', 'http://example.com/a', '2020-06-15 12:00:00');
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringNotContains('<b>pnfmarker</b>', $html);
        $this->assertStringContains('&lt;b&gt;pnfmarker&lt;/b&gt;', $html);
    }

    public function testEscapesRefererAngleBrackets()
    {
        $this->insertRowWithoutSqlEscape('/gone', 'http://example.com/<b>', '2020-06-15 12:00:00');
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('example.com/&lt;b&gt;', $html);
        $this->assertStringNotContains('example.com/<b>', $html);
    }

    public function testLongUriWordWrapsWithBreakElement()
    {
        $uri = '/abcdefghijklmnopqrstuvwxyz0123456789';
        $this->insertRow($uri, 'http://example.com/a', '2020-06-15 12:00:00');
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('/abcdefghijklmnopqrstuvwxyz012<br />3456789', $html);
    }

    public function testAdminPageLinkUsesAdmin404SuffixAndIsNotLogged()
    {
        $this->insertRow('/missing-page', 'http://example.com/a', '2020-06-15 12:00:00');
        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('href="/missing-page-admin404"', $html);

        $before = $this->rowCount();
        $this->setPageNotFoundFrontContext();
        $_SERVER['REQUEST_URI'] = '/missing-page-admin404';
        $this->module->hookDisplayTop([]);
        $this->assertSame($before, $this->rowCount());
    }

    public function testTruncateClearsEveryRowThenRendersEmpty()
    {
        $this->insertRow('/gone', 'http://example.com/a', '2020-06-15 12:00:00');
        $_POST['submitTruncatePNF'] = '1';

        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertSame(0, $this->rowCount());
        $this->assertStringContains('The "pages not found" cache has been emptied.', $html);
        $this->assertStringContains('No "page not found" issue registered for now.', $html);
        $this->assertStringNotContains('name="submitTruncatePNF"', $html);
        $this->assertSame(
            ['/modules/pagesnotfound/views/css/stacking_responsive.css'],
            $this->adminController->cssFiles
        );
    }

    public function testDeleteRemovesOnlyRowsInsideTheDateWindow()
    {
        $this->insertRow('/in-window', 'http://example.com/a', '2020-06-15 12:00:00');
        $this->insertRow('/out-window', 'http://example.com/b', '2020-07-02 12:00:00');
        $_POST['submitDeletePNF'] = '1';

        $html = $this->module->hookDisplayAdminStatsModules();

        $rows = Db::getInstance()->executeS('SELECT request_uri FROM `' . _DB_PREFIX_ . 'pagenotfound` ORDER BY request_uri');
        $this->assertSame(['/out-window'], array_column($rows, 'request_uri'));
        $this->assertStringContains('The "pages not found" cache has been deleted.', $html);
    }

    public function testTruncateTakesPrecedenceWhenBothActionsArePosted()
    {
        $this->insertRow('/in-window', 'http://example.com/a', '2020-06-15 12:00:00');
        $this->insertRow('/out-window', 'http://example.com/b', '2020-07-02 12:00:00');
        $_POST['submitTruncatePNF'] = '1';
        $_POST['submitDeletePNF'] = '1';

        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertSame(0, $this->rowCount());
        $this->assertStringContains('The "pages not found" cache has been emptied.', $html);
        $this->assertStringNotContains('The "pages not found" cache has been deleted.', $html);
    }

    public function testFormActionEscapesAmpersands()
    {
        $this->insertRow('/gone', 'http://example.com/a', '2020-06-15 12:00:00');
        $_SERVER['REQUEST_URI'] = '/admin/index.php?controller=AdminStats&module=pagesnotfound';

        $html = $this->module->hookDisplayAdminStatsModules();

        $this->assertStringContains('/admin/index.php?controller=AdminStats&amp;module=pagesnotfound', $html);
    }
}
