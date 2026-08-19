<?php

namespace Modules\PrototypeIteration\Application\DTOs;

use Modules\PrototypeIteration\Domain\Enums\DesignChangeCategory;

/**
 * The validated input for recording or amending a design change.
 *
 * One shape for both, because a change is small enough that a partial update of
 * it would be more machinery than the thing itself. The category and the reason
 * are the two fields that matter: the category makes a month of changes readable
 * in aggregate, and the reason is why the record is worth keeping at all.
 *
 * The reason is required and the description is not. What changed is usually
 * plain from the title; why it changed never is, and it is the only part nobody
 * can reconstruct later.
 */
final readonly class DesignChangeData
{
    public function __construct(
        public string $title,
        public string $reason,
        public ?string $description = null,
        public DesignChangeCategory $category = DesignChangeCategory::Other,
    ) {}

    /**
     * Build the DTO from already validated request input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        $category = IterationInput::identifier($input, 'category');

        return new self(
            title: IterationInput::requiredText($input, 'title'),
            reason: IterationInput::requiredText($input, 'reason'),
            description: IterationInput::text($input, 'description'),
            category: ($category === null ? null : DesignChangeCategory::tryFrom($category)) ?? DesignChangeCategory::default(),
        );
    }
}
