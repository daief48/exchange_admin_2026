<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Plain sheet reader used by the Master Data bulk importer: returns every
 * row of the first sheet as a plain PHP array (no model hydration).
 */
class MasterDataImport implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}
