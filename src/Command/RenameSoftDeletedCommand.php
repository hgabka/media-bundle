<?php

namespace Hgabka\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\File\FileHandler;
use Hgabka\MediaBundle\Helper\MediaManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'hgabka:media:rename-soft-deleted', description: 'Renames physical files for soft-deleted media', hidden: false)]
class RenameSoftDeletedCommand extends ContainerAwareCommand
{
    public function __construct(protected readonly EntityManagerInterface $manager, protected readonly MediaManager $mediaManager)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Renaming soft-deleted media...');
        /**
         * @var EntityManager
         */
        $em = $this->entityManager;

        $original = $input->getOption('original');
        $medias = $em->getRepository(Media::class)->findAll();
        $manager = $this->mediaManager;
        $updates = 0;
        $fileRenameQueue = [];

        try {
            $em->beginTransaction();
            /** @var Media $media */
            foreach ($medias as $media) {
                $handler = $manager->getHandler($media);
                if ($media->isDeleted() && 'local' === $media->getLocation() && $handler instanceof FileHandler) {
                    $oldFileUrl = $media->getUrl();
                    $newFileName = ($original ? $media->getOriginalFilename() : uniqid() . '.' . pathinfo($oldFileUrl, \PATHINFO_EXTENSION));
                    $newFileUrl = \dirname($oldFileUrl) . '/' . $newFileName;
                    $fileRenameQueue[] = [$oldFileUrl, $newFileUrl, $handler];
                    $media->setUrl($newFileUrl);
                    $em->persist($media);
                    ++$updates;
                }
            }
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $output->writeln('An error occured while updating soft-deleted media : <error>' . $e->getMessage() . '</error>');
            $updates = 0;
            $fileRenameQueue = [];
        }

        foreach ($fileRenameQueue as $row) {
            [$oldFileUrl, $newFileUrl, $handler] = $row;
            $handler->fileSystem->rename(
                preg_replace('~^' . preg_quote($handler->mediaPath, '~') . '~', '/', $oldFileUrl),
                preg_replace('~^' . preg_quote($handler->mediaPath, '~') . '~', '/', $newFileUrl)
            );
            $output->writeln('Renamed <info>' . $oldFileUrl . '</info> to <info>' . basename($newFileUrl) . '</info>');
        }

        $output->writeln('<info>' . $updates . ' soft-deleted media files have been renamed.</info>');

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setHelp(
                'The <info>hgabka:media:rename-soft-deleted</info> command can be used to rename soft-deleted media which is still publically available under the original filename.'
            )
            ->addOption(
                'original',
                'o',
                InputOption::VALUE_NONE,
                'If set renames soft-deleted media to its original filename'
            );
    }
}
