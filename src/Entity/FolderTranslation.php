<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;

#[ORM\Entity]
#[ORM\Table(name: 'hg_media_folder_translation')]
class FolderTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    protected ?string $name = null;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\MediaBundle\Entity\Folder")
     */
    #[Hgabka\Translatable(targetEntity: Folder::class)]
    private ?TranslatableInterface $translatable = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
