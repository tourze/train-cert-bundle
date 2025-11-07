<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\UserServiceContracts\UserManagerInterface;

class CertificateApplicationFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserManagerInterface $userManager,
    ) {
    }
    public function load(ObjectManager $manager): void
    {
        // 获取已存在的证书模板
        $templates = $manager->getRepository(CertificateTemplate::class)->findAll();

        if ([] === $templates) {
            // 如果没有模板，创建一个基本模板用于测试
            $template = new CertificateTemplate();
            $template->setTemplateName('Test Certificate Template');
            $template->setTemplateType('standard');
            $template->setIsActive(true);
            $template->setIsDefault(true);
            $template->setDescription('Test certificate template for fixtures');
            $template->setTemplatePath('/templates/test.html');
            $manager->persist($template);
            $manager->flush();
            $templates = [$template];
        }

        // 获取或创建测试用户
        $users = $this->getUsersFromReferences();
        if ([] === $users) {
            $users = $this->createTestUsers($manager);
        }

        $applicationTypes = ['standard', 'renewal', 'upgrade'];
        $applicationStatuses = ['pending', 'approved', 'rejected'];

        for ($i = 1; $i <= 5; ++$i) {
            $application = new CertificateApplication();

            // 关联用户
            $application->setUser($users[$i % count($users)]);

            // 设置基本信息
            $application->setApplicationType($applicationTypes[array_rand($applicationTypes)]);
            $application->setApplicationStatus($applicationStatuses[array_rand($applicationStatuses)]);

            // 关联模板
            $application->setTemplate($templates[array_rand($templates)]);

            // 设置申请数据
            $application->setApplicationData([
                'reason' => "Application reason {$i}",
                'experience' => "Experience details {$i}",
                'additional_info' => "Additional information {$i}",
            ]);

            // 设置必需文档
            $application->setRequiredDocuments([
                'id_card' => true,
                'photo' => true,
                'qualification_certificate' => false,
            ]);

            // 设置时间
            $application->setApplicationTime(new \DateTimeImmutable("-{$i} days"));

            if ('approved' === $application->getApplicationStatus() || 'rejected' === $application->getApplicationStatus()) {
                $application->setReviewer("Reviewer {$i}");
                $application->setReviewTime(new \DateTimeImmutable('-' . ($i - 1) . ' days'));
                $application->setReviewComment("Review comment for application {$i}");
            }

            $manager->persist($application);
        }

        $manager->flush();
    }

    /**
     * @return UserInterface[]
     */
    private function getUsersFromReferences(): array
    {
        $users = [];

        // 尝试获取用户引用
        $userReferences = ['user_1', 'user_2', 'admin_user', 'test_user'];

        foreach ($userReferences as $reference) {
            try {
                if ($this->hasReference($reference, UserInterface::class)) {
                    $users[] = $this->getReference($reference, UserInterface::class);
                }
            } catch (\Exception) {
                // 引用不存在，继续尝试下一个
                continue;
            }
        }

        return $users;
    }

    /**
     * @return UserInterface[]
     */
    private function createTestUsers(ObjectManager $manager): array
    {
        $users = [];

        // 创建3个测试用户
        for ($i = 1; $i <= 3; ++$i) {
            $testUser = $this->userManager->createUser(
                userIdentifier: "cert_fixtures_user_{$i}",
                nickName: "证书申请测试用户 {$i}",
                password: 'password',
                roles: ['ROLE_USER']
            );
            $manager->persist($testUser);
            $users[] = $testUser;
        }

        $manager->flush();

        return $users;
    }

    public function getDependencies(): array
    {
        return [
            CertificateTemplateFixtures::class,
        ];
    }
}
