<?php

namespace Modules\PetroDirect\Services;

class PetroDirectService
{
    public function moduleName(): string
    {
        return 'Petro Direct';
    }

    public function excludedFeatures(): array
    {
        return ['unload_stock'];
    }
}
