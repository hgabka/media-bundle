<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Tree\Node as GedmoNode;
use Symfony\Component\Validator\Constraints as Assert;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Hgabka\UtilsBundle\Traits\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Prezent\Doctrine\Translatable\TranslatableInterface;

/**
 * Class that defines a folder from the MediaBundle in the database.
 *
 * @ORM\Entity(repositoryClass="Hgabka\MediaBundle\Repository\FolderRepository")
 * @ORM\Table(name="hg_media_folders", indexes={
 *      @ORM\Index(name="idx_folder_internal_name", columns={"internal_name"}),
 *      @ORM\Index(name="idx_folder_deleted", columns={"deleted"})
 * })
 * @Gedmo\Tree(type="nested")
 */
class Folder implements TranslatableInterface
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
     * @Prezent\Translations(targetEntity="Hgabka\MediaBundle\Entity\FolderTranslation")
     */
    private $translations;

    /**
     * @var Folder
     *
     * @ORM\ManyToOne(targetEntity="Folder", inversedBy="children", fetch="LAZY")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", nullable=true)
     * @Gedmo\TreeParent
     */
    protected $parent;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Folder", mappedBy="parent", fetch="LAZY")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    protected $children;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Media", mappedBy="folder", fetch="LAZY")
     */
    protected $media;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $rel;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="internal_name", nullable=true)
     */
    protected $internalName;

    /**
     * @var int
     *
     * @ORM\Column(name="lft", type="integer", nullable=true)
     * @Gedmo\TreeLeft
     */
    protected $lft;

    /**
     * @var int
     *
     * @ORM\Column(name="lvl", type="integer", nullable=true)
     * @Gedmo\TreeLevel
     */
    protected $lvl;

    /**
     * @var int
     *
     * @ORM\Column(name="rgt", type="integer", nullable=true)
     * @Gedmo\TreeRight
     */
    protected $rgt;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $deleted;

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

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Folder
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getRel()
    {
        return $this->rel;
    }

    /**
     * @param string $rel
     *
     * @return Folder
     */
    public function setRel($rel)
    {
        $this->rel = $rel;

        return $this;
    }

    /**
     * @return Folder[]:
     */
    public function getParents()
    {
        $parent = $this->getParent();
        $parents = [];
        while (null !== $parent) {
            $parents[] = $parent;
            $parent = $parent->getParent();
        }

        return array_reverse($parents);
    }

    /**
     * Get parent.
     *
     * @return Folder
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set parent.
     *
     * @param Folder $parent
     *
     * @return Folder
     */
    public function setParent(Folder $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Add a child.
     *
     * @param Folder $child
     *
     * @return Folder
     */
    public function addChild(Folder $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Add file.
     *
     * @param Media $media
     *
     * @return Folder
     */
    public function addMedia(Media $media)
    {
        $this->media[] = $media;

        return $this;
    }

    /**
     * Get media.
     *
     * @param bool $includeDeleted
     *
     * @return ArrayCollection
     */
    public function getMedia($includeDeleted = false)
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

    /**
     * @param int $id
     *
     * @return bool
     */
    public function hasActive($id)
    {
        foreach ($this->getChildren() as $child) {
            if ($child->hasActive($id) || $child->getId() === $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get child folders.
     *
     * @param bool $includeDeleted
     *
     * @return ArrayCollection
     */
    public function getChildren($includeDeleted = false)
    {
        if ($includeDeleted) {
            return $this->children;
        }

        return $this->children->filter(
            function (Folder $entry) {
                if ($entry->isDeleted()) {
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @param ArrayCollection $children
     *
     * @return Folder
     */
    public function setChildren($children)
    {
        $this->children = $children;

        return $this;
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
     * @return Folder
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return string
     */
    public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * @param string $internalName
     *
     * @return Folder
     */
    public function setInternalName($internalName)
    {
        $this->internalName = $internalName;

        return $this;
    }

    /**
     * @param int $lft
     *
     * @return Folder
     */
    public function setLeft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * @return int
     */
    public function getLeft()
    {
        return $this->lft;
    }

    /**
     * @param int $lvl
     *
     * @return Folder
     */
    public function setLevel($lvl)
    {
        $this->lvl = $lvl;

        return $this;
    }

    /**
     * @param int $rgt
     *
     * @return Folder
     */
    public function setRight($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * @return int
     */
    public function getRight()
    {
        return $this->rgt;
    }

    /**
     * @return string
     */
    public function getOptionLabel()
    {
        return str_repeat(
                '-',
                $this->getLevel()
            ).' '.$this->getName();
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->lvl;
    }

    public static function getTranslationEntityClass()
    {
        return FolderTranslation::class;
    }

    public function getName($locale = null)
    {
        return $this->translate($locale)->getName();
    }
}