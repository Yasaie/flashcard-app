<?php

namespace Tests\Unit\Enums\Concerns;

use PHPUnit\Framework\TestCase;
use Tests\Support\Enum\TestEnum;

class EnumTraitTest extends TestCase
{
    public function testGetLabel(): void
    {
        $this->assertEquals('Test Value 1', TestEnum::TEST_VALUE_1->getLabel());
    }

    public function testToArray(): void
    {
        $this->assertEquals([
            1 => 'Test Value 1',
            2 => 'Test Value 2',
            3 => 'Test Value 3',
        ], TestEnum::toArray());
    }
}
