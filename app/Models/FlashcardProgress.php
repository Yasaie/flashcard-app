<?php

namespace App\Models;

use App\Enums\FlashcardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashcardProgress extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'flashcard_id',
        'username',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'status' => FlashcardStatus::class,
    ];

    /**
     * The flashcard that this progress belongs to.
     */
    public function flashcard(): BelongsTo
    {
        return $this->belongsTo(Flashcard::class);
    }
}
