<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCertBundle\Entity\CertificateApplication;
use Tourze\TrainCertBundle\Entity\CertificateAudit;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateAuditRepository;

/**
 * @template-extends AbstractRepositoryTestCase<CertificateAudit>
 * @internal
 */
#[CoversClass(CertificateAuditRepository::class)]
#[RunTestsInSeparateProcesses]
final class CertificateAuditRepositoryTest extends AbstractRepositoryTestCase
{
    private CertificateAuditRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CertificateAuditRepository::class);

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

        // 创建用户
        $user = $this->createNormalUser('test@example.com', 'password');
        $manager->persist($user);

        // 创建模板
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);
        $template->setIsDefault(true);
        $template->setDescription('Test template');
        $manager->persist($template);

        // 创建申请
        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('training');
        $application->setApplicationStatus('approved');
        $manager->persist($application);

        // 创建审核记录
        $audit = new CertificateAudit();
        $audit->setApplication($application);
        $audit->setAuditStatus('approved');
        $audit->setAuditResult('pass');
        $audit->setAuditComment('Test audit');
        $manager->persist($audit);

        $manager->flush();
    }

    public function testFindWithEmptyStringIdShouldReturnNull(): void
    {
        $result = $this->repository->find('');
        $this->assertNull($result);
    }

    public function testSaveWithoutFlushShouldNotPersistEntity(): void
    {
        $audit = $this->createCertificateAudit();

        $this->repository->save($audit, false);

        // 清空实体管理器来模拟新的事务
        $em = self::getEntityManager();
        $em->clear();

        $result = $this->repository->find($audit->getId());
        $this->assertNull($result);
    }

    public function testRemoveWithFlushShouldDeleteEntity(): void
    {
        $audit = $this->createCertificateAudit();
        $this->repository->save($audit);

        $id = $audit->getId();
        $this->repository->remove($audit, true);

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithoutFlushShouldNotDeleteEntity(): void
    {
        $audit = $this->createCertificateAudit();
        $this->repository->save($audit);

        $id = $audit->getId();
        $this->repository->remove($audit, false);

        $result = $this->repository->find($id);
        $this->assertInstanceOf(CertificateAudit::class, $result);
    }

    public function testFindByApplicationIdShouldReturnAuditsForApplication(): void
    {
        $audit = $this->createCertificateAudit();
        $this->repository->save($audit);

        $applicationId = $audit->getApplication()->getId();
        $this->assertNotNull($applicationId);
        $result = $this->repository->findByApplicationId($applicationId);
        $this->assertNotEmpty($result);
    }

    public function testFindByApplicationIdWithNonExistentIdShouldReturnEmptyArray(): void
    {
        $result = $this->repository->findByApplicationId('999999999999999999');
        $this->assertEmpty($result);
    }

    public function testFindPendingAuditsShouldReturnPendingAudits(): void
    {
        $audit = $this->createCertificateAudit();
        $this->repository->save($audit);

        $result = $this->repository->findPendingAudits();
        $this->assertNotEmpty($result);
        $this->assertEquals('pending', $result[0]->getAuditStatus());
    }

    public function testFindByAuditorShouldReturnAuditorAudits(): void
    {
        $auditorName = 'admin_user';
        $audit = $this->createCertificateAudit();
        $audit->setAuditor($auditorName);
        $this->repository->save($audit);

        $result = $this->repository->findByAuditor($auditorName);
        $this->assertNotEmpty($result);
        $this->assertEquals($auditorName, $result[0]->getAuditor());
    }

    public function testFindCompletedAuditsShouldReturnCompletedAudits(): void
    {
        $audit = $this->createCertificateAudit();
        $audit->setAuditStatus('approved');
        $this->repository->save($audit);

        $result = $this->repository->findCompletedAudits();
        $this->assertNotEmpty($result);
        $this->assertContains($result[0]->getAuditStatus(), ['approved', 'rejected', 'completed']);
    }

    private function createCertificateAudit(): CertificateAudit
    {
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();
        $template = $this->createCertificateTemplate();
        $application = $this->createCertificateApplication($user, $template);

        $em = self::getEntityManager();
        $em->persist($application);
        $em->flush();

        $audit = new CertificateAudit();
        $audit->setApplication($application);
        $audit->setAuditStatus('pending');
        $audit->setAuditor('admin_user');

        return $audit;
    }

    private function createCertificateTemplate(): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);
        $template->setIsDefault(false);
        $template->setDescription('Test content');

        $em = self::getEntityManager();
        $em->persist($template);
        $em->flush();

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
        // 创建真实用户
        $user = $this->createNormalUser('test@example.com', 'password');
        self::getEntityManager()->persist($user);
        self::getEntityManager()->flush();

        // 创建证书模板
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);

        // 创建证书申请
        $application = new CertificateApplication();
        $application->setUser($user);
        $application->setTemplate($template);
        $application->setApplicationType('training');
        $application->setApplicationStatus('approved');

        // 创建证书审核
        $entity = new CertificateAudit();
        $entity->setApplication($application);
        $entity->setAuditStatus('pending');
        $entity->setAuditor('admin_user');

        return $entity;
    }

    /**
     * @return CertificateAuditRepository
     */
    protected function getRepository(): CertificateAuditRepository
    {
        return $this->repository;
    }
}
