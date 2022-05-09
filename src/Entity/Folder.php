<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Hgabka\UtilsBundle\Traits\TranslatableTrait;

#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\Table(name: 'hg_media_folders')]
#[ORM\Index(name: 'idx_folder_internal_name', columns: ['internal_name'])]
#[ORM\Index(name: 'idx_folder_deleted', columns: ['deleted'])]
#[Gedmo\Tree(type: 'nested')]
class Folder implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children', fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true)]
    #[Gedmo\TreeParent]
    protected ?Folder $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', fetch: 'LAZY')]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    protected Collection|array|null $children = null;

    #[ORM\OneToMany(targetEntity: Media::class, mappedBy: 'folder', fetch: 'LAZY')]
    protected Collection|array|null $media = null;

    #[ORM\Column(name: 'rel', type: 'string', nullable: true)]
    protected ?string $rel = null;

    #[ORM\Column(name: 'internal_name', type: 'string', nullable: true)]
    protected ?string $internalName = null;

    #[ORM\Column(name: 'lft', type: 'integer', nullable: true)]
    #[Gedmo\TreeLeft]
    protected ?int $lft = null;

    #[ORM\Column(name: 'lvl', type: 'integer', nullable: true)]
    #[Gedmo\TreeLevel]
    protected ?int $lvl = null;

    #[ORM\Column(name: 'rgt', type: 'integer', nullable: true)]
    #[Gedmo\TreeRight]
    protected ?int $rgt = null;

    #[ORM\Column(name: 'deleted', type: 'boolean')]
    protected ?bool $deleted = null;

    #[ORM\Column(name: 'internal', type: 'boolean')]
    protected ?bool $internal = false;

    /**
     * @Hgabka\Translations(targetEntity="Hgabka\MediaBundle\Entity\FolderTranslation")
     */
    #[Hgabka\Translations(targetEntity: FolderTranslation::class)]
    private Collection|array|null $translations = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->deleted = false;
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

    public function getRel(): ?string
    {
        return $this->rel;
    }

    public function setRel(?string $rel): self
    {
        $this->rel = $rel;

        return $this;
    }

    /**
     * @return Folder[]:
     */
    public function getParents(): array
    {
        $parent = $this->getParent();
        $parents = [];
        while (null !== $parent) {
            $parents[] = $parent;
            $parent = $parent->getParent();
        }

        return array_reverse($parents);
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent = null): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    public function addMedia(Media $media): self
    {
        $this->media[] = $media;

        return $this;
    }

    public function getMedia(?bool $includeDeleted = false): Collection|array|null
    {
        if ($includeDeleted) {
            return $this->media;
        }

        return $this->media->filter(
            function (Media $entry) {
                if ($entry->isDeleted()) {
                    return false;
                }

                return true;
            }
        );
    }

    public function hasActive(?int $id): bool
    {
        foreach ($this->getChildren() as $child) {
            if ($child->hasActive($id) || $child->getId() === $id) {
                return true;
            }
        }

        return false;
    }

    public function getChildren(?bool $includeDeleted = false): Collection|array|null
    {
        if ($includeDeleted) {
            return $this->children;
        }

        return $this->children->filter(
            function (self $entry) {
                if ($entry->isDeleted()) {
                    return false;
                }

                return true;
            }
        );
    }

    public function setChildren(Collection|array|null $children): self
    {
        $this->children = $children;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getInternalName(): ?string
    {
        return $this->internalName;
    }

    public function setInternalName(?string $internalName): self
    {
        $this->internalName = $internalName;

        return $this;
    }

    public function setLeft(?int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLeft(): ?int
    {
        return $this->lft;
    }

    public function setLevel(?int $lvl): self
    {
        $this->lvl = $lvl;

        return $this;
    }

    public function setRight(?int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRight(): ?int
    {
        return $this->rgt;
    }

    public function getOptionLabel(): string
    {
        return str_repeat(
            '-',
            $this->getLevel()
        ) . ' ' . $this->getName();
    }

    /**
     * @return int
     */
    public function getLevel(): ?int
    {
        return $this->lvl;
    }

    public static function getTranslationEntityClass(): string
    {
        return FolderTranslation::class;
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

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): self
    {
        $this->internal = $internal;

        return $this;
    }
}
