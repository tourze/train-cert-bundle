<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Controller\Admin\CertificateVerificationCrudController;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

class CertificateVerificationCrudControllerTest extends TestCase
{
    private CertificateVerificationCrudController $controller;

    protected function setUp(): void
    {
        $this->controller = new CertificateVerificationCrudController();
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(
            CertificateVerification::class,
            CertificateVerificationCrudController::getEntityFqcn()
        );
    }

    public function testConfigureCrud(): void
    {
        $crud = $this->controller->configureCrud(
            $this->createMock(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class)
        );

        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::class, $crud);
    }


    public function testConfigureFields(): void
    {
        $fields = $this->controller->configureFields('index');

        $this->assertNotEmpty($fields);
    }
}