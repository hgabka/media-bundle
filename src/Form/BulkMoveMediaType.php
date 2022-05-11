<?php

namespace Hgabka\MediaBundle\Form;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class BulkMoveMediaType.
 */
class BulkMoveMediaType extends AbstractType
{
    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting form the
     * top most type. Type extensions can further modify the form.
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     *
     * @see FormTypeExtensionInterface::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'folder',
                EntityType::class,
                [
                    'class' => Folder::class,
                    'choice_label' => 'optionLabel',
                    'label' => false,
                    'required' => true,
                    'query_builder' => function (FolderRepository $er) {
                        return $er->selectFolderQueryBuilder();
                    },
                ]
            )
            ->add(
                'media',
                HiddenType::class
            );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'hgabka_mediabundle_folder_bulk_move';
    }
}
