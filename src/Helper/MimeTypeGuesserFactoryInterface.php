<?php

namespace Hgabka\MediaBundle\Helper;

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

interface MimeTypeGuesserFactoryInterface
{
    /**
     * @return MimeTypeGuesserInterface
     */
    public function get();
}
