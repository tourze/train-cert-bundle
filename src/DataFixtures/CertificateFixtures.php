<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use BizUserBundle\Entity\BizUser;
use BizUserBundle\Repository\BizUserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\Certificate;

/**
 * 证书测试数据
 */
class CertificateFixtures extends Fixture
{
    public const CERTIFICATE_VALID = 'certificate-valid';
    public const CERTIFICATE_INVALID = 'certificate-invalid';

    public function __construct(
        private readonly BizUserRepository $bizUserRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 获取或创建测试用户
        $user = $this->getOrCreateTestUser($manager);

        // 创建有效证书
        $validCert = new Certificate();
        $validCert->setTitle('安全生产培训证书');
        $validCert->setUser($user);
        $validCert->setImgUrl('https://images.unsplash.com/photo-1589330694653-ded6df03f754');
        $validCert->setValid(true);
        $manager->persist($validCert);
        $this->addReference(self::CERTIFICATE_VALID, $validCert);

        // 创建无效证书
        $invalidCert = new Certificate();
        $invalidCert->setTitle('过期培训证书');
        $invalidCert->setUser($user);
        $invalidCert->setImgUrl('https://images.unsplash.com/photo-1589330694653-ded6df03f755');
        $invalidCert->setValid(false);
        $manager->persist($invalidCert);
        $this->addReference(self::CERTIFICATE_INVALID, $invalidCert);

        $manager->flush();
    }

    private function getOrCreateTestUser(ObjectManager $manager): UserInterface
    {
        // 尝试查找现有用户
        $user = $this->bizUserRepository->findOneBy(['email' => 'admin@test.com']);

        if ($user instanceof UserInterface) {
            return $user;
        }

        // 创建测试用户
        $testUser = new BizUser();
        $testUser->setUsername('cert_test_user_' . uniqid());
        $testUser->setEmail('cert_test@localhost.test');
        $testUser->setPasswordHash('$2y$13$test_hash');
        $manager->persist($testUser);
        $manager->flush();

        return $testUser;
    }
}
