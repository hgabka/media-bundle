<?php

namespace Hgabka\MediaBundle\Form\Type;

use Doctrine\Common\Persistence\ObjectManager;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MediaSimpleType extends AbstractType
{
    /**
     * @var MediaManager
     */
    protected $mediaManager;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /**
     * @param MediaManager  $mediaManager  The media manager
     * @param ObjectManager $objectManager The media manager
     * @param mixed         $utils
     */
    public function __construct($mediaManager, $objectManager, $utils)
    {
        $this->mediaManager = $mediaManager;
        $this->objectManager = $objectManager;
        $this->hgabkaUtils = $utils;
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
        $folder = null;
        $repo = $this->objectManager->getRepository(Folder::class);
        if (!empty($options['folderid'])) {
            $folder = $repo->getFolder($options['folderid']);
        }
        if (!$folder && !empty($options['foldername'])) {
            $folder = $repo->findOneByInternalName($options['foldername']);
        }
        if (!$folder && !empty($options['folder']) && $options['folder'] instanceof Folder) {
            $folder = $options['folder'];
        }
        if (!$folder) {
            if (!empty($options['foldername']) && !empty($options['parentfolder'])) {
                $parentFolder = $options['parentfolder'] instanceof Folder
                    ? $parentFolder
                    : $repo->findOneByInternalName($options['parentfolder']);
                if (!$parentFolder) {
                    $parentFolder = $repo->getFirstTopFolder();
                }
                $folder = new Folder();
                $folder
                    ->setParent($parentFolder)
                    ->setInternalName($options['foldername'])
                    ->setCurrentLocale($this->hgabkaUtils->getCurrentLocale())
                    ->setName($options['foldername'])
                ;
                $this->objectManager->persist($folder);
                $this->objectManager->flush();
            } else {
                $folder = $repo->getFirstTopFolder();
            }
        }
        $builder->add('id', HiddenType::class);
        $builder->add('file', FileType::class);

        $builder->addViewTransformer(
            new FileToMediaTransformer($this->objectManager, $options['current_value_container'], $this->mediaManager, $folder, $options['medianame']),
            true
        );

        $builder->setAttribute('foldername', $options['foldername']);
        $builder->setAttribute('folderid', $options['folderid']);
    }

    public function getParent()
    {
        return FormType::class;
    }

    public function getBlockPrefix()
    {
        return 'media_simple';
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
                'compound' => true,
                'current_value_container' => new CurrentValueContainer(),
                'foldername' => null,
                'medianame' => null,
                'folderid' => null,
                'folder' => null,
                'parentfolder' => null,
                'error_bubbling' => false,
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['mediamanager'] = $this->mediaManager;
        $view->vars['foldername'] = $form->getConfig()->getAttribute('foldername');
        $view->vars['folderid'] = $form->getConfig()->getAttribute('folderid');
    }
}
