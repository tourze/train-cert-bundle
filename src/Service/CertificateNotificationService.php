<?php

namespace Tourze\TrainCertBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Tourze\TrainCertBundle\Entity\Certificate;
use Tourze\TrainCertBundle\Entity\CertificateRecord;
use Tourze\TrainCertBundle\Repository\CertificateRecordRepository;

/**
 * 证书通知服务类
 * 负责证书相关的通知功能，包括发放通知、验证通知、过期提醒等
 */
class CertificateNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly CertificateRecordRepository $recordRepository
    ) {
    }

    /**
     * 发送证书发放通知
     * 
     * @param string $certificateId 证书ID
     * @return void
     */
    public function sendCertificateIssueNotification(string $certificateId): void
    {
        try {
            $record = $this->recordRepository->findOneBy(['certificate' => $certificateId]);
            if (!$record) {
                $this->logger->warning('证书记录不存在，无法发送发放通知', ['certificateId' => $certificateId]);
                return;
            }

            $certificate = $record->getCertificate();
            $user = $certificate->getUser();

            // 构建邮件内容
            $subject = '证书发放通知 - ' . $certificate->getTitle();
            $content = $this->buildIssueNotificationContent($certificate, $record);

            // 发送邮件
            $this->sendEmail($user->getUserIdentifier(), $subject, $content);

            $this->logger->info('证书发放通知已发送', [
                'certificateId' => $certificateId,
                'recipient' => $user->getUserIdentifier(),
            ]);

        } catch  (\Throwable $e) {
            $this->logger->error('发送证书发放通知失败', [
                'certificateId' => $certificateId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 发送证书验证通知
     * 
     * @param string $certificateId 证书ID
     * @param array $verificationData 验证数据
     * @return void
     */
    public function sendVerificationNotification(string $certificateId, array $verificationData): void
    {
        try {
            $record = $this->recordRepository->findOneBy(['certificate' => $certificateId]);
            if (!$record) {
                $this->logger->warning('证书记录不存在，无法发送验证通知', ['certificateId' => $certificateId]);
                return;
            }

            $certificate = $record->getCertificate();
            $user = $certificate->getUser();

            // 构建邮件内容
            $subject = '证书验证通知 - ' . $certificate->getTitle();
            $content = $this->buildVerificationNotificationContent($certificate, $record, $verificationData);

            // 发送邮件
            $this->sendEmail($user->getUserIdentifier(), $subject, $content);

            $this->logger->info('证书验证通知已发送', [
                'certificateId' => $certificateId,
                'recipient' => $user->getUserIdentifier(),
                'verificationResult' => $verificationData['valid'] ?? false,
            ]);

        } catch  (\Throwable $e) {
            $this->logger->error('发送证书验证通知失败', [
                'certificateId' => $certificateId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 发送证书过期提醒
     * 
     * @param int $days 提前天数
     * @return void
     */
    public function sendExpiryReminders(int $days = 30): void
    {
        try {
            $expiringRecords = $this->recordRepository->findExpiringCertificates($days);

            foreach ($expiringRecords as $record) {
                $this->sendSingleExpiryReminder($record);
            }

            $this->logger->info('证书过期提醒批量发送完成', [
                'count' => count($expiringRecords),
                'days' => $days,
            ]);

        } catch  (\Throwable $e) {
            $this->logger->error('发送证书过期提醒失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 发送单个证书过期提醒
     * 
     * @param CertificateRecord $record 证书记录
     * @return void
     */
    public function sendSingleExpiryReminder(CertificateRecord $record): void
    {
        try {
            $certificate = $record->getCertificate();
            $user = $certificate->getUser();

            // 构建邮件内容
            $subject = '证书即将过期提醒 - ' . $certificate->getTitle();
            $content = $this->buildExpiryReminderContent($certificate, $record);

            // 发送邮件
            $this->sendEmail($user->getUserIdentifier(), $subject, $content);

            $this->logger->info('证书过期提醒已发送', [
                'certificateId' => $certificate->getId(),
                'recipient' => $user->getUserIdentifier(),
                'expiryDate' => $record->getExpiryDate()->format('Y-m-d'),
                'remainingDays' => $record->getRemainingDays(),
            ]);

        } catch  (\Throwable $e) {
            $this->logger->error('发送单个证书过期提醒失败', [
                'certificateId' => $record->getCertificate()->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 发送证书撤销通知
     * 
     * @param string $certificateId 证书ID
     * @param string $reason 撤销原因
     * @return void
     */
    public function sendRevocationNotification(string $certificateId, string $reason): void
    {
        try {
            $record = $this->recordRepository->findOneBy(['certificate' => $certificateId]);
            if (!$record) {
                $this->logger->warning('证书记录不存在，无法发送撤销通知', ['certificateId' => $certificateId]);
                return;
            }

            $certificate = $record->getCertificate();
            $user = $certificate->getUser();

            // 构建邮件内容
            $subject = '证书撤销通知 - ' . $certificate->getTitle();
            $content = $this->buildRevocationNotificationContent($certificate, $record, $reason);

            // 发送邮件
            $this->sendEmail($user->getUserIdentifier(), $subject, $content);

            $this->logger->info('证书撤销通知已发送', [
                'certificateId' => $certificateId,
                'recipient' => $user->getUserIdentifier(),
                'reason' => $reason,
            ]);

        } catch  (\Throwable $e) {
            $this->logger->error('发送证书撤销通知失败', [
                'certificateId' => $certificateId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 发送邮件
     * 
     * @param string $recipient 收件人
     * @param string $subject 主题
     * @param string $content 内容
     * @return void
     */
    private function sendEmail(string $recipient, string $subject, string $content): void
    {
        $email = (new Email())
            ->from('noreply@training-system.com')
            ->to($recipient)
            ->subject($subject)
            ->html($content);

        $this->mailer->send($email);
    }

    /**
     * 构建证书发放通知内容
     * 
     * @param Certificate $certificate 证书对象
     * @param CertificateRecord $record 证书记录
     * @return string 邮件内容
     */
    private function buildIssueNotificationContent(Certificate $certificate, CertificateRecord $record): string
    {
        return sprintf(
            '<h2>证书发放通知</h2>
            <p>尊敬的用户，</p>
            <p>您的证书已成功发放，详细信息如下：</p>
            <ul>
                <li><strong>证书名称：</strong>%s</li>
                <li><strong>证书编号：</strong>%s</li>
                <li><strong>发证日期：</strong>%s</li>
                <li><strong>有效期至：</strong>%s</li>
                <li><strong>发证机构：</strong>%s</li>
                <li><strong>验证码：</strong>%s</li>
            </ul>
            <p>请妥善保管您的证书信息。</p>
            <p>如有疑问，请联系我们。</p>',
            $certificate->getTitle(),
            $record->getCertificateNumber(),
            $record->getIssueDate()->format('Y-m-d'),
            $record->getExpiryDate()->format('Y-m-d'),
            $record->getIssuingAuthority(),
            $record->getVerificationCode()
        );
    }

    /**
     * 构建证书验证通知内容
     * 
     * @param Certificate $certificate 证书对象
     * @param CertificateRecord $record 证书记录
     * @param array $verificationData 验证数据
     * @return string 邮件内容
     */
    private function buildVerificationNotificationContent(Certificate $certificate, CertificateRecord $record, array $verificationData): string
    {
        $result = $verificationData['valid'] ? '验证通过' : '验证失败';
        $time = date('Y-m-d H:i:s');

        return sprintf(
            '<h2>证书验证通知</h2>
            <p>尊敬的用户，</p>
            <p>您的证书在 %s 被验证，结果为：<strong>%s</strong></p>
            <ul>
                <li><strong>证书名称：</strong>%s</li>
                <li><strong>证书编号：</strong>%s</li>
                <li><strong>验证时间：</strong>%s</li>
                <li><strong>验证结果：</strong>%s</li>
            </ul>
            <p>如果这不是您本人的操作，请及时联系我们。</p>',
            $time,
            $result,
            $certificate->getTitle(),
            $record->getCertificateNumber(),
            $time,
            $result
        );
    }

    /**
     * 构建证书过期提醒内容
     * 
     * @param Certificate $certificate 证书对象
     * @param CertificateRecord $record 证书记录
     * @return string 邮件内容
     */
    private function buildExpiryReminderContent(Certificate $certificate, CertificateRecord $record): string
    {
        return sprintf(
            '<h2>证书即将过期提醒</h2>
            <p>尊敬的用户，</p>
            <p>您的证书即将过期，请及时办理续期手续：</p>
            <ul>
                <li><strong>证书名称：</strong>%s</li>
                <li><strong>证书编号：</strong>%s</li>
                <li><strong>过期日期：</strong>%s</li>
                <li><strong>剩余天数：</strong>%d 天</li>
            </ul>
            <p>为避免影响您的正常使用，请尽快联系我们办理续期手续。</p>',
            $certificate->getTitle(),
            $record->getCertificateNumber(),
            $record->getExpiryDate()->format('Y-m-d'),
            $record->getRemainingDays()
        );
    }

    /**
     * 构建证书撤销通知内容
     * 
     * @param Certificate $certificate 证书对象
     * @param CertificateRecord $record 证书记录
     * @param string $reason 撤销原因
     * @return string 邮件内容
     */
    private function buildRevocationNotificationContent(Certificate $certificate, CertificateRecord $record, string $reason): string
    {
        return sprintf(
            '<h2>证书撤销通知</h2>
            <p>尊敬的用户，</p>
            <p>很遗憾地通知您，您的证书已被撤销：</p>
            <ul>
                <li><strong>证书名称：</strong>%s</li>
                <li><strong>证书编号：</strong>%s</li>
                <li><strong>撤销原因：</strong>%s</li>
                <li><strong>撤销时间：</strong>%s</li>
            </ul>
            <p>如有疑问，请联系我们了解详情。</p>',
            $certificate->getTitle(),
            $record->getCertificateNumber(),
            $reason,
            date('Y-m-d H:i:s')
        );
    }
} 