<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait EnumTrait
{
    /**
     * Get the label of the item.
     */
    public function getLabel(): string
    {
        return Str::of($this->name)->title()->replace('_', ' ');
    }

    /**
     * Get the items as an array.
     */
    public static function toArray(): array
    {
        $array = [];

        foreach (static::cases() as $item) {
            $array[$item->value] = $item->getLabel();
        }

        return $array;
    }
}
