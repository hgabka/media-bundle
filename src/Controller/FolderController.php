<?php

namespace Hgabka\MediaBundle\Controller;

use Doctrine\ORM\EntityManager;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Form\SubFolderType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * FolderController.
 */
class FolderController extends BaseMediaController
{
    #[Route(
        '/delete/{folderId}',
        name: 'HgabkaMediaBundle_folder_delete',
        requirements: ['folderId' => '\d+']
    )]
    public function deleteAction(int $folderId)
    {
        $this->getAdmin()->checkAccess('delete');

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);
        $folderName = $folder->getName();
        $parentFolder = $folder->getParent();

        if (null === $parentFolder) {
            $this->addFlash(
                'sonata_flash_error',
                $this->getTranslator()->trans('hg_media.folder.delete.failure.text', [
                    '%folder%' => $folderName,
                ])
            );
        } elseif ($folder->isInternal()) {
            $this->addFlash(
                'sonata_flash_error',
                $this->getTranslator()->trans('hg_media.folder.delete.failure.text', [
                    '%folder%' => $folderName,
                ])
            );
            $folderId = $parentFolder->getId();
        } else {
            $em->getRepository(Folder::class)->delete($folder);
            $this->addFlash(
                'sonata_flash_success',
                $this->getTranslator()->trans('hg_media.folder.delete.success.text', [
                    '%folder%' => $folderName,
                ])
            );
            $folderId = $parentFolder->getId();
        }
        if (strpos($_SERVER['HTTP_REFERER'], 'chooser')) {
            $redirect = 'HgabkaMediaBundle_chooser_show_folder';
        } else {
            $redirect = 'admin_hgabka_media_media_list';
        }

        $type = $this->getRequestStack()->getCurrentRequest()->get('type');

        return new RedirectResponse(
            $this->generateUrl(
                $redirect,
                [
                    'folderId' => $folderId,
                    'type' => $type,
                ]
            )
        );
    }

    #[Route(
        '/subcreate/{folderId}',
        name: 'HgabkaMediaBundle_folder_sub_create',
        requirements: ['folderId' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function subCreateAction(Request $request, int $folderId): Response
    {
        $this->getAdmin()->checkAccess('create');

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        // @var Folder $parent
        $parent = $em->getRepository(Folder::class)->getFolder($folderId);
        $folder = new Folder();
        $folder->setParent($parent);
        $folder->setCurrentLocale($this->getUtils()->getCurrentLocale());
        $form = $this->createForm(SubFolderType::class, $folder);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->getRepository('HgabkaMediaBundle:Folder')->save($folder);
                $this->addFlash(
                    'sonata_flash_success',
                    $this->getTranslator()->trans('hg_media.folder.addsub.success.text', [
                        '%folder%' => $folder->getName(),
                    ])
                );
                if (false !== strpos($_SERVER['HTTP_REFERER'], 'chooser')) {
                    $redirect = 'HgabkaMediaBundle_chooser_show_folder';
                } else {
                    $redirect = 'admin_hgabka_media_media_list';
                }

                $type = $request->get('type');

                return new RedirectResponse(
                    $this->generateUrl(
                        $redirect,
                        [
                            'folderId' => $folder->getId(),
                            'type' => $type,
                        ]
                    )
                );
            }
        }

        $galleries = $em->getRepository('HgabkaMediaBundle:Folder')->getAllFolders();

        return $this->render(
            'HgabkaMediaBundle:Folder:addsub-modal.html.twig',
            [
                'subform' => $form->createView(),
                'galleries' => $galleries,
                'folder' => $folder,
                'parent' => $parent,
                'admin' => $this->getAdmin(),
            ]
        );
    }

    #[Route(
        '/empty/{folderId}',
        name: 'HgabkaMediaBundle_folder_empty',
        requirements: ['folderId' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function emptyAction(Request $request, int $folderId): Response
    {
        $this->getAdmin()->checkAccess('delete');

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);

        $form = $this->createEmptyForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $alsoDeleteFolders = $data['checked'];

                $em->getRepository(Folder::class)->emptyFolder($folder, $alsoDeleteFolders);

                $this->addFlash(
                    'sonata_flash_success',
                    $this->getTranslator()->trans('hg_media.folder.empty.success.text', [
                        '%folder%' => $folder->getName(),
                    ])
                );
                if (false !== strpos($_SERVER['HTTP_REFERER'], 'chooser')) {
                    $redirect = 'HgabkaMediaBundle_chooser_show_folder';
                } else {
                    $redirect = 'admin_hgabka_media_media_list';
                }

                return new RedirectResponse(
                    $this->generateUrl(
                        $redirect,
                        [
                            'folderId' => $folder->getId(),
                            'folder' => $folder,
                        ]
                    )
                );
            }
        }

        return $this->render(
            'HgabkaMediaBundle:Folder:empty-modal.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    #[Route(
        '/reorder',
        name: 'HgabkaMediaBundle_folder_reorder'
    )]
    public function reorderAction(Request $request): Response
    {
        $this->getAdmin()->checkAccess('edit');
        $folders = [];
        $nodeIds = $request->get('nodes');

        $em = $this->doctrine->getManager();
        $repository = $em->getRepository(Folder::class);

        foreach ($nodeIds as $id) {
            // @var Folder $folder
            $folder = $repository->find($id);
            $folders[] = $folder;
        }

        foreach ($folders as $id => $folder) {
            $repository->moveDown($folder, true);
        }

        $em->flush();

        return new JsonResponse(
            [
                'Success' => 'The node-translations for have got new weight values',
            ]
        );
    }

    private function createEmptyForm()
    {
        $defaultData = ['checked' => false];
        $form = $this->createFormBuilder($defaultData)
            ->add('checked', CheckboxType::class, ['required' => false, 'label' => 'media.folder.empty.modal.checkbox'])
            ->getForm();

        return $form;
    }
}
