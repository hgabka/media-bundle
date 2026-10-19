<?php

namespace Hgabka\MediaBundle\Tests\Helper;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\File\FileHandler;
use Hgabka\MediaBundle\Helper\FolderManager;
use Hgabka\MediaBundle\Helper\IconFont\IconFontLoaderInterface;
use Hgabka\MediaBundle\Helper\IconFont\IconFontManager;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\MediaBundle\Helper\RemoteVideo\RemoteVideoHandler;
use Hgabka\MediaBundle\Repository\FolderRepository;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mime\MimeTypes;

class ManagersTest extends TestCase
{
    private function mediaManager(): MediaManager
    {
        $utils = $this->createStub(HgabkaUtils::class);
        $utils->method('getCurrentLocale')->willReturn('hu');

        return new MediaManager($utils, '/project', '/uploads/media/', 'data/media/');
    }

    public function testHandlerSelectionPrefersTheMatchingHandlerWithTheHighestPriority(): void
    {
        $file = new FileHandler(0, new MimeTypes());
        $video = new RemoteVideoHandler(1, []);
        $manager = $this->mediaManager()->setDefaultHandler($file);
        $manager->addHandler($file)->addHandler($video);

        $this->assertSame($video, $manager->getHandler('https://youtu.be/abc'));
        $this->assertSame($video, $manager->getHandler((new Media())->setContentType('remote/video')));
        $this->assertSame($file, $manager->getHandler((new Media())->setContentType('application/pdf')));
        $this->assertSame($video, $manager->getHandlerForType('video'));
        $this->assertSame($file, $manager->getHandlerForType('unknown'));
    }

    public function testHandlersAreRegisteredByName(): void
    {
        $manager = $this->mediaManager();
        $video = new RemoteVideoHandler(1, []);

        $manager->addHandler($video);

        $this->assertSame(['Remote Video Handler' => $video], $manager->getHandlers());
    }

    public function testCreateNewAsksEveryHandler(): void
    {
        $manager = $this->mediaManager()->setDefaultHandler(new FileHandler(0, new MimeTypes()));
        $manager->addHandler(new FileHandler(0, new MimeTypes()))->addHandler(new RemoteVideoHandler(1, []));

        $media = $manager->createNew('https://youtu.be/abc');

        $this->assertSame('Youtube abc', $media->getName());
    }

    public function testCreateNewReturnsNullWhenNoHandlerKnowsTheData(): void
    {
        $manager = $this->mediaManager()->setDefaultHandler(new FileHandler(0, new MimeTypes()));
        $manager->addHandler(new FileHandler(0, new MimeTypes()))->addHandler(new RemoteVideoHandler(1, []));

        $this->assertNull($manager->createNew('nothing handles this'));
    }

    public function testFolderManagerDelegatesToTheRepository(): void
    {
        $folder = (new Folder())->setId(5);
        $root = (new Folder())->setId(1);
        $repository = $this->createStub(FolderRepository::class);
        $repository->method('childrenHierarchy')->willReturn(['tree']);
        $repository->method('getParentIds')->willReturn([1, 3]);
        $repository->method('getFolder')->willReturn($root);
        $repository->method('getPath')->willReturn([$root, $folder]);

        $manager = new FolderManager($repository, $this->createStub(Security::class));

        $this->assertSame(['tree'], $manager->getFolderHierarchy($root));
        $this->assertSame([1, 3], $manager->getParentIds($folder));
        $this->assertSame($root, $manager->getRootFolderFor($folder));
        $this->assertSame([$root, $folder], $manager->getParents($folder));
    }

    public function testFoldersAreTraversableAccordingToTheRole(): void
    {
        $normal = (new Folder())->setLevel(2)->setInternal(false);
        $internal = (new Folder())->setLevel(2)->setInternal(true);
        $rootLevel = (new Folder())->setLevel(1)->setInternal(true);
        $repository = $this->createStub(FolderRepository::class);
        $repository->method('find')->willReturnCallback(static fn ($id) => ['n' => $normal, 'i' => $internal][$id] ?? null);

        $user = $this->createStub(Security::class);
        $user->method('isGranted')->willReturn(false);
        $admin = $this->createStub(Security::class);
        $admin->method('isGranted')->willReturnCallback(static fn (string $role): bool => 'ROLE_SUPER_ADMIN' === $role);

        $this->assertTrue((new FolderManager($repository, $user))->isFolderTraversable($normal));
        $this->assertFalse((new FolderManager($repository, $user))->isFolderTraversable($internal));
        $this->assertTrue((new FolderManager($repository, $admin))->isFolderTraversable($internal));
        $this->assertFalse((new FolderManager($repository, $admin))->isFolderTraversable($rootLevel));
        $this->assertTrue((new FolderManager($repository, $user))->isFolderTraversable('n'));
        $this->assertFalse((new FolderManager($repository, $user))->isFolderTraversable('missing'));
    }

    public function testIconFontManager(): void
    {
        $default = $this->createStub(IconFontLoaderInterface::class);
        $other = $this->createStub(IconFontLoaderInterface::class);
        $manager = new IconFontManager();

        $manager->addLoader($other, 'other');
        $manager->setDefaultLoader($default);

        $this->assertSame($other, $manager->getLoader('other'));
        $this->assertSame(['other' => $other], $manager->getLoaders());
        $this->assertSame($default, $manager->getDefaultLoader());
    }
}
