<?php

namespace Modules\GameEconomy\Infrastructure\Persistence\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;

/**
 * Turns a DECIMAL column into an exact amount, and back.
 *
 * Every numeric column in this module goes through here, which is what makes
 * "no floating point anywhere" true rather than aspirational. Without it, a
 * plain attribute read hands back whatever the driver felt like producing — a
 * string on PostgreSQL, a float on SQLite — and the module's arithmetic would be
 * exact or not depending on which database somebody happened to run it against.
 *
 * Null survives the round trip. A resource with no maximum and a resource capped
 * at zero are different statements, and a cast that turned the first into the
 * second would quietly invent a limit.
 *
 * @implements CastsAttributes<Quantity, Quantity|int|float|string>
 */
final class AsQuantity implements CastsAttributes
{
    /**
     * Cast the stored value to an amount.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Quantity
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Quantity::from(is_float($value) || is_int($value) ? $value : (string) $value);
    }

    /**
     * Prepare an amount for storage, at the full column scale.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof Quantity ? $value : Quantity::from($value))->toStorage();
    }
}
