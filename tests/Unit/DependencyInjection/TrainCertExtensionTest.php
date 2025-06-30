<?php

namespace Tourze\TrainCertBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\TrainCertBundle\DependencyInjection\TrainCertExtension;

class TrainCertExtensionTest extends TestCase
{
    private TrainCertExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TrainCertExtension();
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $configs = [];

        $this->extension->load($configs, $container);

        $this->assertInstanceOf(ContainerBuilder::class, $container);
    }
}