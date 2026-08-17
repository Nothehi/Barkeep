<?php

namespace Modules\GameDesign\Infrastructure\Persistence\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\GameDesign\Domain\Enums\MechanicCategory;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Domain\ValueObjects\MechanicSlug;

/**
 * Every read of the mechanics vocabulary.
 *
 * Separate from `GameRepository` rather than folded into it, because the two
 * have opposite scoping stories and mixing them is how one borrows the other's
 * by accident. Every game read starts from a workspace; every mechanic read is
 * deliberately unscoped, because the vocabulary belongs to the platform.
 */
final class MechanicRepository
{
    /**
     * The whole vocabulary, in reading order.
     *
     * Ordered by category and then by name. The category order is the enum's —
     * the order a design gets built in rather than the alphabet — so it cannot
     * be expressed in SQL without restating the enum in a `CASE`, and is
     * applied here instead. The list is small and read whole; this is not the
     * query to optimise.
     *
     * @return Collection<int, Mechanic>
     */
    public function all(bool $includeArchived = false): Collection
    {
        $mechanics = Mechanic::query()
            ->when(! $includeArchived, fn ($query) => $query->available())
            ->ordered()
            ->get();

        /*
         * Sorted on one composite key rather than on two, because the category
         * half is a number and the name half is a string — and a multi-key sort
         * over a mix of the two compares them pairwise in a way that is easy to
         * get subtly wrong. Padding the position makes one string that sorts
         * correctly on its own.
         */
        return $mechanics
            ->sortBy(fn (Mechanic $mechanic): string => sprintf(
                '%02d %s',
                $mechanic->category->position(),
                mb_strtolower($mechanic->name),
            ))
            ->values();
    }

    /**
     * The terms filed under one category.
     *
     * @return Collection<int, Mechanic>
     */
    public function inCategory(MechanicCategory $category, bool $includeArchived = false): Collection
    {
        return Mechanic::query()
            ->where('category', $category)
            ->when(! $includeArchived, fn ($query) => $query->available())
            ->ordered()
            ->get();
    }

    /**
     * Find one term by its address.
     */
    public function findBySlug(MechanicSlug $slug): ?Mechanic
    {
        return Mechanic::query()->where('slug', $slug->value)->first();
    }

    /**
     * The terms named by a set of ids, keyed by id.
     *
     * Used when a game records which mechanics it claims: the ids arrive
     * together and are proved to exist together, so one query answers "are
     * these real, and are they still offered?" for the whole set.
     *
     * @param  array<int, string>  $ids
     * @return Collection<string, Mechanic>
     */
    public function findMany(array $ids, bool $includeArchived = false): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return Mechanic::query()
            ->whereKey(array_values(array_unique($ids)))
            ->when(! $includeArchived, fn ($query) => $query->available())
            ->get()
            ->keyBy(fn (Mechanic $mechanic): string => (string) $mechanic->getKey());
    }

    /**
     * Determine whether an address is already spoken for.
     *
     * Platform-wide, unlike a game address. The ignored id lets a curator save
     * a term without its own address counting against it.
     */
    public function slugExists(MechanicSlug $slug, ?string $ignoreId = null): bool
    {
        return Mechanic::query()
            ->where('slug', $slug->value)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
