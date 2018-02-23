<?php

namespace Hgabka\MediaBundle\Form;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FolderType extends AbstractType
{
    /** @var HgabkaUtils $utils */
    protected $utils;

    /**
     * FolderType constructor.
     *
     * @param HgabkaUtils $utils
     */
    public function __construct(HgabkaUtils $utils)
    {
        $this->utils = $utils;
    }

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $folder = $options['folder'];
        $builder
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'locales' => $this->utils->getAvailableLocales(),
                'required' => false,
                'fields' => [
                    'name' => [
                        'label' => 'media.folder.addsub.form.name',
                        'required' => false,
                        'field_type' => TextType::class,
                    ],
                ],
            ])
            ->add(
                'rel',
                ChoiceType::class,
                [
                    'choices' => [
                        'media' => 'media',
                        'image' => 'image',
                        'slideshow' => 'slideshow',
                        'video' => 'video',
                    ],
                    'label' => 'media.folder.addsub.form.rel',
                ]
            )
            ->add(
                'parent',
                EntityType::class,
                [
                    'class' => Folder::class,
                    'choice_label' => 'optionLabel',
                    'label' => 'media.folder.addsub.form.parent',
                    'required' => true,
                    'query_builder' => function (FolderRepository $er) use ($folder) {
                        return $er->selectFolderQueryBuilder($folder);
                    },
                ]
            )
            ->add(
                'internalName',
                TextType::class,
                [
                    'label' => 'media.folder.addsub.form.internal_name',
                    'required' => false,
                ]
            );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'hgabka_mediabundle_FolderType';
    }

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver the resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Folder::class,
                'folder' => null,
            ]
        );
    }
}
