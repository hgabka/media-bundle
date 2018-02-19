<?php

namespace Hgabka\MediaBundle;

use Hgabka\MediaBundle\DependencyInjection\Compiler\MediaHandlerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * KunstmaanMediaBundle.
 */
class HgabkaMediaBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MediaHandlerCompilerPass());
    }
}
