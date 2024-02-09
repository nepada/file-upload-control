<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Validation;

use Nepada\FileUploadControl\FileUploadControl;
use Nette\Forms\Control;
use Nette\Forms\Rules;

/**
 * @internal
 */
trait UploadValidation
{

    protected FakeUploadControl $fakeUploadControl;

    private Rules $rules;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param callable|string $validator
     * @param string|\Stringable|null $errorMessage
     * @return $this
     */
    public function addRule($validator, $errorMessage = null, mixed $arg = null): static
    {
        $this->rules->addRule($validator, $errorMessage, $arg);
        return $this;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param callable|string $validator
     */
    public function addCondition($validator, mixed $value = null): Rules
    {
        return $this->rules->addCondition($validator, $value);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param callable|string $validator
     */
    public function addConditionOn(Control $control, $validator, mixed $value = null): Rules
    {
        return $this->rules->addConditionOn($control, $validator, $value);
    }

    public function getRules(): Rules
    {
        return $this->rules;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param bool|string|\Stringable $value
     * @return $this
     */
    public function setRequired($value = true): static
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
