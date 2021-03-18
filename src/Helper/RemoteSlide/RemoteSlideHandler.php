<?php

namespace Hgabka\MediaBundle\Helper\RemoteSlide;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\RemoteSlide\RemoteSlideType;
use Hgabka\MediaBundle\Helper\Media\AbstractMediaHandler;

/**
 * RemoteSlideStrategy.
 */
class RemoteSlideHandler extends AbstractMediaHandler
{
    /**
     * @var string
     */
    const CONTENT_TYPE = 'remote/slide';

    const TYPE = 'slide';

    /**
     * @return string
     */
    public function getName()
    {
        return 'Remote Slide Handler';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return RemoteSlideType::class;
    }

    /**
     * @param mixed $object
     *
     * @return bool
     */
    public function canHandle($object)
    {
        if (
            (\is_string($object)) ||
            ($object instanceof Media && self::CONTENT_TYPE === $object->getContentType())
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return RemoteSlideHelper
     */
    public function getFormHelper(Media $media)
    {
        return new RemoteSlideHelper($media);
    }

    /**
     * @throws \RuntimeException when the file does not exist
     */
    public function prepareMedia(Media $media)
    {
        if (null === $media->getUuid()) {
            $uuid = uniqid();
            $media->setUuid($uuid);
        }
        $slide = new RemoteSlideHelper($media);
        $code = $slide->getCode();
        // update thumbnail
        switch ($slide->getType()) {
            case 'slideshare':
                try {
                    $json = json_decode(
                        file_get_contents(
                            'https://www.slideshare.net/api/oembed/2?url=https://www.slideshare.net/slideshow/embed_code/key/'.$code.'&format=json'
                        )
                    );
                    $slide->setThumbnailUrl($json->thumbnail);
                } catch (\ErrorException $e) {
                    // Silent exception - should not bubble up since failure to create a thumbnail is not a fatal error
                }

                break;
        }
    }

    public function saveMedia(Media $media)
    {
    }

    public function removeMedia(Media $media)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function updateMedia(Media $media)
    {
    }

    /**
     * @return array
     */
    public function getAddUrlFor(array $params = [])
    {
        return [
            'slide' => [
                'path' => 'HgabkaMediaBundle_folder_slidecreate',
                'params' => [
                    'folderId' => $params['folderId'],
                ],
            ],
        ];
    }

    /**
     * @param mixed $data
     *
     * @return Media
     */
    public function createNew($data)
    {
        $result = null;
        if (\is_string($data)) {
            if (0 !== strpos($data, 'http')) {
                $data = 'https://'.$data;
            }
            $parsedUrl = parse_url($data);
            switch ($parsedUrl['host']) {
                case 'www.slideshare.net':
                case 'slideshare.net':
                    $result = new Media();
                    $slide = new RemoteSlideHelper($result);
                    $slide->setType('slideshare');
                    $json = json_decode(
                        file_get_contents('https://www.slideshare.net/api/oembed/2?url='.$data.'&format=json')
                    );
                    $slide->setCode($json->{'slideshow_id'});
                    $result = $slide->getMedia();
                    $result->setName('SlideShare '.$data);

                    break;
            }
            if ($result instanceof Media) {
                $result->setCurrentLocale($this->hgabkaUtils->getCurrentLocale());
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getShowTemplate(Media $media)
    {
        return 'HgabkaMediaBundle:Media\RemoteSlide:show.html.twig';
    }

    /**
     * @param Media  $media    The media entity
     * @param string $basepath The base path
     *
     * @return string
     */
    public function getImageUrl(Media $media, $basepath)
    {
        $helper = new RemoteSlideHelper($media);

        return $helper->getThumbnailUrl();
    }

    /**
     * @return array
     */
    public function getAddFolderActions()
    {
        return [
            self::TYPE => [
                'type' => self::TYPE,
                'name' => 'hg_media.slide.add',
            ],
        ];
    }
}
