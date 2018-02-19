<?php

namespace Hgabka\MediaBundle\Controller;

use Doctrine\ORM\EntityNotFoundException;
use Hgabka\MediaBundle\AdminList\MediaAdminListConfigurator;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\FolderType;
use Hgabka\MediaBundle\Helper\Media\AbstractMediaHandler;
use Hgabka\MediaBundle\Helper\MediaManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * ChooserController.
 */
class ChooserController extends Controller
{
    /**
     * @Route("/chooser", name="HgabkaMediaBundle_chooser")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function chooserIndexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $folderId = false;

        $type = $request->get('type', 'all');
        $cKEditorFuncNum = $request->get('CKEditorFuncNum');
        $linkChooser = $request->get('linkChooser');

        $folderName = $request->get('foldername');

        $folderId = false;

        if (!empty($folderName)) {
            $folder = $em->getRepository('HgabkaMediaBundle:Folder')->findOneByInternalName($folderName);
            if ($folder) {
                $folderId = $folder->getId();
            }
        }

        $fid = $request->get('folderid');
        if (empty($folderId) && !empty($fid)) {
            $folder = $em->getRepository('HgabkaMediaBundle:Folder')->find($fid);
            if ($folder) {
                $folderId = $fid;
            }
        }
        // Go to the last visited folder
        if (empty($folderId) && $session->get('last-media-folder')) {
            try {
                $em->getRepository('HgabkaMediaBundle:Folder')->getFolder($session->get('last-media-folder'));
                $folderId = $session->get('last-media-folder');
            } catch (EntityNotFoundException $e) {
                $folderId = false;
            }
        }

        if (!$folderId) {
            // Redirect to the first top folder
            // @var Folder $firstFolder
            $firstFolder = $em->getRepository('HgabkaMediaBundle:Folder')->getFirstTopFolder();
            $folderId = $firstFolder->getId();
        }

        $params = [
            'folderId' => $folderId,
            'type' => $type,
            'CKEditorFuncNum' => $cKEditorFuncNum,
            'linkChooser' => $linkChooser,
        ];

        return $this->redirect($this->generateUrl('HgabkaMediaBundle_chooser_show_folder', $params));
    }

    /**
     * @param Request $request
     * @param int     $folderId The folder id
     *
     * @Route("/chooser/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_chooser_show_folder")
     * @Template()
     *
     * @return array
     */
    public function chooserShowFolderAction(Request $request, $folderId)
    {
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
        $mediaHandler = $this->get('hgabka_media.media_manager');

        // @var Folder $folder
        $folder = $em->getRepository('HgabkaMediaBundle:Folder')->getFolder($folderId);

        /** @var AbstractMediaHandler $handler */
        $handler = null;
        if ($type) {
            $handler = $mediaHandler->getHandlerForType($type);
        }

        // @var MediaManager $mediaManager
        $mediaManager = $this->get('hgabka_media.media_manager');

        $adminListConfigurator = new MediaAdminListConfigurator($em, $mediaManager, $folder, $request);
        $adminList = $this->get('hgabka_adminlist.factory')->createList($adminListConfigurator);
        $adminList->bindRequest($request);

        $sub = new Folder();
        $sub->setParent($folder);
        $subForm = $this->createForm(FolderType::class, $sub, ['folder' => $sub]);

        $linkChooserLink = null;
        if (!empty($linkChooser)) {
            $params = [];
            if (!empty($cKEditorFuncNum)) {
                $params['CKEditorFuncNum'] = $cKEditorFuncNum;
                $routeName = 'HgabkaNodeBundle_ckselecturl';
            } else {
                $routeName = 'HgabkaNodeBundle_selecturl';
            }
            $linkChooserLink = $this->generateUrl($routeName, $params);
        }

        $viewVariabels = [
            'cKEditorFuncNum' => $cKEditorFuncNum,
            'linkChooser' => $linkChooser,
            'linkChooserLink' => $linkChooserLink,
            'mediamanager' => $mediaManager,
            'foldermanager' => $this->get('hgabka_media.folder_manager'),
            'handler' => $handler,
            'type' => $type,
            'folder' => $folder,
            'adminlist' => $adminList,
            'subform' => $subForm->createView(),
        ];

        // generate all forms
        $forms = [];

        foreach ($mediaManager->getFolderAddActions()  as $addAction) {
            $forms[$addAction['type']] = $this->createTypeFormView($mediaHandler, $addAction['type']);
        }

        $viewVariabels['forms'] = $forms;

        return $viewVariabels;
    }

    /**
     * @param MediaManager $mediaManager
     * @param string       $type
     *
     * @return \Symfony\Component\Form\FormView
     */
    private function createTypeFormView(MediaManager $mediaManager, $type)
    {
        $handler = $mediaManager->getHandlerForType($type);
        $media = new Media();
        $helper = $handler->getFormHelper($media);

        return $this->createForm($handler->getFormType(), $helper, $handler->getFormTypeOptions())->createView();
    }
}
