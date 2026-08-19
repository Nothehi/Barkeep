<?php

namespace Modules\GameEconomy\Infrastructure\Calculations;

use Modules\GameEconomy\Domain\Enums\BalanceEntityType;
use Modules\GameEconomy\Domain\Models\BalanceSnapshot;
use Modules\GameEconomy\Domain\ValueObjects\FieldChange;
use Modules\GameEconomy\Domain\ValueObjects\Quantity;
use Modules\GameEconomy\Domain\ValueObjects\SnapshotChange;
use Modules\GameEconomy\Domain\ValueObjects\SnapshotComparison;

/**
 * What changed between two frozen configurations.
 *
 * Reads the two payloads and nothing else. It never touches the live tables,
 * which is what makes a comparison of two year-old snapshots mean the same thing
 * today as it did when they were taken — and what stops a diff from being
 * quietly rewritten by an edit made this morning.
 *
 * ## Matching
 *
 * Records are matched by slug, so "Starting Gold: 10 → 12" survives the resource
 * being deleted and recreated. Flows and effects have no slug and are matched on
 * the pair that identifies them to a reader — the resource and the name for a
 * flow, the target for an effect — because those are what a designer would use
 * to say which one they mean.
 *
 * ## Numbers
 *
 * Amounts are compared as {@see Quantity} rather than as strings, so a value
 * stored at one scale and rewritten at another is correctly reported as
 * unchanged. They are then rendered through `label()`, so the diff reads
 * "10 → 12" rather than "10.000000 → 12.000000".
 */
final class SnapshotComparator
{
    /**
     * The fields compared on a resource, and what each is called on screen.
     *
     * A declared list rather than a loop over the payload's keys, because the
     * payload also carries ids and positions — reporting "id changed" on every
     * recreated record would bury the changes somebody actually made.
     *
     * @return array<string, string>
     */
    private function resourceFields(): array
    {
        return [
            'name' => __('Name'),
            'category' => __('Category'),
            'unit' => __('Unit'),
            'description' => __('Description'),
            'is_tradeable' => __('Tradeable'),
            'is_accumulative' => __('Accumulative'),
            'is_spendable' => __('Spendable'),
            'is_convertible' => __('Convertible'),
            'min_value' => __('Minimum'),
            'max_value' => __('Maximum'),
            'starting_value' => __('Starting value'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function flowFields(): array
    {
        return [
            'flow_type' => __('Flow type'),
            'amount' => __('Amount'),
            'condition' => __('Condition'),
            'description' => __('Description'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function actionFields(): array
    {
        return [
            'name' => __('Name'),
            'description' => __('Description'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function lineFields(): array
    {
        return [
            'amount' => __('Amount'),
            'is_variable' => __('Variable'),
            'min_amount' => __('Minimum'),
            'max_amount' => __('Maximum'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function effectFields(): array
    {
        return [
            'effect_type' => __('Effect type'),
            'value' => __('Value'),
            'description' => __('Description'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function variableFields(): array
    {
        return [
            'name' => __('Name'),
            'value' => __('Value'),
            'unit' => __('Unit'),
            'min_value' => __('Minimum'),
            'max_value' => __('Maximum'),
            'step' => __('Step'),
            'category' => __('Category'),
            'description' => __('Description'),
        ];
    }

    /**
     * Diff two snapshots, earlier first.
     *
     * Direction is fixed rather than chosen by the caller, so "+2" always means
     * the number went up. A comparison that let the order be reversed would make
     * every sign in the result ambiguous.
     */
    public function compare(BalanceSnapshot $from, BalanceSnapshot $to): SnapshotComparison
    {
        $before = $from->snapshot_data;
        $after = $to->snapshot_data;

        $actions = $this->diff(
            BalanceEntityType::Action,
            $this->keyBy($before['actions'] ?? [], 'slug'),
            $this->keyBy($after['actions'] ?? [], 'slug'),
            $this->actionFields(),
        );

        return new SnapshotComparison(
            fromSnapshotId: $from->getKey(),
            fromSnapshotName: $from->name,
            toSnapshotId: $to->getKey(),
            toSnapshotName: $to->name,
            resources: $this->diff(
                BalanceEntityType::Resource,
                $this->keyBy($before['resources'] ?? [], 'slug'),
                $this->keyBy($after['resources'] ?? [], 'slug'),
                $this->resourceFields(),
            ),
            flows: $this->diff(
                BalanceEntityType::Flow,
                $this->keyFlows($before['flows'] ?? []),
                $this->keyFlows($after['flows'] ?? []),
                $this->flowFields(),
            ),
            actions: $actions,
            costs: $this->diff(
                BalanceEntityType::Cost,
                $this->keyLines($before['actions'] ?? [], 'costs'),
                $this->keyLines($after['actions'] ?? [], 'costs'),
                $this->lineFields(),
            ),
            rewards: $this->diff(
                BalanceEntityType::Reward,
                $this->keyLines($before['actions'] ?? [], 'rewards'),
                $this->keyLines($after['actions'] ?? [], 'rewards'),
                $this->lineFields(),
            ),
            effects: $this->diff(
                BalanceEntityType::Effect,
                $this->keyEffects($before['actions'] ?? []),
                $this->keyEffects($after['actions'] ?? []),
                $this->effectFields(),
            ),
            variables: $this->diff(
                BalanceEntityType::Variable,
                $this->keyBy($before['variables'] ?? [], 'slug'),
                $this->keyBy($after['variables'] ?? [], 'slug'),
                $this->variableFields(),
            ),
        );
    }

    /**
     * Compare two keyed sets of records.
     *
     * Removals are reported before additions and additions before changes, which
     * is the order somebody reads a diff in: what is gone, what is new, and then
     * what moved.
     *
     * @param  array<string, array{label: string, data: array<string, mixed>}>  $before
     * @param  array<string, array{label: string, data: array<string, mixed>}>  $after
     * @param  array<string, string>  $fields
     * @return list<SnapshotChange>
     */
    private function diff(BalanceEntityType $entityType, array $before, array $after, array $fields): array
    {
        $changes = [];

        foreach ($before as $key => $entry) {
            if (! array_key_exists($key, $after)) {
                $changes[] = SnapshotChange::removed($entityType, $key, $entry['label']);
            }
        }

        foreach ($after as $key => $entry) {
            if (! array_key_exists($key, $before)) {
                $changes[] = SnapshotChange::added($entityType, $key, $entry['label']);
            }
        }

        foreach ($after as $key => $entry) {
            if (! array_key_exists($key, $before)) {
                continue;
            }

            $moved = $this->changedFields($before[$key]['data'], $entry['data'], $fields);

            if ($moved !== []) {
                $changes[] = SnapshotChange::changed($entityType, $key, $entry['label'], $moved);
            }
        }

        return $changes;
    }

    /**
     * The fields on one record that read differently.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  array<string, string>  $fields
     * @return list<FieldChange>
     */
    private function changedFields(array $before, array $after, array $fields): array
    {
        $moved = [];

        foreach ($fields as $field => $label) {
            $change = new FieldChange(
                field: $field,
                label: $label,
                before: $this->render($before[$field] ?? null),
                after: $this->render($after[$field] ?? null),
            );

            if ($change->isDifferent()) {
                $moved[] = $change;
            }
        }

        return $moved;
    }

    /**
     * Render one stored value as the string a diff shows.
     *
     * Amounts go through {@see Quantity} so that two representations of the same
     * number compare equal, and are then trimmed so the diff reads the way the
     * designer typed it.
     */
    private function render(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? __('Yes') : __('No');
        }

        if (is_int($value) || is_float($value)) {
            return Quantity::from($value)->label();
        }

        if (is_string($value) && Quantity::isValid($value)) {
            return Quantity::from($value)->label();
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Key a list of records by one of their own fields.
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array{label: string, data: array<string, mixed>}>
     */
    private function keyBy(array $records, string $field): array
    {
        $keyed = [];

        foreach ($records as $record) {
            $key = (string) ($record[$field] ?? $record['id'] ?? '');

            if ($key === '') {
                continue;
            }

            $keyed[$key] = [
                'label' => (string) ($record['name'] ?? $key),
                'data' => $record,
            ];
        }

        return $keyed;
    }

    /**
     * Key flows by the resource they move and what they are called.
     *
     * A flow has no slug, and the pair a designer would use to say which one they
     * mean is "the wood harvest" — the resource and the name.
     *
     * @param  list<array<string, mixed>>  $flows
     * @return array<string, array{label: string, data: array<string, mixed>}>
     */
    private function keyFlows(array $flows): array
    {
        $keyed = [];

        foreach ($flows as $flow) {
            $key = ($flow['resource_slug'] ?? '?').'::'.($flow['name'] ?? '');

            $keyed[$key] = [
                'label' => trim(($flow['resource_name'] ?? '').' — '.($flow['name'] ?? ''), ' —'),
                'data' => $flow,
            ];
        }

        return $keyed;
    }

    /**
     * Key an action's cost or reward lines by the action and the resource.
     *
     * @param  list<array<string, mixed>>  $actions
     * @return array<string, array{label: string, data: array<string, mixed>}>
     */
    private function keyLines(array $actions, string $group): array
    {
        $keyed = [];

        foreach ($actions as $action) {
            foreach ($action[$group] ?? [] as $line) {
                $key = ($action['slug'] ?? '?').'::'.($line['resource_slug'] ?? '?');

                $keyed[$key] = [
                    'label' => trim(($action['name'] ?? '').' — '.($line['resource_name'] ?? ''), ' —'),
                    'data' => $line,
                ];
            }
        }

        return $keyed;
    }

    /**
     * Key an action's effects by the action and what the effect targets.
     *
     * @param  list<array<string, mixed>>  $actions
     * @return array<string, array{label: string, data: array<string, mixed>}>
     */
    private function keyEffects(array $actions): array
    {
        $keyed = [];

        foreach ($actions as $action) {
            foreach ($action['effects'] ?? [] as $effect) {
                $key = ($action['slug'] ?? '?').'::'.($effect['target'] ?? '?');

                $keyed[$key] = [
                    'label' => trim(($action['name'] ?? '').' — '.($effect['target'] ?? ''), ' —'),
                    'data' => $effect,
                ];
            }
        }

        return $keyed;
    }
}
