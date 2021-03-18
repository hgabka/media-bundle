<?php

namespace Hgabka\MediaBundle\Repository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityRepository;
use Hgabka\MediaBundle\Entity\Media;

/**
 * MediaRepository.
 */
class MediaRepository extends EntityRepository
{
    public function save(Media $media)
    {
        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();
    }

    public function delete(Media $media)
    {
        $em = $this->getEntityManager();
        $media->setDeleted(true);
        $em->persist($media);
        $em->flush();
    }

    /**
     * @param int $mediaId
     *
     * @throws EntityNotFoundException
     *
     * @return object
     */
    public function getMedia($mediaId)
    {
        $media = $this->find($mediaId);
        if (!$media) {
            throw new EntityNotFoundException();
        }

        return $media;
    }

    /**
     * @param int $pictureId
     *
     * @throws EntityNotFoundException
     *
     * @return object
     */
    public function getPicture($pictureId)
    {
        $em = $this->getEntityManager();

        $picture = $em->getRepository('KunstmaanMediaBundle:Image')->find($pictureId);
        if (!$picture) {
            throw new EntityNotFoundException();
        }

        return $picture;
    }

    /**
     * Finds all Media  that has their deleted flag set to 1
     * and have their remove_from_file_system flag set to 0.
     *
     * @return object[]
     */
    public function findAllDeleted()
    {
        return $this->findBy(['deleted' => true, 'removedFromFileSystem' => false]);
    }

    public function getMediaForFolder($folder, $orderByField = null, $orderDirection = 'ASC', $includeDeleted = false)
    {
        $qb =
            $this
                ->createQueryBuilder('m')
                ->where('m.folder = :folder')
                ->setParameter('folder', $folder)
        ;
        if (null !== $orderByField) {
            $qb->orderBy('m.'.$orderByField, $orderDirection);
        }

        if (!$includeDeleted) {
            $qb->andWhere('m.deleted = 0');
        }

        return $qb->getQuery()->getResult();
    }
}
