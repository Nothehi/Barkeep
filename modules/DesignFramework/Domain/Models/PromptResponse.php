<?php

namespace Modules\DesignFramework\Domain\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\PromptResponseFactory;
use Modules\Identity\Domain\Models\User;

/**
 * What one designer wrote in answer to one of the framework's questions.
 *
 * The framework asks "what is your core player experience?"; this is the
 * paragraph a studio wrote about their game. Held against the game's adoption
 * rather than against the prompt, for the same reason as every other record in
 * this family: the prompt is asked of everybody following the version.
 *
 * One standing answer per prompt per game. Answering again overwrites and
 * `updated_at` records that it moved — a prompt asks what the design is now, not
 * what it used to be. A designer who wants to see how their thinking changed is
 * asking for something this table deliberately does not try to be.
 *
 * @property string $id
 * @property string $game_framework_id
 * @property string $prompt_id
 * @property string $response
 * @property string $answered_by
 * @property CarbonImmutable $answered_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read GameFramework|null $gameFramework
 * @property-read DesignPrompt|null $prompt
 * @property-read User|null $author
 */
#[Fillable(['response'])]
class PromptResponse extends Model
{
    /** @use HasFactory<PromptResponseFactory> */
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answered_at' => 'immutable_datetime',
        ];
    }

    /**
     * The adoption this answer belongs to.
     *
     * @return BelongsTo<GameFramework, $this>
     */
    public function gameFramework(): BelongsTo
    {
        return $this->belongsTo(GameFramework::class);
    }

    /**
     * The question being answered.
     *
     * @return BelongsTo<DesignPrompt, $this>
     */
    public function prompt(): BelongsTo
    {
        return $this->belongsTo(DesignPrompt::class, 'prompt_id');
    }

    /**
     * The account that wrote the answer.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    /**
     * Determine whether the answer has been rewritten since it was first given.
     *
     * Compared against `answered_at` rather than `created_at`, because answering
     * again moves the former: the question is "has this been revisited?", and the
     * timestamps are what make it answerable without a history table.
     */
    public function wasRevised(): bool
    {
        return $this->created_at !== null
            && $this->answered_at->greaterThan($this->created_at);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PromptResponseFactory
    {
        return PromptResponseFactory::new();
    }
}
