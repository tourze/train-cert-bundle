<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\TrainCertBundle\Entity\CertificateRecord;

/**
 * 证书记录Repository
 *
 * @method CertificateRecord|null find($id, $lockMode = null, $lockVersion = null)
 * @method CertificateRecord|null findOneBy(array $criteria, array $orderBy = null)
 * @method CertificateRecord[]    findAll()
 * @method CertificateRecord[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificateRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateRecord::class);
    }

    /**
     * 根据证书编号查找记录
     */
    public function findByCertificateNumber(string $certificateNumber): ?CertificateRecord
    {
        return $this->findOneBy(['certificateNumber' => $certificateNumber]);
    }

    /**
     * 根据验证码查找记录
     */
    public function findByVerificationCode(string $verificationCode): ?CertificateRecord
    {
        return $this->findOneBy(['verificationCode' => $verificationCode]);
    }

    /**
     * 查找即将过期的证书
     */
    public function findExpiringCertificates(int $days = 30): array
    {
        $expiryDate = new \DateTime();
        $expiryDate->add(new \DateInterval("P{$days}D"));

        return $this->createQueryBuilder('cr')
            ->andWhere('cr.expiryDate <= :expiryDate')
            ->andWhere('cr.expiryDate > :now')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('now', new \DateTime())
            ->orderBy('cr.expiryDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找已过期的证书
     */
    public function findExpiredCertificates(): array
    {
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.expiryDate < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('cr.expiryDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据证书类型查找记录
     */
    public function findByCertificateType(string $certificateType): array
    {
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.certificateType = :type')
            ->setParameter('type', $certificateType)
            ->orderBy('cr.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据发证机构查找记录
     */
    public function findByIssuingAuthority(string $authority): array
    {
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.issuingAuthority = :authority')
            ->setParameter('authority', $authority)
            ->orderBy('cr.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 