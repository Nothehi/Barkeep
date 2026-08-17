<?php

namespace Modules\DesignFramework\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\DesignPracticeFactory;

/**
 * Something a methodology tells a designer to go and do.
 *
 * "Write the core loop in one sentence." "Create a paper prototype." "Run a
 * two-player test." "Identify the dominant strategy."
 *
 * Practices are where this module touches the rest of the product without
 * reaching into it. "Run a two-player playtest" is an instruction here and a real
 * thing in Playtesting, and the two are deliberately not wired together: a
 * designer marks the practice complete themselves. Later, a listener on
 * `PlaytestSessionCompleted` could offer to satisfy it — an integration that
 * belongs in whichever module observes both, not in a dependency from one to the
 * other. This module does not know Playtesting exists, and an architecture test
 * keeps it that way.
 *
 * @property string|null $description
 * @property string|null $instructions
 * @property-read Collection<int, PracticeCompletion> $completions
 */
#[Fillable(['title', 'description', 'instructions'])]
class DesignPractice extends PhaseContent
{
    /** @use HasFactory<DesignPracticeFactory> */
    use HasFactory;

    /**
     * Every game that has carried this out.
     *
     * @return HasMany<PracticeCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(PracticeCompletion::class, 'practice_id');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignPracticeFactory
    {
        return DesignPracticeFactory::new();
    }
}
