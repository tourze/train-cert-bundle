<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

/**
 * 证书模板Repository
 *
 * @extends ServiceEntityRepository<CertificateTemplate>
 */
#[AsRepository(entityClass: CertificateTemplate::class)]
class CertificateTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateTemplate::class);
    }

    public function save(CertificateTemplate $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CertificateTemplate $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 查找启用的模板
     *
     * @return array<int, CertificateTemplate>
     *
     * @phpstan-return array<int, CertificateTemplate>
     */
    public function findActiveTemplates(): array
    {
        /** @var array<int, CertificateTemplate> */
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ct.templateName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据类型查找模板
     *
     * @return array<int, CertificateTemplate>
     *
     * @phpstan-return array<int, CertificateTemplate>
     */
    public function findByType(string $type): array
    {
        /** @var array<int, CertificateTemplate> */
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.templateType = :type')
            ->andWhere('ct.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('ct.templateName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找默认模板
     *
     * @phpstan-return CertificateTemplate|null
     */
    public function findDefaultTemplate(): ?CertificateTemplate
    {
        /** @var CertificateTemplate|null */
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.isDefault = :default')
            ->andWhere('ct.isActive = :active')
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据类型查找默认模板
     *
     * @phpstan-return CertificateTemplate|null
     */
    public function findDefaultTemplateByType(string $type): ?CertificateTemplate
    {
        /** @var CertificateTemplate|null */
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.templateType = :type')
            ->andWhere('ct.isDefault = :default')
            ->andWhere('ct.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 清除指定类型的默认模板设置
     *
     * @param string      $templateType 模板类型
     * @param string|null $excludeId    排除的模板ID
     */
    public function clearDefaultTemplate(string $templateType, ?string $excludeId = null): void
    {
        $qb = $this->createQueryBuilder('t');
        $qb->update()
            ->set('t.isDefault', ':false')
            ->where('t.templateType = :type')
            ->andWhere('t.isDefault = :true')
            ->setParameter('false', false)
            ->setParameter('type', $templateType)
            ->setParameter('true', true)
        ;

        if (null !== $excludeId && '' !== $excludeId) {
            $qb->andWhere('t.id != :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        $qb->getQuery()->execute();
    }
}
