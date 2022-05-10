<?php

namespace Hgabka\MediaBundle\Form;

class SubFolderType extends FolderType
{
    public function getBlockPrefix()
    {
        return 'hgabka_mediabundle_subFolderType';
    }
}
