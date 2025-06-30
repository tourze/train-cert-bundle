<?php

namespace Tourze\TrainCertBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\Certificate;

class CertificateTest extends TestCase
{
    private Certificate $certificate;

    protected function setUp(): void
    {
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