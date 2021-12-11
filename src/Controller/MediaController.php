<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\AdminBundle\FlashMessages\FlashTypes;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\BulkMoveMediaType;
use Hgabka\MediaBundle\Helper\MediaManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * MediaController.
 */
class MediaController extends BaseMediaController
{
    /**
     * @param int $mediaId
     *
     * @Route("/{mediaId}", requirements={"mediaId" = "\d+"}, name="HgabkaMediaBundle_media_show")
     *
     * @return Response
     */
    public function show(Request $request, $mediaId)
    {
        $this->getAdmin()->checkAccess('edit');

        $em = $this->getDoctrine()->getManager();

        // @var Media $media
        $media = $em->getRepository(Media::class)->getMedia($mediaId);
        $folder = $media->getFolder();

        // @var MediaManager $mediaManager
        $mediaManager = $this->getManager();
        $handler = $mediaManager->getHandler($media);
        $helper = $handler->getFormHelper($media);

        $form = $this->createForm($handler->getFormType(), $helper, $handler->getFormTypeOptions());

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $media = $helper->getMedia();
                $em->getRepository(Media::class)->save($media);

                return new RedirectResponse($this->generateUrl(
                    'HgabkaMediaBundle_media_show',
                    ['mediaId' => $media->getId()]
                ));
            }
        }
        $showTemplate = $mediaManager->getHandler($media)->getShowTemplate($media);

        return $this->render(
            $showTemplate,
            [
                'handler' => $handler,
                'foldermanager' => $this->getFolderManager(),
                'mediamanager' => $this->getManager(),
                'editform' => $form->createView(),
                'media' => $media,
                'helper' => $helper,
                'folder' => $folder,
                'admin' => $this->getAdmin(),
                'base_template' => $this->getParameter('sonata.admin.configuration.templates')['layout'],
            ]
        );
    }

    /**
     * @param int $mediaId
     *
     * @Route("/delete/{mediaId}", requirements={"mediaId" = "\d+"}, name="HgabkaMediaBundle_media_delete")
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, $mediaId)
    {
        $this->getAdmin()->checkAccess('delete');

        $em = $this->getDoctrine()->getManager();

        // @var Media $media
        $media = $em->getRepository(Media::class)->getMedia($mediaId);
        $medianame = $media->getName();
        $folder = $media->getFolder();

        $em->getRepository(Media::class)->delete($media);

        $this->addFlash(
            'sonata_flash_success',
            $this->getTranslator()->trans('hg_media.flash.deleted_success.%medianame%', [
                '%medianame%' => $medianame,
            ])
        );

        // If the redirect url is passed via the url we use it
        $redirectUrl = $request->query->get('redirectUrl');
        if (empty($redirectUrl) || (0 !== strpos($redirectUrl, $request->getSchemeAndHttpHost()) && 0 !== strpos($redirectUrl, '/'))) {
            $redirectUrl = $this->generateUrl(
                'admin_hgabka_media_media_list',
                ['folderId' => $folder->getId()]
            );
        }

        return new RedirectResponse($redirectUrl);
    }

    /**
     * @param int $folderId
     *
     * @Route("bulkupload/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_media_bulk_upload")
     * @Template()
     *
     * @return array|RedirectResponse
     */
    public function bulkUpload($folderId)
    {
        $this->getAdmin()->checkAccess('create');

        $em = $this->getDoctrine()->getManager();

        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);

        return $this->render('@HgabkaMedia/Media/bulkUpload.html.twig', [
            'folder' => $folder,
            'base_template' => $this->getParameter('sonata.admin.configuration.templates')['layout'],
            'foldermanager' => $this->getFolderManager(),
            'admin' => $this->getAdmin(),
        ]);
    }

    /**
     * @param int $folderId
     *
     * @Route("bulkuploadsubmit/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_media_bulk_upload_submit")
     *
     * @return array|RedirectResponse
     */
    public function bulkUploadSubmit($folderId)
    {
        $this->getAdmin()->checkAccess('create');

        // Make sure file is not cached (as it happens for example on iOS devices)
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        // Settings
        if (ini_get('upload_tmp_dir')) {
            $tempDir = ini_get('upload_tmp_dir');
        } else {
            $tempDir = sys_get_temp_dir();
        }
        $targetDir = rtrim($tempDir, '/') . \DIRECTORY_SEPARATOR . 'plupload';
        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 60 * 60; // Temp file age in seconds

        // Create target dir
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }

        // Get a file name
        if (\array_key_exists('name', $_REQUEST)) {
            $fileName = $_REQUEST['name'];
        } elseif (0 !== \count($_FILES)) {
            $fileName = $_FILES['file']['name'];
        } else {
            $fileName = uniqid('file_', false);
        }
        $filePath = $targetDir . \DIRECTORY_SEPARATOR . $fileName;

        $chunk = 0;
        $chunks = 0;
        // Chunking might be enabled
        if (\array_key_exists('chunk', $_REQUEST)) {
            $chunk = (int) $_REQUEST['chunk'];
        }
        if (\array_key_exists('chunks', $_REQUEST)) {
            $chunks = (int) $_REQUEST['chunks'];
        }

        // Remove old temp files
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                return $this->returnJsonError('100', 'Failed to open temp directory.');
            }

            while (false !== ($file = readdir($dir))) {
                $tmpFilePath = $targetDir . \DIRECTORY_SEPARATOR . $file;

                // If temp file is current file proceed to the next
                if ($tmpFilePath === "{$filePath}.part") {
                    continue;
                }

                // Remove temp file if it is older than the max age and is not the current file
                if (preg_match('/\.part$/', $file) && (filemtime($tmpFilePath) < time() - $maxFileAge)) {
                    $success = @unlink($tmpFilePath);
                    if (true !== $success) {
                        return $this->returnJsonError('106', 'Could not remove temp file: ' . $filePath);
                    }
                }
            }
            closedir($dir);
        }

        // Open temp file
        if (!$out = @fopen("{$filePath}.part", $chunks ? 'ab' : 'wb')) {
            return $this->returnJsonError('102', 'Failed to open output stream.');
        }

        if (0 !== \count($_FILES)) {
            if ($_FILES['file']['error'] || !is_uploaded_file($_FILES['file']['tmp_name'])) {
                return $this->returnJsonError('103', 'Failed to move uploaded file.');
            }

            // Read binary input stream and append it to temp file
            if (!$input = @fopen($_FILES['file']['tmp_name'], 'r')) {
                return $this->returnJsonError('101', 'Failed to open input stream.');
            }
        } else {
            if (!$input = @fopen('php://input', 'r')) {
                return $this->returnJsonError('101', 'Failed to open input stream.');
            }
        }

        while ($buff = fread($input, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($input);

        // Check if file has been uploaded
        if (!$chunks || $chunk === $chunks - 1) {
            // Strip the temp .part suffix off
            rename("{$filePath}.part", $filePath);
        }

        $em = $this->getDoctrine()->getManager();
        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);
        $file = new File($filePath);

        try {
            $handler = $this->getManager()->getHandler($file);
            $handler->setHgabkaUtils($this->getUtils());
            // @var Media $media
            $media = $this->getManager()->getHandler($file)->createNew($file);
            $media->setFolder($folder);
            $em->getRepository(Media::class)->save($media);
        } catch (\Exception $e) {
            return $this->returnJsonError('104', 'Failed performing save on media-manager' . $e->getMessage());
        }

        $success = unlink($filePath);
        if (true !== $success) {
            return $this->returnJsonError('105', 'Could not remove temp file: ' . $filePath);
        }

        // Return Success JSON-RPC response
        return new JsonResponse([
            'jsonrpc' => '2.0',
            'result' => '',
            'id' => 'id',
        ]);
    }

    /**
     * @param int $folderId
     *
     * @Route("drop/{folderId}", requirements={"folderId" = "\d+"}, name="HgabkaMediaBundle_media_drop_upload")
     * @Method({"GET", "POST"})
     *
     * @return array|RedirectResponse
     */
    public function drop(Request $request, $folderId)
    {
        $this->getAdmin()->checkAccess('create');

        $em = $this->getDoctrine()->getManager();

        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);

        $drop = null;

        if (\array_key_exists('files', $_FILES) && 0 === $_FILES['files']['error']) {
            $drop = $request->files->get('files');
        } elseif ($request->files->get('file')) {
            $drop = $request->files->get('file');
        } else {
            $drop = $request->get('text');
        }
        $media = $this->getManager()->createNew($drop);
        if ($media) {
            $media->setFolder($folder);
            $em->getRepository(Media::class)->save($media);

            return new Response(json_encode(['status' => $this->getTranslator()->trans('kuma_admin.media.flash.drop_success')]));
        }

        $request->getSession()->getFlashBag()->add(
            FlashTypes::DANGER,
            $this->getTranslator()->trans('kuma_admin.media.flash.drop_unrecognized')
        );

        return new Response(json_encode(['status' => $this->getTranslator()->trans('kuma_admin.media.flash.drop_unrecognized')]));
    }

    /**
     * @param int    $folderId The folder id
     * @param string $type     The type
     *
     * @Route("create/{folderId}/{type}", requirements={"folderId" = "\d+", "type" = ".+"}, name="HgabkaMediaBundle_media_create")
     * @Method({"GET", "POST"})
     *
     * @return array|RedirectResponse
     */
    public function create(Request $request, $folderId, $type)
    {
        $this->getAdmin()->checkAccess('create');

        $params = $this->createAndRedirect($request, $folderId, $type, 'admin_hgabka_media_media_list');
        if ($params instanceof Response) {
            return $params;
        }

        $params['base_template'] = $this->getParameter('sonata.admin.configuration.templates')['layout'];
        $params['foldermanager'] = $this->getFolderManager();
        $params['admin'] = $this->getAdmin();

        return $this->render('@HgabkaMedia/Media/create.html.twig', $params);
    }

    /**
     * @param int    $folderId The folder id
     * @param string $type     The type
     *
     * @Route("create/modal/{folderId}/{type}", requirements={"folderId" = "\d+", "type" = ".+"}, name="HgabkaMediaBundle_media_modal_create")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @return array|RedirectResponse
     */
    public function createModal(Request $request, $folderId, $type)
    {
        $this->getAdmin()->checkAccess('create');

        $cKEditorFuncNum = $request->get('CKEditorFuncNum');
        $linkChooser = $request->get('linkChooser');

        $extraParams = [];
        if (null !== $cKEditorFuncNum) {
            $extraParams['CKEditorFuncNum'] = $cKEditorFuncNum;
        }
        if (!empty($linkChooser)) {
            $extraParams['linkChooser'] = $linkChooser;
        }

        return $this->createAndRedirect(
            $request,
            $folderId,
            $type,
            'HgabkaMediaBundle_chooser_show_folder',
            $extraParams,
            true
        );
    }

    /**
     * @Route("move/", name="HgabkaMediaBundle_media_move")
     * @Method({"POST"})
     *
     * @return string
     */
    public function moveMedia(Request $request)
    {
        $mediaId = $request->request->get('mediaId');
        $folderId = $request->request->get('folderId');

        if (empty($mediaId) || empty($folderId)) {
            return new JsonResponse(['error' => ['title' => 'Missing media id or folder id']], 400);
        }

        $em = $this->getDoctrine()->getManager();
        $mediaRepo = $em->getRepository(Media::class);

        $media = $mediaRepo->getMedia($mediaId);
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);

        $media->setFolder($folder);
        $mediaRepo->save($media);

        return new JsonResponse();
    }

    /**
     * @Route("/bulk-move", name="HgabkaMediaBundle_media_bulk_move")
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return JsonResponse|Response
     */
    public function bulkMove(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $mediaRepo = $em->getRepository(Media::class);
        $form = $this->createForm(BulkMoveMediaType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Folder $folder */
            $folder = $form->getData()['folder'];
            $mediaIds = explode(',', $form->getData()['media']);

            $mediaRepo->createQueryBuilder('m')
                      ->update()
                      ->set('m.folder', $folder->getId())
                      ->where('m.id in (:mediaIds)')
                      ->setParameter('mediaIds', $mediaIds)
                      ->getQuery()
                      ->execute();

            $this->addFlash('sonata_flash_success', $this->getTranslator()->trans('hg_media.folder.bulk_move.success.text'));

            return new JsonResponse(
                [
                    'Success' => 'The media is moved',
                ]
            );
        }

        return $this->render(
            '@HgabkaMedia/Folder/bulk-move-modal_form.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    private function returnJsonError($code, $message)
    {
        return new JsonResponse([
            'jsonrpc' => '2.0',
            'error ' => [
                'code' => $code,
                'message' => $message,
            ],
            'id' => 'id',
        ]);
    }

    /**
     * @param int    $folderId    The folder Id
     * @param string $type        The type
     * @param string $redirectUrl The url where we want to redirect to on success
     * @param array  $extraParams The extra parameters that will be passed wen redirecting
     * @param mixed  $isInModal
     *
     * @return array
     */
    private function createAndRedirect(Request $request, $folderId, $type, $redirectUrl, $extraParams = [], $isInModal = false)
    {
        $em = $this->getDoctrine()->getManager();

        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);

        // @var MediaManager $mediaManager
        $mediaManager = $this->getManager();
        $handler = $mediaManager->getHandlerForType($type);
        $media = new Media();
        $media->setCurrentLocale($this->getUtils()->getCurrentLocale());
        $helper = $handler->getFormHelper($media);

        $form = $this->createForm($handler->getFormType(), $helper, $handler->getFormTypeOptions());

        if ($request->isMethod('POST')) {
            $params = ['folderId' => $folder->getId()];
            $params = array_merge($params, $extraParams);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $media = $helper->getMedia();
                $media->setFolder($folder);
                $em->getRepository(Media::class)->save($media);

                $this->addFlash(
                    'sonata_flash_success',
                    $this->getTranslator()->trans('hg_media.flash.created', [
                        '%medianame%' => $media->getName(),
                    ])
                );

                return new RedirectResponse($this->generateUrl($redirectUrl, $params));
            }

            if ($isInModal) {
                $this->addFlash(
                    'sonata_flash_error',
                    $this->getTranslator()->trans('hg_media.flash.not_created', [
                        '%mediaerrors%' => $form->getErrors(true, true),
                    ])
                );

                return new RedirectResponse($this->generateUrl($redirectUrl, $params));
            }
        }

        return [
            'type' => $type,
            'form' => $form->createView(),
            'folder' => $folder,
            'admin' => $this->getAdmin(),
        ];
    }
}
