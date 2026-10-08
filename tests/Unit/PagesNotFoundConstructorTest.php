<?php

class PagesNotFoundConstructorTest extends PagesNotFoundTestCase
{
    public function testConstructorPublishesModuleMetadata()
    {
        $this->assertSame('pagesnotfound', $this->module->name);
        $this->assertSame('administration', $this->module->tab);
        $this->assertSame('3.0.0', $this->module->version);
        $this->assertSame('PrestaShop', $this->module->author);
        $this->assertSame(0, $this->module->need_instance);
        $this->assertSame('1.7.7', $this->module->ps_versions_compliancy['min']);
        $this->assertSame('Pages not found', $this->module->displayName);
        $this->assertStringContains('Enrich your stats', $this->module->description);
    }
}
