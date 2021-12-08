<?php

namespace Hgabka\MediaBundle\Admin\Menu;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\MediaBundle\Admin\MediaAdmin;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Helper\FolderManager;
use Sonata\AdminBundle\Event\ConfigureMenuEvent;

class AdminMenuListener
{
    public const ICONS = [
        'default' => 'fas fa-file-o',
        'offline' => 'fas fa-chain-broken',
        'folder' => 'fas fa-folder-o',
        'image' => 'fas fa-picture-o',
        'files' => 'fas fa-files-o',
        'slideshow' => 'fas fa-desktop',
        'video' => 'fas fa-film',
        'media' => 'fas fa-folder-o',
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
    public function __construct(MediaAdmin $mediaAdmin, Registry $doctrine)
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
