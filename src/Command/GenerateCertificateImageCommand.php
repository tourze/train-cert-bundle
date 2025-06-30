<?php

namespace Tourze\TrainCertBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\TrainCertBundle\Exception\CertificateException;

#[AsCommand(name: self::NAME, description: '生成证书图片')]
class GenerateCertificateImageCommand extends Command
{
    
    public const NAME = 'job-training:generate-certificate-image';
public function __construct(
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 不同环境用不同的执行文件
        $projectRoot = $this->kernel->getProjectDir();
        if ((bool) stristr(PHP_OS, 'DAR')) {
            $binFile = $projectRoot . '/vendor/suhanyu/wkhtmltopdf-amd64-mac-os/bin/wkhtmltoimage';
            @system("chmod +x {$binFile}");
        } elseif ((bool) stristr(PHP_OS, 'WIN')) {
            $binFile = $projectRoot . '/vendor/wemersonjanuario/wkhtmltopdf-windows/bin/64bit/wkhtmltoimage.exe';
        } elseif ((bool) stristr(PHP_OS, 'LINUX')) {
            $binFile = $projectRoot . '/vendor/h4cc/wkhtmltoimage-amd64/bin/wkhtmltoimage-amd64';
            @system("chmod +x {$binFile}");
        } else {
            throw new CertificateException('未知操作系统');
        }

        $outputFile = __DIR__ . '/output.png';
        @unlink($outputFile);

        $snappy = new \Knp\Snappy\Image($binFile);

        $snappy->setOption('width', '800');

        $html = '<hr />123';
        $snappy->generateFromHtml($html, $outputFile);

        return Command::SUCCESS;
    }
}
