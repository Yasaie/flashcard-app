<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\FlashcardStatus;
use App\Enums\MainMenu;
use App\Models\Flashcard;
use App\Models\FlashcardProgress;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class FlashcardInteractiveTest extends TestCase
{
    public function testAskForUsername(): void
    {
        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command);
    }

    public function testCreateFlashcard(): void
    {
        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::CREATE_FLASHCARD);

        // Question cannot be empty
        $command->expectsQuestion('Please enter the question', null)
            ->expectsOutput('This field cannot be empty. Please try again.')
            ->expectsQuestion('Please enter the question', 'What is the capital of Iran?');

        // Answer cannot be empty
        $command->expectsQuestion('Please enter the answer', null)
            ->expectsOutput('This field cannot be empty. Please try again.');

        $command->expectsQuestion('Please enter the answer', 'Tehran');

        $command->expectsOutput('Flashcard created successfully.');

        $this->assertMainMenu($command);

        // We have to run the command before we can assert the database,
        // because the command is not run until test destructs
        $command->run();

        $this->assertDatabaseHas('flashcards', [
            'question' => 'What is the capital of Iran?',
            'answer' => 'Tehran',
        ]);
    }

    public function testListFlashcardsWhenEmpty(): void
    {
        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::LIST_ALL_FLASHCARDS);

        $command->expectsOutput('No flashcards found.');

        $this->assertMainMenu($command);
    }

    public function testListFlashcards(): void
    {
        $flashcards = Flashcard::factory(3)->create();

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::LIST_ALL_FLASHCARDS);

        $command
            ->expectsOutput('Flashcards:')
            ->expectsTable(
                ['Question', 'Answer'],
                $flashcards->map(fn ($flashcard) => [$flashcard->question, $flashcard->answer]),
                'box',
            );

        $this->assertMainMenu($command);
    }

    public function testPracticeFlashcardsWhenEmpty(): void
    {
        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::PRACTICE);

        $command->expectsOutput('No flashcards found. Please create some flashcards first.');

        $this->assertMainMenu($command);
    }

    public function testPracticeFlashcardsWithIncorrectId(): void
    {
        $flashcard = Flashcard::factory()->create();

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::PRACTICE);

        $this->assertDisplayProgress($command, $flashcard);

        // With an incorrect ID (ID + 1) the user will get an error message
        $this->assertPractice($command, $flashcard->id + 1);

        $command->expectsOutput('Flashcard not found. Please enter a valid ID.');

        $this->assertDisplayProgress($command, $flashcard);

        $this->assertPractice($command);

        $this->assertMainMenu($command);
    }

    public function testPracticeFlashcardsWhenAlreadyAnsweredCorrectly(): void
    {
        // A flashcard answered correctly by the user
        $flashcardProgress = FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::CORRECT,
        ]);

        $flashcard = $flashcardProgress->flashcard;

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::PRACTICE);

        $this->assertDisplayProgress($command, $flashcard, FlashcardStatus::CORRECT, 100);

        // User cannot practice this flashcard since it was answered correctly
        $this->assertPractice($command, $flashcard->id);

        $command->expectsOutput('You have already answered this flashcard correctly. Please choose another one.');

        $this->assertDisplayProgress($command, $flashcard, FlashcardStatus::CORRECT, 100);

        $this->assertPractice($command);

        $this->assertMainMenu($command);
    }

    public function testPracticeFlashcardsWhenAlreadyAnsweredCorrectlyByOtherUser(): void
    {
        // A flashcard answered correctly by another user
        $flashcardProgress = FlashcardProgress::factory()->create([
            'username' => 'thomas',
            'status' => FlashcardStatus::CORRECT,
        ]);

        $flashcard = $flashcardProgress->flashcard;

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::PRACTICE);

        $this->assertDisplayProgress($command, $flashcard);

        // User can practice this flashcard since it was answered correctly
        // by another user, so for this user it is not answered yet
        $this->assertPractice($command, $flashcard->id);

        $command->expectsQuestion('Enter your answer', $flashcard->answer)
            ->expectsOutput('Correct answer!');

        $this->assertDisplayProgress($command, $flashcard, FlashcardStatus::CORRECT, 100);

        $this->assertPractice($command);

        $this->assertMainMenu($command);
    }

    public function testPracticeFlashcardsWhenNotAnswered(): void
    {
        // A flashcard with no progress
        $flashcard = Flashcard::factory()->create();

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::PRACTICE);

        $this->assertDisplayProgress($command, $flashcard);

        // User can practice this flashcard since they never answered it,
        // and with correct answer it will be marked as correct
        $this->assertPractice($command, $flashcard->id);

        $command->expectsQuestion('Enter your answer', $flashcard->answer)
            ->expectsOutput('Correct answer!');

        $this->assertDisplayProgress($command, $flashcard, FlashcardStatus::CORRECT, 100);

        $this->assertPractice($command);

        $this->assertMainMenu($command);
    }

    public function testPracticeFlashcardsWhenAnsweredIncorrectly(): void
    {
        // A flashcard with no progress
        $flashcard = Flashcard::factory()->create();

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::PRACTICE);

        $this->assertDisplayProgress($command, $flashcard);

        // User can practice this flashcard since they never answered it,
        // and with wrong answer it will be marked as incorrect
        $this->assertPractice($command, $flashcard->id);

        $command->expectsQuestion('Enter your answer', $flashcard->answer.'Wrong answer')
            ->expectsOutput('Incorrect answer!');

        $this->assertDisplayProgress($command, $flashcard, FlashcardStatus::INCORRECT);

        // User can practice the same flashcard again if they answered incorrectly
        $this->assertPractice($command, $flashcard->id);

        $command->expectsQuestion('Enter your answer', $flashcard->answer)
            ->expectsOutput('Correct answer!');

        $this->assertDisplayProgress($command, $flashcard, FlashcardStatus::CORRECT, 100);

        $this->assertPractice($command);

        $this->assertMainMenu($command);
    }

    public function testDisplayStatsWhenEmpty(): void
    {
        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::STATS);

        $this->assertStats($command);

        $this->assertMainMenu($command);
    }

    public function testDisplayStats(): void
    {
        // A flashcard with no progress
        Flashcard::factory()->create();

        // A flashcard with correct answer from the current user
        FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::CORRECT,
        ]);

        // A flashcard with correct answer from the current user
        FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::INCORRECT,
        ]);

        // A flashcard with correct answer from different user
        FlashcardProgress::factory()->create([
            'username' => 'thomas',
            'status' => FlashcardStatus::CORRECT,
        ]);

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::STATS);

        // 4 flashcards
        // 1 correct (only current user answer counts)
        // 1 incorrect
        // 2 unanswered (because other user answered correctly)
        $this->assertStats($command, 4, 50, 25);

        $this->assertMainMenu($command);
    }

    public function testResetProgress(): void
    {
        // A flashcard with no progress
        Flashcard::factory()->create();

        // A flashcard with correct answer from the current user
        FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::CORRECT,
        ]);

        // A flashcard with correct answer from the current user
        FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::INCORRECT,
        ]);

        // A flashcard with correct answer from different user
        FlashcardProgress::factory()->create([
            'username' => 'thomas',
            'status' => FlashcardStatus::CORRECT,
        ]);

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::RESET);

        $command
            ->expectsConfirmation('Are you sure you want to reset all progress? This action cannot be undone.', 'yes')
            ->expectsOutput('All progress has been reset.');

        $this->assertMainMenu($command);

        // We have to run the command before we can assert the database,
        // because the command is not run until test destructs
        $command->run();

        $this->assertDatabaseMissing('flashcard_progress', [
            'username' => 'payam',
        ]);

        $this->assertDatabaseHas('flashcard_progress', [
            'username' => 'thomas',
        ]);
    }

    public function testResetProgressWhenCanceled(): void
    {
        // A flashcard with no progress
        Flashcard::factory()->create();

        // A flashcard with correct answer from the current user
        FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::CORRECT,
        ]);

        // A flashcard with correct answer from the current user
        FlashcardProgress::factory()->create([
            'username' => 'payam',
            'status' => FlashcardStatus::INCORRECT,
        ]);

        // A flashcard with correct answer from different user
        FlashcardProgress::factory()->create([
            'username' => 'thomas',
            'status' => FlashcardStatus::CORRECT,
        ]);

        $command = $this->getCommand();

        $this->assertAskUsername($command);

        $this->assertMainMenu($command, MainMenu::RESET);

        $command->expectsConfirmation(
            'Are you sure you want to reset all progress? This action cannot be undone.',
        );

        $this->assertMainMenu($command);

        // We have to run the command before we can assert the database,
        // because the command is not run until test destructs
        $command->run();

        $this->assertDatabaseCount('flashcard_progress', 3);
    }

    public function testExit(): void
    {
        $command = $this->getCommand();

        $this->assertAskUsername($command);

        // The default choice is exit
        $this->assertMainMenu($command);
    }

    /**
     * Get the artisan command to run.
     */
    private function getCommand(): PendingCommand
    {
        return $this->artisan('flashcard:interactive');
    }

    /**
     * Assert the ask username question.
     */
    private function assertAskUsername(PendingCommand $command): void
    {
        $command->expectsQuestion('Please enter your name to continue', 'Payam');
    }

    /**
     * Assert the main menu.
     */
    private function assertMainMenu(PendingCommand $command, MainMenu $choice = MainMenu::EXIT): void
    {
        $command->expectsChoice(
            'Main menu',
            $choice->getLabel(),
            MainMenu::toArray(),
        );
    }

    /**
     * Assert the display progress.
     */
    private function assertDisplayProgress(
        PendingCommand $command,
        Flashcard $flashcard,
        FlashcardStatus $status = FlashcardStatus::NOT_ANSWERED,
        float $correctPercentage = 0,
    ): void {
        $command
            ->expectsOutput('Practice Progress:')
            ->expectsTable(
                ['ID', 'Question', 'Status'],
                [
                    [
                        $flashcard->id,
                        $flashcard->question,
                        $status->getLabel(),
                    ],
                ],
                'box',
            )
            ->expectsOutput("Correct Percentage: {$correctPercentage}%");
    }

    /**
     * Assert the practice menu.
     */
    private function assertPractice(PendingCommand $command, int $id = 0): void
    {
        $command->expectsQuestion('Enter the ID of the flashcard you want to practice (or enter 0 to exit)', $id);
    }

    /**
     * Assert the stats table.
     */
    private function assertStats(
        PendingCommand $command,
        int $totalFlashcards = 0,
        float $answeredPercentage = 0,
        float $correctPercentage = 0,
    ): void {
        $command
            ->expectsOutput('Stats:')
            ->expectsTable(
                ['Total questions', 'Answered %', 'Correct %'],
                [
                    [
                        'Total Questions' => $totalFlashcards,
                        'Answered %' => "{$answeredPercentage}%",
                        'Correct %' => "{$correctPercentage}%",
                    ],
                ],
                'box',
            );
    }
}
