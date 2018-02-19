<?php

namespace Hgabka\MediaBundle\Helper;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;

class FolderManager
{
    /** @var \Hgabka\MediaBundle\Repository\FolderRepository $repository */
    private $repository;

    /**
     * @var \Hgabka\MediaBundle\Repository\FolderRepository
     */
    public function __construct(FolderRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Folder $rootFolder
     *
     * @return array|string
     */
    public function getFolderHierarchy(Folder $rootFolder)
    {
        return $this->repository->childrenHierarchy($rootFolder);
    }

    /**
     * @param Folder $folder
     *
     * @return Folder
     */
    public function getRootFolderFor(Folder $folder)
    {
        $parentIds = $this->getParentIds($folder);

        return $this->repository->getFolder($parentIds[0]);
    }

    /**
     * @param Folder $folder
     *
     * @return array
     */
    public function getParentIds(Folder $folder)
    {
        return $this->repository->getParentIds($folder);
    }

    /**
     * @param Folder $folder
     *
     * @return array
     */
    public function getParents(Folder $folder)
    {
        return $this->repository->getPath($folder);
    }
}
