<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\MediaBundle\Traits\MediaControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseMediaController extends Controller
{
    use MediaControllerTrait;

    public function getAdmin()
    {
        return $this->get('hg_media.admin.media');
    }
}
