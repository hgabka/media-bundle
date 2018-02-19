<?php

namespace Hgabka\MediaBundle\Command;

use ImagickException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePdfPreviewCommand extends ContainerAwareCommand
{
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Creating PDF preview images...');

        $pdfTransformer = $this->getContainer()->get('hgabka_media.pdf_transformer');
        $webPath = realpath($this->getContainer()->get('kernel')->getRootDir().'/../web').DIRECTORY_SEPARATOR;

        /**
         * @var EntityManager
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $medias = $em->getRepository('HgabkaMediaBundle:Media')->findBy(
            ['contentType' => 'application/pdf', 'deleted' => false]
        );
        /** @var Media $media */
        foreach ($medias as $media) {
            try {
                $pdfTransformer->apply($webPath.$media->getUrl());
            } catch (ImagickException $e) {
                $output->writeln('<comment>'.$e->getMessage().'</comment>');
            }
        }

        $output->writeln('<info>PDF preview images have been created.</info>');
    }

    /**
     * Checks whether the command is enabled or not in the current environment.
     *
     * Override this to check for x or y and return false if the command can not
     * run properly under the current conditions.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getContainer()->getParameter('hgabka_media.enable_pdf_preview');
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('hgabka:media:create-pdf-previews')
            ->setDescription('Create preview images for PDFs that have already been uploaded')
            ->setHelp(
                'The <info>hgabka:media:create-pdf-previews</info> command can be used to create preview images for PDFs that have already been uploaded.'
            );
    }
}
