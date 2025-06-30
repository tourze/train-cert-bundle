<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Procedure;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Procedure\CheckJobTrainingCertificate;

class CheckJobTrainingCertificateTest extends TestCase
{
    public function testProcedureExists(): void
    {
        $this->assertTrue(class_exists(CheckJobTrainingCertificate::class));
    }
}