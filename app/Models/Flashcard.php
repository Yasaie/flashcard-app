<?php

namespace App\Models;

use App\Enums\FlashcardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flashcard extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'question',
        'answer',
    ];

    /**
     * The progress of this flashcard for a user.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(FlashcardProgress::class);
    }

    /**
     * Get the status of this flashcard for a user.
     */
    public function userStatus(string $username): FlashcardStatus
    {
        $progressModel = $this->relationLoaded('progress')
            ? $this->progress
            : $this->progress();

        $progress = $progressModel->firstWhere('username', $username);

        return $progress ? $progress->status : FlashcardStatus::NOT_ANSWERED;
    }
}
