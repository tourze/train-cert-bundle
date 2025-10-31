<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainCertBundle\Entity\CertificateVerification;

/**
 * 证书验证Repository
 *
 * @extends ServiceEntityRepository<CertificateVerification>
 */
#[AsRepository(entityClass: CertificateVerification::class)]
class CertificateVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateVerification::class);
    }

    public function save(CertificateVerification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CertificateVerification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 根据证书ID查找验证记录
     *
     * @return array<int, CertificateVerification>
     *
     * @phpstan-return array<int, CertificateVerification>
     */
    public function findByCertificateId(string $certificateId): array
    {
        /** @var array<int, CertificateVerification> */
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.certificate = :certificateId')
            ->setParameter('certificateId', $certificateId)
            ->orderBy('cv.verificationTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找成功的验证记录
     *
     * @return array<int, CertificateVerification>
     *
     * @phpstan-return array<int, CertificateVerification>
     */
    public function findSuccessfulVerifications(): array
    {
        /** @var array<int, CertificateVerification> */
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.verificationResult = :result')
            ->setParameter('result', true)
            ->orderBy('cv.verificationTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据IP地址查找验证记录
     *
     * @return array<int, CertificateVerification>
     *
     * @phpstan-return array<int, CertificateVerification>
     */
    public function findByIpAddress(string $ipAddress): array
    {
        /** @var array<int, CertificateVerification> */
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.ipAddress = :ipAddress')
            ->setParameter('ipAddress', $ipAddress)
            ->orderBy('cv.verificationTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找指定日期之前的验证记录
     *
     * @return array<int, CertificateVerification>
     *
     * @phpstan-return array<int, CertificateVerification>
     */
    public function findVerificationsBeforeDate(\DateTimeInterface $verificationDate): array
    {
        /** @var array<int, CertificateVerification> */
        return $this->createQueryBuilder('cv')
            ->andWhere('cv.verificationTime < :verificationDate')
            ->setParameter('verificationDate', $verificationDate)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取验证统计数据
     *
     * @param \DateTimeInterface|null $startDate 开始日期
     * @param \DateTimeInterface|null $endDate   结束日期
     *
     * @return array<string, mixed> 统计数据
     *
     * @phpstan-return array<string, mixed>
     */
    public function getVerificationStatistics(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('v');
        $qb->select('COUNT(v.id) as total_verifications')
            ->addSelect('SUM(CASE WHEN v.verificationResult = true THEN 1 ELSE 0 END) as successful_verifications')
            ->addSelect('SUM(CASE WHEN v.verificationResult = false THEN 1 ELSE 0 END) as failed_verifications')
        ;

        if ((bool) $startDate) {
            $qb->andWhere('v.verificationTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if ((bool) $endDate) {
            $qb->andWhere('v.verificationTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<string, mixed> */
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 获取指定证书在特定时间窗口内的验证次数
     *
     * @param string             $certificateId 证书ID
     * @param \DateTimeInterface $since         时间窗口起始时间
     *
     * @return int 验证次数
     */
    public function countVerificationsSince(string $certificateId, \DateTimeInterface $since): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.certificate = :certificateId')
            ->andWhere('v.verificationTime >= :since')
            ->setParameter('certificateId', $certificateId)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
