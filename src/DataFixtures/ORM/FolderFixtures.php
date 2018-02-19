<?php

namespace Hgabka\MediaBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Hgabka\MediaBundle\Entity\Folder;

/**
 * Fixtures that make a general media-folder for a project
 * and for every type of media a folder in that media-folder.
 */
class FolderFixtures extends AbstractFixture implements OrderedFixtureInterface
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
        $gal->setName('Media');
        $gal->setTranslatableLocale('en');
        $manager->persist($gal);
        $manager->flush();
        $this->addReference('media-folder-en', $gal);

        $gal->setTranslatableLocale('hu');
        $manager->refresh($gal);
        $gal->setName('Média');
        $manager->persist($gal);
        $manager->flush();

        $gal->setTranslatableLocale('fr');
        $manager->refresh($gal);
        $gal->setName('Média');
        $manager->persist($gal);
        $manager->flush();

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('image');
        $subgal->setName('Images');
        $subgal->setTranslatableLocale('en');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('images-folder-en', $subgal);

        $subgal->setTranslatableLocale('hu');
        $manager->refresh($subgal);
        $subgal->setName('Képek');
        $manager->persist($subgal);
        $manager->flush();

        $subgal->setTranslatableLocale('fr');
        $manager->refresh($subgal);
        $subgal->setName('Images');
        $manager->persist($subgal);
        $manager->flush();

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('files');
        $subgal->setName('Files');
        $subgal->setTranslatableLocale('en');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('files-folder-en', $subgal);

        $subgal->setTranslatableLocale('hu');
        $manager->refresh($subgal);
        $subgal->setName('Fájlok');
        $manager->persist($subgal);
        $manager->flush();

        $subgal->setTranslatableLocale('fr');
        $manager->refresh($subgal);
        $subgal->setName('Fichiers');
        $manager->persist($subgal);
        $manager->flush();

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('slideshow');
        $subgal->setName('Slides');
        $subgal->setTranslatableLocale('en');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('slides-folder-en', $subgal);

        $subgal->setTranslatableLocale('hu');
        $manager->refresh($subgal);
        $subgal->setName('Bemutatók');
        $manager->persist($subgal);
        $manager->flush();

        $subgal->setTranslatableLocale('fr');
        $manager->refresh($subgal);
        $subgal->setName('Presentations');
        $manager->persist($subgal);
        $manager->flush();

        $subgal = new Folder();
        $subgal->setParent($gal);
        $subgal->setRel('video');
        $subgal->setName('Videos');
        $subgal->setTranslatableLocale('en');
        $manager->persist($subgal);
        $manager->flush();
        $this->addReference('videos-folder-en', $subgal);

        $subgal->setTranslatableLocale('hu');
        $manager->refresh($subgal);
        $subgal->setName('Videók');
        $manager->persist($subgal);
        $manager->flush();

        $subgal->setTranslatableLocale('fr');
        $manager->refresh($subgal);
        $subgal->setName('Vidéos');
        $manager->persist($subgal);
        $manager->flush();
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
