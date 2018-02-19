<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\Entity\TranslationTrait;
use Prezent\Doctrine\Translatable\TranslationInterface;

/**
 * @ORM\Table(name="hg_media_folder_translation")
 * @ORM\Entity
 */
class FolderTranslation
{
    use TranslationTrait;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @Prezent\Translatable(targetEntity="Hgabka\MediaBundle\Entity\Folder")
     */
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
     * @return MediaTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
