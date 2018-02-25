<?php

namespace Hgabka\MediaBundle\Admin;

use Hgabka\MediaBundle\Helper\MediaManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;

class MediaAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'mediatar';

    /** @var MediaManager */
    private $manager;

    public function setManager(MediaManager $manager)
    {
        $this->manager = $manager;
    }

    public function getBatchActions()
    {
        return [];
    }
}
