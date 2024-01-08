<?php
declare(strict_types = 1);

namespace Nepada\Bridges\FileUploadControlDI;

use Nepada\Bridges\FileUploadControlForms\ExtensionMethodRegistrator;
use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use Nepada\FileUploadControl\Storage\FileSystemStorageManager;
use Nepada\FileUploadControl\Storage\Metadata\FileSystemMetadataJournalProvider;
use Nepada\FileUploadControl\Storage\Metadata\MetadataJournalProvider;
use Nepada\FileUploadControl\Storage\StorageManager;
use Nepada\FileUploadControl\Thumbnail\ImageLoader;
use Nepada\FileUploadControl\Thumbnail\ImageThumbnailProvider;
use Nepada\FileUploadControl\Thumbnail\NullThumbnailProvider;
use Nepada\FileUploadControl\Thumbnail\ThumbnailProvider;
use Nepada\FileUploadControl\Utils\DateTimeProvider;
use Nepada\FileUploadControl\Utils\DefaultDateTimeProvider;
use Nepada\FileUploadControl\Utils\FileSystem;
use Nepada\FileUploadControl\Utils\Finder;
use Nepada\FileUploadControl\Utils\NetteFileSystem;
use Nepada\FileUploadControl\Utils\NetteFinder;
use Nepada\FileUploadControl\Utils\NetteRandomProvider;
use Nepada\FileUploadControl\Utils\RandomProvider;
use Nette\DI\CompilerExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

/**
 * @property-read \stdClass $config
 */
class FileUploadControlExtension extends CompilerExtension
{

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'registerExtensionMethod' => Expect::bool(false),
            'uploadDirectory' => Expect::string()->required(),
            'templateFile' => Expect::string(FileUploadControl::DEFAULT_TEMPLATE_FILE)->dynamic(),
            'thumbnails' => Expect::structure([
                'enable' => Expect::bool(true),
                'width' => Expect::int(ImageThumbnailProvider::DEFAULT_WIDTH),
                'height' => Expect::int(ImageThumbnailProvider::DEFAULT_HEIGHT),
            ]),
        ]);
    }

    public function loadConfiguration(): void
    {
        $container = $this->getContainerBuilder();

        $container->addDefinition($this->prefix('utils.dateTimeProvider'))
            ->setType(DateTimeProvider::class)
            ->setFactory(DefaultDateTimeProvider::class);
        $container->addDefinition($this->prefix('utils.randomProvider'))
            ->setType(RandomProvider::class)
            ->setFactory(NetteRandomProvider::class);
        $container->addDefinition($this->prefix('utils.finder'))
            ->setType(Finder::class)
            ->setFactory(NetteFinder::class);
        $container->addDefinition($this->prefix('utils.fileSystem'))
            ->setType(FileSystem::class)
            ->setFactory(NetteFileSystem::class);

        $container->addDefinition($this->prefix('thumbnail.imageLoader'))
            ->setType(ImageLoader::class);
        $thumbnailProvider = $container->addDefinition($this->prefix('thumbnail.thumbnailProvider'))
            ->setType(ThumbnailProvider::class);
        if ($this->config->thumbnails->enable) {
            $thumbnailProvider->setFactory(ImageThumbnailProvider::class)
                ->setArguments(['width' => $this->config->thumbnails->width, 'height' => $this->config->thumbnails->height]);
        } else {
            $thumbnailProvider->setFactory(NullThumbnailProvider::class);
        }

        $container->addDefinition($this->prefix('storage.metadataJournalProvider'))
            ->setType(MetadataJournalProvider::class)
            ->setFactory(FileSystemMetadataJournalProvider::class)
            ->setArguments(['directory' => $this->config->uploadDirectory]);
        $container->addDefinition($this->prefix('storage.storageManager'))
            ->setType(StorageManager::class)
            ->setFactory(FileSystemStorageManager::class)
            ->setArguments(['directory' => $this->config->uploadDirectory]);

        $container->addFactoryDefinition($this->prefix('fileUploadControlFactory'))
            ->setImplement(FileUploadControlFactory::class)
            ->getResultDefinition()
            ->addSetup('setThumbnailProvider')
            ->addSetup('setTemplateFile', [$this->config->templateFile]);
    }

    public function afterCompile(ClassType $class): void
    {
        if ($this->config->registerExtensionMethod) {
            $this->initialization->addBody(
                ExtensionMethodRegistrator::class . '::register($this->getService(?));',
                [$this->prefix('fileUploadControlFactory')],
            );
        }
    }

}
