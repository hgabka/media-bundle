<?php

namespace Hgabka\MediaBundle\Form;

class SubFolderType extends FolderType
{
    public function getBlockPrefix(): string
    {
        return 'hgabka_mediabundle_subfolderType';
    }
}
