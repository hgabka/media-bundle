<?php

namespace Hgabka\MediaBundle\Helper\Media;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;

abstract class AbstractMediaHandler
{
    /** @var HgabkaUtils */
    protected $hgabkaUtils;
    private $priority;

    /**
     * @param int $priority
     */
    public function __construct($priority = 0)
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    public function setHgabkaUtils(HgabkaUtils $utils)
    {
        $this->hgabkaUtils = $utils;
    }

    /**
     * Return the default form type options.
     *
     * @return array
     */
    public function getFormTypeOptions()
    {
        return [];
    }

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return string
     */
    abstract public function getType();

    /**
     * @return string
     */
    abstract public function getFormType();

    /**
     * @param mixed $media
     */
    abstract public function canHandle($media);

    /**
     * @return mixed
     */
    abstract public function getFormHelper(Media $media);

    abstract public function prepareMedia(Media $media);

    abstract public function saveMedia(Media $media);

    abstract public function updateMedia(Media $media);

    abstract public function removeMedia(Media $media);

    /**
     * @param mixed $data
     *
     * @return Media
     */
    abstract public function createNew($data);

    public function getShowTemplate(Media $media)
    {
        return '@HgabkaMedia/Media/show.html.twig';
    }

    /**
     * @param Media  $media    The media entity
     * @param string $basepath The base path
     *
     * @return string
     */
    public function getImageUrl(Media $media, $basepath)
    {
        return null;
    }

    /**
     * @return array
     */
    abstract public function getAddFolderActions();
}
