<?php

namespace Hgabka\MediaBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Media extends Constraint
{
    public const NOT_FOUND_ERROR = 1;
    public const NOT_READABLE_ERROR = 2;
    public const EMPTY_ERROR = 3;
    public const INVALID_MIME_TYPE_ERROR = 5;
    public const TOO_WIDE_ERROR = 11;
    public const TOO_NARROW_ERROR = 12;
    public const TOO_HIGH_ERROR = 13;
    public const TOO_LOW_ERROR = 14;

    public $minHeight;
    public $maxHeight;
    public $minWidth;
    public $maxWidth;
    public $binaryFormat;
    public $mimeTypes = [];
    public $maxSize;

    public $notFoundMessage = 'hg_media.errors.not_found';
    public $notReadableMessage = 'hg_media.errors.not_readable';
    public $mimeTypesMessage = 'hg_media.errors.mime_type';
    public $disallowEmptyMessage = 'hg_media.errors.empty';
    public $maxWidthMessage = 'The image width is too big ({{ width }}px). Allowed maximum width is {{ max_width }}px.';
    public $minWidthMessage = 'The image width is too small ({{ width }}px). Minimum width expected is {{ min_width }}px.';
    public $maxHeightMessage = 'The image height is too big ({{ height }}px). Allowed maximum height is {{ max_height }}px.';
    public $minHeightMessage = 'The image height is too small ({{ height }}px). Minimum height expected is {{ min_height }}px.';
    public $uploadPartialErrorMessage = 'The file was only partially uploaded.';
    public $uploadNoFileErrorMessage = 'No file was uploaded.';
    public $uploadNoTmpDirErrorMessage = 'No temporary folder was configured in php.ini.';
    public $uploadCantWriteErrorMessage = 'Cannot write temporary file to disk.';
    public $uploadExtensionErrorMessage = 'A PHP extension caused the upload to fail.';
    public $uploadErrorMessage = 'The file could not be uploaded.';
    public $maxSizeErrorMessage = 'hg_media.errors.too_big';

    protected static $errorNames = [
        self::NOT_FOUND_ERROR => 'NOT_FOUND_ERROR',
        self::NOT_READABLE_ERROR => 'NOT_READABLE_ERROR',
        self::EMPTY_ERROR => 'EMPTY_ERROR',
        self::INVALID_MIME_TYPE_ERROR => 'INVALID_MIME_TYPE_ERROR',
        self::TOO_HIGH_ERROR => 'TOO_HIGH_ERROR',
        self::TOO_LOW_ERROR => 'TOO_LOW_ERROR',
        self::TOO_WIDE_ERROR => 'TOO_WIDE_ERROR',
        self::TOO_NARROW_ERROR => 'TOO_NARROW_ERROR',
    ];

    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
