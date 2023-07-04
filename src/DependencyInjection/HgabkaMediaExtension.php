<?php

namespace Hgabka\MediaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class HgabkaMediaExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Loads configuration.
     *
     * @param array            $configs   Configuration
     * @param ContainerBuilder $container Container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $container->setParameter(
            'twig.form.resources',
            array_merge(
                $container->getParameter('twig.form.resources'),
                ['@HgabkaMedia/Form/formWidgets.html.twig']
            )
        );
        $container->setParameter('hgabka_media.soundcloud_api_key', $config['soundcloud_api_key']);
        $container->setParameter('hgabka_media.remote_video', $config['remote_video']);
        $container->setParameter('hgabka_media.default_ckeditor_folders', $config['default_ckeditor_folders']);
        $container->setParameter('hgabka_media.enable_pdf_preview', $config['enable_pdf_preview']);
        $container->setParameter('hgabka_media.blacklisted_extensions', $config['blacklisted_extensions']);
        $container->setParameter('hgabka_media.folder_depth', $config['folder_depth']);
        $container->setParameter('hgabka_media.protected_media_download_role', $config['protected_media_download_role']);

        $loader->load('services.yml');
        $loader->load('handlers.yml');

        if (true === $config['enable_pdf_preview']) {
            $loader->load('pdf_preview.yml');
        }

        $container->setParameter('liip_imagine.filter.loader.background.class', 'Hgabka\MediaBundle\Helper\Imagine\BackgroundFilterLoader');
        $container->setParameter('liip_imagine.cache.manager.class', 'Hgabka\MediaBundle\Helper\Imagine\CacheManager');
        $container->setParameter('liip_imagine.cache.resolver.web_path.class', 'Hgabka\MediaBundle\Helper\Imagine\WebPathResolver');
        $container->setParameter('liip_imagine.controller.class', 'Hgabka\MediaBundle\Helper\Imagine\ImagineController');

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('imagine.xml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('hgabka_media.upload_dir')) {
            $container->setParameter('hgabka_media.upload_dir', '/uploads/media/');
        }

        $twigConfig = [];
        $twigConfig['globals']['upload_dir'] = $container->getParameter('hgabka_media.upload_dir');
        $twigConfig['globals']['mediabundleisactive'] = true;
        $twigConfig['globals']['mediamanager'] = '@hgabka_media.media_manager';
        $container->prependExtensionConfig('twig', $twigConfig);

        $liipConfig = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/imagine_filters.yml'));
        $container->prependExtensionConfig('liip_imagine', $liipConfig['liip_imagine']);

        $configs = $container->getExtensionConfig($this->getAlias());
        $this->processConfiguration(new Configuration(), $configs);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'hgabka_media';
    }
}
