<?php
declare(strict_types = 1);

namespace Nepada\FileUploadControl\Storage\Metadata;

use Nepada\FileUploadControl\Storage\UploadNamespace;

interface MetadataJournalProvider
{

    public function get(UploadNamespace $namespace): MetadataJournal;

}
