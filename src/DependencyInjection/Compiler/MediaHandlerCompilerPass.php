<?php

namespace Hgabka\MediaBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * MediaHandlerCompilerPass.
 */
class MediaHandlerCompilerPass implements CompilerPassInterface
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('hgabka_media.media_manager')) {
            $definition = $container->getDefinition('hgabka_media.media_manager');

            foreach ($container->findTaggedServiceIds('hgabka_media.media_handler') as $id => $attributes) {
                $definition->addMethodCall('addHandler', [new Reference($id)]);
            }
        }

        if ($container->hasDefinition('hgabka_media.icon_font_manager')) {
            $definition = $container->getDefinition('hgabka_media.icon_font_manager');

            foreach ($container->findTaggedServiceIds('hgabka_media.icon_font.loader') as $id => $attributes) {
                $definition->addMethodCall('addLoader', [new Reference($id), $id]);
            }
        }
    }
}
