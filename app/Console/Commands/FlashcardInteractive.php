<?php

namespace App\Console\Commands;

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
     * Execute the console command.
     */
    public function handle(): void
    {
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
        );
    }

    /**
     * Practice flashcards.
     */
    private function practiceFlashcards(): void
    {
        //
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
