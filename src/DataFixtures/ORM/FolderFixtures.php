<?php

namespace Hgabka\MediaBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Hgabka\MediaBundle\Entity\Folder;

/**
 * Fixtures that make a general media-folder for a project
 * and for every type of media a folder in that media-folder.
 */
class FolderFixtures extends Fixture implements OrderedFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager.
     */
    public function load(ObjectManager $manager): void
    {
        $gal = new Folder();
        $gal->setRel('media');
        $gal->translate('de')->setName('Medien');
        $gal->translate('en')->setName('Media');
        $gal->translate('hu')->setName('Média');
        $gal->setInternal(true);
        $gal->setInternalName('mediaroot');
        $manager->persist($gal);
        $manager->flush();
        $this->addReference('media-folder', $gal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('image');
        $subgal->translate('de')->setName('Bilder');
        $subgal->translate('en')->setName('Images');
        $subgal->translate('hu')->setName('Képek');
        $subgal->setInternal(true);
        $subgal->setInternalName('imageroot');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('images-folder', $subgal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('files');
        $subgal->translate('de')->setName('Dateien');
        $subgal->translate('en')->setName('Files');
        $subgal->translate('hu')->setName('Fájlok');
        $subgal->setInternal(true);
        $subgal->setInternalName('fileroot');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('files-folder', $subgal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('slideshow');
        $subgal->translate('de')->setName('Folien');
        $subgal->translate('en')->setName('Slides');
        $subgal->translate('hu')->setName('Bemutatók');
        $subgal->setInternal(true);
        $subgal->setInternalName('slideroot');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('slides-folder', $subgal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('video');
        $subgal->translate('de')->setName('Videos');
        $subgal->translate('en')->setName('Videos');
        $subgal->translate('hu')->setName('Videók');
        $subgal->setInternal(true);
        $subgal->setInternalName('videoroot');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('videos-folder', $subgal);
    }

    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder(): int
    {
        return 1;
    }
}
