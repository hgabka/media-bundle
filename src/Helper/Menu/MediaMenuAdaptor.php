<?php

namespace Hgabka\MediaBundle\Helper\Menu;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Hgabka\UtilsBundle\Helper\Menu\MenuAdaptorInterface;
use Hgabka\UtilsBundle\Helper\Menu\MenuBuilder;
use Hgabka\UtilsBundle\Helper\Menu\MenuItem;
use Hgabka\UtilsBundle\Helper\Menu\TopMenuItem;
use Symfony\Component\HttpFoundation\Request;

/**
 * The Media Menu Adaptor.
 */
class MediaMenuAdaptor implements MenuAdaptorInterface
{
    /**
     * @var FolderRepository
     */
    private $repo;

    /**
     * @param FolderRepository $repo
     */
    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    /**
     * In this method you can add children for a specific parent, but also remove and change the already created children.
     *
     * @param MenuBuilder $menu      The MenuBuilder
     * @param MenuItem[]  &$children The current children
     * @param MenuItem    $parent    The parent Menu item
     * @param Request     $request   The Request
     */
    public function adaptChildren(MenuBuilder $menu, array &$children, ?MenuItem $parent = null, ?Request $request = null)
    {
        if (null === $parent) {
            // Add menu item for root gallery
            $rootFolders = $this->repo->getRootNodes();
            $currentId = $request->get('folderId');
            $currentFolder = null;
            if (isset($currentId)) {
                // @var Folder $currentFolder
                $currentFolder = $this->repo->find($currentId);
            }

            /** @var Folder $rootFolder */
            foreach ($rootFolders as $rootFolder) {
                $menuItem = new TopMenuItem($menu);
                $menuItem
                    ->setRoute('HgabkaMediaBundle_folder_show')
                    ->setRouteparams(['folderId' => $rootFolder->getId()])
                    ->setUniqueId('folder-' . $rootFolder->getId())
                    ->setLabel($rootFolder->getName())
                    ->setParent(null)
                    ->setRole($rootFolder->getRel());

                if (null !== $currentFolder) {
                    $parentIds = $this->repo->getParentIds($currentFolder);
                    if (\in_array($rootFolder->getId(), $parentIds, true)) {
                        $menuItem->setActive(true);
                    }
                }
                $children[] = $menuItem;
            }
        }
    }
}
