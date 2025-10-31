<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainCertBundle\Entity\CertificateAudit;

/**
 * 证书审核Repository
 *
 * @extends ServiceEntityRepository<CertificateAudit>
 */
#[AsRepository(entityClass: CertificateAudit::class)]
class CertificateAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateAudit::class);
    }

    public function save(CertificateAudit $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CertificateAudit $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据申请ID查找审核记录
     *
     * @return array<int, CertificateAudit>
     *
     * @phpstan-return array<int, CertificateAudit>
     */
    public function findByApplicationId(string $applicationId): array
    {
        /** @var array<int, CertificateAudit> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.application = :applicationId')
            ->setParameter('applicationId', $applicationId)
            ->orderBy('ca.auditTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找待审核的记录
     *
     * @return array<int, CertificateAudit>
     *
     * @phpstan-return array<int, CertificateAudit>
     */
    public function findPendingAudits(): array
    {
        /** @var array<int, CertificateAudit> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.auditStatus = :status')
            ->setParameter('status', 'pending')
            ->orderBy('ca.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据审核人查找审核记录
     *
     * @return array<int, CertificateAudit>
     *
     * @phpstan-return array<int, CertificateAudit>
     */
    public function findByAuditor(string $auditor): array
    {
        /** @var array<int, CertificateAudit> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.auditor = :auditor')
            ->setParameter('auditor', $auditor)
            ->orderBy('ca.auditTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已完成的审核记录
     *
     * @return array<int, CertificateAudit>
     *
     * @phpstan-return array<int, CertificateAudit>
     */
    public function findCompletedAudits(): array
    {
        /** @var array<int, CertificateAudit> */
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.auditStatus IN (:statuses)')
            ->setParameter('statuses', ['approved', 'rejected', 'completed'])
            ->orderBy('ca.auditTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
