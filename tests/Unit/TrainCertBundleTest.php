<?php

namespace Tourze\TrainCertBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\TrainCertBundle;

class TrainCertBundleTest extends TestCase
{
    public function testBundleIsCreated(): void
    {
        $bundle = new TrainCertBundle();
        $this->assertInstanceOf(TrainCertBundle::class, $bundle);
    }
}