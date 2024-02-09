<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl;

use Nepada\FileUploadControl\Responses\ListResponse;
use Nepada\FileUploadControl\Responses\Response;
use Nepada\FileUploadControl\Responses\UploadErrorResponse;
use Nepada\FileUploadControl\Responses\UploadSuccessResponse;
use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FailedUpload;
use Nepada\FileUploadControl\Storage\FileUploadChunk;
use Nepada\FileUploadControl\Storage\FileUploadId;
use Nepada\FileUploadControl\Storage\FileUploadItem;
use Nepada\FileUploadControl\Storage\FileUploadNotFoundException;
use Nepada\FileUploadControl\Storage\Storage;
use Nepada\FileUploadControl\Storage\StorageDoesNotExistException;
use Nepada\FileUploadControl\Storage\StorageManager;
use Nepada\FileUploadControl\Storage\UnableToSaveFileUploadException;
use Nepada\FileUploadControl\Storage\UploadNamespace;
use Nepada\FileUploadControl\Thumbnail\NullThumbnailProvider;
use Nepada\FileUploadControl\Thumbnail\ThumbnailProvider;
use Nepada\FileUploadControl\Validation\ClientSide;
use Nepada\FileUploadControl\Validation\FakeUploadControl;
use Nepada\FileUploadControl\Validation\UploadValidation;
use Nette;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Nextras\FormComponents\Fragments\UIControl\BaseControl;

class FileUploadControl extends BaseControl
{

    use UploadValidation {
        UploadValidation::addRule as private _addRule;
    }

    final public const TEMPLATE_FILE_BOOTSTRAP4 = __DIR__ . '/templates/bootstrap4.latte';
    final public const TEMPLATE_FILE_BOOTSTRAP5 = __DIR__ . '/templates/bootstrap5.latte';
    final public const DEFAULT_TEMPLATE_FILE = self::TEMPLATE_FILE_BOOTSTRAP4;

    private string $templateFile = self::DEFAULT_TEMPLATE_FILE;

    private StorageManager $storageManager;

    private ThumbnailProvider $thumbnailProvider;

    private bool $httpDataLoaded = false;

    public function __construct(StorageManager $storageManager, string|\Stringable|null $caption = null)
    {
        parent::__construct($caption);
        $this->storageManager = $storageManager;
        $this->thumbnailProvider = new NullThumbnailProvider();
        $this->control = Html::el();
        $this->setOption('type', 'file-upload');
        $this->addComponent(new Nette\Forms\Controls\UploadControl($caption, true), 'upload');
        $this->addComponent(new Nette\Forms\Controls\HiddenField(), 'namespace');
        $this->initializeValidation($this);
        $this->addCondition(fn () => $this->getPresenterIfExists()?->isSignalReceiver($this, 'upload') !== true) // disable during file upload via signal
            ->addRule($this->validateUploadSuccess(...), Nette\Forms\Validator::$messages[Nette\Forms\Controls\UploadControl::VALID]);
        $this->addRule(ClientSide::NO_UPLOAD_IN_PROGRESS, 'File upload is still in progress - wait until it is finished, or abort it.');
    }

    private function validateUploadSuccess(FakeUploadControl $control): bool
    {
        return Arrays::every($control->getValue(), fn (FileUpload $upload): bool => $upload->isOk());
    }

    public function setThumbnailProvider(ThumbnailProvider $thumbnailProvider): void
    {
        $this->thumbnailProvider = $thumbnailProvider;
    }

    public function setTemplateFile(string $templateFile): void
    {
        $this->templateFile = $templateFile;
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    public function loadHttpData(): void
    {
        $this->getNamespaceControl()->loadHttpData();
        try {
            $storage = $this->getStorage();
        } catch (StorageDoesNotExistException $exception) {
            // refresh namespace
            $this->setUploadNamespace($this->storageManager->createNewNamespace());
            try {
                $storage = $this->getStorage();
            } catch (StorageDoesNotExistException $exception) {
                throw new \LogicException($exception->getMessage(), 0, $exception);
            }
        }

        if ($this->httpDataLoaded || $this->isDisabled()) {
            return;
        }

        $this->httpDataLoaded = true;

        $fileUploadChunks = $this->getFileUploadChunks();
        if (count($fileUploadChunks) === 0) {
            return;
        }

        $uploadFailed = false;
        foreach ($fileUploadChunks as $fileUploadChunk) {
            if ($fileUploadChunk instanceof FailedUpload) {
                $uploadFailed = true;
                continue;
            }
            try {
                $storage->save($fileUploadChunk);
            } catch (UnableToSaveFileUploadException $exception) {
                $uploadFailed = true;
            }
        }
        if ($uploadFailed) {
            $this->addError(Nette\Forms\Validator::$messages[Nette\Forms\Controls\UploadControl::VALID]);
        }
    }

    /**
     * @return FileUpload[]
     */
    public function getValue(): array
    {
        if ($this->isDisabled()) {
            return [];
        }

        try {
            return array_map(fn (FileUploadItem $fileUploadItem): FileUpload => $fileUploadItem->fileUpload, $this->getStorage()->list());
        } catch (StorageDoesNotExistException $exception) {
            throw new \LogicException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return $this
     * @internal
     */
    public function setValue(mixed $value): static
    {
        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->getUploadControl()->isDisabled();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param bool $value
     * @return $this
     * @throws Nette\Application\BadRequestException
     */
    public function setDisabled($value = true): static
    {
        $this->getUploadControl()->setDisabled($value);
        if (! $value) {
            return $this;
        }

        $form = $this->getForm(false);
        if ($form !== null && $form->isAnchored() && (bool) $form->isSubmitted()) {
            $this->loadHttpData();
        }

        return $this;
    }

    public function getControlPart(?string $key = null): ?Html
    {
        if ($key === 'namespace') {
            return $this->getNamespaceControl()->getControl();
        }

        if ($key === 'upload') {
            $control = $this->getUploadControl()->getControl();
            assert($control instanceof Html);
            $control->{'data-nette-rules'} = Nette\Forms\Helpers::exportRules($this->getRules());
            return $control;
        }

        return $this->getControl();
    }

    public function getControl(): Html
    {
        $this->setOption('rendered', true);
        $control = clone $this->control;

        $template = $this->getTemplate();
        assert($template instanceof Template);

        try {
            $storage = $this->getStorage();
        } catch (StorageDoesNotExistException $exception) {
            throw new \LogicException($exception->getMessage(), 0, $exception);
        }
        $fileUploadItems = $storage->list();

        $uniqueFilenames = array_map(fn (FileUploadItem $fileUploadItem): string => $fileUploadItem->fileUpload->getUntrustedName(), $fileUploadItems);
        $completedFiles = [];
        $partiallyUploadedFiles = [];
        foreach ($fileUploadItems as $fileUploadItem) {
            if ($fileUploadItem->fileUpload->isOk()) {
                $completedFiles[] = $this->createUploadSuccessResponse($fileUploadItem);
            } else {
                $partiallyUploadedFiles[] = $this->createUploadSuccessResponse($fileUploadItem);
            }
        }

        $template->uniqueFilenames = $uniqueFilenames;
        $template->completedFiles = $completedFiles;
        $template->partiallyUploadedFiles = $partiallyUploadedFiles;
        $template->allFiles = array_merge($completedFiles, $partiallyUploadedFiles);
        $template->uploadUrl = $this->link('upload!', ['namespace' => $this->getUploadNamespace()->toString()]);

        $controlHtml = Nette\Utils\Helpers::capture(function () use ($template): void {
            $template->render($this->templateFile);
        });
        return $control->addHtml($controlHtml);
    }

    public function getLabelPrototype(): Html
    {
        return $this->getUploadControl()->getLabelPrototype();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param string|\Stringable|null $caption
     */
    public function getLabel($caption = null): Html
    {
        $label = $this->getUploadControl()->getLabel($caption);
        assert($label instanceof Html);
        return $label;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param callable|string $validator
     * @param string|\Stringable|null $errorMessage
     * @return $this
     */
    public function addRule($validator, $errorMessage = null, mixed $arg = null): static
    {
        if ($validator === Form::IMAGE) {
            $this->getUploadControl()->setHtmlAttribute('accept', implode(',', FileUpload::IMAGE_MIME_TYPES));
        } elseif ($validator === Form::MIME_TYPE) {
            $mimeTypes = is_array($arg) ? $arg : ($arg === null ? [] : [$arg]);
            $this->getUploadControl()->setHtmlAttribute('accept', implode(',', $mimeTypes));
        }

        return $this->_addRule($validator, $errorMessage, $arg);
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    public function handleUpload(string $namespace): void
    {
        $uploadNamespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($uploadNamespace);

        $fileUploadChunks = $this->getFileUploadChunks();
        $responses = [];
        foreach ($fileUploadChunks as $fileUploadChunk) {
            if ($this->isDisabled()) {
                $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $this->translate('Upload disabled'));
                continue;
            }

            if ($fileUploadChunk instanceof FailedUpload) {
                $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $this->translate(Nette\Forms\Validator::$messages[Nette\Forms\Controls\UploadControl::VALID]));
                continue;
            }

            if ($fileUploadChunk->contentRange->containsFirstByte()) {
                $fakeFileUpload = new FileUpload([
                    'name' => $fileUploadChunk->fileUpload->getUntrustedName(),
                    'size' => $fileUploadChunk->contentRange->getSize(),
                    'tmp_name' => $fileUploadChunk->fileUpload->getTemporaryFile(),
                    'error' => UPLOAD_ERR_OK,
                ]);
                $this->fakeUploadControl->setNewFileUpload($fakeFileUpload);
                $this->validate();
                $error = $this->getError();
                if ($error !== null) {
                    $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $error);
                    continue;
                }
            }

            try {
                $fileUploadItem = $this->getStorage()->save($fileUploadChunk);
                $responses[] = $this->createUploadSuccessResponse($fileUploadItem);
            } catch (StorageDoesNotExistException | UnableToSaveFileUploadException $exception) {
                $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $this->translate(Nette\Forms\Validator::$messages[Nette\Forms\Controls\UploadControl::VALID]));
            }
        }

        $this->sendJson(new ListResponse(...$responses));
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    public function handleDelete(string $namespace, string $id): void
    {
        $fileUploadId = $this->parseFileUploadId($id);
        $uploadNamespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($uploadNamespace);

        try {
            $this->getStorage()->delete($fileUploadId);
        } catch (StorageDoesNotExistException $exception) {
            // noop
        }
        $this->sendJson('');
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    public function handleDownload(string $namespace, string $id): void
    {
        $fileUploadId = $this->parseFileUploadId($id);
        $uploadNamespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($uploadNamespace);

        try {
            $fileUploadItem = $this->getStorage()->load($fileUploadId);
        } catch (StorageDoesNotExistException | FileUploadNotFoundException $exception) {
            throw new Nette\Application\BadRequestException('File upload not found.', 0, $exception);
        }

        $fileUpload = $fileUploadItem->fileUpload;
        $response = new Nette\Application\Responses\FileResponse(
            $fileUpload->getTemporaryFile(),
            $fileUpload->getUntrustedName(),
        );
        $this->getPresenter()->sendResponse($response);
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    public function handleThumbnail(string $namespace, string $id): void
    {
        $fileUploadId = $this->parseFileUploadId($id);
        $uploadNamespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($uploadNamespace);

        try {
            $fileUpload = $this->getStorage()->load($fileUploadId)->fileUpload;
        } catch (StorageDoesNotExistException | FileUploadNotFoundException $exception) {
            throw new Nette\Application\BadRequestException('Source file for thumbnail not found.', 0, $exception);
        }

        if (! $this->thumbnailProvider->isSupported($fileUpload)) {
            throw new Nette\Application\BadRequestException('Thumbnail could not be generated');
        }

        $response = $this->thumbnailProvider->createThumbnail($fileUpload);
        $this->getPresenter()->sendResponse($response);
    }

    protected function createTemplate(?string $class = null): Template
    {
        $template = parent::createTemplate($class);
        assert($template instanceof Template);

        $translator = $this->getTranslator();
        if ($translator !== null) {
            $template->setTranslator($translator);
        }

        $template->getLatte()->addFilter('json', fn (mixed $data): string => Nette\Utils\Json::encode($data));

        return $template;
    }

    protected function getHttpRequest(): Nette\Http\IRequest
    {
        return $this->getPresenter()->getHttpRequest();
    }

    /**
     * Sends back JSON response.
     * Sets the right content type based on the support on the other end.
     * https://github.com/blueimp/jQuery-File-Upload/wiki/Setup#wiki-content-type-negotiation
     */
    protected function sendJson(mixed $data): void
    {
        $contentType = Strings::contains((string) $this->getHttpRequest()->getHeader('accept'), 'application/json') ? 'application/json' : 'text/plain';
        $response = new Nette\Application\Responses\JsonResponse($data, $contentType);
        $this->getPresenter()->sendResponse($response);
    }

    protected function getUploadControl(): Nette\Forms\Controls\UploadControl
    {
        return $this->getComponent('upload');
    }

    protected function getNamespaceControl(): Nette\Forms\Controls\HiddenField
    {
        return $this->getComponent('namespace');
    }

    protected function getThumbnailProvider(): ThumbnailProvider
    {
        return $this->thumbnailProvider;
    }

    protected function getUploadNamespace(): UploadNamespace
    {
        $nameSpaceValue = (string) $this->getNamespaceControl()->getValue();
        if (UploadNamespace::isValid($nameSpaceValue)) {
            return UploadNamespace::fromString($nameSpaceValue);
        }

        $namespace = $this->storageManager->createNewNamespace();
        $this->setUploadNamespace($namespace);
        return $namespace;
    }

    protected function setUploadNamespace(UploadNamespace $namespace): void
    {
        $this->getNamespaceControl()->setValue($namespace->toString());
    }

    /**
     * @throws StorageDoesNotExistException
     */
    protected function getStorage(): Storage
    {
        return $this->storageManager->getStorage($this->getUploadNamespace());
    }

    protected function createUploadSuccessResponse(FileUploadItem $fileUploadItem): Response
    {
        $fileUpload = $fileUploadItem->fileUpload;
        $idValue = $fileUploadItem->id->toString();
        $namespaceValue = $this->getUploadNamespace()->toString();
        return new UploadSuccessResponse(
            $fileUpload->getUntrustedName(),
            $fileUpload->getSize(),
            $fileUpload->getContentType(),
            $this->link('download!', ['namespace' => $namespaceValue, 'id' => $idValue]),
            $this->link('delete!', ['namespace' => $namespaceValue, 'id' => $idValue]),
            $this->thumbnailProvider->isSupported($fileUpload) ? $this->link('thumbnail!', ['namespace' => $namespaceValue, 'id' => $idValue]) : null,
        );
    }

    protected function createUploadErrorResponse(FileUploadChunk|FailedUpload $upload, string $error): Response
    {
        return new UploadErrorResponse(
            $upload->fileUpload->getUntrustedName(),
            $upload->contentRange?->getSize() ?? 0,
            $error,
        );
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    protected function parseUploadNamespace(string $value): UploadNamespace
    {
        if (! UploadNamespace::isValid($value)) {
            throw new Nette\Application\BadRequestException('Invalid namespace value', 400);
        }
        return UploadNamespace::fromString($value);
    }

    /**
     * @throws Nette\Application\BadRequestException
     */
    protected function parseFileUploadId(string $value): FileUploadId
    {
        if (! FileUploadId::isValid($value)) {
            throw new Nette\Application\BadRequestException('Invalid file upload id value', 400);
        }
        return FileUploadId::fromString($value);
    }

    /**
     * @return list<FileUploadChunk|FailedUpload>
     * @throws Nette\Application\BadRequestException
     */
    protected function getFileUploadChunks(): array
    {
        $httpRequest = $this->getHttpRequest();
        /** @var array<int, FileUpload> $files */
        $files = Nette\Forms\Helpers::extractHttpData($httpRequest->getFiles(), $this->getUploadControl()->getHtmlName() . '[]', Form::DATA_FILE);
        if (count($files) === 0) {
            return [];
        }

        $contentRangeHeaderValue = $httpRequest->getHeader('content-range');
        if ($contentRangeHeaderValue !== null) {
            if (count($files) > 1) {
                throw new Nette\Application\BadRequestException('Chunk upload does not support multi-file upload', 400);
            }
            $file = reset($files);

            try {
                $contentRange = ContentRange::fromHttpHeaderValue($contentRangeHeaderValue);
                $fileUpload = $file->isOk() ? FileUploadChunk::partialUpload($file, $contentRange) : FailedUpload::of($file, $contentRange);
                return [$fileUpload];
            } catch (\Throwable $exception) {
                throw new Nette\Application\BadRequestException('Invalid content-range header value', 400, $exception);
            }
        }

        /** @var list<FileUploadChunk|FailedUpload> $fileUploads */
        $fileUploads = [];
        foreach ($files as $file) {
            $fileUploads[] = $file->isOk() ? FileUploadChunk::completeUpload($file) : FailedUpload::of($file);
        }

        return $fileUploads;
    }

}
