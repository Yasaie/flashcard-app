<?php

namespace App\Enums\Concerns;

use App\Enums\MainMenu;
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

        foreach (MainMenu::cases() as $item) {
            $array[$item->value] = $item->getLabel();
        }

        return $array;
    }
}
