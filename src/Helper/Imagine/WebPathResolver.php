<?php

namespace Hgabka\MediaBundle\Helper\Imagine;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RequestContext;

class WebPathResolver extends \Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver
{
    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @param string $webRootDir
     * @param string $cachePrefix
     */
    public function __construct(Filesystem $filesystem, RequestContext $requestContext, $webRootDir, $cachePrefix, FilterConfiguration $filterConfig)
    {
        parent::__construct($filesystem, $requestContext, $webRootDir, $cachePrefix);

        $this->filterConfig = $filterConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($path, $filter): string
    {
        $path = $this->changeFileExtension($path, $filter);

        return parent::resolve($path, $filter);
    }

    public function isStored($path, $filter)
    {
        $path = $this->changeFileExtension($path, $filter);

        return parent::isStored($path, $filter);
    }

    public function store(BinaryInterface $binary, $path, $filter)
    {
        $path = $this->changeFileExtension($path, $filter);

        parent::store($binary, $path, $filter);
    }

    /**
     * @param string $path
     * @param string $format
     *
     * @return string
     */
    private function changeFileExtension(string $path, string $filter): string
    {
        $format = $this->filterConfig->get($filter)['format'] ?? null;
        if (!$format) {
            return $path;
        }

        $info = pathinfo($path);
        $path = $info['dirname'] . \DIRECTORY_SEPARATOR . $info['filename'] . '.' . $format;

        return $path;
    }

    private function getFullPath($path, $filter)
    {
        // crude way of sanitizing URL scheme ("protocol") part
        $path = str_replace('://', '---', $path);

        return $this->cachePrefix . '/' . $filter . '/' . ltrim($path, '/');
    }
}
