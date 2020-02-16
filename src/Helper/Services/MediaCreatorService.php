<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Helper\Services\MediaCreatorService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class CopyProductImagesCommand extends Command
{
    protected static $defaultName = 'haldepo:products:images';

    /** @var EntityManagerInterface */
    protected $doctrine;

    /** @var MediaCreatorService */
    protected $creator;

    /** @var string */
    protected $projectDir;

    /**
     * CopyProductImagesCommand constructor.
     *
     * @param EntityManagerInterface $doctrine
     * @param                        $projectDir
     */
    public function __construct(EntityManagerInterface $doctrine, MediaCreatorService $creator, $projectDir)
    {
        parent::__construct();

        $this->doctrine = $doctrine;
        $this->creator = $creator;
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('Copy product images')
            ->setHelp(
                <<<'EOT'
The <info>haldepo:products:images</info> command copies product images from old system:
EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $folder = $this->doctrine->getRepository(Folder::class)->findOneByInternalName('product');

        $finder = new Finder();
        $finder->files()->in($this->projectDir.'/var/product');
        $batchSize = 100;

        foreach ($finder as $result) {
            $filePath = $result->getRealPath();
            $productId = $result->getBasename('.'.$result->getExtension());

            /** @var Product $product */
            $product = $this->doctrine->getRepository(Product::class)->findOneByProductId($productId);
            if (!$product || !empty($product->getPicture())) {
                continue;
            }

            $media = $this->creator->createFile($filePath, $folder->getId(), false);
            $product->setPicture($media);

            if (--$batchSize <= 0) {
                $this->doctrine->flush();
                $this->doctrine->clear();
                $batchSize = 100;
            }
        }
    }
}
