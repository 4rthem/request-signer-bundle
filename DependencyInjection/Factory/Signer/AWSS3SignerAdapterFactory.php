<?php

declare(strict_types=1);

namespace Arthem\RequestSignerBundle\DependencyInjection\Factory\Signer;

use Arthem\RequestSignerBundle\DependencyInjection\Factory\SignerAdapterFactoryInterface;
use Arthem\RequestSignerBundle\Signer\AWSS3SignerAdapter;
use Aws\S3\S3Client;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class AWSS3SignerAdapterFactory implements SignerAdapterFactoryInterface
{
    public function getKey(): string
    {
        return 'aws_s3';
    }

    public function create(ContainerBuilder $container, $id, array $config): void
    {
        if (!class_exists(S3Client::class)) {
            throw new InvalidArgumentException('Please run composer require aws/aws-sdk-php');
        }

        $container
            ->setDefinition($id, new Definition(AWSS3SignerAdapter::class))
            ->setArgument(0, new Reference($config['client']))
            ->setArgument(1, $config['bucket_name'])
            ->setArgument(2, $config['ttl'])
        ;
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('ttl')->defaultValue(1200)->end()
                ->scalarNode('signature_version')->defaultValue('v3')->end()
                ->scalarNode('signing_key')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('bucket_name')->isRequired()->end()
                ->scalarNode('client')->isRequired()->info('The S3 client service ID')->end()
            ->end()
        ;
    }
}
