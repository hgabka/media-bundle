<?php

namespace Hgabka\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildFolderTreeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('hgabka:media:rebuild-folder-tree')
            ->setDescription('Rebuild the media folder tree.')
            ->setHelp('The <info>hgabka:media:rebuild-folder-tree</info> will loop over all media folders and update the media folder tree.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $em->getRepository('HgabkaMediaBundle:Folder')->rebuildTree();
        $output->writeln('Updated all folders');
    }
}
