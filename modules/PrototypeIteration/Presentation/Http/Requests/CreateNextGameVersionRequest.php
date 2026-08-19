<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

/**
 * Cutting the next design version of the game from what a cycle concluded.
 *
 * The optional action section 48 asks for, and the only request in the module whose ability
 * requires the iteration to be *closed* rather than open. A version cut from an open cycle would
 * claim the design had moved on the strength of conclusions nobody had reached yet.
 *
 * The version itself is created by GameDesign — numbered by its allocator, guarded by its rules,
 * announced by its event. This request is the button; the ownership stays where it belongs.
 */
class CreateNextGameVersionRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return $this->inspectIteration('createGameVersion');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Both fields optional, and no version number accepted — GameDesign allocates that, and a
     * caller naming its own would be able to skip to v999 or overwrite the meaning of an earlier
     * one.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->nextGameVersionRules();
    }

    /**
     * The name the designer wants on the new version, if any.
     */
    public function versionName(): ?string
    {
        $name = $this->validated('name');

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }

    /**
     * What the designer wants recorded about the new version, if anything.
     */
    public function versionDescription(): ?string
    {
        $description = $this->validated('description');

        return is_string($description) && trim($description) !== '' ? trim($description) : null;
    }
}
