<?php

namespace Hgabka\MediaBundle\Helper;

use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;

interface ExtensionGuesserFactoryInterface
{
    /**
     * @return ExtensionGuesserInterface
     */
    public function get();
}
