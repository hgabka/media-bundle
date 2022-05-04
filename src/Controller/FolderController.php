<?php

namespace Hgabka\MediaBundle\Controller;

use Doctrine\ORM\EntityManager;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Form\FolderType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FolderController.
 */
class FolderController extends BaseMediaController
{
    /**
     * @param int $folderId
     *
     * @Route("/delete/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_delete")
     *
     * @return RedirectResponse
     */
    public function deleteAction($folderId)
    {
        $this->getAdmin()->checkAccess('delete');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

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

    /**
     * @param int $folderId
     *
     * @Route("/subcreate/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_sub_create", methods={"GET", "POST"})
     * @Template()
     *
     * @return Response
     */
    public function subCreateAction(Request $request, $folderId)
    {
        $this->getAdmin()->checkAccess('create');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // @var Folder $parent
        $parent = $em->getRepository(Folder::class)->getFolder($folderId);
        $folder = new Folder();
        $folder->setParent($parent);
        $folder->setCurrentLocale($this->getUtils()->getCurrentLocale());
        $form = $this->createForm(FolderType::class, $folder);
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

    /**
     * @param int $folderId
     *
     * @Route("/empty/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_empty", methods={"GET", "POST"})
     * @Template()
     *
     * @return Response
     */
    public function emptyAction(Request $request, $folderId)
    {
        $this->getAdmin()->checkAccess('delete');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

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

    /**
     * @Route("/reorder", name="HgabkaMediaBundle_folder_reorder")
     *
     * @return JsonResponse
     */
    public function reorderAction(Request $request)
    {
        $this->getAdmin()->checkAccess('edit');
        $folders = [];
        $nodeIds = $request->get('nodes');

        $em = $this->getDoctrine()->getManager();
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
