<?php

namespace Modules\Identity\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Modules\Identity\Domain\Models\User;

/**
 * The representation of the account behind the current request.
 *
 * Adds the few fields an account may see about itself but which are not part
 * of its public representation.
 *
 * @mixin User
 */
class AuthenticatedUserResource extends UserResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
