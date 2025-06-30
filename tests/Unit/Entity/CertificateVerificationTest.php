<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

class CertificateVerificationTest extends TestCase
{
    private CertificateVerification $verification;

    protected function setUp(): void
    {
        $this->verification = new CertificateVerification();
    }

    public function testSetAndGetCertificate(): void
    {
        $certificate = new Certificate();
        $this->verification->setCertificate($certificate);
        
        $this->assertSame($certificate, $this->verification->getCertificate());
    }

    public function testSetAndGetVerificationMethod(): void
    {
        $method = 'certificate_number';
        $this->verification->setVerificationMethod($method);
        
        $this->assertEquals($method, $this->verification->getVerificationMethod());
    }

    public function testSetAndGetVerifierInfo(): void
    {
        $this->assertNull($this->verification->getVerifierInfo());
        
        $verifierInfo = '张三，企业管理员';
        $this->verification->setVerifierInfo($verifierInfo);
        
        $this->assertEquals($verifierInfo, $this->verification->getVerifierInfo());
    }

    public function testSetAndGetVerificationResult(): void
    {
        $this->assertFalse($this->verification->isVerificationResult());
        
        $this->verification->setVerificationResult(true);
        $this->assertTrue($this->verification->isVerificationResult());
        
        $this->verification->setVerificationResult(false);
        $this->assertFalse($this->verification->isVerificationResult());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->verification->getId());
    }

    public function testToString(): void
    {
        $method = 'qr_code';
        $this->verification->setVerificationMethod($method);
        
        $this->assertEquals($method, (string) $this->verification);
    }

    public function testRetrieveApiArray(): void
    {
        $user = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);
        $certificate = new Certificate();
        $certificate->setTitle('测试证书');
        $certificate->setUser($user);
        $verificationMethod = 'verification_code';
        $verifierInfo = '验证人信息';
        $verificationResult = true;

        $this->verification->setCertificate($certificate);
        $this->verification->setVerificationMethod($verificationMethod);
        $this->verification->setVerifierInfo($verifierInfo);
        $this->verification->setVerificationResult($verificationResult);

        $apiArray = $this->verification->retrieveApiArray();
        
        $this->assertEquals($verificationMethod, $apiArray['verificationMethod']);
        $this->assertEquals($verifierInfo, $apiArray['verifierInfo']);
        $this->assertEquals($verificationResult, $apiArray['verificationResult']);
    }

    public function testDefaultVerificationResult(): void
    {
        $verification = new CertificateVerification();
        $this->assertFalse($verification->isVerificationResult());
    }

    public function testValidVerificationMethods(): void
    {
        $validMethods = ['certificate_number', 'verification_code', 'qr_code'];
        
        foreach ($validMethods as $method) {
            $this->verification->setVerificationMethod($method);
            $this->assertEquals($method, $this->verification->getVerificationMethod());
        }
    }

    public function testVerifierInfoCanBeNull(): void
    {
        $this->verification->setVerifierInfo(null);
        $this->assertNull($this->verification->getVerifierInfo());
    }
}