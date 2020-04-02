<?php

namespace Arthem\RequestSignerBundle\DependencyInjection;

use LogicException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ArthemRequestSignerExtension extends Extension
{
    /**
     * @var array
     */
    private $signerAdapterFactories = [];

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $signerFactories = $this->getSignerAdapterFactories($container);
        $configuration = new Configuration($signerFactories);
        $config = $this->processConfiguration($configuration, $configs);

        $adapters = [];
        $defaultSigner = $config['default_signer'];
        foreach ($config['signers'] as $name => $signer) {
            if (!$defaultSigner) {
                $defaultSigner = $name;
            }
            $adapters[$name] = $this->createSigner($name, $signer, $container, $signerFactories);
        }

        if (!empty($adapters)) {
            $definition = $container->getDefinition('arthem_request_signer.request_signer');

            $adaptersReferences = [];
            foreach ($adapters as $name => $id) {
                $adaptersReferences[$name] = new Reference($id);
            }
            $definition->replaceArgument(2, $adaptersReferences);
            $definition->replaceArgument(3, $defaultSigner);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('factories.yaml');

        return new Configuration($this->getSignerAdapterFactories($container));
    }

    private function getSignerAdapterFactories(ContainerBuilder $container): array
    {
        if (null !== $this->signerAdapterFactories) {
            return $this->signerAdapterFactories;
        }

        $factories = [];
        $services = $container->findTaggedServiceIds('arthem_request_signer.signer_adapter_factory');

        foreach (array_keys($services) as $id) {
            $factory = $container->get($id);
            $factories[str_replace('-', '_', $factory->getKey())] = $factory;
        }

        return $this->signerAdapterFactories = $factories;
    }

    private function createSigner($name, array $config, ContainerBuilder $container, array $signerFactories): string
    {
        foreach ($config as $key => $signer) {
            if (array_key_exists($key, $signerFactories)) {
                $id = sprintf('arthem_request_signer.%s_signer', $name);
                $signerFactories[$key]->create($container, $id, $signer);

                return $id;
            }
        }

        throw new LogicException(sprintf('The signer \'%s\' is not configured.', $name));
    }
}
