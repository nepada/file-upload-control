<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\Storage\Storage;
use NepadaTests\Environment;
use NepadaTests\FileUploadControl\Fixtures\TestPresenter;
use NepadaTests\FileUploadControl\Storage\InMemoryStorage;
use NepadaTests\FileUploadControl\Storage\InMemoryStorageManager;
use NepadaTests\TestCase;
use Nette\Application;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Form;
use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Nette\Utils\FileSystem;
use Nette\Utils\Helpers;
use Nette\Utils\Json;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class FileUploadControlValidationTest extends TestCase
{

    public function testSubmittedRequiredValueMissing(): void
    {
        $control = $this->createFileUploadControl();
        $control->setRequired();

        $this->submitForm($control);

        Assert::same(['translated:This field is required.'], $control->getErrors());
    }

    public function testSubmittedWithImageValidation(): void
    {
        $storage = InMemoryStorage::createWithFiles(__DIR__ . '/Fixtures/test.txt');
        $control = $this->createFileUploadControl($storage);
        $control->addRule(Form::IMAGE);

        $this->submitForm($control);

        Assert::same(['translated:The uploaded file must be image in format JPEG, GIF, PNG or WebP.'], $control->getErrors());
    }

    public function testSubmittedWithUnprocessedUploadsOverCountLimit(): void
    {
        $control = $this->createFileUploadControl();
        $control->addRule(Form::MAX_LENGTH, 'max 1 upload allowed', 1);

        $files = ['fileUpload' => ['upload' => [
            FileUploadFactory::createFromFile(__DIR__ . '/Fixtures/test.txt'),
            FileUploadFactory::createFromFile(__DIR__ . '/Fixtures/test.txt', 'over-limit.txt'),
        ]]];
        $this->submitForm($control, $files);

        Assert::same(['translated:max 1 upload allowed'], $control->getErrors());
    }

    public function testUploadWithDisabledControl(): void
    {
        $control = $this->createFileUploadControl();
        $control->setDisabled();

        $files = ['fileUpload' => ['upload' => [
            FileUploadFactory::createFromFile(__DIR__ . '/Fixtures/test.txt'),
        ]]];
        $this->doUpload($control, $files);

        Assert::same('{"files":[{"name":"test.txt","size":9,"error":"translated:Upload disabled"}]}', $this->extractJsonResponsePayload($control));
    }

    public function testUploadWithContentTypeValidation(): void
    {
        $control = $this->createFileUploadControl();
        $control->addRule(Form::MIME_TYPE, 'only plain-text', 'text/plain');

        $files = ['fileUpload' => ['upload' => [
            FileUploadFactory::createFromFile(__DIR__ . '/Fixtures/image.png'),
            FileUploadFactory::createFromFile(__DIR__ . '/Fixtures/test.txt'),
        ]]];
        $this->doUpload($control, $files);

        Assert::same(
            Json::encode(['files' => [
                [
                    'name' => 'image.png',
                    'size' => 770,
                    'error' => 'translated:only plain-text',
                ],
                [
                    'name' => 'test.txt',
                    'size' => 9,
                    'url' => '/?form-fileUpload-namespace=testStorage&form-fileUpload-id=test-txt&action=default&do=form-fileUpload-download&presenter=Test',
                    'type' => 'text/plain',
                    'deleteType' => 'GET',
                    'deleteUrl' => '/?form-fileUpload-namespace=testStorage&form-fileUpload-id=test-txt&action=default&do=form-fileUpload-delete&presenter=Test',
                ],
            ]]),
            $this->extractJsonResponsePayload($control),
        );
    }

    public function testPartialUploadOverSizeLimit(): void
    {
        $control = $this->createFileUploadControl();
        $control->addRule(Form::MAX_FILE_SIZE, '64 bytes bytes ought to be enough', 64);

        $files = ['fileUpload' => ['upload' => [
            FileUploadFactory::createWithContents($this->readChunk(__DIR__ . '/Fixtures/image.png', 32), 'image.png'),
        ]]];
        $this->doUpload($control, $files, 'bytes 0-31/666');

        Assert::same(
            Json::encode(['files' => [
                [
                    'name' => 'image.png',
                    'size' => 666,
                    'error' => 'translated:64 bytes bytes ought to be enough',
                ],
            ]]),
            $this->extractJsonResponsePayload($control),
        );
    }

    public function testPartialUploadWithImageValidation(): void
    {
        $control = $this->createFileUploadControl();
        $control->addRule(Form::IMAGE, 'only PNG is allowed');

        $file = Environment::getTempDir() . '/' . uniqid();
        FileSystem::write($file, $this->readChunk(__DIR__ . '/Fixtures/image.png', 64));

        $files = ['fileUpload' => ['upload' => [
            FileUploadFactory::createFromFile($file, 'image.png'),
        ]]];
        $this->doUpload($control, $files, 'bytes 0-63/666');

        Assert::same(
            Json::encode(['files' => [
                [
                    'name' => 'image.png',
                    'size' => 666,
                    'url' => '/?form-fileUpload-namespace=testStorage&form-fileUpload-id=image-png&action=default&do=form-fileUpload-download&presenter=Test',
                    'type' => null,
                    'deleteType' => 'GET',
                    'deleteUrl' => '/?form-fileUpload-namespace=testStorage&form-fileUpload-id=image-png&action=default&do=form-fileUpload-delete&presenter=Test',
                ],
            ]]),
            $this->extractJsonResponsePayload($control),
        );
    }

    private function extractJsonResponsePayload(FileUploadControl $control): string
    {
        /** @var TestPresenter $presenter */
        $presenter = $control->getPresenter();

        $response = $presenter->response;
        Assert::type(JsonResponse::class, $response);

        return Helpers::capture(function () use ($response, $presenter): void {
            $response->send($presenter->getHttpRequest(), $presenter->getHttpResponse());
        });
    }

    /**
     * @param FileUploadControl $control
     * @param mixed[] $files
     * @param string|null $contentRangeHeader
     */
    private function doUpload(FileUploadControl $control, array $files = [], ?string $contentRangeHeader = null): void
    {
        $post = ['_do' => 'form-fileUpload-upload'];
        $parameters = ['form-fileUpload-namespace' => InMemoryStorageManager::TEST_NAMESPACE];
        $headers = [];
        if ($contentRangeHeader !== null) {
            $headers['Content-Range'] = $contentRangeHeader;
        }
        $this->runTestPresenter($control, $post, $files, $parameters, $headers);
    }

    /**
     * @param FileUploadControl $control
     * @param mixed[] $files
     */
    private function submitForm(FileUploadControl $control, array $files = []): void
    {
        $post = ['_do' => 'form-submit', 'fileUpload' => ['namespace' => InMemoryStorageManager::TEST_NAMESPACE]];
        $this->runTestPresenter($control, $post, $files);
    }

    /**
     * @param FileUploadControl $control
     * @param mixed[] $post
     * @param mixed[] $files
     * @param mixed[] $parameters
     * @param mixed[] $headers
     */
    private function runTestPresenter(FileUploadControl $control, array $post = [], array $files = [], array $parameters = [], array $headers = []): void
    {
        $cookies = [
            'nette-samesite' => true, // nette/http <3.1
            '_nss' => true, // nette/http >=3.1
        ];
        $parameters['action'] = 'default';
        $url = (new UrlScript('https://example.com'))->withQuery($parameters);
        $httpRequest = new Request($url, $post, $files, $cookies, $headers);
        $request = new Application\Request('Test', IRequest::POST, $parameters, $post, $files);
        $presenter = TestPresenter::create(
            $httpRequest,
            function (Form $form) use ($control): void {
                $form['fileUpload'] = $control;
            },
        );

        try {
            $presenter->run($request);
        } catch (Application\AbortException $exception) {
            // noop
        }
    }

    private function createFileUploadControl(?Storage $storage = null): FileUploadControl
    {
        $storageManager = InMemoryStorageManager::createWithTestNamespace($storage);
        return new FileUploadControl($storageManager, 'Test control');
    }

    /**
     * @param string $file
     * @param int<0, max> $size
     */
    private function readChunk(string $file, int $size): string
    {
        $fp = fopen($file, 'r');
        assert($fp !== false);
        $contents = fread($fp, $size);
        assert(is_string($contents));
        fclose($fp);
        return $contents;
    }

}


(new FileUploadControlValidationTest())->run();
