<?php

namespace Hgabka\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     *
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('hgabka_media');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('liip_imagine_cache_prefix')->defaultValue('uploads/cache')->end()
                ->scalarNode('liip_imagine_web_root_dir')->defaultValue('%kernel.project_dir%/public')->end()
                ->scalarNode('soundcloud_api_key')->defaultValue('YOUR_CLIENT_ID')->end()
                ->arrayNode('remote_video')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('vimeo')->defaultTrue()->end()
                        ->booleanNode('youtube')->defaultTrue()->end()
                        ->booleanNode('dailymotion')->defaultTrue()->end()
                    ->end()
                ->end()
                ->booleanNode('enable_pdf_preview')->defaultFalse()->end()
                ->arrayNode('blacklisted_extensions')
                    ->defaultValue(['php', 'htaccess'])
                    ->prototype('scalar')->end()
                ->end()
                ->integerNode('folder_depth')->defaultValue(4)->end()
                ->scalarNode('protected_media_download_role')->defaultValue('ROLE_MEDIA_ADMIN')->end()
                ->arrayNode('default_ckeditor_folders')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('images')->defaultValue('imageroot')->end()
                        ->scalarNode('files')->defaultValue('fileroot')->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
