<?php

namespace Hgabka\MediaBundle\Repository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;

/**
 * FolderRepository.
 *
 * @method FolderRepository persistAsFirstChild(object $node)
 * @method FolderRepository persistAsFirstChildOf(object $node, object $parent)
 * @method FolderRepository persistAsLastChild(object $node)
 * @method FolderRepository persistAsLastChildOf(object $node, object $parent)
 * @method FolderRepository persistAsNextSibling(object $node)
 * @method FolderRepository persistAsNextSiblingOf(object $node, object $sibling)
 * @method FolderRepository persistAsPrevSibling(object $node)
 * @method FolderRepository persistAsPrevSiblingOf(object $node, object $sibling)
 */
class FolderRepository extends NestedTreeRepository
{
    /**
     * @param Folder $folder The folder
     *
     * @throws \Exception
     */
    public function save(Folder $folder)
    {
        $em = $this->getEntityManager();
        $parent = $folder->getParent();

        $em->beginTransaction();

        try {
            if (null !== $parent) {
                $this->persistInOrderedTree($folder, $parent);
            } else {
                $em->persist($folder);
            }
            $em->commit();
            $em->flush();
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }
    }

    public function delete(Folder $folder)
    {
        $em = $this->getEntityManager();

        $this->deleteMedia($folder);
        $this->deleteChildren($folder);
        $folder->setDeleted(true);
        $em->persist($folder);
        $em->flush();
    }

    /**
     * @param bool $alsoDeleteFolders
     */
    public function emptyFolder(Folder $folder, $alsoDeleteFolders = false)
    {
        $em = $this->getEntityManager();
        $this->deleteMedia($folder);
        if ($alsoDeleteFolders) {
            $this->deleteChildren($folder);
        }
        $em->flush();
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function getAllFolders($limit = null)
    {
        $qb = $this->createQueryBuilder('folder')
            ->select('folder')
            ->where('folder.parent is null AND folder.deleted != true')
            ->orderBy('folder.name');

        if (false === (null === $limit)) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $folderId
     *
     * @throws EntityNotFoundException
     *
     * @return object
     */
    public function getFolder($folderId)
    {
        $folder = $this->find($folderId);
        if (!$folder) {
            throw new EntityNotFoundException();
        }

        return $folder;
    }

    public function getFirstTopFolder()
    {
        $folder = $this->findOneBy(['parent' => null]);
        if (!$folder) {
            throw new EntityNotFoundException();
        }

        return $folder;
    }

    public function getParentIds(Folder $folder)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getPathQueryBuilder($folder)
            ->select('node.id');

        $result = $qb->getQuery()->getScalarResult();
        $ids = array_map('current', $result);

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function getPathQueryBuilder($node)
    {
        /** @var QueryBuilder $qb */
        $qb = parent::getPathQueryBuilder($node);
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        /** @var QueryBuilder $qb */
        $qb = parent::getRootNodesQueryBuilder($sortByField, $direction);
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function childrenQueryBuilder(
        $node = null,
        $direct = false,
        $sortByField = null,
        $direction = 'ASC',
        $includeNode = false
    ) {
        /** @var QueryBuilder $qb */
        $qb = parent::childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getLeafsQueryBuilder($root = null, $sortByField = null, $direction = 'ASC')
    {
        /** @var QueryBuilder $qb */
        $qb = parent::getLeafsQueryBuilder($root, $sortByField, $direction);
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getNextSiblingsQueryBuilder($node, $includeSelf = false)
    {
        /** @var QueryBuilder $qb */
        $qb = parent::getNextSiblingsQueryBuilder($node, $includeSelf);
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrevSiblingsQueryBuilder($node, $includeSelf = false)
    {
        /** @var QueryBuilder $qb */
        $qb = parent::getPrevSiblingsQueryBuilder($node, $includeSelf);
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchyQueryBuilder(
        $node = null,
        $direct = false,
        array $options = [],
        $includeNode = false
    ) {
        /** @var QueryBuilder $qb */
        $qb = parent::getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode);
        $qb->leftJoin($qb->getRootAliases()[0].'.translations', 'ft');
        $qb->addSelect('ft');
        $qb->andWhere('node.deleted != true');

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        $query = $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode);
        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        return $query->getArrayResult();
    }

    /**
     * Rebuild the nested tree.
     */
    public function rebuildTree()
    {
        $em = $this->getEntityManager();

        // Reset tree...
        $sql = 'UPDATE hg_media_folders SET lvl=NULL,lft=NULL,rgt=NULL';
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();

        $folders =
            $this
                ->createQueryBuilder('f')
                ->leftJoin('f.translations', 'ft')
                ->orderBy('f.parent', 'ASC')
                ->addOrderBy('ft.name', 'ASC')
                ->where('f.deleted = false')
                ->getQuery()
                ->getResult()
        ;

        $rootFolder = $folders[0];
        $first = true;
        foreach ($folders as $folder) {
            // Force parent load
            $parent = $folder->getParent();
            if (null === $parent) {
                $folder->setLevel(0);
                if ($first) {
                    $this->persistAsFirstChild($folder);
                    $first = false;
                } else {
                    $this->persistAsNextSiblingOf($folder, $rootFolder);
                }
            } else {
                $folder->setLevel($parent->getLevel() + 1);
                $this->persistAsLastChildOf($folder, $parent);
            }
        }
        $em->flush();
    }

    /**
     * Used as querybuilder for Folder entity selectors.
     *
     * @param Folder $ignoreSubtree Folder (with children) that has to be filtered out (optional)
     *
     * @return QueryBuilder
     */
    public function selectFolderQueryBuilder(Folder $ignoreSubtree = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('f');
        $qb->where('f.deleted != true')
            ->orderBy('f.lft');

        // Fetch all folders except the current one and its children
        if (null !== $ignoreSubtree && null !== $ignoreSubtree->getId()) {
            $orX = $qb->expr()->orX();
            $orX->add('f.rgt > :right')
                ->add('f.lft < :left');

            $qb->andWhere($orX)
                ->setParameter('left', $ignoreSubtree->getLeft())
                ->setParameter('right', $ignoreSubtree->getRight());
        }

        return $qb;
    }

    /**
     * Used as querybuilder for Folder entity selectors under $parent.
     *
     * @param Folder $parent        Folder used as root
     * @param mixed  $includeParent
     *
     * @return QueryBuilder
     */
    public function selectParentFolderQueryBuilder($parent = null, $includeParent = true)
    {
        if (\is_string($parent)) {
            $parentFolder = $this->findOneByInternalName($parent);
        } else {
            $parentFolder = $parent;
        }

        if (null === $parentFolder || !$parentFolder instanceof Folder) {
            return $this->selectFolderQueryBuilder();
        }

        /** @var QueryBuilder $qb */
        $qb = $this->createQueryBuilder('f');
        $qb->where('f.deleted != true');
        if ($includeParent) {
            $qb
                ->andWhere('f.lft >= :left')
                ->andWhere('f.rgt <= :right')
            ;
        } else {
            $qb
                ->andWhere('f.lft > :left')
                ->andWhere('f.rgt < :right')
            ;
        }
        $qb
            ->orderBy('f.lft')
            ->setParameter('left', $parentFolder->getLeft())
            ->setParameter('right', $parentFolder->getRight())
        ;

        return $qb;
    }

    private function deleteMedia(Folder $folder)
    {
        $em = $this->getEntityManager();

        /** @var Media $media */
        foreach ($folder->getMedia() as $media) {
            $media->setDeleted(true);
            $em->persist($media);
        }
    }

    private function deleteChildren(Folder $folder)
    {
        $em = $this->getEntityManager();

        /** @var Folder $child */
        foreach ($folder->getChildren() as $child) {
            $this->deleteMedia($child);
            $this->deleteChildren($child);
            if (!$child->isInternal()) {
                $child->setDeleted(true);
            }
            $em->persist($child);
        }
    }

    /**
     * @param $parent
     */
    private function persistInOrderedTree(Folder $folder, $parent)
    {
        // Find where to insert the new item
        $children = $parent->getChildren(true);
        if ($children->isEmpty()) {
            // No children yet - insert as first child
            $this->persistAsFirstChildOf($folder, $parent);
        } else {
            $previousChild = null;
            foreach ($children as $child) {
                // Alphabetical sorting - could be nice if we implemented a sorting strategy
                if (strcasecmp($folder->getName(), $child->getName()) < 0) {
                    break;
                }
                $previousChild = $child;
            }
            if (null === $previousChild) {
                $this->persistAsPrevSiblingOf($folder, $children[0]);
            } else {
                $this->persistAsNextSiblingOf($folder, $previousChild);
            }
        }
    }
}
