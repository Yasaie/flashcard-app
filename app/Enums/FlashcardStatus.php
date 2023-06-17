<?php

namespace App\Enums;

use App\Enums\Concerns\EnumTrait;

enum FlashcardStatus: int
{
    use EnumTrait;

    case NOT_ANSWERED = 0;

    case CORRECT = 1;

    case INCORRECT = 2;
}
