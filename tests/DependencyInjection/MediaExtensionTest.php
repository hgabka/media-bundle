<?php

namespace Hgabka\MediaBundle\Tests\DependencyInjection;

use Hgabka\MediaBundle\DependencyInjection\Compiler\MediaHandlerCompilerPass;
use Hgabka\MediaBundle\DependencyInjection\Configuration;
use Hgabka\MediaBundle\DependencyInjection\HgabkaMediaExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MediaExtensionTest extends TestCase
{
    private function process(array $config = []): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('twig.form.resources', []);

        return $container;
    }

    public function testDefaults(): void
    {
        $config = $this->process();

        $this->assertSame('uploads/cache', $config['liip_imagine_cache_prefix']);
        $this->assertSame(['vimeo' => true, 'youtube' => true, 'dailymotion' => true], $config['remote_video']);
        $this->assertFalse($config['enable_pdf_preview']);
        $this->assertSame(['php', 'htaccess'], $config['blacklisted_extensions']);
        $this->assertSame(4, $config['folder_depth']);
        $this->assertSame('ROLE_MEDIA_ADMIN', $config['protected_media_download_role']);
        $this->assertSame(['images' => 'imageroot', 'files' => 'fileroot'], $config['default_ckeditor_folders']);
    }

    public function testParametersAreExposed(): void
    {
        $container = $this->container();

        (new HgabkaMediaExtension())->load([['folder_depth' => 2, 'blacklisted_extensions' => ['exe'], 'soundcloud_api_key' => 'KEY']], $container);

        $this->assertSame(2, $container->getParameter('hgabka_media.folder_depth'));
        $this->assertSame(['exe'], $container->getParameter('hgabka_media.blacklisted_extensions'));
        $this->assertSame('KEY', $container->getParameter('hgabka_media.soundcloud_api_key'));
        $this->assertSame('uploads/cache', $container->getParameter('hgabka_media.liip_imagine.cache_prefix'));
        $this->assertContains('@HgabkaMedia/Form/formWidgets.html.twig', $container->getParameter('twig.form.resources'));
        $this->assertTrue($container->hasDefinition('hgabka_media.media_handlers.image'));
    }

    public function testPdfPreviewServicesAreOptional(): void
    {
        $off = $this->container();
        (new HgabkaMediaExtension())->load([[]], $off);
        $on = $this->container();
        (new HgabkaMediaExtension())->load([['enable_pdf_preview' => true]], $on);

        $this->assertFalse($off->hasDefinition('hgabka_media.pdf_transformer'));
        $this->assertGreaterThan(count($off->getDefinitions()), count($on->getDefinitions()));
    }

    public function testPrependRegistersTwigGlobalsAndImagineFilters(): void
    {
        $container = new ContainerBuilder();

        (new HgabkaMediaExtension())->prepend($container);

        $twig = $container->getExtensionConfig('twig')[0];
        $this->assertSame('/uploads/media/', $twig['globals']['upload_dir']);
        $this->assertTrue($twig['globals']['mediabundleisactive']);
        $this->assertArrayHasKey('media_list_thumbnail', $container->getExtensionConfig('liip_imagine')[0]['filter_sets']);
        $this->assertSame('hgabka_media', (new HgabkaMediaExtension())->getAlias());
    }

    public function testCompilerPassCollectsHandlersAndIconFontLoaders(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('hgabka_media.media_manager', new Definition('stdClass'));
        $container->setDefinition('hgabka_media.icon_font_manager', new Definition('stdClass'));
        $container->setDefinition('handler.a', (new Definition('stdClass'))->addTag('hgabka_media.media_handler'));
        $container->setDefinition('handler.b', (new Definition('stdClass'))->addTag('hgabka_media.media_handler'));
        $container->setDefinition('loader.a', (new Definition('stdClass'))->addTag('hgabka_media.icon_font.loader'));

        (new MediaHandlerCompilerPass())->process($container);

        $calls = $container->getDefinition('hgabka_media.media_manager')->getMethodCalls();
        $this->assertEquals([['addHandler', [new Reference('handler.a')]], ['addHandler', [new Reference('handler.b')]]], $calls);
        $this->assertEquals([['addLoader', [new Reference('loader.a'), 'loader.a']]], $container->getDefinition('hgabka_media.icon_font_manager')->getMethodCalls());
    }

    public function testCompilerPassIsHarmlessWithoutManagers(): void
    {
        $container = new ContainerBuilder();

        (new MediaHandlerCompilerPass())->process($container);

        $this->assertCount(1, $container->getDefinitions()); // only the synthetic service_container
    }
}
