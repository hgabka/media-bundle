<?php

namespace Hgabka\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Folder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'hgabka:media:rebuild-folder-tree', description: 'Rebuilds the media folder tree', hidden: false)]
class RebuildFolderTreeCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
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
