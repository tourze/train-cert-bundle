<?php

namespace Tourze\TrainCertBundle\Tests\Integration\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;
use Tourze\TrainCertBundle\Repository\CertificateApplicationRepository;
use Tourze\TrainCertBundle\Repository\CertificateRepository;
use Tourze\TrainCertBundle\Repository\CertificateTemplateRepository;
use Tourze\TrainCertBundle\Service\CertificateGeneratorService;
use Tourze\TrainCertBundle\Service\CertificateService;

class CertificateServiceTest extends TestCase
{
    private CertificateService $service;
    private EntityManagerInterface&MockObject $entityManager;
    private CertificateRepository&MockObject $certificateRepository;
    private CertificateApplicationRepository&MockObject $applicationRepository;
    private CertificateTemplateRepository&MockObject $templateRepository;
    private CertificateGeneratorService&MockObject $generatorService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->certificateRepository = $this->createMock(CertificateRepository::class);
        $this->applicationRepository = $this->createMock(CertificateApplicationRepository::class);
        $this->templateRepository = $this->createMock(CertificateTemplateRepository::class);
        $this->generatorService = $this->createMock(CertificateGeneratorService::class);
        
        $this->service = new CertificateService(
            $this->entityManager,
            $this->certificateRepository,
            $this->applicationRepository,
            $this->templateRepository,
            $this->generatorService
        );
    }

    public function testGenerateCertificateSuccess(): void
    {
        $userId = 'user123';
        $courseId = 'course456';
        $templateId = 'template789';

        $template = new CertificateTemplate();
        $template->setTemplateName('测试模板');
        $template->setIsActive(true);

        $certificate = new Certificate();
        $certificate->setTitle('测试证书');

        $this->templateRepository->expects($this->once())
            ->method('find')
            ->with($templateId)
            ->willReturn($template);

        $this->generatorService->expects($this->once())
            ->method('generateSingleCertificate')
            ->willReturn($certificate);

        $result = $this->service->generateCertificate($userId, $courseId, $templateId);

        $this->assertInstanceOf(Certificate::class, $result);
        $this->assertEquals('测试证书', $result->getTitle());
    }

    public function testGenerateCertificateWithNonExistentTemplate(): void
    {
        $this->templateRepository->expects($this->once())
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);

        $this->expectException(\Tourze\TrainCertBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板不存在');

        $this->service->generateCertificate('user123', 'course456', 'nonexistent');
    }

    public function testGenerateCertificateWithInactiveTemplate(): void
    {
        $template = new CertificateTemplate();
        $template->setTemplateName('测试模板');
        $template->setIsActive(false);

        $this->templateRepository->expects($this->once())
            ->method('find')
            ->with('template123')
            ->willReturn($template);

        $this->expectException(\Tourze\TrainCertBundle\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('证书模板未启用');

        $this->service->generateCertificate('user123', 'course456', 'template123');
    }
}