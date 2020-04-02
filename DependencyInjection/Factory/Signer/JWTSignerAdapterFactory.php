<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle\DependencyInjection\Factory\Signer;

use Arthem\JWTRequestSigner\JWTRequestSigner;
use Arthem\RequestSignerBundle\DependencyInjection\Factory\SignerAdapterFactoryInterface;
use Arthem\RequestSignerBundle\Signer\JWTSignerAdapter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class JWTSignerAdapterFactory implements SignerAdapterFactoryInterface
{
    public function getKey(): string
    {
        return 'jwt';
    }

    public function create(ContainerBuilder $container, $id, array $config): void
    {
        if (!class_exists(JWTRequestSigner::class)) {
            throw new InvalidArgumentException('Please run composer require arthem/jwt-request-signer');
        }

        $container
            ->setDefinition($id.'.client', new Definition(JWTRequestSigner::class))
            ->setArgument(0, $config['signing_key'])
            ->setArgument(1, $config['ttl'])
            ->setArgument(2, $config['query_param_name'])
        ;

        $container
            ->setDefinition($id, new Definition(JWTSignerAdapter::class))
            ->setArgument('$signer', new Reference($id.'.client'))
        ;
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('ttl')->defaultValue(3600)->end()
                ->scalarNode('query_param_name')->defaultValue('token')->cannotBeEmpty()->end()
                ->scalarNode('signing_key')->isRequired()->end()
            ->end()
        ;
    }
}
