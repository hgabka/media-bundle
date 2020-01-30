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
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FolderType extends AbstractType
{
    /** @var HgabkaUtils $utils */
    protected $utils;

    /** @var AuthorizationCheckerInterface */
    protected $authChecker;

    /**
     * FolderType constructor.
     *
     * @param HgabkaUtils $utils
     */
    public function __construct(HgabkaUtils $utils, AuthorizationCheckerInterface $authChecker)
    {
        $this->utils = $utils;
        $this->authChecker = $authChecker;
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
                        'label' => 'hg_media.folder.addsub.form.name',
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
                        'media' => 'hg_media',
                        'image' => 'image',
                        'slideshow' => 'slideshow',
                        'video' => 'video',
                    ],
                    'label' => 'hg_media.folder.addsub.form.rel',
                ]
            )
            ->add(
                'parent',
                EntityType::class,
                [
                    'class' => Folder::class,
                    'choice_label' => 'optionLabel',
                    'label' => 'hg_media.folder.addsub.form.parent',
                    'required' => true,
                    'query_builder' => function (FolderRepository $er) use ($folder) {
                        return $er->selectFolderQueryBuilder($folder);
                    },
                ]
            )
        ;
        if ($this->authChecker->isGranted('ROLE_ADMIN')) {
            $builder
                ->add(
                    'internalName',
                    TextType::class,
                    [
                        'label' => 'hg_media.folder.addsub.form.internal_name',
                        'required' => false,
                    ]
                )->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                    $folder = $event->getData();
                    $form = $event->getForm();
                    if ($folder->isInternal()) {
                        $form
                            ->remove('rel')
                            ->remove('parent')
                            ->remove('internalName')
                        ;
                    }
                })
            ;
        }
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
