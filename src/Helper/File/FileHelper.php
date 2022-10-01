<?php

namespace Hgabka\MediaBundle\Helper\File;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FileHelper.
 */
class FileHelper
{
    /**
     * @var Media
     */
    protected $media;

    /**
     * @var File
     */
    protected $file;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $mediaPath;    

    public function __construct(Media $media, string $mediaPath)
    {
        $this->media = $media;
        $this->ediaPath = $mediaPath;
    }

    /**
     * __destruct.
     */
    public function __destruct()
    {
        if (null !== $this->path) {
            unlink($this->path);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->media->getName();
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->media->setName($name);
    }

    /**
     * @return Folder
     */
    public function getFolder()
    {
        return $this->media->getFolder();
    }

    public function setFolder(Folder $folder)
    {
        $this->media->setFolder($folder);
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->media->getCopyright();
    }

    /**
     * @param string $copyright
     */
    public function setCopyright($copyright)
    {
        $this->media->setCopyright($copyright);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->media->getDescription();
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->media->setDescription($description);
    }

    public function isProtected(): ?bool
    {
        return $this->media->isProtected();
    }

    /**
     * @param string $description
     */
    public function setProtected(?bool $protected)
    {
        $this->media->setProtected($protected);
    }

    public function getOriginalFilename()
    {
        return $this->media->getOriginalFilename();
    }

    /**
     * @param string $name
     */
    public function setOriginalFilename($name)
    {
        $this->media->setOriginalFilename($name);
    }

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    public function setFile(File $file)
    {
        $this->file = $file;
        if ('' !== $file->getPathname()) {
            $this->media->setContent($file);
            $this->media->setContentType($file->getMimeType());
            $this->media->setUrl(
                $this->mediaPath . $this->media->getUuid() . '.' . $this->media->getContent()->getExtension()
            );
        }
    }

    /**
     * @param string $mediaUrl
     *
     * @throws \Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException
     */
    public function getMediaFromUrl($mediaUrl)
    {
        $path = tempnam(sys_get_temp_dir(), 'kuma_');
        $saveFile = fopen($path, 'w');
        $this->path = $path;

        $ch = curl_init($mediaUrl);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, \CURLOPT_FILE, $saveFile);
        curl_exec($ch);
        $effectiveUrl = curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        fclose($saveFile);
        chmod($path, 0777);

        $url = parse_url($effectiveUrl);
        $info = pathinfo($url['path']);
        $filename = $info['filename'] . '.' . $info['extension'];

        $upload = new UploadedFile($path, $filename);
        $this->getMedia()->setContent($upload);

        if (null === $this->getMedia()) {
            unlink($path);

            throw new AccessDeniedException('Can not link file');
        }
    }

    /**
     * @return Media
     */
    public function getMedia()
    {
        return $this->media;
    }
}
