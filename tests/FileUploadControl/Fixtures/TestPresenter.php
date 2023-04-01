<?php
declare(strict_types = 1);

namespace NepadaTests\FileUploadControl\Fixtures;

use Latte;
use Nette;
use Nette\Application\Routers\SimpleRouter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Template;

final class TestPresenter extends Nette\Application\UI\Presenter
{

    public ?Nette\Application\Response $response = null;

    /**
     * @var callable|null
     */
    private $formConfigurator;

    public static function create(?Nette\Http\IRequest $httpRequest = null, ?callable $formConfigurator = null): self
    {
        $presenter = new self();
        $presenter->autoCanonicalize = false;

        $presenter->formConfigurator = $formConfigurator;

        $httpRequest ??= $presenter->createHttpRequest();
        $httpResponse = $presenter->createHttpResponse();
        $templateFactory = $presenter->createTemplateFactory();
        $router = new SimpleRouter();
        $presenter->injectPrimary(null, null, $router, $httpRequest, $httpResponse, null, null, $templateFactory);

        $presenter->setParent(null, 'Test');
        $presenter->changeAction('default');

        return $presenter;
    }

    public function getForm(): Form
    {
        return $this->getComponent('form');
    }

    protected function createComponentForm(): Form
    {
        $translator = new DummyTranslator();

        $form = new Form();
        $form->setTranslator($translator);

        if ($this->formConfigurator !== null) {
            call_user_func($this->formConfigurator, $form);
        }

        $form->onSubmit[] = function (): void {
        };

        return $form;
    }

    public function actionDefault(): void
    {
    }

    public function sendTemplate(?Template $template = null): void
    {
        $this->sendResponse(new Nette\Application\Responses\TextResponse(''));
    }

    public function sendResponse(Nette\Application\Response $response): void
    {
        $this->response ??= $response;
        parent::sendResponse($response);
    }

    private function createTemplateFactory(): Nette\Bridges\ApplicationLatte\TemplateFactory
    {
        return new Nette\Bridges\ApplicationLatte\TemplateFactory($this->createLatteFactory());
    }

    private function createLatteFactory(): Nette\Bridges\ApplicationLatte\LatteFactory
    {
        return new class () implements Nette\Bridges\ApplicationLatte\LatteFactory {

            public function create(): Latte\Engine
            {
                return new Latte\Engine();
            }

        };
    }

    private function createHttpRequest(): Nette\Http\IRequest
    {
        return new Nette\Http\Request(new Nette\Http\UrlScript('https://example.com'));
    }

    private function createHttpResponse(): Nette\Http\IResponse
    {
        return new Nette\Http\Response();
    }

}
