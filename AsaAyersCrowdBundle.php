<?php

namespace AsaAyers\CrowdBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use AsaAyers\CrowdBundle\DependencyInjection\Security\Factory\SsoFactory;
use AsaAyers\CrowdBundle\DependencyInjection\Security\Factory\CrowdFactory;

class AsaAyersCrowdBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new SsoFactory());
        $extension->addSecurityListenerFactory(new CrowdFactory());
    }
}
