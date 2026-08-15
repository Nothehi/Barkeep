<?php

namespace Modules\GameDesign\Application\DTOs;

use Modules\GameDesign\Domain\Enums\DesignPhase;
use Modules\GameDesign\Domain\Enums\GameStatus;
use Modules\GameDesign\Infrastructure\Search\GameSearchTerm;

/**
 * How a games list is narrowed down.
 *
 * Every field is optional and none of them can widen the list: the workspace
 * scope is applied by the query itself and is not expressible here, so a
 * filter can only ever return fewer games than the caller could already see.
 */
final readonly class GameFilters
{
    public function __construct(
        public ?GameSearchTerm $search = null,
        public ?GameStatus $status = null,
        public ?DesignPhase $designPhase = null,
    ) {}

    /**
     * The unfiltered list.
     */
    public static function none(): self
    {
        return new self;
    }

    /**
     * Build the filters from already validated query string input.
     *
     * @param  array<string, mixed>  $input
     */
    public static function fromArray(array $input): self
    {
        return new self(
            search: GameSearchTerm::fromInput(
                isset($input['search']) ? (string) $input['search'] : null,
            ),
            status: self::enum(GameStatus::class, $input['status'] ?? null),
            designPhase: self::enum(DesignPhase::class, $input['design_phase'] ?? null),
        );
    }

    /**
     * Determine whether anything is actually being filtered on.
     */
    public function areEmpty(): bool
    {
        return $this->search === null
            && $this->status === null
            && $this->designPhase === null;
    }

    /**
     * Resolve an enum from input, treating anything unrecognised as absent.
     *
     * Requests arriving over HTTP have already been validated, so an
     * unrecognised value has been rejected before it gets here. This is for
     * every other caller: a filter is a view of a list, so the safe reading of
     * a value that means nothing is "no filter" rather than a crash.
     *
     * @template TEnum of GameStatus|DesignPhase
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    private static function enum(string $enum, mixed $value): GameStatus|DesignPhase|null
    {
        return is_string($value) && $value !== ''
            ? $enum::tryFrom($value)
            : null;
    }
}
