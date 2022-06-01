<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Validation;

use Nepada\FileUploadControl\FileUploadControl;
use Nette\Forms\Control;
use Nette\Forms\Rules;
use Nette\Utils\Html;

/**
 * @internal
 */
trait UploadValidation
{

    protected FakeUploadControl $fakeUploadControl;

    private Rules $rules;

    /**
     * @param callable|string $validator
     * @param string|Html $errorMessage
     * @param mixed $arg
     * @return static
     */
    public function addRule($validator, $errorMessage = null, $arg = null): self
    {
        $this->rules->addRule($validator, $errorMessage, $arg);
        return $this;
    }

    /**
     * @param callable|string $validator
     * @param mixed $value
     * @return Rules
     */
    public function addCondition($validator, $value = null): Rules
    {
        return $this->rules->addCondition($validator, $value);
    }

    /**
     * @param Control $control
     * @param callable|string $validator
     * @param mixed $value
     * @return Rules
     */
    public function addConditionOn(Control $control, $validator, $value = null): Rules
    {
        return $this->rules->addConditionOn($control, $validator, $value);
    }

    public function getRules(): Rules
    {
        return $this->rules;
    }

    /**
     * @param bool|string|Html $value
     * @return static
     */
    public function setRequired($value = true): self
    {
        $this->rules->setRequired($value);
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->rules->isRequired();
    }

    public function validate(): void
    {
        if ($this->isDisabled()) {
            return;
        }
        $this->cleanErrors();
        $this->rules->validate();
    }

    private function initializeValidation(FileUploadControl $control): void
    {
        $this->fakeUploadControl = new FakeUploadControl($control);
        $this->rules = new Rules($this->fakeUploadControl);
    }

}
