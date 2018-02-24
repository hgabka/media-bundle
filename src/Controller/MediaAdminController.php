<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\FolderType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Hgabka\MediaBundle\Helper\RemoteSlide\RemoteSlideHandler;
use Hgabka\MediaBundle\Helper\RemoteAudio\RemoteAudioHandler;
use Hgabka\MediaBundle\Helper\RemoteVideo\RemoteVideoHandler;
use Symfony\Component\HttpFoundation\Request;

class MediaAdminController extends CRUDController
{
    public function listAction()
    {
        $request = $this->getRequest();

        $this->admin->checkAccess('list');

        $preResponse = $this->preList($request);
        if (null !== $preResponse) {
            return $preResponse;
        }

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

        $em = $this->getDoctrine();
        $repo = $em->getRepository(Folder::class);

        $folderId = $request->query->get('folderId');
        // @var Folder $folder
        $folder = empty($folderId) ? $repo->getFirstTopFolder() : $repo->getFolder($folderId);

        $sub = new Folder();
        $sub->setParent($folder);
        $subForm = $this->createForm(FolderType::class, $sub, ['folder' => $sub]);

        $emptyForm = $this->createEmptyForm();

        $editForm = $this->createForm(FolderType::class, $folder, ['folder' => $folder]);

        if ($request->isMethod('POST')) {
            $editForm->handleRequest($request);
            if ($editForm->isValid()) {
                $repo->save($folder);

                $this->addFlash(
                    FlashTypes::SUCCESS,
                    $this->get('translator')->trans('hg_media.folder.show.success.text', [
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

        $params = [
            'foldermanager' => $this->get('hgabka_media.folder_manager'),
            'mediamanager' => $mediaManager,
            'subform' => $subForm->createView(),
            'emptyform' => $emptyForm->createView(),
            'editform' => $editForm->createView(),
            'folder' => $folder,
            'type' => null,
            'adminlist' => $this->getAdminList($request),
            'orderByFields' => ['name']
            'base_template' => $this->getParameter('sonata.admin.configuration.templates')['layout'],
        ];

        return $this->render('@HgabkaMedia/Folder/show.html.twig', $params);
    }

    private function createEmptyForm()
    {
        $defaultData = ['checked' => false];
        $form = $this->createFormBuilder($defaultData)
                     ->add('checked', CheckboxType::class, ['required' => false, 'label' => 'media.folder.empty.modal.checkbox'])
                     ->getForm();

        return $form;
    }

    private function getAdminList(Request $request)
    {
        $queryBuilder = $this
            ->getDoctrine()
            ->getRepository(Media::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.translations bt WITH bt.lang = :lang')
            ->andWhere('b.folder = :folder')
            ->setParameter('folder', $this->folder->getId())
            ->setParameter('lang', $this->get(HgabkaUtils::class)->getCurrentLocale())
            ->andWhere('b.deleted = 0')
        ;
        $orderBy = $request->query->get('orderBy', 'updatedAt');
        $orderDirection = $request->query->get('orderDirection','DESC');
        $queryBuilder->orderBy($orderBy, $orderDirection);
        $type = $this->request->query->get('type');
        if ($type) {
            switch ($type) {
                case 'file':
                    $queryBuilder->andWhere('b.location = :location')
                                 ->setParameter('location', 'local');

                    break;
                case 'image':
                    $queryBuilder->andWhere('b.contentType LIKE :ctype')
                                 ->setParameter('ctype', '%image%');

                    break;
                case RemoteAudioHandler::TYPE:
                    $queryBuilder->andWhere('b.contentType = :ctype')
                                 ->setParameter('ctype', RemoteAudioHandler::CONTENT_TYPE);

                    break;
                case RemoteSlideHandler::TYPE:
                    $queryBuilder->andWhere('b.contentType = :ctype')
                                 ->setParameter('ctype', RemoteSlideHandler::CONTENT_TYPE);

                    break;
                case RemoteVideoHandler::TYPE:
                    $queryBuilder->andWhere('b.contentType = :ctype')
                                 ->setParameter('ctype', RemoteVideoHandler::CONTENT_TYPE);

                    break;
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }
}