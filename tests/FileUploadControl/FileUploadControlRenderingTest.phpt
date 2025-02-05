<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\Storage\ContentRange;
use Nepada\FileUploadControl\Storage\FileUploadChunk;
use Nepada\FileUploadControl\Storage\Storage;
use Nepada\FileUploadControl\Thumbnail\ImageLoader;
use Nepada\FileUploadControl\Thumbnail\ImageThumbnailProvider;
use NepadaTests\FileUploadControl\Fixtures\TestPresenter;
use NepadaTests\FileUploadControl\Storage\InMemoryStorage;
use NepadaTests\FileUploadControl\Storage\InMemoryStorageManager;
use NepadaTests\TestCase;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';


/**
 * @testCase
 */
class FileUploadControlRenderingTest extends TestCase
{

    public function testLabel(): void
    {
        $control = $this->createFileUploadControl();

        $control->getLabelPrototype()->data('foo', 'bar');
        Assert::same(
            '<label data-foo="bar" for="frm-form-fileUpload-upload">translated:Test control</label>',
            (string) $control->getLabel(),
        );
    }

    /**
     * @dataProvider templateFilesDataProvider
     */
    public function testControlWithFiles(string $templateFile): void
    {
        $storage = InMemoryStorage::createWithFiles(__DIR__ . '/Fixtures/test.txt', __DIR__ . '/Fixtures/image.png');
        $storage->save(FileUploadChunk::partialUpload(
            FileUploadFactory::createFromFile(__DIR__ . '/Fixtures/test.txt', 'partial.txt'),
            ContentRange::fromHttpHeaderValue('bytes 0-8/100'),
        )); // interrupted partial upload

        $control = $this->createFileUploadControl($storage, $templateFile);

        $control->setThumbnailProvider(new ImageThumbnailProvider(new ImageLoader()));
        $control->setRequired();

        HtmlAssert::matchFile(
            $this->formatFixturePath('files', $templateFile),
            trim((string) $control->getControl()),
        );
    }

    /**
     * @dataProvider templateFilesDataProvider
     */
    public function testControlWithErrors(string $templateFile): void
    {
        $control = $this->createFileUploadControl(null, $templateFile);

        $control->addRule(Form::Image);
        $control->addError('some error');
        HtmlAssert::matchFile(
            $this->formatFixturePath('errors', $templateFile),
            trim((string) $control->getControl()),
        );
    }

    /**
     * @dataProvider templateFilesDataProvider
     */
    public function testDisabledControl(string $templateFile): void
    {
        $control = $this->createFileUploadControl(null, $templateFile);

        $control->setDisabled();
        HtmlAssert::matchFile(
            $this->formatFixturePath('disabled', $templateFile),
            trim((string) $control->getControl()),
        );
    }

    private function createFileUploadControl(?Storage $storage = null, string $templateFile = FileUploadControl::DEFAULT_TEMPLATE_FILE): FileUploadControl
    {
        $storageManager = InMemoryStorageManager::createWithTestNamespace($storage);

        $form = TestPresenter::create()->getForm();
        $control = new FileUploadControl($storageManager, 'Test control');
        $control->setTemplateFile($templateFile);
        $form['fileUpload'] = $control;
        assert($control['namespace'] instanceof BaseControl);
        $control['namespace']->setValue(InMemoryStorageManager::TEST_NAMESPACE);

        return $control;
    }

    /**
     * @return mixed[]
     */
    protected function templateFilesDataProvider(): array
    {
        return [
            'bootstrap 4' => [FileUploadControl::TEMPLATE_FILE_BOOTSTRAP4],
            'bootstrap 5' => [FileUploadControl::TEMPLATE_FILE_BOOTSTRAP5],
        ];
    }

    private function formatFixturePath(string $name, string $templateFile): string
    {
        $templateName = explode('.', basename($templateFile))[0];
        return __DIR__ . "/Fixtures/FileUploadControl.{$templateName}.{$name}.html";
    }

}


(new FileUploadControlRenderingTest())->run();
