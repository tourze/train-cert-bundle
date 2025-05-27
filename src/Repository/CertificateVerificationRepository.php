<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

/**
 * 证书验证Repository
 * 
 * @method CertificateVerification|null find($id, $lockMode = null, $lockVersion = null)
 * @method CertificateVerification|null findOneBy(array $criteria, array $orderBy = null)
 * @method CertificateVerification[]    findAll()
 * @method CertificateVerification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificateVerificationRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateVerification::class);
    }

    /**
     * 根据证书ID查找验证记录
     */
    public function findByCertificateId(string $certificateId): array
    {
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.certificate = :certificateId')
            ->setParameter('certificateId', $certificateId)
            ->orderBy('cv.verificationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找成功的验证记录
     */
    public function findSuccessfulVerifications(): array
    {
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.verificationResult = :result')
            ->setParameter('result', true)
            ->orderBy('cv.verificationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据IP地址查找验证记录
     */
    public function findByIpAddress(string $ipAddress): array
    {
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.ipAddress = :ipAddress')
            ->setParameter('ipAddress', $ipAddress)
            ->orderBy('cv.verificationTime', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 