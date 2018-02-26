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

    /**
     * Get the list of actions that can be accessed directly from the dashboard.
     *
     * @return array
     */
    public function getDashboardActions()
    {
        $actions = [];

        if ($this->hasAccess('list')) {
            $actions['list'] = [
                'label' => 'hg_media.admin.list',
                'translation_domain' => 'messages',
                'url' => $this->generateUrl('list'),
                'icon' => 'list',
            ];
        }

        return $actions;
    }
}
