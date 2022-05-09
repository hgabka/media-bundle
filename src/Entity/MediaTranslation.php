<?php

namespace Hgabka\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslationInterface;
use Hgabka\Doctrine\Translatable\TranslatableInterface;

#[ORM\Entity]
#[ORM\Table(name: 'hg_media_media_translation')]
class MediaTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\Column(name: 'name', type: 'string', nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(name: 'description', type: 'string', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'copyright', type: 'string', nullable: true)]
    protected ?string $copyright = null;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\MediaBundle\Entity\Media")
     */
    #[Hgabka\Translatable(targetEntity: Media::class)]
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCopyright(?string $copyright): self
    {
        $this->copyright = $copyright;

        return $this;
    }
}
