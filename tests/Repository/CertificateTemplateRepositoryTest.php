<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;

/**
 * @template-extends AbstractRepositoryTestCase<CertificateTemplate>
 * @internal
 */
#[CoversClass(CertificateTemplateRepository::class)]
#[RunTestsInSeparateProcesses]
final class CertificateTemplateRepositoryTest extends AbstractRepositoryTestCase
{
    private CertificateTemplateRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CertificateTemplateRepository::class);

        // 清理DataFixtures创建的模板数据，确保每个测试在干净的环境中运行
        $this->cleanTemplatesData();

        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' === $currentTest) {
            // 为 count 测试创建测试数据
            $template = new CertificateTemplate();
            $template->setTemplateName('Test Template for Count');
            $template->setTemplateType('training');
            $template->setIsActive(true);
            $template->setIsDefault(false);
            self::getEntityManager()->persist($template);
            self::getEntityManager()->flush();
        }
    }

    /**
     * 清理DataFixtures创建的模板数据
     */
    private function cleanTemplatesData(): void
    {
        $entityManager = self::getEntityManager();

        // 删除所有CertificateTemplate记录
        $templateRepository = $entityManager->getRepository(CertificateTemplate::class);
        $templates = $templateRepository->findAll();
        foreach ($templates as $template) {
            $entityManager->remove($template);
        }

        $entityManager->flush();
        $entityManager->clear();
    }

    public function testFindWithEmptyStringIdShouldReturnNull(): void
    {
        $result = $this->repository->find('');
        $this->assertNull($result);
    }

    public function testSaveWithoutFlushShouldNotPersistEntity(): void
    {
        $template = $this->createCertificateTemplate();

        $this->repository->save($template, false);
        $id = $template->getId();
        self::getEntityManager()->clear();

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithFlushShouldDeleteEntity(): void
    {
        $template = $this->createCertificateTemplate();
        $this->repository->save($template);

        $id = $template->getId();
        $this->repository->remove($template, true);

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithoutFlushShouldNotDeleteEntity(): void
    {
        $template = $this->createCertificateTemplate();
        $this->repository->save($template);

        $id = $template->getId();
        $this->repository->remove($template, false);

        $result = $this->repository->find($id);
        $this->assertInstanceOf(CertificateTemplate::class, $result);
    }

    public function testFindActiveTemplatesShouldReturnActiveTemplates(): void
    {
        $activeTemplate = $this->createCertificateTemplate();
        $activeTemplate->setIsActive(true);
        $this->repository->save($activeTemplate, false);

        $inactiveTemplate = $this->createCertificateTemplate();
        $inactiveTemplate->setIsActive(false);
        $inactiveTemplate->setTemplateName('Inactive Template');
        $this->repository->save($inactiveTemplate, false);

        self::getEntityManager()->flush();

        $result = $this->repository->findActiveTemplates();
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getIsActive());
    }

    public function testFindByTypeShouldReturnTemplatesOfType(): void
    {
        $template = $this->createCertificateTemplate();
        $template->setTemplateType('training');
        $this->repository->save($template);

        $result = $this->repository->findByType('training');
        $this->assertNotEmpty($result);
        $this->assertEquals('training', $result[0]->getTemplateType());
    }

    public function testFindDefaultTemplateShouldReturnDefaultTemplate(): void
    {
        $defaultTemplate = $this->createCertificateTemplate();
        $defaultTemplate->setIsDefault(true);
        $defaultTemplate->setIsActive(true);
        $this->repository->save($defaultTemplate, false);

        $nonDefaultTemplate = $this->createCertificateTemplate();
        $nonDefaultTemplate->setIsDefault(false);
        $nonDefaultTemplate->setTemplateName('Non-Default Template');
        $this->repository->save($nonDefaultTemplate, false);

        self::getEntityManager()->flush();

        $result = $this->repository->findDefaultTemplate();
        $this->assertInstanceOf(CertificateTemplate::class, $result);
        $this->assertTrue($result->getIsDefault());
    }

    public function testFindDefaultTemplateByTypeShouldReturnDefaultTemplateOfType(): void
    {
        $defaultTemplate = $this->createCertificateTemplate();
        $defaultTemplate->setTemplateType('training');
        $defaultTemplate->setIsDefault(true);
        $defaultTemplate->setIsActive(true);
        $this->repository->save($defaultTemplate);

        $result = $this->repository->findDefaultTemplateByType('training');
        $this->assertInstanceOf(CertificateTemplate::class, $result);
        $this->assertEquals('training', $result->getTemplateType());
        $this->assertTrue($result->getIsDefault());
    }

    public function testFindDefaultTemplateByTypeWithNonExistentTypeShouldReturnNull(): void
    {
        $result = $this->repository->findDefaultTemplateByType('non-existent-type');
        $this->assertNull($result);
    }

    public function testClearDefaultTemplateShouldClearDefaultFlag(): void
    {
        // 创建两个相同类型的默认模板
        $template1 = $this->createCertificateTemplate();
        $template1->setTemplateType('training');
        $template1->setIsDefault(true);
        $template1->setTemplateName('Template 1');
        $this->repository->save($template1, false);

        $template2 = $this->createCertificateTemplate();
        $template2->setTemplateType('training');
        $template2->setIsDefault(true);
        $template2->setTemplateName('Template 2');
        $this->repository->save($template2, false);

        self::getEntityManager()->flush();

        // 清除默认模板，排除template2
        $this->repository->clearDefaultTemplate('training', $template2->getId());

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($template1);
        self::getEntityManager()->refresh($template2);

        // 验证template1的默认标志被清除，template2保持默认
        $this->assertFalse($template1->getIsDefault());
        $this->assertTrue($template2->getIsDefault());
    }

    public function testClearDefaultTemplateWithoutExcludeIdShouldClearAllDefaults(): void
    {
        // 创建两个相同类型的默认模板
        $template1 = $this->createCertificateTemplate();
        $template1->setTemplateType('certification');
        $template1->setIsDefault(true);
        $template1->setTemplateName('Template 1');
        $this->repository->save($template1, false);

        $template2 = $this->createCertificateTemplate();
        $template2->setTemplateType('certification');
        $template2->setIsDefault(true);
        $template2->setTemplateName('Template 2');
        $this->repository->save($template2, false);

        self::getEntityManager()->flush();

        // 清除所有默认模板
        $this->repository->clearDefaultTemplate('certification');

        // 刷新实体以获取最新状态
        self::getEntityManager()->refresh($template1);
        self::getEntityManager()->refresh($template2);

        // 验证所有模板的默认标志都被清除
        $this->assertFalse($template1->getIsDefault());
        $this->assertFalse($template2->getIsDefault());
    }

    private function createCertificateTemplate(): CertificateTemplate
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template');
        $template->setTemplateType('training');
        $template->setIsActive(true);
        $template->setIsDefault(false);
        $template->setDescription('Test template content');

        return $template;
    }

    protected function createNewEntity(): object
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('Test Template ' . uniqid());
        $template->setTemplateType('training');
        $template->setIsActive(true);
        $template->setIsDefault(false);
        $template->setDescription('Test template description');

        return $template;
    }

    /**
     * @return CertificateTemplateRepository
     */
    protected function getRepository(): CertificateTemplateRepository
    {
        return $this->repository;
    }
}
