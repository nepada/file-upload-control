<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage;

interface StorageManager
{

    public function createNewNamespace(): UploadNamespace;

    /**
     * @throws StorageDoesNotExistException
     */
    public function getStorage(UploadNamespace $namespace): Storage;

}
