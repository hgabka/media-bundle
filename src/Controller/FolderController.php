<?php

namespace Hgabka\MediaBundle\Controller;

use Doctrine\ORM\EntityManager;
use Hgabka\AdminBundle\FlashMessages\FlashTypes;
use Hgabka\MediaBundle\AdminList\MediaAdminListConfigurator;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Form\FolderType;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * FolderController.
 */
class FolderController extends Controller
{
    /**
     * @param Request $request
     * @param int     $folderId The folder id
     *
     * @Route("/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_show")
     * @Template()
     *
     * @return array
     */
    public function showAction(Request $request, $folderId)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        // Check when user switches between thumb -and list view
        $viewMode = $request->query->get('viewMode');
        if ($viewMode && 'list-view' === $viewMode) {
            $session->set('media-list-view', true);
        } elseif ($viewMode && 'thumb-view' === $viewMode) {
            $session->remove('media-list-view');
        }

        // @var MediaManager $mediaManager
        $mediaManager = $this->get('hgabka_media.media_manager');

        // @var Folder $folder
        $folder = $em->getRepository('HgabkaMediaBundle:Folder')->getFolder($folderId);

        $adminListConfigurator = new MediaAdminListConfigurator($em, $mediaManager, $folder, $request);
        $adminList = $this->get('hgabka_adminlist.factory')->createList($adminListConfigurator);
        $adminList->bindRequest($request);

        $sub = new Folder();
        $sub->setParent($folder);
        $subForm = $this->createForm(FolderType::class, $sub, ['folder' => $sub]);

        $emptyForm = $this->createEmptyForm();

        $editForm = $this->createForm(FolderType::class, $folder, ['folder' => $folder]);

        if ($request->isMethod('POST')) {
            $editForm->handleRequest($request);
            if ($editForm->isValid()) {
                $em->getRepository('HgabkaMediaBundle:Folder')->save($folder);

                $this->addFlash(
                    FlashTypes::SUCCESS,
                    $this->get('translator')->trans('media.folder.show.success.text', [
                        '%folder%' => $folder->getName(),
                    ])
                );

                return new RedirectResponse(
                    $this->generateUrl(
                        'HgabkaMediaBundle_folder_show',
                        ['folderId' => $folderId]
                    )
                );
            }
        }

        return [
            'foldermanager' => $this->get('hgabka_media.folder_manager'),
            'mediamanager' => $this->get('hgabka_media.media_manager'),
            'subform' => $subForm->createView(),
            'emptyform' => $emptyForm->createView(),
            'editform' => $editForm->createView(),
            'folder' => $folder,
            'adminlist' => $adminList,
            'type' => null,
        ];
    }

    /**
     * @param int $folderId
     *
     * @Route("/delete/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_delete")
     *
     * @return RedirectResponse
     */
    public function deleteAction($folderId)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // @var Folder $folder
        $folder = $em->getRepository('HgabkaMediaBundle:Folder')->getFolder($folderId);
        $folderName = $folder->getName();
        $parentFolder = $folder->getParent();

        if (null === $parentFolder) {
            $this->addFlash(
                'sonata_flash_error',
                $this->get('translator')->trans('hg_media.folder.delete.failure.text', [
                    '%folder%' => $folderName,
                ])
            );
        } else {
            $em->getRepository('HgabkaMediaBundle:Folder')->delete($folder);
            $this->addFlash(
                'sonata_flash_success',
                $this->get('translator')->trans('hg_media.folder.delete.success.text', [
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

        $type = $this->get('request_stack')->getCurrentRequest()->get('type');

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
     * @param Request $request
     * @param int     $folderId
     *
     * @Route("/subcreate/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_sub_create")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @return Response
     */
    public function subCreateAction(Request $request, $folderId)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // @var Folder $parent
        $parent = $em->getRepository('HgabkaMediaBundle:Folder')->getFolder($folderId);
        $folder = new Folder();
        $folder->setParent($parent);
        $folder->setCurrentLocale($this->get(HgabkaUtils::class)->getCurrentLocale());
        $form = $this->createForm(FolderType::class, $folder);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em->getRepository('HgabkaMediaBundle:Folder')->save($folder);
                $this->addFlash(
                    'sonata_flash_success',
                    $this->get('translator')->trans('hg_media.folder.addsub.success.text', [
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
            ]
        );
    }

    /**
     * @param Request $request
     * @param int     $folderId
     *
     * @Route("/empty/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_folder_empty")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @return Response
     */
    public function emptyAction(Request $request, $folderId)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // @var Folder $folder
        $folder = $em->getRepository('HgabkaMediaBundle:Folder')->getFolder($folderId);

        $form = $this->createEmptyForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();
                $alsoDeleteFolders = $data['checked'];

                $em->getRepository('HgabkaMediaBundle:Folder')->emptyFolder($folder, $alsoDeleteFolders);

                $this->addFlash(
                    'sonata_flash_success',
                    $this->get('translator')->trans('hg_media.folder.empty.success.text', [
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function reorderAction(Request $request)
    {
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
