<?php

namespace Hgabka\MediaBundle\Helper;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Symfony\Component\Security\Core\Security;

class FolderManager
{
    /** @var \Hgabka\MediaBundle\Repository\FolderRepository */
    private $repository;

    /** @var Security */
    private $security;

    /**
     * @var \Hgabka\MediaBundle\Repository\FolderRepository
     */
    public function __construct(FolderRepository $repository, Security $security)
    {
        $this->repository = $repository;
        $this->security = $security;
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

    public function isFolderTraversable($folder): bool
    {
        if (!$folder instanceof Folder) {
            $folder = $this->repository->find($folder);
        }

        if (!$folder) {
            return false;
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $folder->getLevel() > 1;
        }

        return !$folder->isInternal();
    }
}
