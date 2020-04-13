<?php
declare(strict_types = 1);

namespace Nepada\Bridges\FileUploadControlForms;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\FileUploadControlFactory;
use Nette\Utils\Html;

trait FileUploadControlMixin
{

    private FileUploadControlFactory $fileUploadControlFactory;

    public function injectFileUploadFactory(FileUploadControlFactory $factory): void
    {
        $this->fileUploadControlFactory = $factory;
    }

    /**
     * @param string|int $name
     * @param string|Html|null $label
     * @return FileUploadControl
     */
    public function addFileUpload($name, $label = null): FileUploadControl
    {
        return $this[$name] = $this->fileUploadControlFactory->create($label);
    }

}
