<?php

namespace Hgabka\MediaBundle\Validator\Constraints;

use Symfony\Component\Mime\MimeTypes;
use Hgabka\MediaBundle\Helper\MimeTypeGuesserFactoryInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Class hasGuessableExtensionValidator.
 */
class HasGuessableExtensionValidator extends ConstraintValidator
{
    /**
     * @var MimeTypes
     */
    private $mimeTypes;

    /**
     * @param $value
     *
     * @throws ConstraintDefinitionException
     * @throws UnexpectedTypeException
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof HasGuessableExtension) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\HasGuessableExtension');
        }

        if (!$value instanceof UploadedFile) {
            return;
        }

        if (\UPLOAD_ERR_OK !== $value->getError()) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, new File())
            ;

            return;
        }
        $contentType = $this->mimeTypes->guess($value->getPathname());
        $pathInfo = pathinfo($value->getClientOriginalName());
        if (!\array_key_exists('extension', $pathInfo)) {
	    $extensions = $this->mimeTypes->getExtensions($contentType);
            $pathInfo['extension'] = empty($extensions) ? null : reset($extensions);
        }

        if (null === $pathInfo['extension']) {
            $this->context->buildViolation($constraint->notGuessableErrorMessage)
                ->setCode(HasGuessableExtension::NOT_GUESSABLE_ERROR)
                ->addViolation();
        }
    }

    public function setGuesser(MimeTypes $mimeTypes)
    {
        $this->mimeTypes = $mimeTypes;
    }

}
