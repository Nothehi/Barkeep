<?php

namespace Modules\DesignFramework\Application\Commands;

use Illuminate\Support\Facades\DB;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Events\FrameworkPublished;
use Modules\DesignFramework\Domain\Exceptions\InvalidFrameworkTransition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\Identity\Domain\Models\User;

/**
 * Make a framework visible to every designer on the platform.
 *
 * Publishing the framework and publishing a version are two different acts, and the
 * order between them is deliberately not enforced. This one says "this methodology
 * exists and is ours"; {@see PublishFrameworkVersion} says "there is an edition of it
 * a game can follow". An author who publishes the framework first has an announced
 * methodology with nothing to adopt yet, which is a perfectly reasonable state to be
 * in for the week it takes to finish v1.
 *
 * What publishing the framework *does* freeze is the framework's own record: its
 * name, its address, its description. Those are what games and prose cite.
 *
 * The move is decided under a row lock and against the status read inside it, not
 * against whatever the caller was looking at. Two people pressing "Publish" and
 * "Archive" at the same moment therefore produce one winner and one honest refusal,
 * instead of a last-write-wins result where the losing action reports success.
 */
final class PublishFramework
{
    public function handle(User $actor, Framework $framework): Framework
    {
        $publishedAt = now()->toImmutable();

        DB::transaction(function () use ($framework): void {
            $fresh = Framework::query()
                ->whereKey($framework->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $fresh->status->canTransitionTo(FrameworkStatus::Published)) {
                throw InvalidFrameworkTransition::between($fresh->status, FrameworkStatus::Published);
            }

            $fresh->forceFill(['status' => FrameworkStatus::Published])->save();

            /*
             * Carry the saved row back onto the instance the caller holds, so what gets
             * rendered afterwards is the state that was written rather than the one that
             * was read before the lock.
             */
            $framework->setRawAttributes($fresh->getAttributes(), sync: true);
        });

        event(new FrameworkPublished(
            frameworkId: $framework->id,
            slug: $framework->slug,
            publishedBy: $actor->id,
            publishedAt: $publishedAt->toDateTimeImmutable(),
        ));

        return $framework;
    }
}
