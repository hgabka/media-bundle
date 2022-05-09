<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\MediaBundle\Repository\MediaRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Hgabka\UtilsBundle\Traits\TranslatableTrait;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Table(name: 'hg_media_media')]
#[ORM\Index(name: 'idx_media_deleted', columns: ['deleted'])]
class Media implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', unique: true, length: 255)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?string $uuid = null;

    #[ORM\Column(name: 'location', type: 'string', nullable: true)]
    protected ?string $location = null;

    #[ORM\Column(name: 'content_type', type: 'string')]
    protected ?string $contentType = null;

    #[ORM\Column(name: 'metadata', type: 'array')]
    protected ?array $metadata = [];

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'folder_id', referencedColumnName: 'id')]
    protected ?Folder $folder = null;

    /**
     * @var mixed
     */
    protected $content;

    #[ORM\Column(name: 'filesize', type: 'integer', nullable: true)]
    protected ?int $filesize = null;

    #[ORM\Column(name: 'url', type: 'string', nullable: true)]
    protected ?string $url = null;

    #[ORM\Column(name: 'original_filename', type: 'string', nullable: true)]
    protected ?string $originalFilename = null;

    #[ORM\Column(name: 'deleted', type: 'boolean')]
    protected ?bool $deleted = null;

    #[ORM\Column(name: 'removed_from_file_system', type: 'boolean')]
    protected ?bool $removedFromFileSystem = null;

    /**
     * @Hgabka\Translations(targetEntity="Hgabka\MediaBundle\Entity\MediaTranslation")
     */
    #[Hgabka\Translations(targetEntity: MediaTranslation::class)]
    private Collection|array|null $translations = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->deleted = false;
        $this->removedFromFileSystem = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFileSize(): ?string
    {
        $size = $this->filesize;
        if ($size < 1024) {
            return $size . ' B';
        }
        $help = $size / 1024;
        if ($help < 1024) {
            return round($help, 1) . ' kB';
        }

        return round(($help / 1024), 1) . ' MB';
    }

    public function getFileSizeBytes(): ?int
    {
        return $this->filesize;
    }

    public function setFileSize(?int $filesize): self
    {
        $this->filesize = $filesize;

        return $this;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location.
     *
     * @return string
     */
    public function getLocation(): ?string
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
    public function setContentType(?string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getContentTypeShort(): ?string
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
    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get metadata.
     *
     * @return array
     */
    public function getMetadata(): ?array
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
    public function setMetadataValue(string $key, mixed $value)
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
    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    /**
     * Set content.
     *
     * @param mixed $content
     *
     * @return Media
     */
    public function setContent(mixed $content): self
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
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * Set folder.
     *
     * @return Media
     */
    public function setFolder(Folder $folder): self
    {
        $this->folder = $folder;

        return $this;
    }

    /**
     * Get folder.
     *
     * @return Folder
     */
    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setCopyright(string $copyright, ?string $locale = null): self
    {
        $this->translate($locale)->setCopyright($copyright);

        return $this;
    }

    public function getCopyright(?string $locale = null): ?string
    {
        return $this->translate($locale)->getCopyright();
    }

    public function setOriginalFilename(?string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    /**
     * @return bool
     */
    public function isRemovedFromFileSystem(): ?bool
    {
        return $this->removedFromFileSystem;
    }

    /**
     * @param bool $removedFromFileSystem
     */
    public function setRemovedFromFileSystem(bool $removedFromFileSystem): self
    {
        $this->removedFromFileSystem = $removedFromFileSystem;
    }

    /**
     * @ORM\PrePersist
     */
    #[ORM\PrePersist]
    public function prePersist()
    {
        if (empty($this->getName())) {
            $this->setName($this->getOriginalFilename());
        }
    }

    public static function getTranslationEntityClass(): string
    {
        return MediaTranslation::class;
    }

    public function getName(?string $locale = null): ?string
    {
        return $this->translate($locale)->getName();
    }

    public function setName(?string $name, ?string $locale = null): self
    {
        $this->translate($locale)->setName($name);

        return $this;
    }

    public function getDescription(?string $locale = null): ?string
    {
        return $this->translate($locale)->getDescription();
    }

    public function setDescription(?string $description, ?string $locale = null): self
    {
        $this->translate($locale)->setDescription($description);

        return $this;
    }
}
