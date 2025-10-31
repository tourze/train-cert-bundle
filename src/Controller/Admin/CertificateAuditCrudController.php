<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\TrainCertBundle\Entity\CertificateAudit;

/**
 * 证书审核管理控制器
 *
 * @extends AbstractCrudController<CertificateAudit>
 */
#[AdminCrud(routePath: '/train-cert/certificate-audit', routeName: 'train_cert_certificate_audit')]
final class CertificateAuditCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CertificateAudit::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('证书审核')
            ->setEntityLabelInPlural('证书审核')
            ->setPageTitle('index', '证书审核管理')
            ->setPageTitle('new', '创建审核记录')
            ->setPageTitle('edit', '编辑审核记录')
            ->setPageTitle('detail', '审核记录详情')
            ->setDefaultSort(['auditTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', '通过审核', 'fa fa-check')
            ->linkToCrudAction('approveAudit')
            ->setCssClass('btn btn-success')
            ->displayIf(static function (CertificateAudit $audit) {
                return 'pending' === $audit->getAuditStatus();
            })
        ;

        $reject = Action::new('reject', '拒绝审核', 'fa fa-times')
            ->linkToCrudAction('rejectAudit')
            ->setCssClass('btn btn-danger')
            ->displayIf(static function (CertificateAudit $audit) {
                return 'pending' === $audit->getAuditStatus();
            })
        ;

        $reopen = Action::new('reopen', '重新审核', 'fa fa-redo')
            ->linkToCrudAction('reopenAudit')
            ->setCssClass('btn btn-warning')
            ->displayIf(static function (CertificateAudit $audit) {
                return $audit->isCompleted();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $reject)
            ->add(Crud::PAGE_INDEX, $reopen)
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->add(Crud::PAGE_DETAIL, $reopen)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('auditStatus', '审核状态')
                ->setChoices([
                    '待审核' => 'pending',
                    '审核中' => 'in_progress',
                    '已通过' => 'approved',
                    '已拒绝' => 'rejected',
                    '已完成' => 'completed',
                ]))
            ->add(ChoiceFilter::new('auditResult', '审核结果')
                ->setChoices([
                    '通过' => 'approved',
                    '拒绝' => 'rejected',
                    '需要补充材料' => 'require_documents',
                ]))
            ->add(DateTimeFilter::new('auditTime', '审核时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID')
                ->hideOnForm(),

            AssociationField::new('application', '关联申请')
                ->setRequired(true)
                ->autocomplete()
                ->setHelp('关联的证书申请'),

            ChoiceField::new('auditStatus', '审核状态')
                ->setChoices([
                    '待审核' => 'pending',
                    '审核中' => 'in_progress',
                    '已通过' => 'approved',
                    '已拒绝' => 'rejected',
                    '已完成' => 'completed',
                ])
                ->setRequired(true)
                ->renderExpanded(false),

            ChoiceField::new('auditResult', '审核结果')
                ->setChoices([
                    '通过' => 'approved',
                    '拒绝' => 'rejected',
                    '需要补充材料' => 'require_documents',
                ])
                ->setHelp('审核的最终结果'),

            TextareaField::new('auditComment', '审核意见')
                ->setNumOfRows(4)
                ->setHelp('详细的审核意见和建议')
                ->hideOnIndex(),

            ArrayField::new('auditDetails', '审核详情')
                ->setHelp('审核过程中的详细信息')
                ->hideOnIndex(),

            TextField::new('auditor', '审核人')
                ->setMaxLength(100)
                ->setHelp('执行审核的人员'),

            DateTimeField::new('auditTime', '审核时间')
                ->setRequired(true)
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('createTime', '创建时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),

            DateTimeField::new('updateTime', '更新时间')
                ->hideOnForm()
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }

    /**
     * 通过审核操作
     */
    #[AdminAction(routeName: 'train_cert_audit_approve', routePath: '/approve')]
    public function approveAudit(): Response
    {
        // TODO: 实现审核通过逻辑
        $this->addFlash('success', '审核已通过');

        return $this->redirectToRoute('admin');
    }

    /**
     * 拒绝审核操作
     */
    #[AdminAction(routeName: 'train_cert_audit_reject', routePath: '/reject')]
    public function rejectAudit(): Response
    {
        // TODO: 实现审核拒绝逻辑
        $this->addFlash('warning', '审核已拒绝');

        return $this->redirectToRoute('admin');
    }

    /**
     * 重新审核操作
     */
    #[AdminAction(routeName: 'train_cert_audit_reopen', routePath: '/reopen')]
    public function reopenAudit(): Response
    {
        // TODO: 实现重新审核逻辑
        $this->addFlash('info', '审核已重新开启');

        return $this->redirectToRoute('admin');
    }
}
