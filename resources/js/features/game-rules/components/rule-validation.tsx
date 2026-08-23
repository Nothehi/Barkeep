import { CheckCircle2 } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/lib/i18n';
import type { ValidationError } from '../types/game-rules';
import { SeverityBadge } from './status-badges';

type RuleValidationProps = {
    errors: ValidationError[];
    warnings: ValidationError[];
};

/**
 * What the validator found, errors first.
 *
 * Three pieces of text per finding, and each does a different job: the title is the heading it is filed
 * under, the message names the thing it is about, and the explanation says why the check exists at all —
 * which is the one a designer reads when they disagree with it. All three are worded by the server, because
 * the same sentences appear in the API and would otherwise be written twice.
 *
 * Nothing here is a blocker. A rule set is written over weeks and is full of these for most of that time;
 * the one place a finding stops something is activation, and only for errors.
 */
export default function RuleValidation({
    errors,
    warnings,
}: RuleValidationProps) {
    const { t } = useTranslation();

    if (errors.length === 0 && warnings.length === 0) {
        return (
            <Card>
                <CardContent className="flex items-center gap-3 px-6 py-5 text-sm text-muted-foreground">
                    <CheckCircle2 className="size-5 text-emerald-500" />
                    {t('Nothing to fix. These rules hold together.')}
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>{t('What we noticed')}</CardTitle>
            </CardHeader>

            <CardContent className="space-y-6">
                {errors.length > 0 && (
                    <FindingGroup
                        heading={t('Errors')}
                        description={t(
                            'These have to be fixed before the rules can go into play.',
                        )}
                        findings={errors}
                    />
                )}

                {warnings.length > 0 && (
                    <FindingGroup
                        heading={t('Warnings')}
                        description={t(
                            'Worth a look. None of these stops you saving or activating.',
                        )}
                        findings={warnings}
                    />
                )}
            </CardContent>
        </Card>
    );
}

function FindingGroup({
    heading,
    description,
    findings,
}: {
    heading: string;
    description: string;
    findings: ValidationError[];
}) {
    return (
        <section className="space-y-3">
            <div>
                <h3 className="text-sm font-medium">{heading}</h3>
                <p className="text-xs text-muted-foreground">{description}</p>
            </div>

            <ul className="space-y-2">
                {findings.map((finding, index) => (
                    <li
                        key={`${finding.code}-${finding.entity_id ?? index}`}
                        className="rounded-md border p-3"
                        data-test={`finding-${finding.code}`}
                    >
                        <div className="flex flex-wrap items-center gap-2">
                            <SeverityBadge
                                severity={finding.severity}
                                status={finding.severity}
                                label={finding.severity_label}
                            />

                            <span className="text-sm font-medium">
                                {finding.title}
                            </span>

                            <span className="text-xs text-muted-foreground">
                                {finding.entity_type_label}
                            </span>
                        </div>

                        <p className="mt-1.5 text-sm" dir="auto">
                            {finding.message}
                        </p>

                        <p className="mt-1 text-xs text-muted-foreground">
                            {finding.explanation}
                        </p>
                    </li>
                ))}
            </ul>
        </section>
    );
}
