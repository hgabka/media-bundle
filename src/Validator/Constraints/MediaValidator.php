<?php

namespace Hgabka\MediaBundle\Validator\Constraints;

use Hgabka\MediaBundle\Entity\Media as MediaObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class MediaValidator extends ConstraintValidator
{
    /**
     * @param MediaObject $value
     * @param Constraint  $constraint
     *
     * @throws ConstraintDefinitionException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Media) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Media');
        }

        if (!$value instanceof MediaObject) {
            return;
        }

        $mimeType = $value->getContentType();

        if ($constraint->mimeTypes) {
            $mimeTypes = (array) $constraint->mimeTypes;

            if (false === $this->validateMimeType($value, $mimeTypes)) {
                $this->context->buildViolation($constraint->mimeTypesMessage)
                    ->setParameter('{{ type }}', $this->formatValue($mimeType))
                    ->setParameter('{{ types }}', $this->formatValues($mimeTypes))
                    ->setCode(Media::INVALID_MIME_TYPE_ERROR)
                    ->addViolation();

                return;
            }
        }

        if ($constraint->maxSize) {
            if ($value->getFileSizeBytes() > $constraint->maxSize) {
                $this->context->buildViolation($constraint->maxSizeErrorMessage)
                              ->setParameter('{{ size }}', $value->getFileSize())
                              ->setParameter('{{ maxSize }}', $this->getFileSizeText($constraint->maxSize))
                              ->setCode(Media::INVALID_MIME_TYPE_ERROR)
                              ->addViolation();

                return;
            }
        }

        if (!preg_match('^image\/*^', $mimeType) || 'image/svg+xml' === $mimeType) {
            return;
        }

        $this->validateDimensions($value, $constraint);
    }

    /**
     * @param mixed $size
     *
     * @return string
     */
    public function getFileSizeText($size)
    {
        if ($size < 1024) {
            return $size.' B';
        }
        $help = $size / 1024;
        if ($help < 1024) {
            return round($help, 1).' KB';
        }

        return round(($help / 1024), 1).' MB';
    }

    private function validateMimeType(MediaObject $value, $allowedMimeTypes)
    {
        $mimeType = strtolower($value->getContentType());

        foreach ($allowedMimeTypes as $type) {
            if ('image' === $type) {
                return in_array($mimeType, [
                    'image/gif',
                    'image/png',
                    'image/jpeg',
                    'image/bmp',
                    'image/webp',
                ], true);
            }
            $type = strtolower($type);
            if ($type === $mimeType) {
                return true;
            }

            if ($discrete = strstr($type, '/*', true)) {
                if (strstr($mimeType, '/', true) === $discrete) {
                    return true;
                }
            }
        }

        return false;
    }

    private function validateDimensions(MediaObject $value, Media $constraint)
    {
        $height = $value->getMetadataValue('original_height');
        $width = $value->getMetadataValue('original_width');

        if ($constraint->minHeight) {
            if (!ctype_digit((string) $constraint->minHeight)) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        '"%s" is not a valid minimum height',
                        $constraint->minHeight
                    )
                );
            }

            if ($height < $constraint->minHeight) {
                $this->context->buildViolation($constraint->minHeightMessage)
                    ->setParameter('{{ height }}', $height)
                    ->setParameter('{{ min_height }}', $constraint->minHeight)
                    ->setCode(Media::TOO_LOW_ERROR)
                    ->addViolation();

                return;
            }
        }

        if ($constraint->maxHeight) {
            if (!ctype_digit((string) $constraint->maxHeight)) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        '"%s" is not a valid maximum height',
                        $constraint->maxHeight
                    )
                );
            }

            if ($height > $constraint->maxHeight) {
                $this->context->buildViolation($constraint->maxHeightMessage)
                    ->setParameter('{{ height }}', $height)
                    ->setParameter('{{ max_height }}', $constraint->maxHeight)
                    ->setCode(Media::TOO_HIGH_ERROR)
                    ->addViolation();

                return;
            }
        }

        if ($constraint->minWidth) {
            if (!ctype_digit((string) $constraint->minWidth)) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        '"%s" is not a valid minimum width',
                        $constraint->minWidth
                    )
                );
            }

            if ($width < $constraint->minWidth) {
                $this->context->buildViolation($constraint->minWidthMessage)
                    ->setParameter('{{ width }}', $width)
                    ->setParameter('{{ min_width }}', $constraint->minWidth)
                    ->setCode(Media::TOO_NARROW_ERROR)
                    ->addViolation();

                return;
            }
        }

        if ($constraint->maxWidth) {
            if (!ctype_digit((string) $constraint->maxWidth)) {
                throw new ConstraintDefinitionException(
                    sprintf(
                        '"%s" is not a valid maximum width',
                        $constraint->maxWidth
                    )
                );
            }

            if ($width > $constraint->maxWidth) {
                $this->context->buildViolation($constraint->maxWidthMessage)
                    ->setParameter('{{ width }}', $width)
                    ->setParameter('{{ max_width }}', $constraint->maxWidth)
                    ->setCode(Media::TOO_WIDE_ERROR)
                    ->addViolation();

                return;
            }
        }
    }
}
