<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslationInterface;

/**
 * @ORM\Table(name="hg_media_folder_translation")
 * @ORM\Entity
 */
class FolderTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\MediaBundle\Entity\Folder")
     */
    #[Hgabka\Translatable(targetEntity: Folder::class)]
    private $translatable;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return MediaTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
