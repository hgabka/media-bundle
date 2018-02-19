<?php

namespace Hgabka\MediaBundle\Helper\IconFont;

interface IconFontLoaderInterface
{
    public function setData(array $data);

    public function getCssLink();

    public function getCssClasses();
}
