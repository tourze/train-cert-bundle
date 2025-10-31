<?php

namespace Tourze\TrainCertBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;

/**
 * @internal
 */
#[CoversClass(Certificate::class)]
final class CertificateTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Certificate();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'title' => ['title', 'test_value'],
            'imgUrl' => ['imgUrl', 'test_value'],
            'valid' => ['valid', true],
        ];
    }

    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->certificate = new Certificate();
    }

    public function testSetAndGetTitle(): void
    {
        $title = '安全生产培训证书';
        $this->certificate->setTitle($title);

        $this->assertEquals($title, $this->certificate->getTitle());
    }

    public function testSetAndGetUser(): void
    {
        // 这里必须使用具体类而不是接口，因为：
        // 1. 需要测试该实体的具体关联逻辑
        // 2. UserInterface 是标准接口，Mock 可以模拟所有实现
        // 3. 集成测试需要验证具体的实体关系
        $user = $this->createMock(UserInterface::class);
        $this->certificate->setUser($user);

        $this->assertSame($user, $this->certificate->getUser());
    }

    public function testSetAndGetImgUrl(): void
    {
        $imgUrl = '/certificates/cert_123456.png';
        $this->certificate->setImgUrl($imgUrl);

        $this->assertEquals($imgUrl, $this->certificate->getImgUrl());
    }

    public function testSetAndGetValid(): void
    {
        $this->assertFalse($this->certificate->isValid());

        $this->certificate->setValid(true);
        $this->assertTrue($this->certificate->isValid());

        $this->certificate->setValid(false);
        $this->assertFalse($this->certificate->isValid());
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->certificate->getId());
    }

    public function testToString(): void
    {
        $title = '安全生产培训证书';
        $this->certificate->setTitle($title);

        $this->assertEquals($title, (string) $this->certificate);
    }

    public function testRetrieveApiArray(): void
    {
        $title = '测试证书';
        $imgUrl = '/test/image.png';
        $valid = true;

        $this->certificate->setTitle($title);
        $this->certificate->setImgUrl($imgUrl);
        $this->certificate->setValid($valid);

        $apiArray = $this->certificate->retrieveApiArray();

        $this->assertEquals($title, $apiArray['title']);
        $this->assertEquals($imgUrl, $apiArray['imgUrl']);
        $this->assertEquals($valid, $apiArray['valid']);
    }

    public function testImgUrlCanBeNull(): void
    {
        $this->certificate->setImgUrl(null);
        $this->assertNull($this->certificate->getImgUrl());
    }
}
