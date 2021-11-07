<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl;

use Nepada\FileUploadControl\Responses\ListResponse;
use Nepada\FileUploadControl\Responses\Response;
use Nepada\FileUploadControl\Responses\UploadErrorResponse;
use Nepada\FileUploadControl\Responses\UploadSuccessResponse;
use Nepada\FileUploadControl\Storage\ContentRange;
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
use Nepada\FileUploadControl\Validation\UploadValidation;
use Nette;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Form;
use Nette\Http\FileUpload;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Nextras\FormComponents\Fragments\UIControl\BaseControl;

class FileUploadControl extends BaseControl
{

    use UploadValidation {
        UploadValidation::addRule as private _addRule;
    }

    public const DEFAULT_TEMPLATE_FILE = __DIR__ . '/templates/bootstrap4.latte';

    private string $templateFile = self::DEFAULT_TEMPLATE_FILE;

    private StorageManager $storageManager;

    private ThumbnailProvider $thumbnailProvider;

    private bool $httpDataLoaded = false;

    /**
     * @param StorageManager $storageManager
     * @param string|Html|null $caption
     */
    public function __construct(StorageManager $storageManager, $caption = null)
    {
        parent::__construct($caption);
        $this->storageManager = $storageManager;
        $this->thumbnailProvider = new NullThumbnailProvider();
        $this->control = Html::el();
        $this->setOption('type', 'file-upload');
        $this->addComponent(new Nette\Forms\Controls\UploadControl($caption, true), 'upload');
        $this->addComponent(new Nette\Forms\Controls\HiddenField(), 'namespace');
        $this->initializeValidation($this);
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
            try {
                $storage->save($fileUploadChunk);
            } catch (UnableToSaveFileUploadException $exception) {
                $uploadFailed = true;
            }
        }
        if ($uploadFailed) {
            $this->addError('Upload error');
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
            return array_map(fn (FileUploadItem $fileUploadItem): FileUpload => $fileUploadItem->getFileUpload(), $this->getStorage()->list());
        } catch (StorageDoesNotExistException $exception) {
            throw new \LogicException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @param mixed $value
     * @return static
     * @internal
     */
    public function setValue($value): self
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
     * @return static
     * @throws Nette\Application\BadRequestException
     */
    public function setDisabled($value = true): self
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
            /** @var Html $control */
            $control = $this->getUploadControl()->getControl();
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
        $uniqueFilenames = array_map(fn (FileUploadItem $fileUploadItem): string => $fileUploadItem->getFileUpload()->getName(), $fileUploadItems);
        $completedFiles = array_map(
            fn (FileUploadItem $fileUploadItem): Response => $this->createUploadSuccessResponse($fileUploadItem),
            array_filter($fileUploadItems, fn (FileUploadItem $fileUploadItem): bool => $fileUploadItem->getFileUpload()->isOk()),
        );

        $template->uploadUrl = $this->link('upload!', ['namespace' => $this->getUploadNamespace()->toString()]);
        $template->uniqueFilenames = $uniqueFilenames;
        $template->completedFiles = $completedFiles;

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
     * @param string|Html|null $caption
     * @return Html
     */
    public function getLabel($caption = null): Html
    {
        $label = $this->getUploadControl()->getLabel($caption);
        assert($label instanceof Html);
        return $label;
    }

    /**
     * @param callable|string $validator
     * @param string|Html|null $errorMessage
     * @param mixed $arg
     * @return static
     */
    public function addRule($validator, $errorMessage = null, $arg = null): self
    {
        if ($validator === Form::IMAGE) {
            $this->getUploadControl()->setHtmlAttribute('accept', implode(',', FileUpload::IMAGE_MIME_TYPES));
        } elseif ($validator === Form::MIME_TYPE) {
            $this->getUploadControl()->setHtmlAttribute('accept', implode(',', (array) $arg));
        }

        return $this->_addRule($validator, $errorMessage, $arg);
    }

    /**
     * @param string $namespace
     * @throws Nette\Application\BadRequestException
     */
    public function handleUpload(string $namespace): void
    {
        $namespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($namespace);

        $fileUploadChunks = $this->getFileUploadChunks();
        $responses = [];
        foreach ($fileUploadChunks as $fileUploadChunk) {
            if ($this->isDisabled()) {
                $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $this->translate('Upload disabled'));
                continue;
            }

            $fakeFileUpload = new FileUpload([
                'name' => $fileUploadChunk->getFileUpload()->getName(),
                'size' => $fileUploadChunk->getContentRange()->getSize(),
                'tmp_name' => $fileUploadChunk->getFileUpload()->getTemporaryFile(),
                'error' => UPLOAD_ERR_OK,
            ]);
            $this->fakeUploadControl->setNewFileUpload($fakeFileUpload);
            $this->validate();
            $error = $this->getError();
            if ($error !== null) {
                $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $error);
                continue;
            }

            try {
                $fileUploadItem = $this->getStorage()->save($fileUploadChunk);
                $responses[] = $this->createUploadSuccessResponse($fileUploadItem);
            } catch (StorageDoesNotExistException | UnableToSaveFileUploadException $exception) {
                $responses[] = $this->createUploadErrorResponse($fileUploadChunk, $this->translate('Upload error'));
            }
        }

        $this->sendJson(new ListResponse(...$responses));
    }

    /**
     * @param string $namespace
     * @param string $id
     * @throws Nette\Application\BadRequestException
     */
    public function handleDelete(string $namespace, string $id): void
    {
        $id = $this->parseFileUploadId($id);
        $namespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($namespace);

        try {
            $this->getStorage()->delete($id);
        } catch (StorageDoesNotExistException $exception) {
            // noop
        }
        $this->sendJson('');
    }

    /**
     * @param string $namespace
     * @param string $id
     * @throws Nette\Application\BadRequestException
     */
    public function handleDownload(string $namespace, string $id): void
    {
        $id = $this->parseFileUploadId($id);
        $namespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($namespace);

        try {
            $fileUploadItem = $this->getStorage()->load($id);
        } catch (StorageDoesNotExistException | FileUploadNotFoundException $exception) {
            throw new Nette\Application\BadRequestException('File upload not found.');
        }

        $fileUpload = $fileUploadItem->getFileUpload();
        $response = new Nette\Application\Responses\FileResponse(
            $fileUpload->getTemporaryFile(),
            $fileUpload->getName(),
        );
        $this->getPresenter()->sendResponse($response);
    }

    /**
     * @param string $namespace
     * @param string $id
     * @throws Nette\Application\BadRequestException
     */
    public function handleThumbnail(string $namespace, string $id): void
    {
        $id = $this->parseFileUploadId($id);
        $namespace = $this->parseUploadNamespace($namespace);
        $this->setUploadNamespace($namespace);

        try {
            $fileUpload = $this->getStorage()->load($id)->getFileUpload();
        } catch (StorageDoesNotExistException | FileUploadNotFoundException $exception) {
            throw new Nette\Application\BadRequestException('Source file for thumbnail not found.');
        }

        if (! $this->thumbnailProvider->isSupported($fileUpload)) {
            throw new Nette\Application\BadRequestException('Thumbnail could not be generated');
        }

        $response = $this->thumbnailProvider->createThumbnail($fileUpload);
        $this->getPresenter()->sendResponse($response);
    }

    protected function createTemplate(): Template
    {
        $template = parent::createTemplate();
        assert($template instanceof Template);

        $translator = $this->getTranslator();
        if ($translator !== null) {
            $template->setTranslator($translator);
        }

        $template->getLatte()->addFilter('json', fn ($data): string => Nette\Utils\Json::encode($data));

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
     *
     * @param mixed $data
     */
    protected function sendJson($data): void
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
     * @return Storage
     * @throws StorageDoesNotExistException
     */
    protected function getStorage(): Storage
    {
        return $this->storageManager->getStorage($this->getUploadNamespace());
    }

    protected function createUploadSuccessResponse(FileUploadItem $fileUploadItem): Response
    {
        $fileUpload = $fileUploadItem->getFileUpload();
        $idValue = $fileUploadItem->getId()->toString();
        $namespaceValue = $this->getUploadNamespace()->toString();
        return new UploadSuccessResponse(
            $fileUpload->getName(),
            $fileUpload->getSize(),
            $fileUpload->getContentType(),
            $this->link('download!', ['namespace' => $namespaceValue, 'id' => $idValue]),
            $this->link('delete!', ['namespace' => $namespaceValue, 'id' => $idValue]),
            $this->thumbnailProvider->isSupported($fileUpload) ? $this->link('thumbnail!', ['namespace' => $namespaceValue, 'id' => $idValue]) : null,
        );
    }

    protected function createUploadErrorResponse(FileUploadChunk $fileUploadChunk, string $error): Response
    {
        return new UploadErrorResponse(
            $fileUploadChunk->getFileUpload()->getName(),
            $fileUploadChunk->getContentRange()->getSize(),
            $error,
        );
    }

    /**
     * @param string $value
     * @return UploadNamespace
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
     * @param string $value
     * @return FileUploadId
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
     * @return FileUploadChunk[]
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
            try {
                $contentRange = ContentRange::fromHttpHeaderValue($contentRangeHeaderValue);
                $fileUploadChunk = FileUploadChunk::partialUpload(reset($files), $contentRange);
                return [$fileUploadChunk];
            } catch (\Throwable $exception) {
                throw new Nette\Application\BadRequestException('Invalid content-range header value', 400, $exception);
            }
        }

        /** @var FileUploadChunk[] $fileUploadChunks */
        $fileUploadChunks = [];
        foreach ($files as $file) {
            $fileUploadChunks[] = FileUploadChunk::completeUpload($file);
        }

        return $fileUploadChunks;
    }

}
