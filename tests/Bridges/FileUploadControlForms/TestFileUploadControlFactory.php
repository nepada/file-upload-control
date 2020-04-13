<?php
declare(strict_types = 1);

namespace NepadaTests\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use NepadaTests\FileUploadControl\Storage\InMemoryStorageManager;
use Nette;
use Nette\Utils\Html;

final class TestFileUploadControlFactory implements FileUploadControlFactory
{

    use Nette\SmartObject;

    /**
     * @param Html|string|null $caption
     * @return FileUploadControl
     */
    public function create($caption): FileUploadControl
    {
        $storageManager = new InMemoryStorageManager();
        return new FileUploadControl($storageManager, $caption);
    }

}
