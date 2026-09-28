<?php

namespace Modules\PetroGeneral\Http\Controllers\DailyCollection;

use Modules\PetroGeneral\Http\Controllers\DailyCollectionController as BaseDailyCollectionController;

/**
 * PG019 safe wrapper controller.
 *
 * This class intentionally inherits the existing DailyCollectionController
 * methods without changing business logic. It allows Petro General routes to
 * be split into small functionality controllers first, then the logic can be
 * moved method-by-method in later packages after testing.
 */
class DailyCollectionShortageExcessController extends BaseDailyCollectionController
{
}
