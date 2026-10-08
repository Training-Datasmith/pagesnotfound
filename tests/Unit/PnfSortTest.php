<?php

use PHPUnit\Framework\TestCase;

class PnfSortTest extends TestCase
{
    public function testHigherCountComesFirst()
    {
        $this->assertSame(-1, pnfSort(['nb' => 5], ['nb' => 1]));
    }

    public function testLowerCountComesAfter()
    {
        $this->assertSame(1, pnfSort(['nb' => 1], ['nb' => 5]));
    }

    public function testEqualCountsTie()
    {
        $this->assertSame(0, pnfSort(['nb' => 3], ['nb' => 3]));
    }
}
