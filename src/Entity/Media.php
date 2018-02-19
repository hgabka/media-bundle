<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hgabka\UtilsBundle\Traits\TranslatableTrait;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * Media.
 *
 * @ORM\Entity(repositoryClass="Hgabka\MediaBundle\Repository\MediaRepository")
 * @ORM\Table(name="hg_media_media", indexes={
 *      @ORM\Index(name="idx_media_name", columns={"name"}),
 *      @ORM\Index(name="idx_media_deleted", columns={"deleted"})
 * })
 * @ORM\HasLifecycleCallbacks
 */
class Media
{
    use TranslatableTrait;
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;


    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, length=255)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $uuid;

    /**
     * @var string
     *
     * @ORM\Column(name="copyright", type="string", nullable=true)
     * @Gedmo\Translatable
     */
    protected $copyright;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="location", nullable=true)
     */
    protected $location;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="content_type")
     */
    protected $contentType;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $metadata = [];

    /**
     * @var Folder
     *
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="media")
     * @ORM\JoinColumn(name="folder_id", referencedColumnName="id")
     */
    protected $folder;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $filesize;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true, name="original_filename")
     */
    protected $originalFilename;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $deleted;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", name="removed_from_file_system")
     */
    protected $removedFromFileSystem;

    /**
     * @Prezent\Translations(targetEntity="Hgabka\MediaBundle\Entity\MediaTranslation")
     */
    private $translations;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->deleted = false;
        $this->removedFromFileSystem = false;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id The unique identifier
     *
     * @return AbstractEntity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    /**
     * @return string
     */
    public function getFileSize()
    {
        $size = $this->filesize;
        if ($size < 1024) {
            return $size.'b';
        }
        $help = $size / 1024;
        if ($help < 1024) {
            return round($help, 1).'kb';
        }

        return round(($help / 1024), 1).'mb';
    }

    /**
     * @return int
     */
    public function getFileSizeBytes()
    {
        return $this->filesize;
    }

    /**
     * @param int $filesize
     *
     * @return Media
     */
    public function setFileSize($filesize)
    {
        $this->filesize = $filesize;

        return $this;
    }

    /**
     * Set uuid.
     *
     * @param string $uuid
     *
     * @return Media
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid.
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set location.
     *
     * @param string $location
     *
     * @return Media
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set contentType.
     *
     * @param string $contentType
     *
     * @return Media
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Get contentType.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Get contentType.
     *
     * @return string
     */
    public function getContentTypeShort()
    {
        $contentType = $this->contentType;
        $array = explode('/', $contentType);
        $contentType = end($array);

        return $contentType;
    }

    /**
     * Set metadata.
     *
     * @param array $metadata
     *
     * @return Media
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get metadata.
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set the specified metadata value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Media
     */
    public function setMetadataValue($key, $value)
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * Get the specified metadata value.
     *
     * @param string $key
     *
     * @return null|mixed
     */
    public function getMetadataValue($key)
    {
        return isset($this->metadata[$key]) ? $this->metadata[$key] : null;
    }

    /**
     * Set content.
     *
     * @param mixed $content
     *
     * @return Media
     */
    public function setContent($content)
    {
        $this->content = $content;
        $this->setUpdatedAt(new \DateTime());

        return $this;
    }

    /**
     * Get content.
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set folder.
     *
     * @param Folder $folder
     *
     * @return Media
     */
    public function setFolder(Folder $folder)
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get folder.
     *
     * @return Folder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return Media
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Media
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string $copyright
     *
     * @return Media
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * @param string $originalFilename
     *
     * @return Media
     */
    public function setOriginalFilename($originalFilename)
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalFilename()
    {
        return $this->originalFilename;
    }

    /**
     * @return bool
     */
    public function isRemovedFromFileSystem()
    {
        return $this->removedFromFileSystem;
    }

    /**
     * @param bool $removedFromFileSystem
     */
    public function setRemovedFromFileSystem($removedFromFileSystem)
    {
        $this->removedFromFileSystem = $removedFromFileSystem;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (empty($this->name)) {
            $this->setName($this->getOriginalFilename());
        }
    }

    public static function getTranslationEntityClass()
    {
        return MediaTranslation::class;
    }
}