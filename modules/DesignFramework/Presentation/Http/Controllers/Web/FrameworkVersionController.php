<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DesignFramework\Application\Commands\CreateFrameworkVersion;
use Modules\DesignFramework\Application\Commands\UpdateFrameworkVersion;
use Modules\DesignFramework\Application\Queries\GetFrameworkPhases;
use Modules\DesignFramework\Application\Queries\GetPhaseChecklists;
use Modules\DesignFramework\Application\Queries\GetPhaseCriteria;
use Modules\DesignFramework\Application\Queries\GetPhasePractices;
use Modules\DesignFramework\Application\Queries\GetPhasePrinciples;
use Modules\DesignFramework\Application\Queries\GetPhasePrompts;
use Modules\DesignFramework\Domain\Enums\FrameworkContentStatus;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Domain\Models\FrameworkVersion;
use Modules\DesignFramework\Presentation\Http\Requests\CreateFrameworkVersionRequest;
use Modules\DesignFramework\Presentation\Http\Requests\UpdateFrameworkVersionRequest;
use Modules\DesignFramework\Presentation\Http\Resources\ChecklistResource;
use Modules\DesignFramework\Presentation\Http\Resources\CriterionResource;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkResource;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkVersionResource;
use Modules\DesignFramework\Presentation\Http\Resources\PhaseResource;
use Modules\DesignFramework\Presentation\Http\Resources\PracticeResource;
use Modules\DesignFramework\Presentation\Http\Resources\PrincipleResource;
use Modules\DesignFramework\Presentation\Http\Resources\PromptResource;

/**
 * The framework builder.
 *
 * One screen, one request. The whole edition — every phase and every piece of content in it — arrives
 * together, because the builder is a hierarchy a framework author scrolls and drags through, and
 * fetching each phase's content as it opens would make the outline useless for seeing the shape of the
 * methodology.
 *
 * Six flat collections rather than a nested tree, and the client assembles them by `phase_id`. That is
 * a deliberate trade: the payload stays shallow and cacheable, reordering a phase does not invalidate
 * a nested structure, and content filed under no phase has somewhere obvious to live.
 *
 * Whether the builder is editable at all is `version.permissions.canUpdate`, which is the policy's
 * answer to both "may this account edit frameworks?" and "is this edition still a draft?". Section 34
 * asks for published versions to be read-only, and this is where the interface learns that — from the
 * server, not from comparing a status string.
 */
class FrameworkVersionController extends Controller
{
    /**
     * Open a new edition and go straight into it.
     */
    public function store(
        CreateFrameworkVersionRequest $request,
        Framework $framework,
        CreateFrameworkVersion $createVersion,
    ): RedirectResponse {
        $version = $createVersion->handle($request->user(), $framework, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version :label created.', ['label' => $version->label()])]);

        return to_route('frameworks.versions.show', [$framework, $version]);
    }

    /**
     * Show the builder for one edition.
     */
    public function show(
        Request $request,
        Framework $framework,
        FrameworkVersion $version,
        GetFrameworkPhases $getPhases,
        GetPhasePrinciples $getPrinciples,
        GetPhaseCriteria $getCriteria,
        GetPhasePractices $getPractices,
        GetPhasePrompts $getPrompts,
        GetPhaseChecklists $getChecklists,
    ): Response {
        Gate::authorize('viewVersion', $version);

        /*
         * An author sees their unfinished content; everybody else sees the methodology as published.
         * `updateVersion` is false for a published edition even for an administrator, which is right
         * here too — a frozen version has no draft content anybody needs to see.
         */
        $authoring = Gate::allows('updateVersion', $version);

        return Inertia::render('frameworks/builder', [
            'framework' => FrameworkResource::make($framework),
            'version' => FrameworkVersionResource::make($version->loadCount(['phases', 'adoptions'])),
            'phases' => PhaseResource::collection($getPhases->handle($version, $authoring)),
            'principles' => PrincipleResource::collection($getPrinciples->handle($version, null, $authoring)),
            'criteria' => CriterionResource::collection($getCriteria->handle($version, null, $authoring)),
            'practices' => PracticeResource::collection($getPractices->handle($version, null, $authoring)),
            'prompts' => PromptResource::collection($getPrompts->handle($version, null, $authoring)),
            'checklists' => ChecklistResource::collection($getChecklists->handle($version, null, $authoring)),
            'options' => [
                'content_statuses' => array_map(
                    fn (FrameworkContentStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ],
                    FrameworkContentStatus::cases(),
                ),
            ],
        ]);
    }

    /**
     * Change a draft edition's name or description.
     */
    public function update(
        UpdateFrameworkVersionRequest $request,
        Framework $framework,
        FrameworkVersion $version,
        UpdateFrameworkVersion $updateVersion,
    ): RedirectResponse {
        $updateVersion->handle($request->user(), $version, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version updated.')]);

        return back();
    }
}
