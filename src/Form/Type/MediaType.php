<?php

namespace Hgabka\MediaBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Helper\MediaManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MediaType.
 */
class MediaType extends AbstractType
{
    /**
     * @var MediaManager
     */
    protected $mediaManager;

    /**
     * @var EntityManagerInterface
     */
    protected $objectManager;

    /**
     * @param MediaManager  $mediaManager  The media manager
     * @param ObjectManager $objectManager The media manager
     */
    public function __construct($mediaManager, $objectManager)
    {
        $this->mediaManager = $mediaManager;
        $this->objectManager = $objectManager;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(
            new IdToMediaTransformer($this->objectManager, $options['current_value_container']),
            true
        );
        $builder->setAttribute('chooser', $options['chooser']);
        $builder->setAttribute('mediatype', $options['mediatype']);
        $builder->setAttribute('foldername', $options['foldername']);
        $builder->setAttribute('folderid', $options['folderid']);
        $builder->setAttribute('editor_filter', $options['editor_filter']);
        $builder->setAttribute('editor_filter_retina', $options['editor_filter_retina']);
    }

    /**
     * @return string
     */
    public function getParent(): ?string
    {
        return FormType::class;
    }

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver the resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'compound' => false,
                'chooser' => 'HgabkaMediaBundle_chooser',
                'mediatype' => null,
                'current_value_container' => new CurrentValueContainer(),
                'foldername' => null,
                'folderid' => null,
                'editor_filter' => 'media_list_thumbnail',
                'editor_filter_retina' => 'media_list_thumbnail_retina',
                'protected' => false,
           ]
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'media';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['chooser'] = $form->getConfig()->getAttribute('chooser');
        $view->vars['mediatype'] = $form->getConfig()->getAttribute('mediatype');
        $view->vars['mediamanager'] = $this->mediaManager;
        $view->vars['foldername'] = $form->getConfig()->getAttribute('foldername');
        $view->vars['folderid'] = $form->getConfig()->getAttribute('folderid');
        $view->vars['editor_filter'] = $form->getConfig()->getAttribute('editor_filter');
        $view->vars['editor_filter_retina'] = $form->getConfig()->getAttribute('editor_filter_retina');
    }
}
