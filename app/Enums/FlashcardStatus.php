<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum FlashcardStatus: int
{
    case NOT_ANSWERED = 0;

    case CORRECT = 1;

    case INCORRECT = 2;

    /**
     * Get the title of the status.
     */
    public function title(): string
    {
        return Str::of($this->name)->title()->replace('_', ' ');
    }
}
