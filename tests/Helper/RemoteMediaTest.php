<?php

namespace Hgabka\MediaBundle\Tests\Helper;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Form\RemoteVideo\RemoteVideoType;
use Hgabka\MediaBundle\Helper\RemoteAudio\RemoteAudioHandler;
use Hgabka\MediaBundle\Helper\RemoteAudio\RemoteAudioHelper;
use Hgabka\MediaBundle\Helper\RemoteSlide\RemoteSlideHandler;
use Hgabka\MediaBundle\Helper\RemoteSlide\RemoteSlideHelper;
use Hgabka\MediaBundle\Helper\RemoteVideo\RemoteVideoHandler;
use Hgabka\MediaBundle\Helper\RemoteVideo\RemoteVideoHelper;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use PHPUnit\Framework\TestCase;

class RemoteMediaTest extends TestCase
{
    private function videoHandler(array $configuration = ['youtube' => true]): RemoteVideoHandler
    {
        $utils = $this->createStub(HgabkaUtils::class);
        $utils->method('getCurrentLocale')->willReturn('hu');
        $handler = new RemoteVideoHandler(1, $configuration);
        $handler->setHgabkaUtils($utils);

        return $handler;
    }

    public function testUnknownHostsAndNonStringsAreNotHandled(): void
    {
        $handler = $this->videoHandler();

        $this->assertNull($handler->createNew('https://example.com/video/1'));
        $this->assertNull($handler->createNew(new \stdClass()));
    }

    public function testVideoHandlerInformation(): void
    {
        $handler = $this->videoHandler(['youtube' => true, 'vimeo' => false]);

        $this->assertSame('video', $handler->getType());
        $this->assertSame(RemoteVideoType::class, $handler->getFormType());
        $this->assertSame(['configuration' => ['youtube' => true, 'vimeo' => false]], $handler->getFormTypeOptions());
        $this->assertTrue($handler->canHandle('https://youtu.be/x'));
        $this->assertTrue($handler->canHandle((new Media())->setContentType('remote/video')));
        $this->assertFalse($handler->canHandle((new Media())->setContentType('image/png')));
        $this->assertSame(['video' => ['type' => 'video', 'name' => 'hg_media.video.add']], $handler->getAddFolderActions());
    }

    public function testVideoThumbnailIsTheImageUrl(): void
    {
        $media = (new Media())->setContentType('remote/video');
        (new RemoteVideoHelper($media))->setThumbnailUrl('https://img/thumb.jpg');

        $this->assertSame('https://img/thumb.jpg', $this->videoHandler()->getImageUrl($media, '/base'));
    }

    public function testHelpersWriteThroughToTheMedia(): void
    {
        $media = new Media();
        $media->setCurrentLocale('hu');
        $helper = new RemoteVideoHelper($media);

        $helper->setName('Clip');
        $helper->setDescription('Desc');
        $helper->setCopyright('©');
        $helper->setType('vimeo');
        $helper->setCode('42');
        $helper->setThumbnailUrl('t.jpg');

        $this->assertSame($media, $helper->getMedia());
        $this->assertSame(['Clip', 'Desc', '©', 'vimeo', '42', 't.jpg'], [$media->getName(), $media->getDescription(), $media->getCopyright(), $helper->getType(), $helper->getCode(), $helper->getThumbnailUrl()]);
        $this->assertSame('vimeo', $media->getMetadataValue('type'));
    }

    public function testAudioAndSlideHelpersSetTheirContentTypes(): void
    {
        $audio = new Media();
        $slide = new Media();

        new RemoteAudioHelper($audio);
        new RemoteSlideHelper($slide);

        $this->assertSame(RemoteAudioHandler::CONTENT_TYPE, $audio->getContentType());
        $this->assertSame(RemoteSlideHandler::CONTENT_TYPE, $slide->getContentType());
    }

    public function testAudioAndSlideHandlersMatchOnlyTheirOwnMedia(): void
    {
        $audioHandler = new RemoteAudioHandler(1, 'key');
        $slideHandler = new RemoteSlideHandler(1);
        $audio = (new Media())->setContentType(RemoteAudioHandler::CONTENT_TYPE);
        $slide = (new Media())->setContentType(RemoteSlideHandler::CONTENT_TYPE);

        $this->assertTrue($audioHandler->canHandle($audio));
        $this->assertFalse($audioHandler->canHandle($slide));
        $this->assertTrue($slideHandler->canHandle($slide));
        $this->assertFalse($slideHandler->canHandle($audio));
        $this->assertSame('@HgabkaMedia/Media/RemoteAudio/show.html.twig', $audioHandler->getShowTemplate($audio));
        $this->assertNull($audioHandler->createNew('https://soundcloud.com/x'));
    }

    public function testPrepareMediaAssignsAUuidOnce(): void
    {
        $handler = new RemoteAudioHandler(1, 'key');
        $media = new Media();

        $handler->prepareMedia($media);
        $uuid = $media->getUuid();
        $handler->prepareMedia($media);

        $this->assertNotEmpty($uuid);
        $this->assertSame($uuid, $media->getUuid());
    }
}
