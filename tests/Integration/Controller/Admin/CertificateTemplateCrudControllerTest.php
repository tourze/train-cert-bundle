<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Controller\Admin\CertificateTemplateCrudController;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

class CertificateTemplateCrudControllerTest extends TestCase
{
    private CertificateTemplateCrudController $controller;

    protected function setUp(): void
    {
        $this->controller = new CertificateTemplateCrudController();
    }

    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(
            CertificateTemplate::class,
            CertificateTemplateCrudController::getEntityFqcn()
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