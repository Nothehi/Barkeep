<?php

namespace Modules\Identity\Presentation\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Identity\Application\DTOs\UpdateProfileData;
use Modules\Identity\Application\Validation\ProfileValidationRules;
use Modules\Identity\Domain\Models\User;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->can('update', $user);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }

    /**
     * Get the validated input as an application layer DTO.
     */
    public function toData(): UpdateProfileData
    {
        return UpdateProfileData::fromArray($this->validated());
    }
}
