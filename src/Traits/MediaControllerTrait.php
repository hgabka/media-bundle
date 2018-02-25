<?php

namespace Hgabka\MediaBundle\Traits;

use Symfony\Component\HttpFoundation\Request;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Hgabka\MediaBundle\Helper\RemoteVideo\RemoteVideoHandler;
use Hgabka\MediaBundle\Helper\RemoteAudio\RemoteAudioHandler;
use Hgabka\MediaBundle\Helper\RemoteSlide\RemoteSlideHandler;
use Hgabka\MediaBundle\Entity\Media;
use Pagerfanta\Pagerfanta;

trait MediaControllerTrait
{
    protected function getPager(Request $request, Folder $folder)
    {
        $queryBuilder = $this
            ->getDoctrine()
            ->getRepository(Media::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.translations','bt', 'WITH', 'bt.locale = :locale')
            ->andWhere('b.folder = :folder')
            ->setParameter('folder', $folder->getId())
            ->setParameter('locale', $this->get(HgabkaUtils::class)->getCurrentLocale())
            ->andWhere('b.deleted = 0')
        ;
        $orderBy = $request->query->get('orderBy', 'updatedAt');
        $orderDirection = $request->query->get('orderDirection','DESC');
        if ($orderBy === 'name') {
            $orderBy = 'bt.name';
        } else {
            $orderBy = 'b.'.$orderBy;
        }
        $queryBuilder->orderBy($orderBy, $orderDirection);
        $type = $request->query->get('type');
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
        $adapter = new DoctrineORMAdapter($queryBuilder->getQuery());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setMaxPerPage(250);
        $pagerfanta->setCurrentPage($request->query->get('page', 1));


        return $pagerfanta;
    }

    protected function getBaseTemplate()
    {
        return $this->getParameter('sonata.admin.configuration.templates')['layout'];
    }

}