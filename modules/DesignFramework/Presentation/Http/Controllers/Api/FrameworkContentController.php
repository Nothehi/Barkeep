<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\DesignFramework\Application\Queries\GetFrameworkPhases;
use Modules\DesignFramework\Application\Queries\GetPhaseChecklists;
use Modules\DesignFramework\Application\Queries\GetPhaseCriteria;
use Modules\DesignFramework\Application\Queries\GetPhasePractices;
use Modules\DesignFramework\Application\Queries\GetPhasePrinciples;
use Modules\DesignFramework\Application\Queries\GetPhasePrompts;
use Modules\DesignFramework\Domain\Models\DesignPhaseDefinition;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Resources\ChecklistResource;
use Modules\DesignFramework\Presentation\Http\Resources\CriterionResource;
use Modules\DesignFramework\Presentation\Http\Resources\PhaseResource;
use Modules\DesignFramework\Presentation\Http\Resources\PracticeResource;
use Modules\DesignFramework\Presentation\Http\Resources\PrincipleResource;
use Modules\DesignFramework\Presentation\Http\Resources\PromptResource;

/**
 * Reading one edition's content.
 *
 * Six read endpoints in one controller, because they are six answers to the same question — "what
 * does this version of the methodology say?" — and each is three lines. Splitting them into six
 * classes would spread one authorization decision across six files.
 *
 * That decision is `viewVersion`, and it is the whole of the access control here: a published version
 * inside a published framework is readable by every signed in account, and a draft is visible only to
 * framework administrators. Whether *unpublished content* is included follows from the same answer,
 * because the only caller who should see a half-written criterion is the person writing it.
 *
 * The lists are ordered by phase and then by position, with phase-less content first, so a client can
 * render a flat response as a hierarchy without sorting anything.
 */
class FrameworkContentController extends Controller
{
    /**
     * The edition's stages.
     */
    public function phases(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetFrameworkPhases $getPhases,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersion', $version);

        return PhaseResource::collection($getPhases->handle($version, $this->mayAuthor($version)));
    }

    /**
     * One stage of the edition.
     */
    public function phase(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        DesignPhaseDefinition $phase,
    ): PhaseResource {
        Gate::authorize('viewVersion', $version);

        return PhaseResource::make($phase);
    }

    /**
     * The edition's design rules.
     */
    public function principles(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetPhasePrinciples $getPrinciples,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersion', $version);

        return PrincipleResource::collection(
            $getPrinciples->handle($version, null, $this->mayAuthor($version)),
        );
    }

    /**
     * The edition's assessment questions.
     */
    public function criteria(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetPhaseCriteria $getCriteria,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersion', $version);

        return CriterionResource::collection(
            $getCriteria->handle($version, null, $this->mayAuthor($version)),
        );
    }

    /**
     * The edition's activities.
     */
    public function practices(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetPhasePractices $getPractices,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersion', $version);

        return PracticeResource::collection(
            $getPractices->handle($version, null, $this->mayAuthor($version)),
        );
    }

    /**
     * The edition's thinking questions.
     */
    public function prompts(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetPhasePrompts $getPrompts,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersion', $version);

        return PromptResource::collection(
            $getPrompts->handle($version, null, $this->mayAuthor($version)),
        );
    }

    /**
     * The edition's readiness gates, with their requirements.
     */
    public function checklists(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetPhaseChecklists $getChecklists,
    ): AnonymousResourceCollection {
        Gate::authorize('viewVersion', $version);

        return ChecklistResource::collection(
            $getChecklists->handle($version, null, $this->mayAuthor($version)),
        );
    }

    /**
     * Whether the caller is authoring this edition, and should see unfinished content.
     *
     * Asked of the policy, so "who administers methodology" has one definition. Note that
     * `updateVersion` is false for a published version even for an administrator — which is correct
     * here too: a published edition has no draft content anybody needs to see.
     */
    private function mayAuthor(FrameworkVersion $version): bool
    {
        return Gate::allows('updateVersion', $version);
    }
}
