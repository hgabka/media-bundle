<?php

namespace Hgabka\MediaBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Hgabka\MediaBundle\Entity\Folder;

/**
 * Fixtures that make a general media-folder for a project
 * and for every type of media a folder in that media-folder.
 */
class FolderFixtures extends Fixture implements OrderedFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager.
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $gal = new Folder();
        $gal->setRel('media');
        $gal->translate('en')->setName('Media');
        $gal->translate('hu')->setName('Média');
        $manager->persist($gal);
        $manager->flush();
        $this->addReference('media-folder', $gal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('image');
        $subgal->translate('en')->setName('Images');
        $subgal->translate('hu')->setName('Képek');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('images-folder', $subgal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('files');
        $subgal->translate('en')->setName('Files');
        $subgal->translate('hu')->setName('Fájlok');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('files-folder', $subgal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('slideshow');
        $subgal->translate('en')->setName('Slides');
        $subgal->translate('hu')->setName('Bemutatók');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('slides-folder', $subgal);

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('video');
        $subgal->translate('en')->setName('Videos');
        $subgal->translate('hu')->setName('Videók');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('videos-folder', $subgal);
    }

    /**
     * Get the order of this fixture.
     *
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}
