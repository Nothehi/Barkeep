import { ArrowRight, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { createRuleReference, deleteRuleReference } from '../api';
import { useRuleForm } from '../hooks/use-rule-form';
import type { RuleSetScope } from '../hooks/use-rule-scope';
import { emptyReferenceInput } from '../schemas/game-rules';
import type {
    GameRule,
    ReferenceType,
    RuleOptions,
    RuleReference,
} from '../types/game-rules';
import OptionSelect from './option-select';
import { ReferenceTypeBadge } from './status-badges';

type RuleReferenceListProps = {
    rule: GameRule;
    rules: GameRule[];
    references: RuleReference[];
    referencedBy: RuleReference[];
    options: RuleOptions;
    scope: RuleSetScope;
    canEdit: boolean;
};

/**
 * How this rule relates to the others, in both directions.
 *
 * The second list is the one that earns the screen. "What breaks if I change this?" is the question a
 * designer asks before editing a rule, and it is answered by what points *at* it — which is a fact neither
 * rule holds on its own.
 *
 * A reference that would close a loop among the directed kinds is refused by the server, with the message on
 * the picker. Not prevented here: working out which rules would cycle needs the whole graph, and a refusal
 * that explains itself teaches more than a shortened list.
 */
export default function RuleReferenceList({
    rule,
    rules,
    references,
    referencedBy,
    options,
    scope,
    canEdit,
}: RuleReferenceListProps) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const form = useRuleForm({
        initial: emptyReferenceInput,
        validate: (input) => ({
            referenced_rule_id:
                input.referenced_rule_id === ''
                    ? t('Choose the rule this one relates to.')
                    : undefined,
        }),
        perform: (input, mutation) =>
            createRuleReference(
                { ...scope, gameRule: rule.id },
                input,
                mutation,
            ),
        onSuccess: () => setOpen(false),
    });

    return (
        <section className="space-y-3">
            <div className="flex items-center justify-between gap-2">
                <h3 className="text-sm font-medium">{t('Related rules')}</h3>

                {canEdit && (
                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button
                                size="sm"
                                variant="outline"
                                data-test="add-reference"
                            >
                                <Plus className="size-4" />
                                {t('Add reference')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('How does this relate?')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(
                                        'Writing this down is what lets the app notice a contradiction before a playtester does.',
                                    )}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="reference-type">
                                        {t('This rule')}
                                    </Label>
                                    <OptionSelect
                                        id="reference-type"
                                        value={form.input.reference_type}
                                        options={options.reference_types}
                                        onChange={(value) =>
                                            form.setField(
                                                'reference_type',
                                                value as ReferenceType,
                                            )
                                        }
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="reference-rule">
                                        {t('This one')}
                                    </Label>
                                    <OptionSelect
                                        id="reference-rule"
                                        value={form.input.referenced_rule_id}
                                        options={rules
                                            .filter(
                                                (candidate) =>
                                                    candidate.id !== rule.id,
                                            )
                                            .map((candidate) => ({
                                                value: candidate.id,
                                                label: candidate.name,
                                            }))}
                                        onChange={(value) =>
                                            form.setField(
                                                'referenced_rule_id',
                                                value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={form.errors.referenced_rule_id}
                                    />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    variant="ghost"
                                    onClick={() => setOpen(false)}
                                    disabled={form.processing}
                                >
                                    {t('Cancel')}
                                </Button>

                                <Button
                                    onClick={form.submit}
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    {t('Add reference')}
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>

            {references.length === 0 && referencedBy.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    {t('This rule stands on its own.')}
                </p>
            ) : (
                <div className="space-y-3">
                    {references.length > 0 && (
                        <ul className="space-y-2">
                            {references.map((reference) => (
                                <li
                                    key={reference.id}
                                    className="flex flex-wrap items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                >
                                    <ReferenceTypeBadge
                                        type={reference.reference_type}
                                        label={reference.reference_type_label}
                                        isDirected={reference.is_directed}
                                    />

                                    <ArrowRight className="size-4 text-muted-foreground rtl:rotate-180" />

                                    <span dir="auto">
                                        {reference.referenced_rule_name}
                                    </span>

                                    {canEdit && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="ms-auto"
                                            aria-label={t('Remove reference')}
                                            onClick={() =>
                                                deleteRuleReference({
                                                    ...scope,
                                                    gameRule: rule.id,
                                                    reference: reference.id,
                                                })
                                            }
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    {referencedBy.length > 0 && (
                        <div>
                            <p className="text-xs font-medium text-muted-foreground">
                                {t('What would break if this changed')}
                            </p>

                            <ul className="mt-1 space-y-1">
                                {referencedBy.map((reference) => (
                                    <li
                                        key={reference.id}
                                        className="flex flex-wrap items-center gap-2 text-sm"
                                    >
                                        <span dir="auto">
                                            {reference.rule_name}
                                        </span>

                                        <ReferenceTypeBadge
                                            type={reference.reference_type}
                                            label={
                                                reference.reference_type_label
                                            }
                                            isDirected={reference.is_directed}
                                        />

                                        <span className="text-muted-foreground">
                                            {t('this rule')}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>
            )}
        </section>
    );
}
