<?php

namespace Hgabka\MediaBundle\Helper\File;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

/**
 * SVGMimeTypeGuesser.
 */
class SVGExtensionGuesser implements ExtensionGuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guess($mimeType)
    {
        if ('image/svg+xml' === $mimeType) {
            return 'svg';
        }

        return null;
    }
}
