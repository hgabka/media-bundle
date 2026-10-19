<?php

namespace Hgabka\MediaBundle\Tests\Entity;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\FolderTranslation;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Entity\MediaTranslation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MediaFolderTest extends TestCase
{
    #[DataProvider('fileSizes')]
    public function testHumanReadableFileSize(int $bytes, string $expected): void
    {
        $this->assertSame($expected, (new Media())->setFileSize($bytes)->getFileSize());
    }

    public static function fileSizes(): array
    {
        return [
            'bytes' => [512, '512 B'],
            'kilobytes' => [1536, '1.5 kB'],
            'megabytes' => [5 * 1024 * 1024, '5 MB'],
        ];
    }

    public function testFileSizeInBytes(): void
    {
        $this->assertSame(2048, (new Media())->setFileSize(2048)->getFileSizeBytes());
    }

    public function testContentTypeShort(): void
    {
        $this->assertSame('png', (new Media())->setContentType('image/png')->getContentTypeShort());
        $this->assertSame('video', (new Media())->setContentType('remote/video')->getContentTypeShort());
    }

    public function testMetadataValues(): void
    {
        $media = new Media();

        $media->setMetadataValue('code', 'abc')->setMetadataValue('type', 'youtube');

        $this->assertSame('abc', $media->getMetadataValue('code'));
        $this->assertSame(['code' => 'abc', 'type' => 'youtube'], $media->getMetadata());
        $this->assertNull($media->getMetadataValue('missing'));
    }

    public function testSettingContentTouchesTheUpdateTimestamp(): void
    {
        $media = new Media();
        $media->setUpdatedAt(new \DateTime('2000-01-01'));

        $media->setContent('x');

        $this->assertSame('x', $media->getContent());
        $this->assertGreaterThan(new \DateTime('2001-01-01'), $media->getUpdatedAt());
    }

    public function testFlagsAndSimpleAccessors(): void
    {
        $media = (new Media())->setUuid('u')->setLocation('local')->setUrl('/uploads/a.png')->setOriginalFilename('a.png')->setProtected(true)->setDeleted(true)->setRemovedFromFileSystem(true);

        $this->assertSame(['u', 'local', '/uploads/a.png', 'a.png', true, true, true], [
            $media->getUuid(), $media->getLocation(), $media->getUrl(), $media->getOriginalFilename(), $media->isProtected(), $media->isDeleted(), $media->isRemovedFromFileSystem(),
        ]);
    }

    public function testTranslatableFields(): void
    {
        $media = new Media();
        $media->setName('Név', 'hu')->setDescription('Leírás', 'hu')->setCopyright('© hu', 'hu');
        $media->setName('Name', 'en');

        $this->assertSame(['Név', 'Leírás', '© hu', 'Name'], [$media->getName('hu'), $media->getDescription('hu'), $media->getCopyright('hu'), $media->getName('en')]);
        $this->assertSame(MediaTranslation::class, Media::getTranslationEntityClass());
    }

    public function testFolderTreeNavigation(): void
    {
        $root = (new Folder())->setId(1);
        $middle = (new Folder())->setId(2);
        $leaf = (new Folder())->setId(3);
        $root->addChild($middle);
        $middle->addChild($leaf);

        $this->assertSame($middle, $leaf->getParent());
        $this->assertSame([$root, $middle], $leaf->getParents());
        $this->assertTrue($root->hasActive(3));
        $this->assertTrue($root->hasActive(2));
        $this->assertFalse($leaf->hasActive(1));
        $this->assertFalse($root->hasActive(99));
    }

    public function testDeletedChildrenAndMediaAreHiddenUnlessRequested(): void
    {
        $folder = new Folder();
        $folder->addChild(new Folder());
        $folder->addChild((new Folder())->setDeleted(true));
        $folder->addMedia(new Media());
        $folder->addMedia((new Media())->setDeleted(true));

        $this->assertCount(1, $folder->getChildren());
        $this->assertCount(2, $folder->getChildren(true));
        $this->assertCount(1, $folder->getMedia());
        $this->assertCount(2, $folder->getMedia(true));
    }

    public function testFolderAccessors(): void
    {
        $folder = (new Folder())->setRel('media')->setInternalName('imageroot')->setInternal(true)->setLeft(1)->setRight(4)->setLevel(2);
        $folder->setName('Képek', 'hu');
        $folder->setCurrentLocale('hu');

        $this->assertSame(['media', 'imageroot', true, 1, 4, 2], [$folder->getRel(), $folder->getInternalName(), $folder->isInternal(), $folder->getLeft(), $folder->getRight(), $folder->getLevel()]);
        $this->assertSame('-- Képek', $folder->getOptionLabel());
        $this->assertSame(FolderTranslation::class, Folder::getTranslationEntityClass());
    }
}
