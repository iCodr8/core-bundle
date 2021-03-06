<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator as ParentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the UrlGenerator class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UrlGeneratorTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        unset($GLOBALS['TL_AUTO_ITEM']);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Routing\UrlGenerator', $this->getGenerator());
    }

    /**
     * Tests the setContext() method.
     */
    public function testCanWriteTheContext()
    {
        $generator = new UrlGenerator(
            new ParentUrlGenerator(new RouteCollection(), new RequestContext()),
            $this->mockContaoFramework(),
            false
        );

        $context = new RequestContext();
        $generator->setContext($context);

        $this->assertSame($context, $generator->getContext());
    }

    /**
     * Tests the router.
     */
    public function testGeneratesUrls()
    {
        $this->assertSame('contao_frontend', $this->getGenerator(false, 0)->generate('foobar'));
        $this->assertSame('contao_frontend', $this->getGenerator(true, 0)->generate('foobar'));
        $this->assertSame('contao_frontend', $this->getGenerator(false, 0)->generate('foobar/test'));
    }

    /**
     * Tests the router without parameters.
     */
    public function testGeneratesUrlsWithoutParameters()
    {
        $this->assertSame('foobar', $this->getGenerator()->generate('foobar')['alias']);
        $this->assertSame('foobar/test', $this->getGenerator()->generate('foobar/test')['alias']);
        $this->assertSame('foobar/article/test', $this->getGenerator()->generate('foobar/article/test')['alias']);
    }

    /**
     * Tests that the index fragment is omitted.
     */
    public function testOmitsTheIndexFragment()
    {
        $this->assertSame('contao_index', $this->getGenerator(false, 0)->generate('index'));
        $this->assertSame('contao_index', $this->getGenerator(true, 0)->generate('index'));
        $this->assertArrayNotHasKey('alias', $this->getGenerator()->generate('index'));

        $this->assertSame('contao_frontend', $this->getGenerator(false, 0)->generate('index/foobar'));
        $this->assertArrayHasKey('alias', $this->getGenerator()->generate('index/foobar'));

        $this->assertSame(
            'contao_frontend',
            $this->getGenerator(false, 0)->generate('index/{foo}', ['foo' => 'bar'])
        );

        $this->assertArrayHasKey('alias', $this->getGenerator()->generate('index/{foo}', ['foo' => 'bar']));
        $this->assertSame('index/foo/bar', $this->getGenerator()->generate('index/{foo}', ['foo' => 'bar'])['alias']);
    }

    /**
     * Tests that the locale is removed if prepend_locale is not set.
     */
    public function testRemovesTheLocaleIfPrependLocaleIsNotSet()
    {
        $params = $this->getGenerator(false)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayNotHasKey('_locale', $params);

        $params = $this->getGenerator(true)->generate('foobar', ['_locale' => 'en']);

        $this->assertArrayHasKey('_locale', $params);
    }

    /**
     * Tests the parameter replacement.
     */
    public function testReplacesParameters()
    {
        $params = ['items' => 'bar', 'article' => 'test'];

        $result = $this->getGenerator()->generate('foo/{article}', $params);

        $this->assertSame('foo/article/test', $result['alias']);
        $this->assertArrayNotHasKey('article', $result);
        $this->assertArrayHasKey('items', $result);

        $result = $this->getGenerator()->generate('foo/{items}/{article}', $params);

        $this->assertSame('foo/items/bar/article/test', $result['alias']);
        $this->assertArrayNotHasKey('article', $result);
        $this->assertArrayNotHasKey('items', $result);
    }

    /**
     * Tests the auto_item support.
     */
    public function testHandlesAutoItems()
    {
        $this->assertSame(
            'foo/bar',
            $this->getGenerator()->generate(
                'foo/{items}',
                ['items' => 'bar', 'auto_item' => 'items']
            )['alias']
        );

        $this->assertSame(
            'foo/bar/article/test',
            $this->getGenerator()->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this->assertSame(
            'foo/bar',
            $this->getGenerator()->generate(
                'foo/{items}',
                ['items' => 'bar']
            )['alias']
        );

        $this->assertSame(
            'foo/bar/article/test',
            $this->getGenerator()->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );
    }

    /**
     * Tests the router with auto_item being disabled.
     */
    public function testIgnoresAutoItemsIfTheyAreDisabled()
    {
        $this->assertSame(
            'foo/items/bar',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}',
                ['items' => 'bar', 'auto_item' => 'items']
            )['alias']
        );

        $this->assertSame(
            'foo/items/bar/article/test',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );

        $GLOBALS['TL_AUTO_ITEM'] = ['article', 'items'];

        $this->assertSame(
            'foo/items/bar',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}',
                ['items' => 'bar']
            )['alias']
        );

        $this->assertSame(
            'foo/items/bar/article/test',
            $this->getGenerator(false, 1, false)->generate(
                'foo/{items}/{article}',
                ['items' => 'bar', 'article' => 'test', 'auto_item' => 'items']
            )['alias']
        );
    }

    /**
     * Tests that an exception is thrown if a parameter is missing.
     */
    public function testFailsIfAParameterIsMissing()
    {
        $this->expectException(MissingMandatoryParametersException::class);

        $this->getGenerator()->generate('foo/{article}');
    }

    /**
     * Tests setting the context from a domain.
     */
    public function testReadsTheContextFromTheDomain()
    {
        $routes = new RouteCollection();
        $routes->add('contao_index', new Route('/'));

        $generator = new UrlGenerator(
            new ParentUrlGenerator($routes, new RequestContext()),
            $this->mockContaoFramework(),
            false
        );

        $this->assertSame(
            'https://contao.org/',
            $generator->generate(
                'index',
                ['_domain' => 'contao.org:443', '_ssl' => true],
                UrlGeneratorInterface::ABSOLUTE_URL
           )
        );

        $this->assertSame(
            'http://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org'], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $this->assertSame(
            'http://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org:80'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * Tests that the context is not modified if the hostname is set.
     *
     * To tests this case, we omit the _ssl parameter and set the scheme to "https" in the context. If the
     * generator still returns a HTTPS URL, we know that the context has not been modified.
     */
    public function testDoesNotModifyTheContextIfThereIsAHostname()
    {
        $routes = new RouteCollection();
        $routes->add('contao_index', new Route('/'));

        $context = new RequestContext();
        $context->setHost('contao.org');
        $context->setScheme('https');

        $generator = new UrlGenerator(
            new ParentUrlGenerator($routes, $context),
            $this->mockContaoFramework(),
            false
        );

        $this->assertSame(
            'https://contao.org/',
            $generator->generate('index', ['_domain' => 'contao.org'], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    /**
     * Tests the generator with non-array parameters.
     */
    public function testHandlesNonArrayParameters()
    {
        $this->assertSame('foo', $this->getGenerator()->generate('foo', 'bar')['alias']);
    }

    /**
     * Returns an UrlGenerator object.
     *
     * @param bool $prependLocale
     * @param int  $returnArgument
     * @param bool $useAutoItem
     *
     * @return UrlGenerator
     */
    private function getGenerator($prependLocale = false, $returnArgument = 1, $useAutoItem = true)
    {
        $router = $this->createMock(UrlGeneratorInterface::class);

        $router
            ->method('generate')
            ->willReturnArgument($returnArgument)
        ;

        $router
            ->method('getContext')
            ->willReturn(new RequestContext())
        ;

        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) use ($useAutoItem) {
                switch ($key) {
                    case 'useAutoItem':
                        return $useAutoItem;

                    case 'timeZone':
                        return 'Europe/Berlin';

                    default:
                        return null;
                }
            })
        ;

        return new UrlGenerator(
            $router,
            $this->mockContaoFramework(null, null, [Config::class => $configAdapter]),
            $prependLocale
        );
    }
}
