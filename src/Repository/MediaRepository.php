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
    /**
     * @param Media $media
     */
    public function save(Media $media)
    {
        $em = $this->getEntityManager();
        $em->persist($media);
        $em->flush();
    }

    /**
     * @param Media $media
     */
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
}
