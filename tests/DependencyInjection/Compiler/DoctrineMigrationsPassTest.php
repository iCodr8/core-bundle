<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Tests the DoctrineMigrationsPass class.
 *
 * @author Andreas Schempp <http://github.com/aschempp>
 */
class DoctrineMigrationsPassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $pass = new DoctrineMigrationsPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\DoctrineMigrationsPass', $pass);
    }

    /**
     * Tests adding the definition if the migrations bundle is installed.
     */
    public function testAddsTheDefinitionIfTheMigrationsBundleIsInstalled()
    {
        $container = $this->createContainerBuilder([DoctrineMigrationsBundle::class]);

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertTrue($container->hasDefinition(DoctrineMigrationsPass::DIFF_COMMAND_ID));
    }

    /**
     * Tests adding the definition if the migrations bundle is not installed.
     */
    public function testDoesNotAddTheDefinitionIfTheMigrationsBundleIsNotInstalled()
    {
        $container = $this->createContainerBuilder();

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(DoctrineMigrationsPass::DIFF_COMMAND_ID));
    }

    /**
     * Tests adding the command to the "console.command" tags.
     */
    public function testAddsTheCommandIdToTheConsoleCommandIds()
    {
        $container = $this->createContainerBuilder([DoctrineMigrationsBundle::class]);

        $pass = new DoctrineMigrationsPass();
        $pass->process($container);

        $this->assertFalse($container->hasParameter('console.command.ids'));

        $container->setParameter('console.command.ids', []);

        $pass->process($container);

        $this->assertTrue($container->hasParameter('console.command.ids'));

        $this->assertContains(
            DoctrineMigrationsPass::DIFF_COMMAND_ID,
            $container->getParameter('console.command.ids')
        );
    }

    /**
     * Creates a ContainerBuilder and loads the commands.yml file.
     *
     * @param array $bundles
     *
     * @return ContainerBuilder
     */
    private function createContainerBuilder(array $bundles = [])
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->setDefinition('service_container', (new Definition(Container::class, []))->setSynthetic(true));

        $container->setDefinition(
            'contao.doctrine.schema_provider',
            (new Definition(DcaSchemaProvider::class))->addArgument('foo')
        );

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../../src/Resources/config')
        );

        $loader->load('commands.yml');

        return $container;
    }
}
