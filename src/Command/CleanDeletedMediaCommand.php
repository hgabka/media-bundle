<?php

namespace Hgabka\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'hgabka:media:clean-deleted-media', description: 'Throws away all files from the file system that have been deleted in the database', hidden: false)]
class CleanDeletedMediaCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly MediaManager $mediaManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                'The <info>hgabka:media:clean-deleted-media</info> command can be used to clean up your file system after having deleted Media items using the backend.'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'If set does not prompt the user if he is certain he wants to remove Media'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (true !== $input->getOption('force')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('<question>Are you sure you want to remove all deleted Media from the file system?</question> ', false);

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $output->writeln('<info>Removing all Media from the file system that have their status set to deleted in the database.</info>');

        $em = $this->entityManager;
        $mediaManager = $this->mediaManager;

        $medias = $em->getRepository(Media::class)->findAllDeleted();

        try {
            $em->beginTransaction();
            foreach ($medias as $media) {
                $mediaManager->removeMedia($media);
            }
            $em->flush();
            $em->commit();
            $output->writeln('<info>All Media flagged as deleted, have now been removed from the file system.<info>');
        } catch (\Exception $e) {
            $em->rollback();
            $output->writeln('An error occured while trying to delete Media from the file system:');
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        return Command::SUCCESS;
    }
}
