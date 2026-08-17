<?php

namespace Modules\DesignFramework\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\DesignFramework\Application\Commands\CreateFramework;
use Modules\DesignFramework\Application\Commands\UpdateFramework;
use Modules\DesignFramework\Application\Queries\GetFrameworks;
use Modules\DesignFramework\Application\Queries\GetFrameworkVersions;
use Modules\DesignFramework\Domain\Enums\FrameworkStatus;
use Modules\DesignFramework\Domain\Models\Framework;
use Modules\DesignFramework\Infrastructure\Authorization\FrameworkAdministrators;
use Modules\DesignFramework\Infrastructure\Authorization\FrameworkPermissions;
use Modules\DesignFramework\Presentation\Http\Requests\CreateFrameworkRequest;
use Modules\DesignFramework\Presentation\Http\Requests\FrameworkFilterRequest;
use Modules\DesignFramework\Presentation\Http\Requests\UpdateFrameworkRequest;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkResource;
use Modules\DesignFramework\Presentation\Http\Resources\FrameworkVersionResource;

/**
 * The framework administration screens.
 *
 * These render pages and hand off to the same application commands, form requests and queries the JSON
 * API uses, so there is one implementation of every rule and two ways to reach it.
 *
 * The screens live at `/app/frameworks` rather than under a workspace, which is the interface telling
 * the truth about the domain: a methodology is not a studio's document.
 */
class FrameworkController extends Controller
{
    /**
     * Show the platform's methodologies.
     *
     * `administration_configured` is sent so the screen can explain itself. An installation with no
     * framework administrators shows a read-only list and says why, which is far more useful to
     * whoever is setting Barkeep up than a missing button.
     */
    public function index(FrameworkFilterRequest $request, GetFrameworks $getFrameworks): Response
    {
        $filters = $request->toFilters();
        $permissions = app(FrameworkPermissions::class);
        $canCreate = $permissions->canCreate($request->user());

        return Inertia::render('frameworks/index', [
            'frameworks' => FrameworkResource::collection(
                $getFrameworks->handle($filters, includeDrafts: $canCreate),
            ),
            'filters' => [
                'search' => $filters->search,
                'status' => $filters->status?->value,
            ],
            'options' => $this->options(),
            'can' => [
                'create' => $canCreate,
            ],
            'administration_configured' => app(FrameworkAdministrators::class)->anyConfigured(),
        ]);
    }

    /**
     * Start writing a new methodology and go straight to it.
     */
    public function store(CreateFrameworkRequest $request, CreateFramework $createFramework): RedirectResponse
    {
        $framework = $createFramework->handle($request->user(), $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework created.')]);

        return to_route('frameworks.show', $framework);
    }

    /**
     * Show one methodology: what it is, and which editions exist.
     *
     * The versions come with the page because the framework detail screen is mostly about them — the
     * overview, versions and structure tabs are three views of the same fetched data, which is what
     * makes switching between them instant.
     */
    public function show(
        Request $request,
        Framework $framework,
        GetFrameworkVersions $getVersions,
    ): Response {
        Gate::authorize('view', $framework);

        return Inertia::render('frameworks/show', [
            'framework' => FrameworkResource::make($framework->loadCount('versions')),
            'versions' => FrameworkVersionResource::collection(
                $getVersions->handle($framework, includeDrafts: Gate::allows('createVersion', $framework)),
            ),
            'options' => $this->options(),
        ]);
    }

    /**
     * Change a framework's name, address or description.
     */
    public function update(
        UpdateFrameworkRequest $request,
        Framework $framework,
        UpdateFramework $updateFramework,
    ): RedirectResponse {
        $updateFramework->handle($request->user(), $framework, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Framework updated.')]);

        return back();
    }

    /**
     * The values the framework screens let somebody choose between.
     *
     * Sent from the server so that the labels, the ordering and the sets themselves have one
     * definition. A client that hard-coded them would be a second opinion waiting to go stale.
     *
     * @return array{statuses: list<array{value: string, label: string}>}
     */
    private function options(): array
    {
        return [
            'statuses' => array_map(
                fn (FrameworkStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                FrameworkStatus::cases(),
            ),
        ];
    }
}
