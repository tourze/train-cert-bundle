<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateAudit;

class CertificateAuditTest extends TestCase
{
    private CertificateAudit $audit;

    protected function setUp(): void
    {
        $this->audit = new CertificateAudit();
    }

    public function testSetAndGetApplication(): void
    {
        $application = new CertificateApplication();
        $this->audit->setApplication($application);
        
        $this->assertSame($application, $this->audit->getApplication());
    }

    public function testSetAndGetAuditStatus(): void
    {
        $this->assertEquals('pending', $this->audit->getAuditStatus());
        
        $status = 'approved';
        $this->audit->setAuditStatus($status);
        
        $this->assertEquals($status, $this->audit->getAuditStatus());
    }

    public function testSetAndGetAuditResult(): void
    {
        $this->assertNull($this->audit->getAuditResult());
        
        $result = 'passed';
        $this->audit->setAuditResult($result);
        
        $this->assertEquals($result, $this->audit->getAuditResult());
    }

    public function testSetAndGetAuditComment(): void
    {
        $this->assertNull($this->audit->getAuditComment());
        
        $comment = '申请材料齐全，审核通过';
        $this->audit->setAuditComment($comment);
        
        $this->assertEquals($comment, $this->audit->getAuditComment());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->audit->getId());
    }

    public function testToString(): void
    {
        $status = 'approved';
        $this->audit->setAuditStatus($status);
        
        $this->assertEquals($status, (string) $this->audit);
    }

    public function testRetrieveApiArray(): void
    {
        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $template = new \Tourze\TrainCertBundle\Entity\CertificateTemplate();
        $template->setTemplateName('测试模板');
        $template->setTemplateType('standard');
        
        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('standard');
        $auditStatus = 'approved';
        $auditResult = 'passed';
        $auditComment = '审核通过';

        $this->audit->setApplication($application);
        $this->audit->setAuditStatus($auditStatus);
        $this->audit->setAuditResult($auditResult);
        $this->audit->setAuditComment($auditComment);

        $apiArray = $this->audit->retrieveApiArray();
        
        $this->assertEquals($auditStatus, $apiArray['auditStatus']);
        $this->assertEquals($auditResult, $apiArray['auditResult']);
        $this->assertEquals($auditComment, $apiArray['auditComment']);
    }

    public function testDefaultAuditStatus(): void
    {
        $audit = new CertificateAudit();
        $this->assertEquals('pending', $audit->getAuditStatus());
    }

    public function testValidAuditStatuses(): void
    {
        $validStatuses = ['pending', 'in_progress', 'approved', 'rejected'];
        
        foreach ($validStatuses as $status) {
            $this->audit->setAuditStatus($status);
            $this->assertEquals($status, $this->audit->getAuditStatus());
        }
    }

    public function testValidAuditResults(): void
    {
        $validResults = ['passed', 'failed', 'conditional'];
        
        foreach ($validResults as $result) {
            $this->audit->setAuditResult($result);
            $this->assertEquals($result, $this->audit->getAuditResult());
        }
    }
}