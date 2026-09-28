<?php

namespace Modules\Vat\Support;

use Modules\Vat\Services\VatFormatter;

/**
 * Separation step 1 (see document 5-18): access to the module's own formatter.
 *
 * Provided as a trait rather than a constructor dependency on purpose. The VAT
 * controllers share a constructor signature that takes core utility objects;
 * adding a parameter would have meant editing 35 constructors and every place
 * that instantiates them. A trait changes one line per file - the `use` - and
 * leaves the existing signatures untouched, which keeps this reviewable and
 * revertible file by file.
 *
 * Resolved through the container and memoised per instance, so repeated calls
 * inside a single request do not rebuild it.
 */
trait FormatsVatNumbers
{
    /** @var VatFormatter|null */
    private $vatFormatterInstance = null;

    protected function vatFormatter(): VatFormatter
    {
        if ($this->vatFormatterInstance === null) {
            $this->vatFormatterInstance = app(VatFormatter::class);
        }

        return $this->vatFormatterInstance;
    }
}
