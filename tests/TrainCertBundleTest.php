<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\TrainCertBundle\TrainCertBundle;

/**
 * @internal
 */
#[CoversClass(TrainCertBundle::class)]
#[RunTestsInSeparateProcesses]
final class TrainCertBundleTest extends AbstractBundleTestCase
{
}
