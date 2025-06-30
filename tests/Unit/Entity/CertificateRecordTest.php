<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;

class CertificateRecordTest extends TestCase
{
    private CertificateRecord $record;

    protected function setUp(): void
    {
        $this->record = new CertificateRecord();
    }

    public function testSetAndGetCertificate(): void
    {
        $certificate = new Certificate();
        $this->record->setCertificate($certificate);
        
        $this->assertSame($certificate, $this->record->getCertificate());
    }

    public function testSetAndGetCertificateNumber(): void
    {
        $certificateNumber = 'CERT-2024-001';
        $this->record->setCertificateNumber($certificateNumber);
        
        $this->assertEquals($certificateNumber, $this->record->getCertificateNumber());
    }

    public function testSetAndGetCertificateType(): void
    {
        $certificateType = 'safety';
        $this->record->setCertificateType($certificateType);
        
        $this->assertEquals($certificateType, $this->record->getCertificateType());
    }

    public function testSetAndGetIssueDate(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01');
        $this->record->setIssueDate($issueDate);
        
        $this->assertEquals($issueDate, $this->record->getIssueDate());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->record->getId());
    }

    public function testRetrieveApiArray(): void
    {
        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $certificate = new Certificate();
        $certificate->setTitle('测试证书');
        $certificate->setUser($user);
        $certificateNumber = 'CERT-2024-001';
        $certificateType = 'safety';
        $issueDate = new \DateTimeImmutable('2024-01-01');

        $this->record->setCertificate($certificate);
        $this->record->setCertificateNumber($certificateNumber);
        $this->record->setCertificateType($certificateType);
        $this->record->setIssueDate($issueDate);
        $this->record->setIssuingAuthority('测试发证机构');
        $this->record->setVerificationCode('TEST123456');

        $apiArray = $this->record->retrieveApiArray();
        
        $this->assertEquals($certificateNumber, $apiArray['certificateNumber']);
        $this->assertEquals($certificateType, $apiArray['certificateType']);
        $this->assertEquals($issueDate->format('Y-m-d'), $apiArray['issueDate']);
    }

    public function testValidCertificateTypes(): void
    {
        $validTypes = ['safety', 'skill', 'management', 'special'];
        
        foreach ($validTypes as $type) {
            $this->record->setCertificateType($type);
            $this->assertEquals($type, $this->record->getCertificateType());
        }
    }

    public function testCertificateNumberUniqueness(): void
    {
        $number1 = 'CERT-2024-001';
        $number2 = 'CERT-2024-002';
        
        $this->record->setCertificateNumber($number1);
        $this->assertEquals($number1, $this->record->getCertificateNumber());
        
        $this->record->setCertificateNumber($number2);
        $this->assertEquals($number2, $this->record->getCertificateNumber());
    }

    public function testIssueDateFormat(): void
    {
        $issueDate = new \DateTimeImmutable('2024-01-01 10:30:00');
        $this->record->setIssueDate($issueDate);
        
        $this->assertInstanceOf(\DateTimeInterface::class, $this->record->getIssueDate());
        $this->assertEquals('2024-01-01', $this->record->getIssueDate()->format('Y-m-d'));
    }
}