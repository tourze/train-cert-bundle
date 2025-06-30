<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

class CertificateApplicationTest extends TestCase
{
    private CertificateApplication $application;

    protected function setUp(): void
    {
        $this->application = new CertificateApplication();
    }

    public function testSetAndGetUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $this->application->setUser($user);
        
        $this->assertSame($user, $this->application->getUser());
    }

    public function testSetAndGetTemplate(): void
    {
        $template = new CertificateTemplate();
        $this->application->setTemplate($template);
        
        $this->assertSame($template, $this->application->getTemplate());
    }

    public function testSetAndGetApplicationType(): void
    {
        $applicationType = 'standard';
        $this->application->setApplicationType($applicationType);
        
        $this->assertEquals($applicationType, $this->application->getApplicationType());
    }

    public function testSetAndGetApplicationStatus(): void
    {
        $this->assertEquals('pending', $this->application->getApplicationStatus());
        
        $status = 'approved';
        $this->application->setApplicationStatus($status);
        
        $this->assertEquals($status, $this->application->getApplicationStatus());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->application->getId());
    }

    public function testRetrieveApiArray(): void
    {
        $user = $this->createMock(UserInterface::class);
        $template = new CertificateTemplate();
        $template->setTemplateName('测试模板');
        $template->setTemplateType('standard');
        $applicationType = 'standard';
        $applicationStatus = 'approved';

        $this->application->setUser($user);
        $this->application->setTemplate($template);
        $this->application->setApplicationType($applicationType);
        $this->application->setApplicationStatus($applicationStatus);

        $apiArray = $this->application->retrieveApiArray();
        
        $this->assertEquals($applicationType, $apiArray['applicationType']);
        $this->assertEquals($applicationStatus, $apiArray['applicationStatus']);
    }

    public function testDefaultApplicationStatus(): void
    {
        $application = new CertificateApplication();
        $this->assertEquals('pending', $application->getApplicationStatus());
    }

    public function testValidApplicationTypes(): void
    {
        $validTypes = ['standard', 'renewal', 'upgrade'];
        
        foreach ($validTypes as $type) {
            $this->application->setApplicationType($type);
            $this->assertEquals($type, $this->application->getApplicationType());
        }
    }

    public function testValidApplicationStatuses(): void
    {
        $validStatuses = ['pending', 'approved', 'rejected', 'issued'];
        
        foreach ($validStatuses as $status) {
            $this->application->setApplicationStatus($status);
            $this->assertEquals($status, $this->application->getApplicationStatus());
        }
    }
}