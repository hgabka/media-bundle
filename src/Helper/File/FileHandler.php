<?php

namespace Hgabka\MediaBundle\Helper\File;

use Gaufrette\Filesystem;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\File\FileType;
use Hgabka\MediaBundle\Helper\ExtensionGuesserFactoryInterface;
use Hgabka\MediaBundle\Helper\Media\AbstractMediaHandler;
use Hgabka\MediaBundle\Helper\MimeTypeGuesserFactoryInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FileHandler.
 */
class FileHandler extends AbstractMediaHandler
{
    /**
     * @var string
     */
    const TYPE = 'file';

    /**
     * @var string
     */
    public $mediaPath;

    /**
     * @var Filesystem
     */
    public $fileSystem;

    /**
     * @var MimeTypeGuesserInterface
     */
    public $mimeTypeGuesser;

    /**
     * @var ExtensionGuesserInterface
     */
    public $extensionGuesser;

    /**
     * Files with a blacklisted extension will be converted to txt.
     *
     * @var array
     */
    private $blacklistedExtensions = [];

    /**
     * Constructor.
     *
     * @param int $priority
     */
    public function __construct($priority, MimeTypeGuesserFactoryInterface $mimeTypeGuesserFactory, ExtensionGuesserFactoryInterface $extensionGuesserFactoryInterface)
    {
        parent::__construct($priority);
        $this->mimeTypeGuesser = $mimeTypeGuesserFactory->get();
        $this->extensionGuesser = $extensionGuesserFactoryInterface->get();
    }

    /**
     * Inject the blacklisted.
     */
    public function setBlacklistedExtensions(array $blacklistedExtensions)
    {
        $this->blacklistedExtensions = $blacklistedExtensions;
    }

    /**
     * Inject the path used in media urls.
     *
     * @param string $mediaPath
     */
    public function setMediaPath($mediaPath)
    {
        $this->mediaPath = $mediaPath;
    }

    public function setFileSystem(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'File Handler';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return FileType::class;
    }

    /**
     * @param mixed $object
     *
     * @return bool
     */
    public function canHandle($object)
    {
        if ($object instanceof File ||
            ($object instanceof Media &&
            (is_file($object->getContent()) || 'local' === $object->getLocation()))
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return FileHelper
     */
    public function getFormHelper(Media $media)
    {
        return new FileHelper($media);
    }

    /**
     * @throws \RuntimeException when the file does not exist
     */
    public function prepareMedia(Media $media)
    {
        if (null === $media->getUuid()) {
            $uuid = uniqid();
            $media->setUuid($uuid);
        }

        $content = $media->getContent();
        if (empty($content)) {
            return;
        }

        if (!$content instanceof File) {
            if (!is_file($content)) {
                throw new \RuntimeException('Invalid file');
            }

            $file = new File($content);
            $media->setContent($file);
        }

        $contentType = $this->mimeTypeGuesser->guess($content->getPathname());
        if ($content instanceof UploadedFile) {
            $pathInfo = pathinfo($content->getClientOriginalName());

            if (!\array_key_exists('extension', $pathInfo)) {
                $pathInfo['extension'] = $this->extensionGuesser->guess($contentType);
            }

            $media->setOriginalFilename($this->hgabkaUtils->slugify($pathInfo['filename']).'.'.$pathInfo['extension']);
            $name = $media->getName();

            if (empty($name)) {
                $media->setName($media->getOriginalFilename());
            }
        }

        $media->setContentType($contentType);
        $media->setFileSize(filesize($media->getContent()));
        $media->setUrl($this->mediaPath.$this->getFilePath($media));
        $media->setLocation('local');
    }

    public function removeMedia(Media $media)
    {
        $adapter = $this->fileSystem->getAdapter();

        // Remove the file from filesystem
        $fileKey = $this->getFilePath($media);
        if ($adapter->exists($fileKey)) {
            $adapter->delete($fileKey);
        }

        // Remove the files containing folder if there's nothing left
        $folderPath = $this->getFileFolderPath($media);
        if ($adapter->exists($folderPath) && $adapter->isDirectory($folderPath) && !empty($folderPath)) {
            $allMyKeys = $adapter->keys();
            $everythingfromdir = preg_grep('/'.$folderPath, $allMyKeys);

            if (1 === \count($everythingfromdir)) {
                $adapter->delete($folderPath);
            }
        }

        $media->setRemovedFromFileSystem(true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateMedia(Media $media)
    {
        $this->saveMedia($media);
    }

    public function saveMedia(Media $media)
    {
        if (!$media->getContent() instanceof File) {
            return;
        }

        $originalFile = $this->getOriginalFile($media);
        $originalFile->setContent(file_get_contents($media->getContent()->getRealPath()));
    }

    /**
     * @return \Gaufrette\File
     */
    public function getOriginalFile(Media $media)
    {
        return $this->fileSystem->get($this->getFilePath($media), true);
    }

    /**
     * @param mixed $data
     *
     * @return Media
     */
    public function createNew($data)
    {
        if ($data instanceof File) {
            /** @var $data File */
            $media = new Media();
            if (method_exists($data, 'getClientOriginalName')) {
                $media->setOriginalFilename($data->getClientOriginalName());
            } else {
                $media->setOriginalFilename($data->getFilename());
            }
            $media->setContent($data);

            $contentType = $this->mimeTypeGuesser->guess($media->getContent()->getPathname());
            $media->setContentType($contentType);
            $media->setFileSize($data->getSize());
            $media->setCurrentLocale($this->hgabkaUtils->getCurrentLocale());

            return $media;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getShowTemplate(Media $media)
    {
        return '@HgabkaMedia/Media/File/show.html.twig';
    }

    /**
     * @return array
     */
    public function getAddFolderActions()
    {
        return [
            self::TYPE => [
                'type' => self::TYPE,
                'name' => 'hg_media.file.add',
            ],
        ];
    }

    /**
     * @return string
     */
    private function getFilePath(Media $media)
    {
        $filename = $media->getOriginalFilename();
        $filename = str_replace(['/', '\\', '%'], '', $filename);

        if (!empty($this->blacklistedExtensions)) {
            $filename = preg_replace('/\.('.implode('|', $this->blacklistedExtensions).')$/', '.txt', $filename);
        }

        $parts = pathinfo($filename);
        $filename = $this->hgabkaUtils->slugify($parts['filename']);
        if (\array_key_exists('extension', $parts)) {
            $filename .= '.'.strtolower($parts['extension']);
        }

        return sprintf(
            '%s/%s',
            $media->getUuid(),
            $filename
        );
    }

    /**
     * @return string
     */
    private function getFileFolderPath(Media $media)
    {
        return substr($this->getFilePath($media), 0, strrpos($this->getFilePath($media), $media->getOriginalFilename()));
    }
}
