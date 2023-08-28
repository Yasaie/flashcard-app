<?php

namespace App\Console\Commands;

use App\Enums\FlashcardStatus;
use App\Enums\MainMenu;
use App\Models\Flashcard;
use App\Models\FlashcardProgress;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

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
        // We will convert the username to lowercase to avoid case sensitivity issues
        $this->username = strtolower($this->askRequired('Please enter your name to continue'));
    }

    /**
     * Display the main menu and handle user input.
     */
    private function displayMainMenu(): void
    {
        while (true) {
            $choice = $this->choice('Main menu', MainMenu::toArray(), 6);

            switch ($choice) {
                case MainMenu::CREATE_FLASHCARD->getLabel():
                    $this->createFlashcard();
                    break;
                case MainMenu::LIST_ALL_FLASHCARDS->getLabel():
                    $this->listFlashcards();
                    break;
                case MainMenu::PRACTICE->getLabel():
                    $this->practiceFlashcards();
                    break;
                case MainMenu::STATS->getLabel():
                    $this->displayStats();
                    break;
                case MainMenu::RESET->getLabel():
                    $this->resetProgress();
                    break;
                case MainMenu::DELETE_FLASHCARD->getLabel():
                    $this->deleteFlashcard();
                    break;
                case MainMenu::EXIT->getLabel():
                    return; // Exit the program
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

            // We will convert the answer to lowercase to avoid case sensitivity issues
            $isCorrect = strtolower($flashcard->answer) === strtolower($userAnswer);

            // We store the progress in new record if it doesn't exist, or update the existing one
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

        $tableRows = $flashcards
            ->map(fn ($flashcard) => [
                $flashcard->id,
                $flashcard->question,
                $flashcard->userStatus($this->username)->getLabel(),
            ]);

        $correctAnswers = Flashcard::query()
            ->whereHas('progress', fn (Builder $query) => $query
                ->where('username', $this->username)
                ->where('status', FlashcardStatus::CORRECT)
            )
            ->count();

        $correctPercentage = $this->getPercentage($correctAnswers, $flashcards->count());

        $this->info('Practice Progress:');

        $this->table($tableHeaders, $tableRows, 'box');

        $this->line("Correct Percentage: {$correctPercentage}%");
    }

    /**
     * Display the statistics.
     */
    private function displayStats(): void
    {
        $totalFlashcards = Flashcard::count();

        $answeredQuestions = Flashcard::query()
            ->whereHas('progress', fn (Builder $query) => $query
                ->where('username', $this->username)
                ->whereNot('status', FlashcardStatus::NOT_ANSWERED)
            )
            ->count();

        $correctAnswers = Flashcard::query()
            ->whereHas('progress', fn (Builder $query) => $query
                ->where('username', $this->username)
                ->where('status', FlashcardStatus::CORRECT)
            )
            ->count();

        $answeredPercentage = $this->getPercentage($answeredQuestions, $totalFlashcards);
        $correctPercentage = $this->getPercentage($correctAnswers, $totalFlashcards);

        $tableHeaders = ['Total questions', 'Answered %', 'Correct %'];

        // Table only has one row with the stats
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

    private function deleteFlashcard(): void
    {
        $this->info('');

        $this->table(
            ['ID', 'Question'],
            Flashcard::get('id', 'question'),
            'box',
        );

        $flashcardId = $this->ask('Please choose a flashcard by ID to delete');

        $flashcard = Flashcard::find($flashcardId);

        if (! $flashcard) {
            $this->error('Flashcard not found please try another ID.');

            return;
        }

        $flashcard->delete();
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

    /**
     * Get the percentage of two numbers.
     */
    private function getPercentage(float $first, float $second): float
    {
        // We return zero to avoid division by zero error
        return $first ? round(($first / $second) * 100, 2) : 0;
    }
}
