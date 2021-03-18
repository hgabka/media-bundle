<?php

namespace Hgabka\MediaBundle\Helper;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;

class FolderManager
{
    /** @var \Hgabka\MediaBundle\Repository\FolderRepository */
    private $repository;

    /**
     * @var \Hgabka\MediaBundle\Repository\FolderRepository
     */
    public function __construct(FolderRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return array|string
     */
    public function getFolderHierarchy(Folder $rootFolder)
    {
        return $this->repository->childrenHierarchy($rootFolder);
    }

    /**
     * @return Folder
     */
    public function getRootFolderFor(Folder $folder)
    {
        $parentIds = $this->getParentIds($folder);

        return $this->repository->getFolder($parentIds[0]);
    }

    /**
     * @return array
     */
    public function getParentIds(Folder $folder)
    {
        return $this->repository->getParentIds($folder);
    }

    /**
     * @return array
     */
    public function getParents(Folder $folder)
    {
        return $this->repository->getPath($folder);
    }
}
