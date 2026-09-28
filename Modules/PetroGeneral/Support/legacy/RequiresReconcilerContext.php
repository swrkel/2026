<?php

namespace Modules\Petro\Entities\Concerns;

/**
 * Compatibility name retained for shared settlement models outside the Petro
 * modules. The implementation is fully owned by PetroGeneral.
 */
trait RequiresReconcilerContext
{
    use \Modules\PetroGeneral\Entities\Concerns\RequiresReconcilerContext;
}
