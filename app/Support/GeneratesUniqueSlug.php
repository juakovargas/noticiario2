<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    protected function uniqueSlug(string $modelClass, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $counter = 1;

        while ($this->slugExists($modelClass, $slug, $ignoreId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $modelClass, string $slug, ?int $ignoreId = null): bool
    {
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $modelClass::query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
