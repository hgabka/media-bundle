<?php

namespace Hgabka\MediaBundle\Tests\Helper;

use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\File\FileHandler;
use Hgabka\MediaBundle\Helper\Image\ImageHandler;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileHandlerTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAYAAAC56t6BAAAAEElEQVR4nGNgYGD4z4AGAAARAgEAf2cmSgAAAABJRU5ErkJggg==';

    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/hg_media_' . bin2hex(random_bytes(4));
        foreach (['public', 'private', 'tmp'] as $sub) {
            mkdir($this->dir . '/' . $sub, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->dir);
    }

    private function utils(): HgabkaUtils
    {
        $utils = $this->createStub(HgabkaUtils::class);
        $utils->method('getCurrentLocale')->willReturn('hu');
        $utils->method('slugify')->willReturnCallback(static fn (string $text): string => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-'));

        return $utils;
    }

    private function configure(FileHandler $handler): FileHandler
    {
        $handler->setHgabkaUtils($this->utils());
        $handler->setFileSystem(new Filesystem(new Local($this->dir . '/public', true)));
        $handler->setProtectedFileSystem(new Filesystem(new Local($this->dir . '/private', true)));
        $handler->setMediaPath('/uploads/media/');
        $handler->setProtectedMediaPath('data/media/');
        $handler->setBlacklistedExtensions(['php', 'htaccess']);
        $handler->setFolderDepth(2);

        return $handler;
    }

    private function handler(): FileHandler
    {
        return $this->configure(new FileHandler(0, new MimeTypes()));
    }

    private function imageHandler(): ImageHandler
    {
        $handler = new ImageHandler(1, new MimeTypes(), null);
        $this->configure($handler);
        $handler->setUrlGenerator($this->createStub(UrlGeneratorInterface::class));

        return $handler;
    }

    private function upload(string $name, string $content): UploadedFile
    {
        $path = $this->dir . '/tmp/' . uniqid() . '.bin';
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function testBasicInformation(): void
    {
        $handler = $this->handler();

        $this->assertSame('File Handler', $handler->getName());
        $this->assertSame('file', $handler->getType());
        $this->assertSame('@HgabkaMedia/Media/File/show.html.twig', $handler->getShowTemplate(new Media()));
        $this->assertArrayHasKey('file', $handler->getAddFolderActions());
    }

    public function testCanHandleFilesAndLocalMedia(): void
    {
        $handler = $this->handler();
        $file = new File(__FILE__);

        $this->assertTrue($handler->canHandle($file));
        $this->assertTrue($handler->canHandle((new Media())->setLocation('local')));
        $this->assertTrue($handler->canHandle((new Media())->setContent(__FILE__)));
        $this->assertFalse($handler->canHandle((new Media())->setLocation('remote')));
        $this->assertFalse($handler->canHandle('a string'));
    }

    public function testCreateNewFromAnUploadedFile(): void
    {
        $upload = $this->upload('Report.txt', 'hello');

        $media = $this->handler()->createNew($upload);

        $this->assertInstanceOf(Media::class, $media);
        $this->assertSame('Report.txt', $media->getOriginalFilename());
        $this->assertSame($upload, $media->getContent());
        $this->assertSame('text/plain', $media->getContentType());
        $this->assertSame(5, $media->getFileSizeBytes());
    }

    public function testCreateNewIgnoresOtherData(): void
    {
        $this->assertNull($this->handler()->createNew('http://example.com'));
    }

    public function testPrepareMediaFillsInTheDerivedFields(): void
    {
        $handler = $this->handler();
        $media = $handler->createNew($this->upload('My Report.TXT', 'hello'));

        $handler->prepareMedia($media);

        $this->assertNotEmpty($media->getUuid());
        $this->assertSame('my-report.TXT', $media->getOriginalFilename());
        $this->assertSame('my-report.TXT', $media->getName());
        $this->assertSame('local', $media->getLocation());
        $this->assertSame(5, $media->getFileSizeBytes());
        $this->assertMatchesRegularExpression('#^/uploads/media/\w/\w/my-report\.txt$#', $media->getUrl());
    }

    public function testPrepareMediaWithoutContentOnlyAssignsAUuid(): void
    {
        $handler = $this->handler();
        $media = new Media();

        $handler->prepareMedia($media);

        $this->assertNotEmpty($media->getUuid());
        $this->assertNull($media->getLocation());
    }

    public function testPrepareMediaRejectsMissingFiles(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->handler()->prepareMedia((new Media())->setContent($this->dir . '/missing.txt'));
    }

    public function testSaveMediaStoresTheFileInThePublicFilesystem(): void
    {
        $handler = $this->handler();
        $media = $handler->createNew($this->upload('note.txt', 'stored content'));
        $handler->prepareMedia($media);

        $handler->saveMedia($media);

        $stored = $this->dir . '/public/' . substr($media->getUrl(), strlen('/uploads/media/'));
        $this->assertFileExists($stored);
        $this->assertSame('stored content', file_get_contents($stored));
    }

    public function testBlacklistedExtensionsAreStoredAsText(): void
    {
        $handler = $this->handler();
        $media = $handler->createNew($this->upload('shell.php', '<?php echo 1;'));
        $handler->prepareMedia($media);
        $handler->saveMedia($media);

        $this->assertStringEndsWith('/shell.txt', $media->getUrl());
        $this->assertFileExists($this->dir . '/public/' . substr($media->getUrl(), strlen('/uploads/media/')));
    }

    public function testProtectedMediaGoesToThePrivateFilesystem(): void
    {
        $handler = $this->handler();
        $media = $handler->createNew($this->upload('secret.txt', 'secret'));
        $media->setProtected(true);
        $handler->prepareMedia($media);

        $handler->saveMedia($media);

        $this->assertStringStartsWith('data/media/', $media->getUrl());
        $this->assertFileExists($this->dir . '/private/' . substr($media->getUrl(), strlen('data/media/')));
        $this->assertSame([], glob($this->dir . '/public/*'));
    }

    public function testRemoveMediaDeletesTheFileAndFlagsTheMedia(): void
    {
        $handler = $this->handler();
        $media = $handler->createNew($this->upload('gone.txt', 'bye'));
        $handler->prepareMedia($media);
        $handler->saveMedia($media);
        $stored = $this->dir . '/public/' . substr($media->getUrl(), strlen('/uploads/media/'));
        $this->assertFileExists($stored);

        $handler->removeMedia($media);

        $this->assertFileDoesNotExist($stored);
        $this->assertTrue($media->isRemovedFromFileSystem());
    }

    public function testConvertToProtectedAndBack(): void
    {
        $handler = $this->handler();
        $media = $handler->createNew($this->upload('move.txt', 'content'));
        $handler->prepareMedia($media);
        $handler->saveMedia($media);
        $relative = substr($media->getUrl(), strlen('/uploads/media/'));

        $this->assertTrue($handler->convertToProtected($media));
        $this->assertFileDoesNotExist($this->dir . '/public/' . $relative);
        $this->assertFileExists($this->dir . '/private/' . $relative);
        $this->assertSame('data/media/' . $relative, $media->getUrl());

        $this->assertTrue($handler->convertToNonProtected($media));
        $this->assertFileExists($this->dir . '/public/' . $relative);
        $this->assertFileDoesNotExist($this->dir . '/private/' . $relative);
        $this->assertSame('/uploads/media/' . $relative, $media->getUrl());
    }

    public function testConvertingAMissingFileFails(): void
    {
        $media = (new Media())->setUuid('abc')->setOriginalFilename('nothing.txt');

        $this->assertFalse($this->handler()->convertToProtected($media));
        $this->assertFalse($this->handler()->convertToNonProtected($media));
    }

    public function testImageHandlerOnlyHandlesImages(): void
    {
        $handler = $this->imageHandler();
        $png = $this->dir . '/tmp/a.png';
        file_put_contents($png, base64_decode(self::PNG));

        $this->assertSame('image', $handler->getType());
        $this->assertTrue($handler->canHandle(new File($png)));
        $this->assertTrue($handler->canHandle((new Media())->setLocation('local')->setContentType('image/jpeg')));
        $this->assertFalse($handler->canHandle((new Media())->setLocation('local')->setContentType('application/pdf')));
    }

    public function testImageHandlerRecordsTheOriginalDimensions(): void
    {
        $handler = $this->imageHandler();
        $media = $handler->createNew($this->upload('pixel.png', base64_decode(self::PNG)));

        $handler->prepareMedia($media);

        $this->assertSame('image/png', $media->getContentType());
        $this->assertSame(2, $media->getMetadataValue('original_width'));
        $this->assertSame(3, $media->getMetadataValue('original_height'));
    }

    public function testImageUrlForLocalAndRemoteMedia(): void
    {
        $handler = $this->imageHandler();

        $this->assertSame('/base/uploads/a.png', $handler->getImageUrl((new Media())->setLocation('local')->setUrl('/uploads/a.png'), '/base'));
        $this->assertSame('http://cdn/a.png', $handler->getImageUrl((new Media())->setLocation('remote')->setUrl('http://cdn/a.png'), '/base'));
    }

    public function testProtectedImagesAreServedThroughTheDownloadRoute(): void
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->expects($this->once())->method('generate')->with('HgabkaMediaBundle_admin_download_inline', ['media' => 7])->willReturn('/download/7');
        $handler = $this->imageHandler();
        $handler->setUrlGenerator($urls);

        $this->assertSame('/download/7', $handler->getImageUrl((new Media())->setId(7)->setProtected(true), ''));
    }
}
