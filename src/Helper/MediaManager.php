<?php

namespace Hgabka\MediaBundle\Helper;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\File\FileHandler;
use Hgabka\MediaBundle\Helper\Media\AbstractMediaHandler;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * MediaManager.
 */
class MediaManager
{
    /**
     * @var AbstractMediaHandler[]
     */
    protected $handlers = [];

    /**
     * @var AbstractMediaHandler
     */
    protected $defaultHandler;

    /**
     * MediaManager constructor.
     */
    public function __construct(protected HgabkaUtils $utils, protected string $projectDir, protected string $mediaPath, protected string $protectedMediaPath)
    {
    }

    /**
     * @param AbstractMediaHandler $handler Media handler
     *
     * @return MediaManager
     */
    public function addHandler(AbstractMediaHandler $handler)
    {
        $handler->setHgabkaUtils($this->utils);
        $this->handlers[$handler->getName()] = $handler;

        return $this;
    }

    /**
     * @param AbstractMediaHandler $handler Media handler
     *
     * @return MediaManager
     */
    public function setDefaultHandler(AbstractMediaHandler $handler)
    {
        $this->defaultHandler = $handler;

        return $this;
    }

    /**
     * Returns handler with the highest priority to handle the Media item which can handle the item. If no handler is found, it returns FileHandler.
     *
     * @param File|Media $media
     *
     * @return AbstractMediaHandler
     */
    public function getHandler($media)
    {
        $bestHandler = $this->defaultHandler;
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($media) && $handler->getPriority() > $bestHandler->getPriority()) {
                $bestHandler = $handler;
            }
        }

        return $bestHandler;
    }

    /**
     * Returns handler with the highest priority to handle the Media item based on the Type. If no handler is found, it returns FileHandler.
     *
     * @param string $type
     *
     * @return AbstractMediaHandler
     */
    public function getHandlerForType($type)
    {
        $bestHandler = $this->defaultHandler;
        foreach ($this->handlers as $handler) {
            if ($handler->getType() === $type && $handler->getPriority() > $bestHandler->getPriority()) {
                $bestHandler = $handler;
            }
        }

        return $bestHandler;
    }

    /**
     * @return AbstractMediaHandler[]
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * @return MediaManager
     */
    public function prepareMedia(Media $media)
    {
        $handler = $this->getHandler($media);
        $handler->prepareMedia($media);

        return $this;
    }

    /**
     * @param Media $media The media
     * @param bool  $new   Is new
     */
    public function saveMedia(Media $media, $new = false)
    {
        $handler = $this->getHandler($media);

        if ($new) {
            $handler->saveMedia($media);
        } else {
            $handler->updateMedia($media);
        }
    }

    public function removeMedia(Media $media)
    {
        $handler = $this->getHandler($media);
        $handler->removeMedia($media);
    }

    /**
     * @param mixed $data
     *
     * @return Media
     */
    public function createNew($data)
    {
        foreach ($this->handlers as $handler) {
            $result = $handler->createNew($data);
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getFolderAddActions()
    {
        $result = [];
        foreach ($this->handlers as $handler) {
            $actions = $handler->getAddFolderActions();
            if ($actions) {
                $result = array_merge($actions, $result);
            }
        }

        return $result;
    }

    /**
     * @return bool|string
     */
    public function getMediaContent(Media $media)
    {
        /** @var SplFileInfo $file */
        $file = $this->getHandler($media)->getOriginalFile($media);

        return $file ? $file->getContent() : null;
    }

    public function convertToProtected(Media $media)
    {
        $handler = $this->getHandler($media);

        if ($handler instanceof FileHandler) {
            $handler->convertToProtected($media);
        }
    }

    public function convertToNonProtected(Media $media)
    {
        $handler = $this->getHandler($media);

        if ($handler instanceof FileHandler) {
            $handler->convertToNonProtected($media);
        }
    }

    /**
     * @return bool|string
     */
    public function getMediaSize(Media $media)
    {
        /** @var SplFileInfo $file */
        $file = $this->getHandler($media)->getOriginalFile($media);

        return $file ? $file->getSize() : 0;
    }

    public function getMediaPath(Media $media): string
    {
        if ($media->isProtected()) {
            return $this->projectDir . '/' . $this->protectedMediaPath . '/' . $media->getUrl();
        }

        return $this->projectDir . '/public' . $media->getUrl();
    }

    /**
     * @return array|null[]
     */
    public function getMediaInfo(Media $media): array
    {
        $handler = $this->getHandler($media);
        if (!$handler) {
            $file = null;
        } else {
            $file = $handler->getOriginalFile($media);
        }
        /** @var \Gaufrette\File $file */
        if (!$file || !$file->exists()) {
            return [
                'imageinfo' => null,
                'extrainfo' => null,
                'fileinfo' => null,
                'width' => null,
                'height' => null,
                'type' => null,
                'attr' => null,
            ];
        }
        $filePath = $this->getMediaPath($media);
        $fileInfo = new SplFileInfo($filePath);

        $info = getimagesize($filePath, $additionalInfo);

        return [
            'imageinfo' => $info,
            'extrainfo' => $additionalInfo,
            'fileinfo' => $fileInfo,
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
            'attr' => $info[3],
        ];
    }

    public function getDownloadResponse(Media $media, string $disposition = HeaderUtils::DISPOSITION_ATTACHMENT, ?string $fileName = null, bool $addOriginalExtension = true): Response
    {
        $fileContent = $this->getMediaContent($media);

        $response = new Response($fileContent);

        if (null === $fileName) {
            $fileName = $media->getOriginalFilename();
        } elseif ($addOriginalExtension) {
            $extension = pathinfo($media->getOriginalFilename(), \PATHINFO_EXTENSION);
            $fileName .= ('.' . $extension);
        }

        $disposition = HeaderUtils::makeDisposition(
            $disposition,
            $fileName
        );

        $response->headers->set('Content-Type', $media->getContentType());
        $response->headers->set('Content-Length', strlen($fileContent));

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }
}
