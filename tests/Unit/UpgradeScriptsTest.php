<?php

use PHPUnit\Framework\TestCase;

class UpgradeScriptsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Module::resetHarness();
    }

    public function testUpgrade201MovesTheFrontHook()
    {
        $module = new PagesNotFound();
        upgrade_module_2_0_1($module);

        $this->assertSame(
            ['unregister:top', 'displayTop'],
            Module::getRegisteredHooks()
        );
    }

    public function testUpgrade202MovesTheStatsHook()
    {
        $module = new PagesNotFound();
        upgrade_module_2_0_2($module);

        $this->assertSame(
            ['unregister:AdminStatsModules', 'displayAdminStatsModules'],
            Module::getRegisteredHooks()
        );
    }
}
