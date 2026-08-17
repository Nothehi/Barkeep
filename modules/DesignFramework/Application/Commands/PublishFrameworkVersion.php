<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Events\FrameworkVersionPublished;
use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkTransition;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\Identity\Domain\Models\User;

/**
 * Freeze an edition and release it.
 *
 * The most consequential operation in the module. Before it, the version's phases,
 * principles, criteria, practices, prompts, checklists and items are a work in
 * progress that only framework administrators can see. After it they are immutable
 * and games may adopt them.
 *
 * There is no way back, and that is the invariant the whole module is built to
 * protect. Unpublishing would let the questions change underneath the answers already
 * given — a criterion reworded after a studio graded itself against it, a checklist
 * item deleted after somebody ticked it. `FrameworkStatus` has no Published → Draft
 * transition, so the refusal is structural rather than a check somebody could forget.
 *
 * `published_at` is written here as well as the status. It is derivable from the
 * status, and it is stored anyway because it is the fact a game's adoption is read
 * against: "this game follows v1, published in March" is the sentence historical
 * integrity exists to keep true.
 *
 * The move is decided under a row lock and against the status read inside it, so two
 * people publishing at the same moment produce one winner and one honest refusal.
 *
 * Note what is *not* checked: that the version has any content. A version published
 * empty is a mistake, but it is the author's mistake and it is recoverable by
 * publishing v2 — whereas a rule saying "at least one phase" would be this module
 * deciding what a methodology has to look like. The phase count travels on the event
 * so that whatever eventually announces new editions can notice.
 */
final class PublishFrameworkVersion
{
    public function handle(User $actor, FrameworkVersion $version): FrameworkVersion
    {
        $publishedAt = now()->toImmutable();

        DB::transaction(function () use ($version, $publishedAt): void {
            $fresh = FrameworkVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(FrameworkStatus::Published)) {
                throw InvalidFrameworkTransition::between($fresh->status, FrameworkStatus::Published);
            }

            $fresh->forceFill([
                'status' => FrameworkStatus::Published,
                'published_at' => $publishedAt,
            ])->save();

            /*
             * Carry the saved row back onto the instance the caller holds, so what gets
             * rendered afterwards is the state that was written rather than the one that
             * was read before the lock.
             */
            $version->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new FrameworkVersionPublished(
            frameworkVersionId: $version->id,
            frameworkId: $version->framework_id,
            versionNumber: $version->version_number,
            phaseCount: $version->phases()->count(),
            publishedBy: $actor->id,
            publishedAt: $publishedAt->toDateTimeImmutable(),
        ));

        return $version;
    }
}
