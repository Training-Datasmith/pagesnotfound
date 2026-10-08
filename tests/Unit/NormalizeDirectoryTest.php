<?php

class NormalizeDirectoryTest extends PagesNotFoundTestCase
{
    public function testAppendsMissingSeparator()
    {
        $result = $this->invokeNormalize('/var/ps');

        $this->assertSame('/var/ps/', $result);
    }

    public function testDoesNotDoubleAnExistingSlash()
    {
        $result = $this->invokeNormalize('/var/ps/');

        $this->assertSame('/var/ps/', $result);
    }

    public function testReplacesATrailingBackslash()
    {
        $result = $this->invokeNormalize('/var/ps\\');

        $this->assertSame('/var/ps/', $result);
    }

    private function invokeNormalize($directory)
    {
        $reflection = new ReflectionClass($this->module);
        $method = $reflection->getMethod('_normalizeDirectory');
        $method->setAccessible(true);

        return $method->invoke($this->module, $directory);
    }
}
