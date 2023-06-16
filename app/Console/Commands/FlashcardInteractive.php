<?php

namespace App\Console\Commands;

use App\Enums\FlashcardStatus;
use App\Models\Flashcard;
use App\Models\FlashcardProgress;
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
        $this->askUsername();

        $this->displayMainMenu();
    }

    /**
     * Ask the user for their username.
     */
    private function askUsername(): void
    {
        $this->username = strtolower($this->askRequired('Please enter your name to continue'));
    }

    /**
     * Display the main menu and handle user input.
     */
    private function displayMainMenu(): void
    {
        while (true) {
            $this->line('');
            $this->info('Main Menu:');
            $this->line('1. Create a flashcard');
            $this->line('2. List all flashcards');
            $this->line('3. Practice');
            $this->line('4. Stats');
            $this->line('5. Reset');
            $this->line('6. Exit');

            $choice = $this->ask('Please enter your choice', 6);

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
                    $this->resetProgress();
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
        Flashcard::create([
            'question' => $this->askRequired('Please enter the question'),
            'answer' => $this->askRequired('Please enter the answer'),
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

        $this->info('Flashcards:');

        $this->table(
            ['Question', 'Answer'],
            $flashcards->map(fn ($flashcard) => [$flashcard->question, $flashcard->answer]),
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

            $flashcardId = $this->ask('Enter the ID of the flashcard you want to practice (or enter 0 to exit)', 0);

            if ($flashcardId == 0) {
                break;
            }

            $flashcard = Flashcard::find($flashcardId);

            if (! $flashcard) {
                $this->error('Flashcard not found. Please enter a valid ID.');

                continue;
            }

            $status = $flashcard->userStatus($this->username);

            if ($status === FlashcardStatus::CORRECT) {
                $this->warn('You have already answered this flashcard correctly. Please choose another one.');

                continue;
            }

            $userAnswer = $this->askRequired('Enter your answer');

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

        $correctPercentage = round(($correctlyAnswered / $flashcards->count()) * 100, 2);

        $this->info('Practice Progress:');

        $this->table($tableHeaders, $tableRows, 'box');

        $this->line("Correct Percentage: {$correctPercentage}%");
    }

    /**
     * Display the statistics.
     */
    private function displayStats(): void
    {
        $flashcards = Flashcard::with('progress')->get();

        $answeredQuestions = $flashcards
            ->filter(fn ($flashcard) => $flashcard->userStatus($this->username) !== FlashcardStatus::NOT_ANSWERED);
        $correctAnswers = $flashcards
            ->filter(fn ($flashcard) => $flashcard->userStatus($this->username) === FlashcardStatus::CORRECT);

        $totalFlashcards = $flashcards->count();
        $answeredPercentage = round(($answeredQuestions->count() / $totalFlashcards) * 100, 2);
        $correctPercentage = round(($correctAnswers->count() / $totalFlashcards) * 100, 2);

        $tableHeaders = ['Total questions', 'Answered %', 'Correct %'];
        $tableRows = [
            [
                'Total Questions' => $totalFlashcards,
                'Answered %' => "{$answeredPercentage}%",
                'Correct %' => "{$correctPercentage}%",
            ],
        ];

        $this->info('Stats:');

        $this->table($tableHeaders, $tableRows, 'box');
    }

    /**
     * Reset all flashcard progress.
     */
    private function resetProgress(): void
    {
        if ($this->confirm('Are you sure you want to reset all progress? This action cannot be undone.')) {
            FlashcardProgress::whereUsername($this->username)->delete();

            $this->info('All progress has been reset.');
        }
    }

    /**
     * Ask a question and require an answer.
     */
    private function askRequired(string $question): string
    {
        do {
            $answer = $this->ask($question);

            if ($answer === null) {
                $this->error('This field cannot be empty. Please try again.');
            } elseif (strlen($answer) > 255) {
                $this->error('This field cannot be longer than 255 characters. Please try again.');
            } else {
                break;
            }
        } while (true);

        return $answer;
    }
}
