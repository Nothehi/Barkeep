<?php

namespace Modules\DesignFramework\Domain\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DesignFramework\Infrastructure\Persistence\Eloquent\Factories\DesignPrincipleFactory;

/**
 * A design rule a methodology asserts.
 *
 * "Every decision should have meaningful consequences." "Players should
 * understand why they won or lost." "Complexity must produce meaningful depth."
 *
 * Principles are the only content type a designer does nothing *with*. They are
 * not evaluated, completed or answered — there is no `PrincipleCompletion`, and
 * adding one would be a category error. A principle is read, held in mind, and
 * argued with; it shapes how the criteria beside it are answered.
 *
 * That is also why they carry no weight in progress. A framework whose progress
 * bar advanced when you ticked "I have read this principle" would be measuring
 * reading rather than designing.
 *
 * @property string|null $description
 */
#[Fillable(['title', 'description'])]
class DesignPrinciple extends PhaseContent
{
    /** @use HasFactory<DesignPrincipleFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): DesignPrincipleFactory
    {
        return DesignPrincipleFactory::new();
    }
}
