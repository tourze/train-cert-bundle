<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Controller\Admin\CertificateApplicationCrudController;
use Tourze\TrainCertBundle\Entity\CertificateApplication;

class CertificateApplicationCrudControllerTest extends TestCase
{
    private CertificateApplicationCrudController $controller;

    protected function setUp(): void
    {
        $this->controller = new CertificateApplicationCrudController();
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(
            CertificateApplication::class,
            CertificateApplicationCrudController::getEntityFqcn()
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