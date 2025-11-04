<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainCertBundle\Entity\CertificateRecord;

/**
 * 证书记录Repository
 *
 * @extends ServiceEntityRepository<CertificateRecord>
 */
#[AsRepository(entityClass: CertificateRecord::class)]
class CertificateRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateRecord::class);
    }

    public function save(CertificateRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CertificateRecord $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据证书编号查找记录
     *
     * @phpstan-return CertificateRecord|null
     */
    public function findByCertificateNumber(string $certificateNumber): ?CertificateRecord
    {
        return $this->findOneBy(['certificateNumber' => $certificateNumber]);
    }

    /**
     * 根据验证码查找记录
     *
     * @phpstan-return CertificateRecord|null
     */
    public function findByVerificationCode(string $verificationCode): ?CertificateRecord
    {
        return $this->findOneBy(['verificationCode' => $verificationCode]);
    }

    /**
     * 查找即将过期的证书
     *
     * @return array<int, CertificateRecord>
     *
     * @phpstan-return array<int, CertificateRecord>
     */
    public function findExpiringCertificates(int $days = 30): array
    {
        $expiryDate = new \DateTime();
        $expiryDate->add(new \DateInterval("P{$days}D"));

        /** @var array<int, CertificateRecord> */
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.expiryDate <= :expiryDate')
            ->andWhere('cr.expiryDate > :now')
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('now', new \DateTime())
            ->orderBy('cr.expiryDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找已过期的证书
     *
     * @return array<int, CertificateRecord>
     *
     * @phpstan-return array<int, CertificateRecord>
     */
    public function findExpiredCertificates(): array
    {
        /** @var array<int, CertificateRecord> */
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.expiryDate < :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('cr.expiryDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据证书类型查找记录
     *
     * @return array<int, CertificateRecord>
     *
     * @phpstan-return array<int, CertificateRecord>
     */
    public function findByCertificateType(string $certificateType): array
    {
        /** @var array<int, CertificateRecord> */
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.certificateType = :type')
            ->setParameter('type', $certificateType)
            ->orderBy('cr.issueDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据发证机构查找记录
     *
     * @return array<int, CertificateRecord>
     *
     * @phpstan-return array<int, CertificateRecord>
     */
    public function findByIssuingAuthority(string $authority): array
    {
        /** @var array<int, CertificateRecord> */
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.issuingAuthority = :authority')
            ->setParameter('authority', $authority)
            ->orderBy('cr.issueDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找过期超过指定日期的证书记录
     *
     * @return array<int, CertificateRecord>
     *
     * @phpstan-return array<int, CertificateRecord>
     */
    public function findExpiredBefore(\DateTimeInterface $expiredDate): array
    {
        /** @var array<int, CertificateRecord> */
        return $this->createQueryBuilder('cr')
            ->andWhere('cr.expiryDate < :expiredDate')
            ->setParameter('expiredDate', $expiredDate)
            ->getQuery()
            ->getResult()
        ;
    }
}
