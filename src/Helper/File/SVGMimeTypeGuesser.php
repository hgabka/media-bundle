<?php

namespace Hgabka\MediaBundle\Helper\File;

use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * SVGMimeTypeGuesser.
 *
 * Simple Mime type guesser to detect SVG image files, it will test if the file is an XML file and return SVG mime type
 * if the XML contains a valid SVG namespace...
 */
class SVGMimeTypeGuesser implements MimeTypeGuesserInterface
{
    private $_MIMETYPE_NAMESPACES = [
        'http://www.w3.org/2000/svg' => 'image/svg+xml',
    ];

    public function guessMimeType(string $path): ?string
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" file does not exist.', $path));
        }

        if (!is_readable($path)) {
            throw new \InvalidArgumentException(\sprintf('The "%s" file is not readable.', $path));
        }

        if (!$this->isGuesserSupported()) {
            return null;
        }

        $dom = new \DOMDocument();
        $xml = $dom->load($path, \LIBXML_NOERROR + \LIBXML_ERR_FATAL + \LIBXML_ERR_NONE);
        if (false === $xml) {
            return null;
        }
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('namespace::*') as $node) {
            if (isset($this->_MIMETYPE_NAMESPACES[$node->nodeValue])) {
                return $this->_MIMETYPE_NAMESPACES[$node->nodeValue];
            }
        }

        return null;
    }

    public function isGuesserSupported(): bool
    {
        return class_exists('DOMDocument') && class_exists('DOMXPath');
    }
}
