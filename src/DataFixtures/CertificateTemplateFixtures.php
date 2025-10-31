<?php

declare(strict_types=1);

namespace Tourze\TrainCertBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\TrainCertBundle\Entity\CertificateTemplate;

class CertificateTemplateFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $types = ['safety', 'skill', 'management', 'special'];

        for ($i = 0; $i < 10; ++$i) {
            $template = new CertificateTemplate();
            $template->setTemplateName("Certificate Template {$i}");
            $template->setTemplateType($types[array_rand($types)]);
            $template->setIsActive((bool) rand(0, 1));
            $template->setIsDefault(0 === $i); // 第一个设为默认
            $template->setDescription("This is certificate template number {$i}");
            $template->setTemplatePath("/templates/certificate_{$i}.html");

            $manager->persist($template);
        }

        $manager->flush();
    }
}
