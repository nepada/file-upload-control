<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\FileUploadControlDI;

use Nepada\FileUploadControl\FileUploadControlFactory;
use Nepada\FileUploadControl\Storage\FileSystemStorageManager;
use Nepada\FileUploadControl\Thumbnail\ImageThumbnailProvider;
use Nepada\FileUploadControl\Thumbnail\NullThumbnailProvider;
use NepadaTests\Bridges\FileUploadControlDI\Fixtures\FormFactory;
use NepadaTests\TestCase;
use Nette;
use Nette\MemberAccessException;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class FileUploadControlExtensionTest extends TestCase
{

    public function testServices(): void
    {
        $container = $this->createContainer();
        Assert::type(ImageThumbnailProvider::class, $container->getService('fileUploadControl.thumbnail.thumbnailProvider'));
        Assert::type(FileSystemStorageManager::class, $container->getService('fileUploadControl.storage.storageManager'));
        Assert::type(FileUploadControlFactory::class, $container->getService('fileUploadControl.fileUploadControlFactory'));
    }

    public function testNoThumbnails(): void
    {
        $container = $this->createContainer(__DIR__ . '/Fixtures/config.noImageThumbnails.neon');
        Assert::type(NullThumbnailProvider::class, $container->getService('fileUploadControl.thumbnail.thumbnailProvider'));
    }

    public function testRegisterExtensionMethodOff(): void
    {
        $container = $this->createContainer();
        $form = $container->getByType(FormFactory::class)->create();
        Assert::exception(
            function () use ($form): void {
                $form->addFileUpload('upload');
            },
            MemberAccessException::class,
            'Call to undefined method Nette\Application\UI\Form::addFileUpload(), did you mean addUpload()?',
        );
    }

    public function testRegisterExtensionMethodOn(): void
    {
        $container = $this->createContainer(__DIR__ . '/Fixtures/config.registerExtensionMethod.neon');
        $form = $container->getByType(FormFactory::class)->create();
        Assert::noError(
            function () use ($form): void {
                $form->addFileUpload('upload');
            },
        );
    }

    private function createContainer(string $config = __DIR__ . '/Fixtures/config.default.neon'): Nette\DI\Container
    {
        $configurator = new Nette\Configurator();
        $configurator->setTempDirectory(TEMP_DIR);
        $configurator->setDebugMode(true);
        $configurator->addConfig($config);
        return $configurator->createContainer();
    }

}


(new FileUploadControlExtensionTest())->run();
