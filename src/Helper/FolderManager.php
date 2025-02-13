<?php

namespace Hgabka\MediaBundle\Helper;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Symfony\Bundle\SecurityBundle\Security;

class FolderManager
{
    private FolderRepository $repository;

    private Security $security;

    public function __construct(FolderRepository $repository, Security $security)
    {
        $this->repository = $repository;
        $this->security = $security;
    }

    /**
     * @return array|string
     */
    public function getFolderHierarchy(Folder $rootFolder): array|string
    {
        return $this->repository->childrenHierarchy($rootFolder);
    }

    /**
     * @return Folder
     */
    public function getRootFolderFor(Folder $folder): Folder
    {
        $parentIds = $this->getParentIds($folder);

        return $this->repository->getFolder($parentIds[0]);
    }

    /**
     * @return array
     */
    public function getParentIds(Folder $folder): array
    {
        return $this->repository->getParentIds($folder);
    }

    /**
     * @return array
     */
    public function getParents(Folder $folder): array
    {
        return $this->repository->getPath($folder);
    }

    public function isFolderTraversable(Folder|int|string $folder): bool
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
