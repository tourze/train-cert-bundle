<?php

namespace Tourze\TrainCertBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\TrainCertBundle\DependencyInjection\TrainCertExtension;

/**
 * @internal
 */
#[CoversClass(TrainCertExtension::class)]
final class TrainCertExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private TrainCertExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TrainCertExtension();
    }

    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $configs = [];

        $this->extension->load($configs, $container);

        $this->assertNotNull($container);
    }
}
