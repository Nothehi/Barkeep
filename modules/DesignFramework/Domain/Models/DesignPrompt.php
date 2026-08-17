<?php

namespace Modules\DesignFramework\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\DesignPromptFactory;

/**
 * A question designed to make a designer think, rather than to be scored.
 *
 * "What is the most interesting decision in your game?" "What happens if players
 * always choose the safest option?" "Where does tension come from?" "What does
 * the player learn during the first five minutes?"
 *
 * The difference from a criterion is the shape of the answer. A criterion is
 * graded — weak, needs work, good, strong — and the grade is the point. A prompt
 * is answered in prose, and there is no right answer: what it produces is a
 * paragraph the designer writes for themselves and rereads six months later when
 * the design has drifted.
 *
 * The question lives in `prompt` and the label in `title`, because they are read
 * at different moments — the title is scanned in a list, the question sits above
 * the textarea.
 *
 * @property string $prompt
 * @property-read Collection<int, PromptResponse> $responses
 */
#[Fillable(['title', 'prompt'])]
class DesignPrompt extends PhaseContent
{
    /** @use HasFactory<DesignPromptFactory> */
    use HasFactory;

    /**
     * Every game's answer to this question.
     *
     * @return HasMany<PromptResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(PromptResponse::class, 'prompt_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignPromptFactory
    {
        return DesignPromptFactory::new();
    }
}
