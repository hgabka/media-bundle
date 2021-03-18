<?php

namespace Hgabka\MediaBundle\Admin\Menu;

use Doctrine\Common\Persistence\ManagerRegistry;
use Hgabka\MediaBundle\Admin\MediaAdmin;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Helper\FolderManager;
use Sonata\AdminBundle\Event\ConfigureMenuEvent;

class AdminMenuListener
{
    const ICONS = [
        'default' => 'fa fa-file-o',
        'offline' => 'fa fa-chain-broken',
        'folder' => 'fa fa-folder-o',
        'image' => 'fa fa-picture-o',
        'files' => 'fa fa-files-o',
        'slideshow' => 'fa fa-desktop',
        'video' => 'fa fa-film',
        'media' => 'fa fa-folder-o',
    ];
    /** @var MediaAdmin */
    protected $mediaAdmin;

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * AdminMenuListener constructor.
     *
     * @param FolderManager $folderManager
     */
    public function __construct(MediaAdmin $mediaAdmin, ManagerRegistry $doctrine)
    {
        $this->mediaAdmin = $mediaAdmin;
        $this->doctrine = $doctrine;
    }

    public function addMenuItems(ConfigureMenuEvent $event)
    {
        if ($this->mediaAdmin->hasAccess('list')) {
            $menu = $event->getMenu();
            $group = $menu->getChild('hg_media.group');

            if ($group) {
                foreach ($group->getChildren() as $key => $child) {
                    $group->removeChild($key);
                }

                $repo = $this->doctrine->getRepository(Folder::class);
                $root = $repo->getFirstTopFolder();
                foreach ($root->getChildren() as $folder) {
                    $iconClass = static::ICONS[$folder->getRel()] ?? static::ICONS['default'];
                    $group->addChild($folder->getName(), [
                        'route' => 'admin_hgabka_media_media_list',
                        'routeParameters' => ['folderId' => $folder->getId()],
                        'label' => $folder->getName(),
                    ])->setExtra('icon', '<i class="'.$iconClass.'"></i>')
                    ;
                }
            }
        }
    }
}
