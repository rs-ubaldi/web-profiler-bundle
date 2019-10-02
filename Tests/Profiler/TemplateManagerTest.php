<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\WebProfilerBundle\Tests\Profiler;

use Symfony\Bundle\WebProfilerBundle\Profiler\TemplateManager;
use Symfony\Bundle\WebProfilerBundle\Tests\TestCase;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Loader\SourceContextLoaderInterface;

/**
 * Test for TemplateManager class.
 *
 * @author Artur Wielogórski <wodor@wodor.net>
 */
class TemplateManagerTest extends TestCase
{
    /**
     * @var Environment
     */
    protected $twigEnvironment;

    /**
     * @var \Symfony\Component\HttpKernel\Profiler\Profiler
     */
    protected $profiler;

    /**
     * @var \Symfony\Bundle\WebProfilerBundle\Profiler\TemplateManager
     */
    protected $templateManager;

    protected function setUp(): void
    {
        parent::setUp();

        $profiler = $this->mockProfiler();
        $twigEnvironment = $this->mockTwigEnvironment();
        $templates = [
            'data_collector.foo' => ['foo', '@Foo/Collector/foo.html.twig'],
            'data_collector.bar' => ['bar', '@Foo/Collector/bar.html.twig'],
            'data_collector.baz' => ['baz', '@Foo/Collector/baz.html.twig'],
        ];

        $this->templateManager = new TemplateManager($profiler, $twigEnvironment, $templates);
    }

    public function testGetNameOfInvalidTemplate()
    {
        $this->expectException('Symfony\Component\HttpKernel\Exception\NotFoundHttpException');
        $this->templateManager->getName(new Profile('token'), 'notexistingpanel');
    }

    /**
     * if template exists in both profile and profiler then its name should be returned.
     */
    public function testGetNameValidTemplate()
    {
        $this->profiler->expects($this->any())
            ->method('has')
            ->withAnyParameters()
            ->willReturnCallback([$this, 'profilerHasCallback']);

        $this->assertEquals('@Foo/Collector/foo.html.twig', $this->templateManager->getName(new ProfileDummy(), 'foo'));
    }

    public function profilerHasCallback($panel)
    {
        switch ($panel) {
            case 'foo':
            case 'bar':
                return true;
            default:
                return false;
        }
    }

    public function profileHasCollectorCallback($panel)
    {
        switch ($panel) {
            case 'foo':
            case 'baz':
                return true;
            default:
                return false;
        }
    }

    protected function mockProfile()
    {
        return $this->getMockBuilder('Symfony\Component\HttpKernel\Profiler\Profile')->disableOriginalConstructor()->getMock();
    }

    protected function mockTwigEnvironment()
    {
        $loader = $this->getMockBuilder('Twig\Loader\LoaderInterface')->getMock();
        $loader->method('getSourceContext')->willReturn(new Source('source-code', 'source-name'));
        $loader->method('exists')->willReturn(true);

        $this->twigEnvironment = $this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock();
        $this->twigEnvironment->expects($this->any())->method('getLoader')->willReturn($loader);

        return $this->twigEnvironment;
    }

    protected function mockProfiler()
    {
        $this->profiler = $this->getMockBuilder('Symfony\Component\HttpKernel\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock();

        return $this->profiler;
    }
}

class ProfileDummy extends Profile
{
    public function __construct()
    {
        parent::__construct('token');
    }

    public function hasCollector($name): bool
    {
        switch ($name) {
            case 'foo':
            case 'bar':
                return true;
            default:
                return false;
        }
    }
}
