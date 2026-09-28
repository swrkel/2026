<?php

namespace Modules\PetroDirectNew\Reports\Contracts;

interface PetroDirectNewReport
{
    public function key(): string;
    public function label(): string;
    public function rows(array $filters = []);
}
