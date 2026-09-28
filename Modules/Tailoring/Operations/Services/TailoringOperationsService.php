<?php
namespace Modules\Tailoring\Operations\Services;
use Illuminate\Http\Request;
class TailoringOperationsService
{
    public function summary(string $area, Request $request): array
    {
        return ['pending'=>0,'in_progress'=>0,'completed'=>0,'overdue'=>0,'today'=>0,'efficiency'=>0];
    }
}
