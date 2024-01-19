<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Validation;

/**
 * Dummy validator for client-side form validation rules
 */
final class ClientSide
{

    public const NO_UPLOAD_IN_PROGRESS = self::class . '::noUploadInProgress';

    private function __construct()
    {
    }

    public static function noUploadInProgress(FakeUploadControl $control): bool
    {
        return true;
    }

}
