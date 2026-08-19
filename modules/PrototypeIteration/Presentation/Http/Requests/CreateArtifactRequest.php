<?php

namespace Modules\PrototypeIteration\Presentation\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\PrototypeIteration\Application\DTOs\CreateArtifactData;
use Modules\PrototypeIteration\Application\Validation\IterationValidationRules;

class CreateArtifactRequest extends PrototypeIterationRequest
{
    use IterationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     *
     * Asked of the prototype rather than of the version, because the version has no permissions
     * of its own — and note that a version which has already been built upon still answers yes.
     * The immutability rule freezes what a version *is*; a print sheet filed later documents what
     * it was.
     */
    public function authorize(): Response
    {
        return $this->inspectPrototype('createArtifact');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A size ceiling and nothing else on the file. There is deliberately no mime-type allow-list:
     * a prototype's assets are genuinely open-ended, and a list would refuse real work while
     * providing no safety, since the stored name is generated, the path is built by the storage
     * adapter, and the file is only ever streamed back as an attachment.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => $this->artifactFileRules(),
            'name' => $this->artifactNameRules(),
            'type' => $this->artifactTypeRules(),
        ];
    }

    /**
     * Get the validated input as an application layer DTO.
     *
     * The file is taken from the request's uploaded files rather than from `validated()`, because
     * Laravel's validated array holds the upload for a `file` rule but the DTO wants it typed —
     * and merging it in here keeps that shape decision at the boundary.
     */
    public function toData(): CreateArtifactData
    {
        return CreateArtifactData::fromArray([
            ...$this->validated(),
            'file' => $this->file('file'),
        ]);
    }
}
