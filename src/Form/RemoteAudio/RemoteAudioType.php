<?php

namespace Hgabka\MediaBundle\Form\RemoteAudio;

use Hgabka\MediaBundle\Form\AbstractRemoteType;
use Hgabka\MediaBundle\Helper\RemoteAudio\RemoteAudioHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * RemoteAudioType.
 */
class RemoteAudioType extends AbstractRemoteType
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
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'type',
                ChoiceType::class,
                [
                    'label' => 'hg_media.form.remote_audio.type.label',
                    'choices' => ['soundcloud' => 'soundcloud'],
                    'constraints' => [new NotBlank()],
                    'required' => true,
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
        return 'hgabka_mediabundle_audiotype';
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
                'data_class' => RemoteAudioHelper::class,
            ]
        );
    }
}
