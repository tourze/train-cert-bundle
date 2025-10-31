<?php

namespace Tourze\TrainCertBundle\Tests\Procedure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\JsonRPC\Core\Tests\AbstractProcedureTestCase;
use Tourze\TrainCertBundle\Procedure\CheckJobTrainingCertificate;

/**
 * @internal
 */
#[CoversClass(CheckJobTrainingCertificate::class)]
#[RunTestsInSeparateProcesses]
final class CheckJobTrainingCertificateTest extends AbstractProcedureTestCase
{
    protected function onSetUp(): void
    {
        // 此测试类不需要特殊的 setUp 逻辑
    }

    public function testHasRequiredProperties(): void
    {
        $reflectionClass = new \ReflectionClass(CheckJobTrainingCertificate::class);

        $this->assertTrue($reflectionClass->hasProperty('idcard'));
        $this->assertTrue($reflectionClass->hasProperty('name'));
        $this->assertTrue($reflectionClass->hasProperty('number'));

        $idcardProperty = $reflectionClass->getProperty('idcard');
        $this->assertTrue($idcardProperty->isPublic());
        $propertyType = $idcardProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $propertyType);
        $this->assertEquals('string', $propertyType->getName());

        $nameProperty = $reflectionClass->getProperty('name');
        $this->assertTrue($nameProperty->isPublic());
        $propertyType = $nameProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $propertyType);
        $this->assertEquals('string', $propertyType->getName());

        $numberProperty = $reflectionClass->getProperty('number');
        $this->assertTrue($numberProperty->isPublic());
        $propertyType = $numberProperty->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $propertyType);
        $this->assertEquals('string', $propertyType->getName());
    }

    public function testExecuteMethodSignature(): void
    {
        $reflectionMethod = new \ReflectionMethod(CheckJobTrainingCertificate::class, 'execute');

        $this->assertTrue($reflectionMethod->isPublic());
        $returnType = $reflectionMethod->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    public function testServiceIsAccessible(): void
    {
        $service = self::getService(CheckJobTrainingCertificate::class);
        $this->assertInstanceOf(CheckJobTrainingCertificate::class, $service);
    }
}
