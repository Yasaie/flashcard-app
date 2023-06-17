<?php

namespace Tests\Unit\Models;

use App\Enums\FlashcardStatus;
use App\Models\Flashcard;
use App\Models\FlashcardProgress;
use Tests\TestCase;

class FlashcardTest extends TestCase
{
    public function testUserStatus(): void
    {
        $notAnsweredFlashcard = Flashcard::factory()->create();

        $answeredFlashcard = Flashcard::factory()->create();

        FlashcardProgress::factory()->create([
            'flashcard_id' => $answeredFlashcard->id,
            'username' => 'payam',
            'status' => FlashcardStatus::CORRECT,
        ]);

        // This flashcard is answered by another user, so it should not be counted as answered for user 'payam'.
        FlashcardProgress::factory()->create([
            'username' => 'thomas',
            'status' => FlashcardStatus::INCORRECT,
        ]);

        $this->assertEquals(FlashcardStatus::NOT_ANSWERED, $notAnsweredFlashcard->userStatus('payam'));

        $this->assertEquals(FlashcardStatus::CORRECT, $answeredFlashcard->userStatus('payam'));
    }
}
