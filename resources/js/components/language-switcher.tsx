import { Check, Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale, useSetLocale, useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type LanguageSwitcherProps = {
    className?: string;
};

/**
 * Choose the language the interface is written in.
 *
 * Each language is listed in its own script — somebody who cannot read the
 * current interface has to be able to recognise their own row, and "Persian"
 * written in English is no help to a reader looking for فارسی.
 */
export default function LanguageSwitcher({ className }: LanguageSwitcherProps) {
    const { t } = useTranslation();
    const { current, supported } = useLocale();
    const setLocale = useSetLocale();

    if (supported.length < 2) {
        return null;
    }

    const active = supported.find((locale) => locale.code === current);

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="sm"
                    className={cn('gap-2', className)}
                    aria-label={t('Change language')}
                >
                    <Languages className="size-4" />
                    <span className="text-sm">{active?.native ?? current}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {supported.map((locale) => (
                    <DropdownMenuItem
                        key={locale.code}
                        onSelect={() => setLocale(locale.code)}
                        className="cursor-pointer gap-2"
                    >
                        <Check
                            className={cn(
                                'size-4',
                                locale.code === current
                                    ? 'opacity-100'
                                    : 'opacity-0',
                            )}
                        />
                        <span dir={locale.direction}>{locale.native}</span>
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
