<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

interface StorageManager
{

    public function createNewNamespace(): UploadNamespace;

    /**
     * @param UploadNamespace $namespace
     * @return Storage
     * @throws StorageDoesNotExistException
     */
    public function getStorage(UploadNamespace $namespace): Storage;

}
