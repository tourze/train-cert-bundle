<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainCertBundle\Entity\CertificateAudit;

/**
 * 证书审核Repository
 * 
 * @method CertificateAudit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CertificateAudit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CertificateAudit[]    findAll()
 * @method CertificateAudit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificateAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateAudit::class);
    }

    /**
     * 根据申请ID查找审核记录
     */
    public function findByApplicationId(string $applicationId): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.application = :applicationId')
            ->setParameter('applicationId', $applicationId)
            ->orderBy('ca.auditTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找待审核的记录
     */
    public function findPendingAudits(): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.auditStatus = :status')
            ->setParameter('status', 'pending')
            ->orderBy('ca.createTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据审核人查找审核记录
     */
    public function findByAuditor(string $auditor): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.auditor = :auditor')
            ->setParameter('auditor', $auditor)
            ->orderBy('ca.auditTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已完成的审核记录
     */
    public function findCompletedAudits(): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.auditStatus IN (:statuses)')
            ->setParameter('statuses', ['approved', 'rejected', 'completed'])
            ->orderBy('ca.auditTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 