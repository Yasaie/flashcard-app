<?php

namespace App\Enums;

use App\Enums\Concerns\EnumTrait;

enum MainMenu: int
{
    use EnumTrait;

    case CREATE_FLASHCARD = 1;

    case LIST_ALL_FLASHCARDS = 2;

    case PRACTICE = 3;

    case STATS = 4;

    case RESET = 5;

    case EXIT = 6;

}
