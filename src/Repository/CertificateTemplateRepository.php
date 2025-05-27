<?php

namespace Tourze\TrainCertBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DoctrineEnhanceBundle\Repository\CommonRepositoryAware;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

/**
 * 证书模板Repository
 * 
 * @method CertificateTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method CertificateTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method CertificateTemplate[]    findAll()
 * @method CertificateTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CertificateTemplateRepository extends ServiceEntityRepository
{
    use CommonRepositoryAware;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CertificateTemplate::class);
    }

    /**
     * 查找启用的模板
     */
    public function findActiveTemplates(): array
    {
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ct.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据类型查找模板
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.templateType = :type')
            ->andWhere('ct.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('active', true)
            ->orderBy('ct.templateName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找默认模板
     */
    public function findDefaultTemplate(): ?CertificateTemplate
    {
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.isDefault = :default')
            ->andWhere('ct.isActive = :active')
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 根据类型查找默认模板
     */
    public function findDefaultTemplateByType(string $type): ?CertificateTemplate
    {
        return $this->createQueryBuilder('ct')
            ->andWhere('ct.templateType = :type')
            ->andWhere('ct.isDefault = :default')
            ->andWhere('ct.isActive = :active')
            ->setParameter('type', $type)
            ->setParameter('default', true)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 