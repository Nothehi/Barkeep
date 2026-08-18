import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from '@/lib/i18n';
import { useCreateWorkspace } from '../hooks/use-create-workspace';

/**
 * Opens a new workspace.
 *
 * The address is suggested from the name and stays editable. Leaving it as
 * suggested is fine — if the address is taken the server picks the next one
 * along; if it was typed by hand the server says so instead of renaming it.
 */
export default function CreateWorkspacePage() {
    const { t } = useTranslation();
    const {
        input,
        errors,
        processing,
        isValid,
        setName,
        setSlug,
        setDescription,
        submit,
    } = useCreateWorkspace();

    return (
        <>
            <Head title={t('Create workspace')} />

            <div className="mx-auto max-w-xl space-y-6 px-4 py-6">
                <Heading
                    title={t('Create a workspace')}
                    description={t(
                        'A workspace is where you and your collaborators design a game',
                    )}
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        submit();
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">{t('Name')}</Label>

                        <Input
                            id="name"
                            name="name"
                            value={input.name}
                            onChange={(event) => setName(event.target.value)}
                            placeholder={t('My Board Game Studio')}
                            autoFocus
                            required
                        />

                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">{t('Address')}</Label>

                        <Input
                            id="slug"
                            name="slug"
                            value={input.slug}
                            onChange={(event) => setSlug(event.target.value)}
                            placeholder="my-board-game-studio"
                            autoComplete="off"
                            spellCheck={false}
                        />

                        <p className="text-sm text-muted-foreground">
                            /app/workspaces/
                            {input.slug || 'my-board-game-studio'}
                        </p>

                        <InputError message={errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">
                            {t('Description')}{' '}
                            <span className="text-muted-foreground">
                                {t('(optional)')}
                            </span>
                        </Label>

                        <Textarea
                            id="description"
                            name="description"
                            value={input.description}
                            onChange={(event) =>
                                setDescription(event.target.value)
                            }
                            placeholder={t('What is this workspace for?')}
                            rows={3}
                        />

                        <InputError message={errors.description} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing || !isValid}
                        data-test="submit-create-workspace-button"
                    >
                        {processing && <Spinner />}
                        {t('Create workspace')}
                    </Button>
                </form>
            </div>
        </>
    );
}
