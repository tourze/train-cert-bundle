<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Repository\CertificateRepository;

/**
 * @template-extends AbstractRepositoryTestCase<Certificate>
 * @internal
 */
#[CoversClass(CertificateRepository::class)]
#[RunTestsInSeparateProcesses]
final class CertificateRepositoryTest extends AbstractRepositoryTestCase
{
    private CertificateRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(CertificateRepository::class);

        // 检查当前测试是否需要 DataFixtures 数据
        $currentTest = $this->name();
        if ('testCountWithDataFixtureShouldReturnGreaterThanZero' === $currentTest) {
            // 为 count 测试创建测试数据
            $certificate = new Certificate();
            $certificate->setTitle('Test Certificate for Count');
            $certificate->setUser($this->createNormalUser('test@example.com', 'password'));
            $certificate->setValid(true);
            self::getEntityManager()->persist($certificate);
            self::getEntityManager()->flush();
        }
    }

    public function testFindWithEmptyStringIdShouldReturnNull(): void
    {
        $result = $this->repository->find('');
        $this->assertNull($result);
    }

    public function testSaveWithoutFlushShouldNotPersistEntity(): void
    {
        $certificate = $this->createCertificate();

        $this->repository->save($certificate, false);
        $id = $certificate->getId();
        self::getEntityManager()->clear();

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithFlushShouldDeleteEntity(): void
    {
        $certificate = $this->createCertificate();
        $this->repository->save($certificate);

        $id = $certificate->getId();
        $this->repository->remove($certificate, true);

        $result = $this->repository->find($id);
        $this->assertNull($result);
    }

    public function testRemoveWithoutFlushShouldNotDeleteEntity(): void
    {
        $certificate = $this->createCertificate();
        $this->repository->save($certificate);

        $id = $certificate->getId();
        $this->repository->remove($certificate, false);

        $result = $this->repository->find($id);
        $this->assertInstanceOf(Certificate::class, $result);
    }

    private function createCertificate(): Certificate
    {
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate ' . uniqid());
        $certificate->setUser($this->createNormalUser('test@example.com', 'password'));
        $certificate->setValid(true);

        return $certificate;
    }

    protected function createNewEntity(): object
    {
        $certificate = new Certificate();
        $certificate->setTitle('Test Certificate ' . uniqid());
        $certificate->setUser($this->createNormalUser('test@example.com', 'password'));
        $certificate->setImgUrl('https://cdn.example.com/test-cert.jpg');
        $certificate->setValid(true);

        return $certificate;
    }

    /**
     * @return CertificateRepository
     */
    protected function getRepository(): CertificateRepository
    {
        return $this->repository;
    }
}
