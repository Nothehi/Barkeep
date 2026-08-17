<?php

namespace Modules\GameDesign\Presentation\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\GameDesign\Application\Commands\CreateMechanic;
use Modules\GameDesign\Application\Commands\UpdateMechanic;
use Modules\GameDesign\Application\Queries\GetMechanics;
use Modules\GameDesign\Domain\Enums\MechanicCategory;
use Modules\GameDesign\Domain\Models\Mechanic;
use Modules\GameDesign\Infrastructure\Authorization\MechanicCurators;
use Modules\GameDesign\Infrastructure\Authorization\MechanicPermissions;
use Modules\GameDesign\Presentation\Http\Requests\CreateMechanicRequest;
use Modules\GameDesign\Presentation\Http\Requests\UpdateMechanicRequest;
use Modules\GameDesign\Presentation\Http\Resources\MechanicResource;

/**
 * The design vocabulary screen.
 *
 * Lives at `/app/mechanics` rather than under a workspace, which is the
 * interface telling the truth about the domain: these words are not a studio's.
 * Every signed in account reads the same list; only a curator changes it.
 *
 * Retired terms are shown to curators and hidden from everybody else, for the
 * same reason draft framework content is: a designer picking mechanics should
 * not see a word the platform has withdrawn, and the person who withdrew it
 * needs to see that they did.
 */
class MechanicController extends Controller
{
    /**
     * Show the vocabulary.
     */
    public function index(Request $request, GetMechanics $getMechanics): Response
    {
        Gate::authorize('viewAny', Mechanic::class);

        $canCurate = app(MechanicPermissions::class)->canCreate($request->user());

        return Inertia::render('mechanics/index', [
            'mechanics' => MechanicResource::collection(
                $getMechanics->handle(includeArchived: $canCurate),
            ),
            'options' => $this->options(),
            'can' => [
                'create' => $canCurate,
            ],

            /*
             * Sent so the screen can explain itself. An installation with no
             * curators configured shows a read-only vocabulary and says why,
             * which is far more useful to whoever is setting Barkeep up than a
             * missing button.
             */
            'curation_configured' => app(MechanicCurators::class)->anyConfigured(),
        ]);
    }

    /**
     * Add a term to the vocabulary.
     */
    public function store(CreateMechanicRequest $request, CreateMechanic $createMechanic): RedirectResponse
    {
        $createMechanic->handle($request->user(), $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mechanic added.')]);

        return to_route('mechanics.index');
    }

    /**
     * Change what a term is called or means.
     */
    public function update(
        UpdateMechanicRequest $request,
        Mechanic $mechanic,
        UpdateMechanic $updateMechanic,
    ): RedirectResponse {
        $updateMechanic->handle($request->user(), $mechanic, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mechanic updated.')]);

        return back();
    }

    /**
     * The values the vocabulary screen lets somebody choose between.
     *
     * Sent from the server so that the labels, the ordering and the set itself
     * have one definition. A client that hard-coded them would be a second
     * opinion waiting to go stale.
     *
     * @return array{categories: list<array{value: string, label: string, description: string, position: int}>}
     */
    private function options(): array
    {
        return [
            'categories' => array_map(
                fn (MechanicCategory $category): array => [
                    'value' => $category->value,
                    'label' => $category->label(),
                    'description' => $category->description(),
                    'position' => $category->position(),
                ],
                MechanicCategory::cases(),
            ),
        ];
    }
}
