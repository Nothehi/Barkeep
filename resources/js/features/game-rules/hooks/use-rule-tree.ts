import { useMemo } from 'react';
import type { GamePhase, GameRule } from '../types/game-rules';

export type TreeNode<T> = {
    record: T;
    depth: number;
    children: TreeNode<T>[];
};

/**
 * Assemble a flat list of rules into the tree the rulebook actually is.
 *
 * The server sends rules flat, at every depth, in reading order. That is deliberate on both sides: one query
 * rather than one per level, and — more importantly — a cycle in the data cannot make a *relation* recurse
 * forever if there is no nested relation to recurse through.
 *
 * Which leaves the recursion here, so this is where the guard lives. A node is only ever visited once, so a
 * loop that survived the server's refusal and the validator's report still cannot hang the browser: the
 * rules caught in it simply come out at the top level, which is also where somebody will notice them.
 *
 * Anything whose parent is not in the list is treated as a root, for the same reason. A rule pointing at a
 * parent that was filtered out belongs somewhere visible rather than nowhere.
 */
export function useRuleTree(rules: GameRule[]): TreeNode<GameRule>[] {
    return useMemo(
        () => buildTree(rules, (rule) => rule.parent_rule_id),
        [rules],
    );
}

/**
 * The same assembly for phases, whose nesting works the same way.
 */
export function usePhaseTree(phases: GamePhase[]): TreeNode<GamePhase>[] {
    return useMemo(
        () => buildTree(phases, (phase) => phase.parent_phase_id),
        [phases],
    );
}

/**
 * Flatten a tree back into rows, keeping each row's depth.
 *
 * What a table renders from: a tree of components would need every row to know how to indent itself, and
 * this keeps the indentation a number rather than a nesting.
 */
export function flattenTree<T>(nodes: TreeNode<T>[]): TreeNode<T>[] {
    return nodes.flatMap((node) => [node, ...flattenTree(node.children)]);
}

function buildTree<T extends { id: string }>(
    records: T[],
    parentOf: (record: T) => string | null,
): TreeNode<T>[] {
    const byId = new Map(records.map((record) => [record.id, record]));
    const childrenOf = new Map<string, T[]>();
    const roots: T[] = [];

    for (const record of records) {
        const parentId = parentOf(record);

        if (parentId === null || !byId.has(parentId)) {
            roots.push(record);

            continue;
        }

        childrenOf.set(parentId, [...(childrenOf.get(parentId) ?? []), record]);
    }

    const visited = new Set<string>();

    const build = (record: T, depth: number): TreeNode<T> => {
        visited.add(record.id);

        return {
            record,
            depth,
            children: (childrenOf.get(record.id) ?? [])
                .filter((child) => !visited.has(child.id))
                .map((child) => build(child, depth + 1)),
        };
    };

    const tree = roots.map((root) => build(root, 0));

    /*
     * Anything a loop kept out of the tree is shown at the top level rather than
     * dropped. A rule that has gone missing from the screen is a worse bug than
     * one drawn in the wrong place, and the validator is already saying why.
     */
    const orphans = records
        .filter((record) => !visited.has(record.id))
        .map((record) => build(record, 0));

    return [...tree, ...orphans];
}
