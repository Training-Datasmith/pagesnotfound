<?php

class PagesNotFoundInstallTest extends PagesNotFoundTestCase
{
    public function testInstallCreatesSchemaAndRegistersBothHooks()
    {
        $this->dropTableIfExists();
        $this->assertTrue($this->module->install());
        $this->assertSame(['displayTop', 'displayAdminStatsModules'], Module::getRegisteredHooks());

        $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $fields = [];
        foreach ($columns as $column) {
            $fields[$column['Field']] = $column;
        }

        $this->assertArrayHasKey('id_pagenotfound', $fields);
        $this->assertStringContains('unsigned', strtolower($fields['id_pagenotfound']['Type']));
        $this->assertSame('1', $fields['id_shop']['Default']);
        $this->assertSame('1', $fields['id_shop_group']['Default']);
        $this->assertSame('varchar(256)', $fields['request_uri']['Type']);
        $this->assertSame('varchar(256)', $fields['http_referer']['Type']);
        $this->assertSame('datetime', $fields['date_add']['Type']);

        $indexes = Db::getInstance()->executeS('SHOW INDEX FROM `' . _DB_PREFIX_ . 'pagenotfound`');
        $indexColumns = array_column($indexes, 'Column_name');
        $this->assertContains('date_add', $indexColumns);

        $status = Db::getInstance()->executeS('SHOW TABLE STATUS LIKE \'' . _DB_PREFIX_ . 'pagenotfound\'');
        $this->assertSame('InnoDB', $status[0]['Engine']);
    }

    public function testInstallReturnsFalseAndSkipsTableWhenParentInstallFails()
    {
        $this->dropTableIfExists();
        Module::setParentInstallResult(false);
        $this->assertFalse($this->module->install());
        $this->assertFalse($this->tableExists());
    }

    public function testInstallStopsBeforeSecondHookAndSkipsTable()
    {
        $this->dropTableIfExists();
        Module::setRegisterHookFailOn('displayTop');
        $this->assertFalse($this->module->install());
        $this->assertSame([], Module::getRegisteredHooks());
        $this->assertFalse($this->tableExists());
    }

    public function testInstallRollsBackWhenAdminHookFails()
    {
        $this->dropTableIfExists();
        Module::setRegisterHookFailOn('displayAdminStatsModules');
        $this->assertFalse($this->module->install());
        $this->assertSame(['displayTop'], Module::getRegisteredHooks());
        $this->assertFalse($this->tableExists());
    }

    public function testUninstallDropsTable()
    {
        $this->dropTableIfExists();
        $this->module->install();
        $this->assertTrue($this->module->uninstall());
        $this->assertFalse($this->tableExists());
    }

    public function testUninstallLeavesTableWhenParentUninstallFails()
    {
        $this->module->install();
        Module::setParentUninstallResult(false);
        $this->assertFalse($this->module->uninstall());
        $this->assertTrue($this->tableExists());
    }

    private function tableExists(): bool
    {
        $rows = Db::getInstance()->executeS(
            'SHOW TABLES LIKE \'' . _DB_PREFIX_ . 'pagenotfound\''
        );

        return count($rows) > 0;
    }
}
