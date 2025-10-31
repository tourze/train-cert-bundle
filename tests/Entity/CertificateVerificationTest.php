<?php

namespace Tourze\TrainCertBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

/**
 * @internal
 */
#[CoversClass(CertificateVerification::class)]
final class CertificateVerificationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new CertificateVerification();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'verificationMethod' => ['verificationMethod', 'test_value'],
            'verificationResult' => ['verificationResult', true],
        ];
    }

    private CertificateVerification $verification;

    protected function setUp(): void
    {
        parent::setUp();

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
        // 这里必须使用具体类而不是接口，因为：
        // 1. 需要测试该实体的具体关联逻辑
        // 2. UserInterface 是标准接口，Mock 可以模拟所有实现
        // 3. 集成测试需要验证具体的实体关系
        $user = $this->createMock(UserInterface::class);
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
