<?php

namespace Hgabka\MediaBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class HasGuessableExtension extends Constraint
{
    public const NOT_GUESSABLE_ERROR = 1;

    public $notGuessableErrorMessage = 'The uploaded file has no extension and could not be automatically guessed by the system.';

    protected static $errorNames = [
        self::NOT_GUESSABLE_ERROR => 'NOT_GUESSABLE_ERROR',
    ];

    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
