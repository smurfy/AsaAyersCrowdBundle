<?php

namespace AsaAyers\CrowdBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class SsoFactory implements SecurityFactoryInterface
{
	public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
	{
		$providerId = 'security.authentication.provider.crowd.'.$id;
		$container
			->setDefinition($providerId, new DefinitionDecorator('crowd.security.authentication.provider'))
			->replaceArgument(0, new Reference($userProvider))
			;

		$listenerId = 'security.authentication.listener.sso.'.$id;
		$listener = $container->setDefinition($listenerId, new DefinitionDecorator('sso.security.authentication.listener'));

		return array($providerId, $listenerId, $defaultEntryPoint);
	}

	public function addConfiguration(NodeDefinition $node)
	{
		$builder = $node ->children();
		$builder->end();
		;
	}

	public function getPosition()
	{
		return 'http';
	}

	public function getKey()
	{
		return 'crowd_sso';
	}

}

