<?php

namespace Modules\GameEconomy\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\GameEconomy\Application\DTOs\BalanceAnalysis;

/**
 * The representation of a complete reading of a configuration.
 *
 * The configuration travels with the findings rather than being fetched again by
 * whoever renders them. An analysis screen shows the warning and the resource it
 * concerns side by side, and a second request to resolve the ids would be a
 * second chance for the two halves to disagree.
 *
 * Errors and warnings are separated as well as being in one list. That is not
 * redundancy — the summary counts them apart because a designer acts on them
 * differently, and the split lists are what the two sections of the analysis
 * screen draw.
 *
 * @mixin BalanceAnalysis
 */
class BalanceAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = $this->summary;

        return [
            'profile' => BalanceProfileResource::make($this->profile),
            'resources' => ResourceTypeResource::collection($this->resources),
            'flows' => ResourceFlowResource::collection($this->flows),
            'actions' => EconomyActionResource::collection($this->actions),
            'variables' => BalanceVariableResource::collection($this->variables),
            'net_flows' => ResourceNetFlowResource::collection(array_values($this->netFlows)),
            'profitability' => ActionProfitabilityResource::collection(array_values($this->profitability)),
            'conversions' => ConversionRatioResource::collection($this->conversions),
            'warnings' => BalanceWarningResource::collection($this->warnings),
            'errors' => BalanceWarningResource::collection($this->errors()),
            'advisories' => BalanceWarningResource::collection($this->advisories()),
            'summary' => [
                'resources' => $summary->resources,
                'flows' => $summary->flows,
                'actions' => $summary->actions,
                'costs' => $summary->costs,
                'rewards' => $summary->rewards,
                'effects' => $summary->effects,
                'variables' => $summary->variables,
                'scenarios' => $summary->scenarios,
                'assumptions' => $summary->assumptions,
                'observations' => $summary->observations,
                'warnings' => $summary->warnings,
                'errors' => $summary->errors,
                'is_empty' => $summary->isEmpty(),
                'has_errors' => $summary->hasErrors(),
                'has_findings' => $summary->hasFindings(),
            ],
        ];
    }
}
