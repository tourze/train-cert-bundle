<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainCertBundle\Entity\CertificateApplication;

/**
 * 证书申请Repository
 *
 * @extends ServiceEntityRepository<CertificateApplication>
 */
#[AsRepository(entityClass: CertificateApplication::class)]
class CertificateApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateApplication::class);
    }

    public function save(CertificateApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CertificateApplication $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据用户ID查找申请记录
     *
     * @return array<int, CertificateApplication>
     *
     * @phpstan-return array<int, CertificateApplication>
     */
    public function findByUserId(string $userId): array
    {
        /** @var array<int, CertificateApplication> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ca.applicationTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据状态查找申请记录
     *
     * @return array<int, CertificateApplication>
     *
     * @phpstan-return array<int, CertificateApplication>
     */
    public function findByStatus(string $status): array
    {
        /** @var array<int, CertificateApplication> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.applicationStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('ca.applicationTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找待审核的申请
     *
     * @return array<int, CertificateApplication>
     */
    public function findPendingApplications(): array
    {
        return $this->findByStatus('pending');
    }

    /**
     * 查找已通过的申请
     *
     * @return array<int, CertificateApplication>
     */
    public function findApprovedApplications(): array
    {
        return $this->findByStatus('approved');
    }

    /**
     * 根据模板ID查找申请记录
     *
     * @return array<int, CertificateApplication>
     *
     * @phpstan-return array<int, CertificateApplication>
     */
    public function findByTemplateId(string $templateId): array
    {
        /** @var array<int, CertificateApplication> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.template = :templateId')
            ->setParameter('templateId', $templateId)
            ->orderBy('ca.applicationTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
