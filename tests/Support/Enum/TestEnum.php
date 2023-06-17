<?php

namespace Tests\Support\Enum;

use App\Enums\Concerns\EnumTrait;

enum TestEnum: int
{
    use EnumTrait;

    case TEST_VALUE_1 = 1;
    case TEST_VALUE_2 = 2;
    case TEST_VALUE_3 = 3;
}
