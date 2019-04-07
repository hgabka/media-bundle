<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\MediaBundle\Traits\MediaControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseMediaController extends AbstractController
{
    use MediaControllerTrait;

    public function getAdmin()
    {
        return $this->get('hg_media.admin.media');
    }
}
