<?php

namespace Hgabka\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Folder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildFolderTreeCommand extends Command
{
    protected static $defaultName = 'hgabka:media:rebuild-folder-tree';

    /** @var EntityManagerInterface */
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::$defaultName)
            ->setDescription('Rebuild the media folder tree.')
            ->setHelp('The <info>hgabka:media:rebuild-folder-tree</info> will loop over all media folders and update the media folder tree.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manager->getRepository(Folder::class)->rebuildTree();
        $output->writeln('Updated all folders');

        return Command::SUCCESS;
    }
}
