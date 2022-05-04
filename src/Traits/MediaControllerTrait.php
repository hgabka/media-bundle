<?php

namespace Hgabka\MediaBundle\Traits;

use Hgabka\MediaBundle\Admin\MediaAdmin;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\FolderManager;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\MediaBundle\Helper\RemoteAudio\RemoteAudioHandler;
use Hgabka\MediaBundle\Helper\RemoteSlide\RemoteSlideHandler;
use Hgabka\MediaBundle\Helper\RemoteVideo\RemoteVideoHandler;
use Hgabka\UtilsBundle\AdminList\FilterBuilder;
use Hgabka\UtilsBundle\AdminList\FilterType\FilterTypeInterface;
use Hgabka\UtilsBundle\AdminList\FilterType\ORM;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sonata\AdminBundle\Templating\TemplateRegistryInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

trait MediaControllerTrait
{
    /** @var FilterBuilder */
    protected $filterBuilder;

    /** @var MediaAdmin */
    protected $admin;

    /** @var MediaManager */
    protected $manager;

    /** @var HgabkaUtils */
    protected $utils;

    /** @var FolderManager */
    protected $folderManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TemplateRegistryInterface */
    private $globalTemplateRegistry;

    /**
     * MediaControllerTrait constructor.
     */
    public function __construct(MediaAdmin $admin, MediaManager $manager, HgabkaUtils $utils, FolderManager $folderManager, TranslatorInterface $translator, RequestStack $requestStack, ManagerRegistry $doctrine)
    {
        $this->admin = $admin;
        $this->manager = $manager;
        $this->utils = $utils;
        $this->folderManager = $folderManager;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
    }

    /**
     * @param string              $columnName The column name
     * @param FilterTypeInterface $type       The filter type
     * @param string              $filterName The name of the filter
     * @param array               $options    Options
     *
     * @return AbstractAdminListConfigurator
     */
    public function addFilter(
        $columnName,
        FilterTypeInterface $type = null,
        $filterName = null,
        array $options = []
    ) {
        $this->getFilterBuilder()->add($columnName, $type, $filterName, $options);

        return $this;
    }

    /**
     * @return FilterBuilder
     */
    public function getFilterBuilder()
    {
        if (null === $this->filterBuilder) {
            $this->filterBuilder = new FilterBuilder();
        }

        return $this->filterBuilder;
    }

    public function getAdmin()
    {
        return $this->admin;
    }

    public function getManager(): MediaManager
    {
        return $this->manager;
    }

    public function getUtils(): HgabkaUtils
    {
        return $this->utils;
    }

    public function getFolderManager(): FolderManager
    {
        return $this->folderManager;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function setTemplateRegistry(TemplateRegistryInterface $globalTemplateRegistry)
    {
        $this->globalTemplateRegistry = $globalTemplateRegistry;
    }

    protected function getPager(Request $request, Folder $folder)
    {
        $queryBuilder = $this
            ->getDoctrine()
            ->getRepository(Media::class)
            ->createQueryBuilder('b')
            ->leftJoin('b.translations', 'bt', 'WITH', 'bt.locale = :locale')
            ->andWhere('b.folder = :folder')
            ->setParameter('folder', $folder->getId())
            ->setParameter('locale', $this->getUtils()->getCurrentLocale())
            ->andWhere('b.deleted = 0')
        ;
        $orderBy = $request->query->get('orderBy', 'updatedAt');
        $orderDirection = $request->query->get('orderDirection', 'DESC');
        if ('name' === $orderBy) {
            $orderBy = 'bt.name';
        } else {
            $orderBy = 'b.' . $orderBy;
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
        // Apply filters
        $filters = $this->getFilterBuilder()->getCurrentFilters();
        // @var Filter $filter
        foreach ($filters as $filter) {
            // @var AbstractORMFilterType $type
            $type = $filter->getType();
            $type->setQueryBuilder($queryBuilder);
            $filter->apply();
        }

        $adapter = new QueryAdapter($queryBuilder->getQuery());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setMaxPerPage(250);
        $pagerfanta->setCurrentPage($request->query->get('page', 1));

        return $pagerfanta;
    }

    protected function getBaseTemplate(): string
    {
        return $this->globalTemplateRegistry->getTemplate('layout');
    }

    protected function buildFilters()
    {
        $this->addFilter('name', new ORM\StringFilterType('bt.name'), 'hg_media.adminlist.configurator.filter.name');
        $this->addFilter('contentType', new ORM\StringFilterType('contentType'), 'hg_media.adminlist.configurator.filter.type');
        $this->addFilter('updatedAt', new ORM\DateFilterType('updatedAt'), 'hg_media.adminlist.configurator.filter.updated_at');
        $this->addFilter('filesize', new ORM\NumberFilterType('filesize'), 'hg_media.adminlist.configurator.filter.filesize');
    }
}
