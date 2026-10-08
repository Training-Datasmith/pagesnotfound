<?php

use PHPUnit\Framework\TestCase;

class ModuleGuardTest extends TestCase
{
    public function testModuleFileStopsWhenVersionConstantMissing()
    {
        $modulePath = dirname(__DIR__, 2) . '/pagesnotfound.php';
        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $code = 'class Module {};' .
            'ob_start();' .
            'require ' . var_export($modulePath, true) . ';' .
            'ob_end_clean();' .
            'echo "loaded";';
        $command = escapeshellarg($phpBinary) . ' -r ' . escapeshellarg($code);
        $output = shell_exec($command);

        $this->assertSame('', trim((string) $output));
    }
}
