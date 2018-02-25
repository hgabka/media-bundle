<?php

namespace Hgabka\MediaBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class FileToMediaTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /** @var Folder */
    private $folder;

    /**
     * @var CurrentValueContainer
     */
    private $currentValueContainer;

    /** @var MediaManager */
    private $mediaManager;

    /**
     * @param ObjectManager         $objectManager         The object manager
     * @param CurrentValueContainer $currentValueContainer The current value container
     */
    public function __construct(ObjectManager $objectManager, CurrentValueContainer $currentValueContainer, MediaManager $manager, Folder $folder = null)
    {
        $this->objectManager = $objectManager;
        $this->folder = $folder;
        $this->currentValueContainer = $currentValueContainer;
        $this->mediaManager = $manager;
    }

    /**
     * @param Media $entity The value in the original representation
     *
     * @throws UnexpectedTypeException   when the argument is not an object
     * @throws \InvalidArgumentException when the parameter is a collection
     *
     * @return mixed The value in the transformed representation
     */
    public function transform($entity)
    {
        if (empty($entity)) {
            return [
                'id' => '',
            ];
        }
        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }
        if ($entity instanceof Collection) {
            throw new \InvalidArgumentException('Expected an object, but got a collection. Did you forget to pass "multiple=true" to an entity field?');
        }
        $this->currentValueContainer->setCurrentValue($entity);

        return [
            'ent' => $entity,
            'id' => $entity->getId(),
        ];
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws UnexpectedTypeException       when the parameter is not numeric
     * @throws TransformationFailedException when the media item cannot be loaded/found
     *
     * @return Media
     */
    public function reverseTransform($value)
    {
        if (!empty($value) && !empty($value['file'])) {
            if ($value['file']->isValid()) {
                $entity = $this->mediaManager->createNew($value['file']);
                $entity->setFolder($this->folder);
                $this->currentValueContainer->setCurrentValue($entity);
            }
        }

        return $this->currentValueContainer->getCurrentValue();
    }
}
