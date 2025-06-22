<?php

namespace Tourze\TrainCertBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Entity\CertificateVerification;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;
use Tourze\TrainCertBundle\Repository\CertificateVerificationRepository;

/**
 * 证书验证服务类
 * 负责证书的验证、查询、验证记录管理等功能
 */
class CertificateVerificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CertificateRecordRepository $recordRepository,
        private readonly CertificateVerificationRepository $verificationRepository,
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * 验证证书
     *
     * @param string $certificateNumber 证书编号
     * @return array 验证结果
     */
    public function verifyCertificate(string $certificateNumber): array
    {
        $record = $this->recordRepository->findOneBy(['certificateNumber' => $certificateNumber]);
        
        if ($record === null) {
            $result = [
                'valid' => false,
                'message' => '证书不存在',
                'data' => null,
            ];
        } else {
            $result = $this->validateCertificateRecord($record);
        }

        // 记录验证过程
        $this->recordVerification($record?->getCertificate(), 'certificate_number', $result);

        return $result;
    }

    /**
     * 通过验证码验证证书
     *
     * @param string $verificationCode 验证码
     * @return array 验证结果
     */
    public function verifyByVerificationCode(string $verificationCode): array
    {
        $record = $this->recordRepository->findOneBy(['verificationCode' => $verificationCode]);
        
        if ($record === null) {
            $result = [
                'valid' => false,
                'message' => '验证码无效',
                'data' => null,
            ];
        } else {
            $result = $this->validateCertificateRecord($record);
        }

        // 记录验证过程
        $this->recordVerification($record?->getCertificate(), 'verification_code', $result);

        return $result;
    }

    /**
     * 记录验证过程
     *
     * @param Certificate|null $certificate 证书对象
     * @param string $verificationMethod 验证方式
     * @param array $verificationData 验证数据
     * @return CertificateVerification 验证记录
     */
    public function recordVerification(?Certificate $certificate, string $verificationMethod, array $verificationData): CertificateVerification
    {
        $verification = new CertificateVerification();
        
        if ((bool) $certificate) {
            $verification->setCertificate($certificate);
        }
        
        $verification->setVerificationMethod($verificationMethod);
        $verification->setVerificationResult($verificationData['valid']);
        $verification->setVerificationDetails($verificationData);

        // 获取请求信息
        $request = $this->requestStack->getCurrentRequest();
        if ((bool) $request) {
            $verification->setIpAddress($request->getClientIp());
            $verification->setUserAgent($request->headers->get('User-Agent'));
            $verification->setVerifierInfo($this->extractVerifierInfo($request));
        }

        $this->entityManager->persist($verification);
        $this->entityManager->flush();

        return $verification;
    }

    /**
     * 批量验证证书
     *
     * @param array $certificateNumbers 证书编号列表
     * @return array 验证结果列表
     */
    public function batchVerifyCertificates(array $certificateNumbers): array
    {
        $results = [];
        
        foreach ($certificateNumbers as $number) {
            $results[$number] = $this->verifyCertificate($number);
        }

        return $results;
    }

    /**
     * 获取证书详细信息
     *
     * @param string $certificateNumber 证书编号
     * @return array|null 证书信息
     */
    public function getCertificateDetails(string $certificateNumber): ?array
    {
        $record = $this->recordRepository->findOneBy(['certificateNumber' => $certificateNumber]);
        
        if ($record === null) {
            return null;
        }

        $certificate = $record->getCertificate();
        
        return [
            'certificateNumber' => $record->getCertificateNumber(),
            'certificateType' => $record->getCertificateType(),
            'holderName' => $certificate->getUser()->getUserIdentifier(),
            'title' => $certificate->getTitle(),
            'issueDate' => $record->getIssueDate()->format('Y-m-d'),
            'expiryDate' => $record->getExpiryDate()->format('Y-m-d'),
            'issuingAuthority' => $record->getIssuingAuthority(),
            'verificationCode' => $record->getVerificationCode(),
            'isValid' => $certificate->isValid(),
            'isExpired' => $record->isExpired(),
            'remainingDays' => $record->getRemainingDays(),
            'metadata' => $record->getMetadata(),
        ];
    }

    /**
     * 获取验证历史
     *
     * @param string $certificateId 证书ID
     * @return CertificateVerification[] 验证历史
     */
    public function getVerificationHistory(string $certificateId): array
    {
        return $this->verificationRepository->findByCertificateId($certificateId);
    }

    /**
     * 获取验证统计
     *
     * @param \DateTimeInterface|null $startDate 开始日期
     * @param \DateTimeInterface|null $endDate 结束日期
     * @return array 统计数据
     */
    public function getVerificationStatistics(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(v.id) as total_verifications')
           ->addSelect('SUM(CASE WHEN v.verificationResult = true THEN 1 ELSE 0 END) as successful_verifications')
           ->addSelect('SUM(CASE WHEN v.verificationResult = false THEN 1 ELSE 0 END) as failed_verifications')
           ->from(CertificateVerification::class, 'v');

        if ((bool) $startDate) {
            $qb->andWhere('v.verificationTime >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ((bool) $endDate) {
            $qb->andWhere('v.verificationTime <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $result = $qb->getQuery()->getSingleResult();

        return [
            'totalVerifications' => (int) $result['total_verifications'],
            'successfulVerifications' => (int) $result['successful_verifications'],
            'failedVerifications' => (int) $result['failed_verifications'],
            'successRate' => $result['total_verifications'] > 0 
                ? round($result['successful_verifications'] / $result['total_verifications'] * 100, 2) 
                : 0,
        ];
    }

    /**
     * 检查证书是否被频繁验证
     *
     * @param string $certificateId 证书ID
     * @param int $timeWindow 时间窗口（秒）
     * @param int $threshold 阈值
     * @return bool 是否频繁验证
     */
    public function isFrequentlyVerified(string $certificateId, int $timeWindow = 3600, int $threshold = 10): bool
    {
        $since = new \DateTime();
        $since->sub(new \DateInterval("PT{$timeWindow}S"));

        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(CertificateVerification::class, 'v')
            ->where('v.certificate = :certificateId')
            ->andWhere('v.verificationTime >= :since')
            ->setParameter('certificateId', $certificateId)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return $count >= $threshold;
    }

    /**
     * 验证证书记录
     *
     * @param CertificateRecord $record 证书记录
     * @return array 验证结果
     */
    private function validateCertificateRecord(CertificateRecord $record): array
    {
        $certificate = $record->getCertificate();
        $errors = [];
        $warnings = [];

        // 检查证书是否有效
        if (!$certificate->isValid()) {
            $errors[] = '证书已被撤销或无效';
        }

        // 检查证书是否过期
        if ($record->isExpired()) {
            $errors[] = '证书已过期';
        } elseif ($record->getRemainingDays() <= 30) {
            $warnings[] = "证书将在 {$record->getRemainingDays()} 天后过期";
        }

        $isValid = empty($errors);

        return [
            'valid' => $isValid,
            'message' => $isValid ? '证书验证通过' : implode(', ', $errors),
            'warnings' => $warnings,
            'data' => [
                'certificateNumber' => $record->getCertificateNumber(),
                'certificateType' => $record->getCertificateType(),
                'holderName' => $certificate->getUser()->getUserIdentifier(),
                'title' => $certificate->getTitle(),
                'issueDate' => $record->getIssueDate()->format('Y-m-d'),
                'expiryDate' => $record->getExpiryDate()->format('Y-m-d'),
                'issuingAuthority' => $record->getIssuingAuthority(),
                'remainingDays' => $record->getRemainingDays(),
            ],
        ];
    }

    /**
     * 提取验证者信息
     *
     * @param \Symfony\Component\HttpFoundation\Request $request 请求对象
     * @return string 验证者信息
     */
    private function extractVerifierInfo(\Symfony\Component\HttpFoundation\Request $request): string
    {
        $info = [];
        
        if (($referer = $request->headers->get('Referer')) !== null) {
            $info[] = "来源: {$referer}";
        }
        
        if (($acceptLanguage = $request->headers->get('Accept-Language')) !== null) {
            $info[] = "语言: {$acceptLanguage}";
        }

        return implode(', ', $info);
    }
} 