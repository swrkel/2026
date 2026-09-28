<?php

namespace Modules\PetroDirect\Entities\Concerns;

/**
 * PetroDirect settlement entities may reference this trait from older/newer
 * reconciliation builds. Keep it inside PetroDirect so the module remains
 * standalone and does not depend on Petro or main-system entity traits.
 *
 * The trait is intentionally safe/no-op: it only stores an optional context
 * array on the model instance when future code supplies it. Existing models
 * that simply `use RequiresReconcilerContext` will now load without fatal
 * errors.
 */
trait RequiresReconcilerContext
{
    /**
     * Optional runtime reconciliation context.
     *
     * @var array
     */
    protected $reconcilerContext = [];

    /**
     * Attach reconciliation context to the current model instance.
     *
     * @param array $context
     * @return $this
     */
    public function withReconcilerContext(array $context)
    {
        $this->reconcilerContext = $context;

        return $this;
    }

    /**
     * Get reconciliation context stored on the current model instance.
     *
     * @return array
     */
    public function getReconcilerContext(): array
    {
        return $this->reconcilerContext ?? [];
    }
}
