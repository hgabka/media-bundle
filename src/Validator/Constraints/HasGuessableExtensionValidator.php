<?php

namespace Hgabka\MediaBundle\Validator\Constraints;

use Hgabka\MediaBundle\Helper\ExtensionGuesserFactoryInterface;
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
     * @var ExtensionGuesserInterface
     */
    private $extensionGuesser;

    /**
     * @var MimeTypeGuesserInterface
     */
    private $mimeTypeGuesser;

    /**
     * @param $value
     * @param Constraint $constraint
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

        if ($value->getError() != UPLOAD_ERR_OK) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, new File())
            ;

            return;
        }
        $contentType = $this->mimeTypeGuesser->guess($value->getPathname());
        $pathInfo = pathinfo($value->getClientOriginalName());
        if (!array_key_exists('extension', $pathInfo)) {
            $pathInfo['extension'] = $this->extensionGuesser->guess($contentType);
        }

        if (null === $pathInfo['extension']) {
            $this->context->buildViolation($constraint->notGuessableErrorMessage)
                ->setCode(HasGuessableExtension::NOT_GUESSABLE_ERROR)
                ->addViolation();
        }
    }

    /**
     * @param ExtensionGuesserFactoryInterface $extensionGuesserFactory
     */
    public function setExtensionGuesser(ExtensionGuesserFactoryInterface $extensionGuesserFactory)
    {
        $this->extensionGuesser = $extensionGuesserFactory->get();
    }

    /**
     * @param MimeTypeGuesserFactoryInterface $mimeTypeGuesserFactory
     */
    public function setMimeTypeGuesser(MimeTypeGuesserFactoryInterface $mimeTypeGuesserFactory)
    {
        $this->mimeTypeGuesser = $mimeTypeGuesserFactory->get();
    }
}
