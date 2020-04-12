<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl;

use Nepada\FileUploadControl\FileUploadControl;
use Nepada\FileUploadControl\Storage\Storage;
use Nepada\FileUploadControl\Thumbnail\ImageLoader;
use Nepada\FileUploadControl\Thumbnail\ImageThumbnailProvider;
use NepadaTests\FileUploadControl\Fixtures\TestPresenter;
use NepadaTests\FileUploadControl\Storage\InMemoryStorage;
use NepadaTests\FileUploadControl\Storage\InMemoryStorageManager;
use NepadaTests\TestCase;
use Nette\Application\UI\Form;
use Nette\Utils\FileSystem;
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

    public function testControlWithFiles(): void
    {
        $storage = InMemoryStorage::createWithFiles(__DIR__ . '/Fixtures/test.txt', __DIR__ . '/Fixtures/image.png');
        $control = $this->createFileUploadControl($storage);

        $control->setThumbnailProvider(new ImageThumbnailProvider(new ImageLoader()));
        $control->setRequired();

        Assert::same(
            FileSystem::read(__DIR__ . '/Fixtures/FileUploadControl.files.html'),
            (string) $control->getControl(),
        );
    }

    public function testControlWithErrors(): void
    {
        $control = $this->createFileUploadControl();

        $control->addRule(Form::IMAGE);
        $control->addError('some error');
        Assert::same(
            FileSystem::read(__DIR__ . '/Fixtures/FileUploadControl.errors.html'),
            (string) $control->getControl(),
        );
    }

    public function testDisabledControl(): void
    {
        $control = $this->createFileUploadControl();

        $control->setDisabled();
        Assert::same(
            FileSystem::read(__DIR__ . '/Fixtures/FileUploadControl.disabled.html'),
            (string) $control->getControl(),
        );
    }

    private function createFileUploadControl(?Storage $storage = null): FileUploadControl
    {
        $storageManager = InMemoryStorageManager::createWithTestNamespace($storage);

        $form = TestPresenter::create()->getForm();
        $control = new FileUploadControl($storageManager, 'Test control');
        $form['fileUpload'] = $control;
        $control->getComponent('namespace')->setValue(InMemoryStorageManager::TEST_NAMESPACE);

        return $control;
    }

}


(new FileUploadControlRenderingTest())->run();
