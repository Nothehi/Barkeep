import { CalendarDays, Check, Hash } from 'lucide-react';
import { useLocale, useTranslation } from '@/lib/i18n';
import SectionHeading from './section-heading';

/**
 * A figure worth formatting: large enough to need a group separator, which is
 * the part that differs between the two languages.
 */
const SPECIMEN_NUMBER = 12480;

/**
 * The same interface, read from either side of the page.
 *
 * Each card is one of `config('locales.supported')`, laid out in its own
 * direction with its own calendar and digits — the date and the number are
 * formatted through `Intl` for that locale rather than written out, so a
 * language added to the config appears here without anybody remembering to
 * come back for it, and nothing on the page can claim a locale that is not
 * really on offer.
 *
 * The specimen labels are icons rather than words on purpose: a card written
 * in Persian cannot borrow a label from the English catalogue, and the reverse
 * is just as true.
 */
export default function LanguageShowcase() {
    const { t } = useTranslation();
    const { supported } = useLocale();

    const now = new Date();

    const points = [
        t(
            'One catalogue, keyed by the English sentence, so the two languages cannot drift apart.',
        ),
        t(
            'Dates, times and numbers are formatted in the calendar the reader actually uses.',
        ),
        t(
            'Right to left is a layout rather than a mirror: what should flip flips, and nothing else does.',
        ),
    ];

    return (
        <section
            id="languages"
            className="scroll-mt-16 border-y bg-muted/30 py-20 sm:py-28"
        >
            <div className="mx-auto w-full max-w-6xl px-6">
                <SectionHeading
                    eyebrow={t('Two directions')}
                    title={t('It reads the way you read')}
                    description={t(
                        'Persian is not a layer bolted on top. Every page is laid out from the side the reader starts on, and the numbers and dates arrive in their own calendar.',
                    )}
                />

                <div className="mx-auto mt-14 grid max-w-3xl gap-4 sm:grid-cols-2">
                    {supported.map((locale) => (
                        <div
                            key={locale.code}
                            dir={locale.direction}
                            lang={locale.code}
                            className="rounded-xl border bg-card p-6 text-start"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-lg font-medium">
                                    {locale.native}
                                </p>

                                <span
                                    dir="ltr"
                                    className="rounded-md border px-2 py-0.5 text-xs font-medium text-muted-foreground uppercase"
                                >
                                    {locale.direction}
                                </span>
                            </div>

                            <dl className="mt-5 space-y-3 text-sm">
                                <div className="flex items-center gap-3">
                                    <dt>
                                        <CalendarDays
                                            aria-hidden="true"
                                            className="size-4 text-muted-foreground"
                                        />
                                    </dt>

                                    <dd>
                                        {new Intl.DateTimeFormat(locale.code, {
                                            dateStyle: 'long',
                                        }).format(now)}
                                    </dd>
                                </div>

                                <div className="flex items-center gap-3">
                                    <dt>
                                        <Hash
                                            aria-hidden="true"
                                            className="size-4 text-muted-foreground"
                                        />
                                    </dt>

                                    <dd className="tabular-nums">
                                        {new Intl.NumberFormat(
                                            locale.code,
                                        ).format(SPECIMEN_NUMBER)}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    ))}
                </div>

                <ul className="mx-auto mt-10 grid max-w-4xl gap-4 md:grid-cols-3">
                    {points.map((point) => (
                        <li key={point} className="flex gap-3">
                            <Check className="mt-0.5 size-4.5 shrink-0 text-amber-600 dark:text-amber-400" />

                            <span className="text-sm leading-relaxed text-pretty text-muted-foreground">
                                {point}
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}
