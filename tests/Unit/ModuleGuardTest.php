<?php

use PHPUnit\Framework\TestCase;

class ModuleGuardTest extends TestCase
{
    public function testModuleFileStopsWhenVersionConstantMissing()
    {
        $modulePath = dirname(__DIR__, 2) . '/pagesnotfound.php';
        $phpBinary = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $scriptPath = sys_get_temp_dir() . '/pnf_module_guard_' . uniqid('', true) . '.php';
        file_put_contents(
            $scriptPath,
            '<?php
class Module {}
ob_start();
require ' . var_export($modulePath, true) . ';
ob_end_clean();
echo "loaded";
'
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath);
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            @unlink($scriptPath);
            $this->fail('Could not start PHP subprocess for module guard check');
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        @unlink($scriptPath);

        $this->assertSame(0, $exitCode);
        $this->assertSame('', trim((string) $output));
    }
}
