<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use Tourze\TrainCertBundle\Entity\CertificateApplication;

/**
 * 证书申请Repository
 * 
 * @method CertificateApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method CertificateApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method CertificateApplication[]    findAll()
 * @method CertificateApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificateApplicationRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateApplication::class);
    }

    /**
     * 根据用户ID查找申请记录
     */
    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ca.applicationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据状态查找申请记录
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.applicationStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('ca.applicationTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找待审核的申请
     */
    public function findPendingApplications(): array
    {
        return $this->findByStatus('pending');
    }

    /**
     * 查找已通过的申请
     */
    public function findApprovedApplications(): array
    {
        return $this->findByStatus('approved');
    }

    /**
     * 根据模板ID查找申请记录
     */
    public function findByTemplateId(string $templateId): array
    {
        return $this->createQueryBuilder('ca')
            ->andWhere('ca.template = :templateId')
            ->setParameter('templateId', $templateId)
            ->orderBy('ca.applicationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 