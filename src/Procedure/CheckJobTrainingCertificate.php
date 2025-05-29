<?php

namespace Tourze\TrainCertBundle\Procedure;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tourze\JsonRPC\Core\Attribute\MethodDoc;
use Tourze\JsonRPC\Core\Attribute\MethodExpose;
use Tourze\JsonRPC\Core\Attribute\MethodParam;
use Tourze\JsonRPC\Core\Exception\ApiException;
use Tourze\JsonRPC\Core\Model\JsonRpcParams;
use Tourze\JsonRPCLockBundle\Procedure\LockableProcedure;
use Tourze\JsonRPCLogBundle\Attribute\Log;
use Tourze\TrainCertBundle\Repository\CertificateRepository;

#[MethodDoc('查询证件信息')]
#[MethodExpose('CheckJobTrainingCertificate')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Log]
class CheckJobTrainingCertificate extends LockableProcedure
{
    #[MethodParam('身份证号码')]
    public string $idcard;

    #[MethodParam('姓名')]
    public string $name;

    #[MethodParam('证书编号')]
    public string $number;

    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly CertificateRepository $certificateRepository,
        private readonly Security $security,
    ) {
    }

    public function execute(): array
    {
        $student = $this->studentRepository->findOneBy([
            'realName' => $this->name,
            'idCardNumber' => $this->idcard,
        ]);
        if (!$student) {
            throw new ApiException('找不到学员信息');
        }

        $cert = $this->certificateRepository->findOneBy([
            'id' => $this->number,
            'student' => $student,
        ]);
        if (!$cert) {
            throw new ApiException('找不到证书信息');
        }

        return [
            'id' => $cert->getId(),
            'createTime' => $cert->getCreateTime()->format('Y-m-d H:i:s'),
            '__showToast' => "证书[{$this->number}]有效",
        ];
    }

    protected function getLockResource(JsonRpcParams $params): array
    {
        return [
            $this->security->getUser()->getUserIdentifier(),
            static::getProcedureName() . $params->get('number', ''),
        ];
    }
}
