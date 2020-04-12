<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Validation;

use Nepada\FileUploadControl\FileUploadControl;
use Nette\Forms\Controls\UploadControl;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Html;

/**
 * @internal
 */
final class FakeUploadControl extends UploadControl
{

    private FileUploadControl $fileUploadControl;

    private ?FileUpload $newFileUpload = null;

    public function __construct(FileUploadControl $fileUploadControl)
    {
        parent::__construct();
        $this->fileUploadControl = $fileUploadControl;
        $fileUploadControl->monitor(Form::class, function (Form $form): void {
            $this->setParent(null, $this->fileUploadControl->getName());
        });
    }

    public function setNewFileUpload(FileUpload $fileUpload): void
    {
        $this->newFileUpload = $fileUpload;
    }

    public function getFileUploadControl(): FileUploadControl
    {
        return $this->fileUploadControl;
    }

    /**
     * @return Html|string|null
     */
    public function getCaption()
    {
        return $this->fileUploadControl->getCaption();
    }

    /**
     * @return FileUpload[]
     */
    public function getValue(): array
    {
        $fileUploads = $this->fileUploadControl->getValue();
        if ($this->newFileUpload !== null) {
            $fileUploads[] = $this->newFileUpload;
        }
        return $fileUploads;
    }

    public function isFilled(): bool
    {
        return count($this->getValue()) > 0;
    }

    public function getForm(bool $throw = true): ?Form
    {
        return $this->fileUploadControl->getForm($throw);
    }

    /**
     * @param Html|string $message
     * @param bool $translate
     */
    public function addError($message, bool $translate = true): void
    {
        $this->fileUploadControl->addError($message, $translate);
    }

}
