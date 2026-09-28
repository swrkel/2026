<?php

namespace Modules\Chequer\Utils;

class ChequerTransactionUtil
{
    public function num_uf($input)
    {
        if ($input === null || $input === '') return 0;
        return (float) str_replace(',', '', $input);
    }

    public function num_f($input, $add_symbol = false)
    {
        return number_format((float) $input, 2, '.', ',');
    }
}
