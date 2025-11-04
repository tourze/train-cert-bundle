<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateApplicationRepository;

/**
 * @template-extends AbstractRepositoryTestCase<CertificateApplication>
 * @internal
 */
#[CoversClass(CertificateApplicationRepository::class)]
#[RunTestsInSeparateProcesses]
final class CertificateApplicationRepositoryTest extends AbstractRepositoryTestCase
{
    private CertificateApplicationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CertificateApplicationRepository::class);

        // 确保 DataFixtures 已加载
        $this->ensureDataFixturesLoaded();
    }

    /**
     * 确保 DataFixtures 已加载
     */
    private function ensureDataFixturesLoaded(): void
    {
        // 检查是否已有数据
        if ($this->repository->count() > 0) {
            return;
        }

        // 如果没有数据，手动创建一些测试数据
        $manager = self::getEntityManager();

        // 创建模板
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);
        $template->setIsDefault(true);
        $template->setDescription('Test template');
        $manager->persist($template);

        // 创建用户
        $user = $this->createNormalUser('test@example.com', 'password');
        $manager->persist($user);

        // 创建申请记录
        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('training');
        $application->setApplicationStatus('pending');
        $manager->persist($application);

        $manager->flush();
    }

    public function testFindWithEmptyStringIdShouldReturnNull(): void
    {
        $result = $this->repository->find('');
        $this->assertNull($result);
    }

    public function testSaveWithoutFlushShouldNotCommitToDatabase(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);

        $this->repository->save($application, false);

        // 清空实体管理器来模拟新的事务
        $em = self::getEntityManager();
        $em->clear();

        $result = $this->repository->find($application->getId());
        $this->assertNull($result);
    }

    public function testRemoveWithFlushShouldDeleteEntity(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $this->repository->save($application);

        $id = $application->getId();
        $this->repository->remove($application, true);

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithoutFlushShouldNotDeleteEntity(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $this->repository->save($application);

        $id = $application->getId();
        $this->repository->remove($application, false);

        $result = $this->repository->find($id);
        $this->assertInstanceOf(CertificateApplication::class, $result);
    }

    public function testFindByUserShouldReturnUserApplications(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $this->repository->save($application);

        // UserInterface 没有 getId() 方法，使用其他标识符
        $result = $this->repository->findBy(['user' => $user]);
        $this->assertNotEmpty($result);
    }

    public function testFindByStatusShouldReturnApplicationsWithSpecificStatus(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $application->setApplicationStatus('approved');
        $this->repository->save($application);

        $result = $this->repository->findByStatus('approved');
        $this->assertNotEmpty($result);
        $this->assertEquals('approved', $result[0]->getApplicationStatus());
    }

    public function testFindPendingApplicationsShouldReturnPendingApplications(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $this->repository->save($application);

        $result = $this->repository->findPendingApplications();
        $this->assertNotEmpty($result);
        $this->assertEquals('pending', $result[0]->getApplicationStatus());
    }

    public function testFindApprovedApplicationsShouldReturnApprovedApplications(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $application->setApplicationStatus('approved');
        $this->repository->save($application);

        $result = $this->repository->findApprovedApplications();
        $this->assertNotEmpty($result);
        $this->assertEquals('approved', $result[0]->getApplicationStatus());
    }

    public function testFindByTemplateIdShouldReturnTemplateApplications(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $this->repository->save($application);

        $templateId = $template->getId();
        $this->assertNotNull($templateId);
        $result = $this->repository->findByTemplateId($templateId);
        $this->assertNotEmpty($result);
    }

    public function testFindByUserIdShouldReturnUserApplications(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);
        $this->repository->save($application);

        // 使用用户对象的ID（假设BizUser有getId方法）
        if (method_exists($user, 'getId')) {
            /** @var mixed $id */
            $id = $user->getId();
            $userId = null === $id ? '' : (is_scalar($id) ? (string) $id : '');
        } else {
            $userId = $user->getUserIdentifier();
        }
        $result = $this->repository->findByUserId($userId);
        $this->assertIsArray($result);
    }

    public function testFindByUserIdWithNonExistentUserShouldReturnEmptyArray(): void
    {
        $result = $this->repository->findByUserId('non-existent-user-id');
        $this->assertEmpty($result);
    }

    private function createCertificateTemplate(): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);
        $template->setIsDefault(false);
        $template->setDescription('Test content');

        return $template;
    }

    private function createCertificateApplication(UserInterface $user, CertificateTemplate $template): CertificateApplication
    {
        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('training');
        $application->setApplicationStatus('pending');

        return $application;
    }

    protected function createNewEntity(): object
    {
        // 创建用户
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();

        // 创建证书模板
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);

        // 创建证书申请
        $entity = new CertificateApplication();
        $entity->setUser($user);
        $entity->setTemplate($template);
        $entity->setApplicationType('training');
        $entity->setApplicationStatus('pending');

        return $entity;
    }

    /**
     * @return CertificateApplicationRepository
     */
    protected function getRepository(): CertificateApplicationRepository
    {
        return $this->repository;
    }
}
