<?php

namespace Hgabka\MediaBundle\Helper\Services;

use Doctrine\ORM\EntityManager;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Service to easily add a media file to an existing media folder.
 * This is especially useful in migrations or places where you want to automate the uploading of media.
 *
 * Class MediaCreatorService
 */
class MediaCreatorService
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FolderRepository
     */
    protected $folderRepository;

    /**
     * Constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $container->get('doctrine')->getManager();
        $this->folderRepository = $this->em->getRepository(Folder::class);
    }

    /**
     * @param $filePath string  The full filepath of the asset you want to upload. The filetype will be automatically detected.
     * @param $folderId integer For now you still have to manually pass the correct folder ID
     * @param mixed $andFlush
     *
     * @return Media
     */
    public function createFile($filePath, $folderId, $andFlush = true)
    {
        $fileHandler = $this->container->get('hgabka_media.media_handlers.file');

        // Get file from FilePath.
        $data = new File($filePath, true);

        /** @var $media Media */
        $media = $fileHandler->createNew($data);
        if ($folderId instanceof Folder) {
            $folder = $folderId;
        } else {
            /** @var $folder Folder */
            $folder = $this->folderRepository->getFolder($folderId);
        }
        $media->setFolder($folder);

        $fileHandler->prepareMedia($media);
        $fileHandler->updateMedia($media);
        $fileHandler->saveMedia($media);

        $this->em->persist($media);
        if ($andFlush) {
            $this->em->flush();
        }

        return $media;
    }
}
