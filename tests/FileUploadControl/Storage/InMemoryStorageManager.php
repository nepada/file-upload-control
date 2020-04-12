<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Storage;

use Nepada\FileUploadControl\Storage\Storage;
use Nepada\FileUploadControl\Storage\StorageDoesNotExistException;
use Nepada\FileUploadControl\Storage\StorageManager;
use Nepada\FileUploadControl\Storage\UploadNamespace;
use Nepada\FileUploadControl\Utils\NetteRandomProvider;
use Nette;

final class InMemoryStorageManager implements StorageManager
{

    use Nette\SmartObject;

    public const TEST_NAMESPACE = 'testStorage';

    /** @var array<string, Storage> */
    private array $storages = [];

    public static function createWithTestNamespace(?Storage $storage): StorageManager
    {
        $storageManager = new self();
        $storageManager->setStorage(UploadNamespace::fromString(self::TEST_NAMESPACE), $storage ?? new InMemoryStorage());
        return $storageManager;
    }

    public function setStorage(UploadNamespace $namespace, Storage $storage): void
    {
        $this->storages[$namespace->toString()] = $storage;
    }

    public function createNewNamespace(): UploadNamespace
    {
        $namespace = UploadNamespace::generate(new NetteRandomProvider());
        $this->storages[$namespace->toString()] = new InMemoryStorage();
        return $namespace;
    }

    public function getStorage(UploadNamespace $namespace): Storage
    {
        $namespaceValue = $namespace->toString();
        if (! isset($this->storages[$namespaceValue])) {
            throw StorageDoesNotExistException::withNamespace($namespace);
        }
        return $this->storages[$namespaceValue];
    }

}
