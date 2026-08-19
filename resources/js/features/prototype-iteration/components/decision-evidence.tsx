import { Link } from '@inertiajs/react';
import { EyeOff, Link2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/lib/i18n';
import playtests from '@/routes/playtests';
import type { CitedEvidence } from '../types/prototype-iteration';

type DecisionEvidenceProps = {
    evidence: CitedEvidence[];
    workspace: string;
    game: string;
};

/**
 * What a decision cites in support of itself.
 *
 * Every excerpt here was read live from the context that owns it at the moment this page was rendered — so a
 * correction to an observation shows up in every decision that cited it, and no wording on this screen is a
 * copy that can go stale.
 *
 * Clicking a citation goes to the playtest it came from, which is section 45's requirement: evidence belongs
 * to Playtesting, and the way to see it in full is to go there rather than to have it duplicated here.
 *
 * An unresolvable citation is drawn as one rather than dropped. The commonest reason is permission, not
 * deletion — somebody who can see this iteration but not the playtest — and "this cites something you cannot
 * see" is honest where a silently shorter list would read as "nothing supported this".
 */
export default function DecisionEvidence({
    evidence,
    workspace,
    game,
}: DecisionEvidenceProps) {
    const { t } = useTranslation();

    if (evidence.length === 0) {
        return (
            <p
                className="text-xs text-muted-foreground"
                data-test="evidence-empty"
            >
                {t('Nothing cited yet.')}
            </p>
        );
    }

    return (
        <ul className="space-y-2" data-test="decision-evidence">
            {evidence.map((item) => (
                <li key={item.id} className="space-y-1 border-s ps-3 text-sm">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">{item.type_label}</Badge>

                        {item.is_linkable && item.playtest_id && (
                            <Link
                                href={playtests.show.url({
                                    workspace,
                                    game,
                                    playtest: item.playtest_id,
                                })}
                                className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:underline"
                                data-test={`evidence-link-${item.id}`}
                            >
                                <Link2 className="size-3" />
                                {t('Open in playtests')}
                            </Link>
                        )}

                        {!item.is_resolved && (
                            <span
                                className="inline-flex items-center gap-1 text-xs text-muted-foreground"
                                data-test={`evidence-unresolved-${item.id}`}
                            >
                                <EyeOff className="size-3" />
                                {t('You cannot see what this cites')}
                            </span>
                        )}
                    </div>

                    {item.excerpt && (
                        <p>
                            <span dir="auto">“{item.excerpt}”</span>
                        </p>
                    )}

                    {item.attribution && (
                        <p className="text-xs text-muted-foreground" dir="auto">
                            {item.attribution}
                        </p>
                    )}

                    {item.description && (
                        <p className="text-xs text-muted-foreground" dir="auto">
                            {item.description}
                        </p>
                    )}
                </li>
            ))}
        </ul>
    );
}
