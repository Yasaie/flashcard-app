<?php

namespace App\Console\Commands;

use App\Enums\FlashcardStatus;
use App\Models\Flashcard;
use Illuminate\Console\Command;

class FlashcardInteractive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flashcard:interactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive CLI program for Flashcard practice';

    /**
     * The username of the user.
     */
    private string $username;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->username = $this->ask('Please enter your name to start');

        $this->displayMainMenu();
    }

    /**
     * Display the main menu and handle user input.
     */
    private function displayMainMenu(): void
    {
        while (true) {
            $this->line('');
            $this->line('<info>Main Menu:</info>');
            $this->line('1. Create a flashcard');
            $this->line('2. List all flashcards');
            $this->line('3. Practice');
            $this->line('4. Stats');
            $this->line('5. Reset');
            $this->line('6. Exit');

            $choice = $this->ask('Please enter your choice');

            switch ($choice) {
                case '1':
                    $this->createFlashcard();
                    break;
                case '2':
                    $this->listFlashcards();
                    break;
                case '3':
                    $this->practiceFlashcards();
                    break;
                case '4':
                    $this->displayStats();
                    break;
                case '5':
                    $this->resetFlashcards();
                    break;
                case '6':
                    return;
                default:
                    $this->error('Invalid choice. Please try again.');
            }
        }
    }

    /**
     * Create a new flashcard.
     */
    private function createFlashcard(): void
    {
        $question = $this->ask('Please enter the question');

        $answer = $this->ask('Please enter the answer');

        Flashcard::create([
            'question' => $question,
            'answer' => $answer,
        ]);

        $this->info('Flashcard created successfully.');
    }

    /**
     * List all flashcards.
     */
    private function listFlashcards(): void
    {
        $flashcards = Flashcard::all();

        if ($flashcards->isEmpty()) {
            $this->info('No flashcards found.');
            return;
        }

        $this->line('');

        $this->line('<info>Flashcards:</info>');

        $this->table(
            ['Question', 'Answer'],
            $flashcards->map(fn($flashcard) => [$flashcard->question, $flashcard->answer]),
            'box',
        );
    }

    /**
     * Practice flashcards.
     */
    private function practiceFlashcards(): void
    {
        if (! Flashcard::exists()) {
            $this->info('No flashcards found. Please create some flashcards first.');
            return;
        }

        while (true) {
            $this->line('');

            $this->displayProgress();

            $flashcardId = $this->ask('Enter the ID of the flashcard you want to practice (or enter 0 to exit)');

            if ($flashcardId == 0) {
                break;
            }

            $flashcard = Flashcard::find($flashcardId);

            if (!$flashcard) {
                $this->error('Flashcard not found. Please enter a valid ID.');
                continue;
            }

            $status = $flashcard->userStatus($this->username);

            if ($status === FlashcardStatus::CORRECT) {
                $this->warn('You have already answered this flashcard correctly. Please choose another one.');
                continue;
            }

            $userAnswer = $this->ask('Enter your answer');

            $isCorrect = strtolower($flashcard->answer) === strtolower($userAnswer);

            $flashcard->progress()->updateOrCreate(
                ['username' => $this->username],
                ['status' => $isCorrect ? FlashcardStatus::CORRECT : FlashcardStatus::INCORRECT]
            );

            if ($isCorrect) {
                $this->info('Correct answer!');
            } else {
                $this->error('Incorrect answer!');
            }
        }
    }

    /**
     * Display the practice progress and statistics.
     */
    private function displayProgress(): void
    {
        $flashcards = Flashcard::with('progress')->get();

        $tableHeaders = ['ID', 'Question', 'Status'];
        $tableRows = [];

        $correctlyAnswered = 0;

        foreach ($flashcards as $flashcard) {
            $status = $flashcard->userStatus($this->username);

            if ($status === FlashcardStatus::CORRECT) {
                $correctlyAnswered++;
            }

            $tableRows[] = [$flashcard->id, $flashcard->question, $status->title()];
        }

        $completionPercentage = ($correctlyAnswered / $flashcards->count()) * 100;

        $this->info('Practice Progress:');

        $this->table($tableHeaders, $tableRows, 'box');

        $this->line("Completion Percentage: {$completionPercentage}%");
    }

    /**
     * Display the statistics.
     */
    private function displayStats(): void
    {
        //
    }

    /**
     * Reset all flashcard progress.
     */
    private function resetFlashcards(): void
    {
        //
    }
}
