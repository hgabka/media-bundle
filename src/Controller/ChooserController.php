<?php

namespace Hgabka\MediaBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Hgabka\MediaBundle\AdminList\MediaAdminListConfigurator;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\SubFolderType;
use Hgabka\MediaBundle\Helper\Media\AbstractMediaHandler;
use Hgabka\MediaBundle\Helper\MediaManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * ChooserController.
 */
class ChooserController extends BaseMediaController
{
    /**
     * @Route("/chooser", name="HgabkaMediaBundle_chooser")
     *
     * @return RedirectResponse
     */
    public function chooserIndexAction(Request $request)
    {
        $this->getAdmin()->checkAccess('list');

        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $folderId = false;

        $type = $request->get('type', 'all');
        $cKEditorFuncNum = $request->get('CKEditorFuncNum');
        $linkChooser = $request->get('linkChooser');

        $folderName = $request->get('foldername');

        $folderId = false;

        if (!empty($folderName)) {
            $folder = $em->getRepository(Folder::class)->findOneByInternalName($folderName);
            if ($folder) {
                $folderId = $folder->getId();
            }
        }

        $fid = $request->get('folderid');
        if (empty($folderId) && !empty($fid)) {
            $folder = $em->getRepository(Folder::class)->find($fid);
            if ($folder) {
                $folderId = $fid;
            }
        }
        // Go to the last visited folder
        if (empty($folderId) && $session->get('last-media-folder')) {
            try {
                $em->getRepository(Folder::class)->getFolder($session->get('last-media-folder'));
                $folderId = $session->get('last-media-folder');
            } catch (EntityNotFoundException $e) {
                $folderId = false;
            }
        }
        
        $folderConfig = $this->getParameter('hgabka_media.default_ckeditor_folders');

        if (!$folderId) {
            $folderConfig = $this->getParameter('hgabka_media.default_ckeditor_folders');
            $defaultFolder = 'image' === $type ? $folderConfig['images'] : $folderConfig['files'];

            $repo = $em->getRepository(Folder::class);
            if (!empty($defaultFolder)) {
                if (\is_int($defaultFolder)) {
                    $folder = $repo->find($defaultFolder);
                } else {
                    $folder = $repo->findOneBy(['internalName' => $defaultFolder]);
                }

                $folderId = $folder ? $folder->getId() : null;
            }

            if (empty($folderId)) {
                // Redirect to the first top folder
                // @var Folder $firstFolder
                $firstFolder = $repo->getFirstTopFolder();
                $folderId = $firstFolder->getId();
            }
        }
        
        $params = [
            'folderId' => $folderId,
            'type' => $type,
            'CKEditorFuncNum' => $cKEditorFuncNum,
            'linkChooser' => $linkChooser,
            'admin' => $this->getAdmin(),
        ];

        return $this->redirect($this->generateUrl('HgabkaMediaBundle_chooser_show_folder', $params));
    }

    /**
     * @param int $folderId The folder id
     *
     * @Route("/chooser/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_chooser_show_folder")
     *
     * @return array
     */
    public function chooserShowFolderAction(Request $request, $folderId)
    {
        $this->getAdmin()->checkAccess('list');
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $type = $request->get('type');
        $cKEditorFuncNum = $request->get('CKEditorFuncNum');
        $linkChooser = $request->get('linkChooser');

        // Remember the last visited folder in the session
        $session->set('last-media-folder', $folderId);

        // Check when user switches between thumb -and list view
        $viewMode = $request->query->get('viewMode');
        if ($viewMode && 'list-view' === $viewMode) {
            $session->set('media-list-view', true);
        } elseif ($viewMode && 'thumb-view' === $viewMode) {
            $session->remove('media-list-view');
        }

        // @var MediaManager $mediaHandler
        $mediaHandler = $this->getManager();

        $em = $this->getDoctrine();
        $repo = $em->getRepository(Folder::class);

        // @var Folder $folder
        $folder = empty($folderId) ? $repo->getFirstTopFolder() : $repo->getFolder($folderId);

        /** @var AbstractMediaHandler $handler */
        $handler = null;
        if ($type) {
            $handler = $mediaHandler->getHandlerForType($type);
        }

        // @var MediaManager $mediaManager
        $mediaManager = $this->getManager();

        /*       $adminListConfigurator = new MediaAdminListConfigurator($em, $mediaManager, $folder, $request);
               $adminList = $this->get('hgabka_adminlist.factory')->createList($adminListConfigurator);
               $adminList->bindRequest($request);*/

        $sub = new Folder();
        $sub->setParent($folder);
        $sub->setCurrentLocale($this->getUtils()->getCurrentLocale());
        $subForm = $this->createForm(SubFolderType::class, $sub, ['folder' => $sub]);

        $linkChooserLink = null;
        if (!empty($linkChooser)) {
            $params = [];
            if (null !== $cKEditorFuncNum) {
                $params['CKEditorFuncNum'] = $cKEditorFuncNum;
                $routeName = 'HgabkaNodeBundle_ckselecturl';
            } else {
                $routeName = 'HgabkaNodeBundle_selecturl';
            }
            $linkChooserLink = $this->generateUrl($routeName, $params);
        }
        $orderBy = $request->query->get('orderBy', 'updatedAt');
        $orderDirection = $request->query->get('orderDirection', 'DESC');

        $this->buildFilters();
        $this->getFilterBuilder()->bindRequest($request);

        $viewVariabels = [
            'cKEditorFuncNum' => $cKEditorFuncNum,
            'linkChooser' => $linkChooser,
            'linkChooserLink' => $linkChooserLink,
            'mediamanager' => $mediaManager,
            'foldermanager' => $this->getFolderManager(),
            'handler' => $handler,
            'type' => $type,
            'folder' => $folder,
            'pagerfanta' => $this->getPager($request, $folder),
            'orderByFields' => ['name', 'contentType', 'updatedAt', 'filesize'],
            'base_template' => $this->getBaseTemplate(),
            'orderBy' => $orderBy,
            'orderDirection' => $orderDirection,
            'subform' => $subForm->createView(),
            'admin' => $this->getAdmin(),
            'filter' => $this->getFilterBuilder(),
        ];

        // generate all forms
        $forms = [];

        foreach ($mediaManager->getFolderAddActions()  as $addAction) {
            $forms[$addAction['type']] = $this->createTypeFormView($mediaHandler, $addAction['type']);
        }

        $viewVariabels['forms'] = $forms;

        return $this->render('@HgabkaMedia/Chooser/chooserShowFolder.html.twig', $viewVariabels);
    }

    /**
     * @param string $type
     *
     * @return \Symfony\Component\Form\FormView
     */
    private function createTypeFormView(MediaManager $mediaManager, $type)
    {
        $handler = $mediaManager->getHandlerForType($type);
        $media = new Media();
        $media->setCurrentLocale($this->getUtils()->getCurrentLocale());
        $helper = $handler->getFormHelper($media);

        return $this->createForm($handler->getFormType(), $helper, $handler->getFormTypeOptions())->createView();
    }
}
