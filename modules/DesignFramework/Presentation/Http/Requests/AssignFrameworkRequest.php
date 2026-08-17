<?php

namespace Modules\DesignFramework\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\DesignFramework\Application\Validation\FrameworkValidationRules;

/**
 * Point a game at a framework version.
 *
 * The only game-side request that carries an identifier in its body, and the only one that could:
 * a game adopting a framework is choosing from every published edition on the platform, so there is
 * no parent segment to resolve the choice through.
 *
 * The id is checked for shape only. Whether it names a published version inside a published
 * framework is `AssignFrameworkToGame`'s decision, made through `FrameworkModificationGuard` and
 * reported against this same field — because "that version is still a draft" is a statement about
 * the version, not about the syntax of the request.
 */
class AssignFrameworkRequest extends FrameworkRequest
{
    use FrameworkValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Asked against the game, because there is no adoption yet. Open to every member of the
     * workspace: choosing a methodology is design work.
     */
    public function authorize(): Response
    {
        return $this->inspectGame('assign');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'framework_version_id' => ['required', 'string', 'uuid'],
        ];
    }

    /**
     * The edition the caller chose.
     */
    public function frameworkVersionId(): string
    {
        return (string) $this->validated('framework_version_id');
    }
}
