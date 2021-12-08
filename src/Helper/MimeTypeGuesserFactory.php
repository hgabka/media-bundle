<?php

namespace Hgabka\MediaBundle\Helper;

use Hgabka\MediaBundle\Helper\File\SVGMimeTypeGuesser;
use Symfony\Component\Mime\MimeTypes;

class MimeTypeGuesserFactory implements MimeTypeGuesserFactoryInterface
{
    /**
     * Should return a mime type guesser instance, used for file uploads.
     *
     * NOTE: If you override this, you'll probably still have to register the SVGMimeTypeGuesser as last guesser...
     *
     * @return MimeTypeGuesser
     */
    public function get()
    {
        $guesser = new MimeTypes();
        $guesser->registerGuesser(new SVGMimeTypeGuesser());

        return $guesser;
    }
}
