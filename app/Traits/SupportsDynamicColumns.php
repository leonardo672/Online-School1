<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait SupportsDynamicColumns
{
    protected function filterExistingColumns(string $table, array $data, array $allowed): array
    {
        return collect($data)
            ->only($allowed)
            ->filter(function ($value, $key) use ($table) {
                return Schema::hasColumn($table, $key);
            })
            ->toArray();
    }
}
