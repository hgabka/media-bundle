<?php

namespace Hgabka\MediaBundle\Command;

use Doctrine\ORM\EntityManager;
use Hgabka\MediaBundle\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateNameCommand extends ContainerAwareCommand
{
    /** @var EntityManager */
    protected $em;

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Migrating media name...');
        /**
         * @var EntityManager
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $medias = $em->getRepository('HgabkaMediaBundle:Media')->findAll();
        $updates = 0;

        try {
            $em->beginTransaction();
            /** @var Media $media */
            foreach ($medias as $media) {
                $filename = $media->getOriginalFilename();
                if (empty($filename)) {
                    $media->setOriginalFilename($media->getName());
                    $em->persist($media);
                    ++$updates;
                }
            }
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $output->writeln('An error occured while migrating media name : <error>'.$e->getMessage().'</error>');
        }
        $output->writeln('<info>'.$updates.' media files have been migrated.</info>');
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('hgabka:media:migrate-name')
            ->setDescription('Migrate media name to new column.')
            ->setHelp(
                'The <info>hgabka:media:migrate-name</info> command can be used to migrate the media name to the newly added column.'
            );
    }
}
