<?php

namespace Hgabka\MediaBundle\Helper\Imagine;

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
        return sprintf(
            '%s/%s',
            $this->getBaseUrl(),
            $this->getFileUrl($path, $filter)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFileUrl($path, $filter): string
    {
        $filterConf = $this->filterConfig->get($filter);
        $path = $this->changeFileExtension($path, $filterConf['format']);

        return parent::getFileUrl($path, $filter);
    }

    /**
     * @param string $path
     * @param string $format
     *
     * @return string
     */
    private function changeFileExtension(string $path, ?string $format): string
    {
        if (!$format) {
            return $path;
        }

        $info = pathinfo($path);
        $path = $info['dirname'] . \DIRECTORY_SEPARATOR . $info['filename'] . '.' . $format;

        return $path;
    }
}
