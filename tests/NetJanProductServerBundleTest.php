<?php

namespace NetJan\ProductServerBundle\Tests;

use NetJan\ProductServerBundle\NetJanProductServerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NetJanProductServerBundleTest extends TestCase
{
    public function testInstance()
    {
        $this->assertInstanceOf(Bundle::class, new NetJanProductServerBundle());
    }
}
