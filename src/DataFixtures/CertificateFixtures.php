<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\UserServiceContracts\UserManagerInterface;

/**
 * 证书测试数据
 */
class CertificateFixtures extends Fixture
{
    public const CERTIFICATE_VALID = 'certificate-valid';
    public const CERTIFICATE_INVALID = 'certificate-invalid';

    public function __construct(
        private readonly UserManagerInterface $userManager,
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
        $user = $this->userManager->loadUserByIdentifier('cert_test_user');

        if ($user instanceof UserInterface) {
            // 如果用户未被 EntityManager 管理，尝试获取托管实体
            if (!$manager->contains($user)) {
                $managedUser = $this->tryGetManagedUser($manager, $user);
                if (null !== $managedUser) {
                    return $managedUser;
                }
            }
            return $user;
        }

        // 创建测试用户
        $testUser = $this->userManager->createUser(
            userIdentifier: 'cert_test_user',
            nickName: '证书测试用户',
            password: 'password',
            roles: ['ROLE_USER']
        );

        $manager->persist($testUser);
        $manager->flush();

        return $testUser;
    }

    /**
     * 尝试从 EntityManager 获取托管的用户实体
     */
    private function tryGetManagedUser(ObjectManager $manager, UserInterface $user): ?UserInterface
    {
        // 尝试通过主键获取
        if (method_exists($user, 'getId')) {
            $id = $user->getId();
            if (null !== $id) {
                $managed = $manager->find($user::class, $id);
                if ($managed instanceof UserInterface) {
                    return $managed;
                }
            }
        }

        // 尝试通过仓库查询
        $repo = $manager->getRepository($user::class);
        $managed = $repo->findOneBy(['userIdentifier' => 'cert_test_user']);

        return $managed instanceof UserInterface ? $managed : null;
    }
}
