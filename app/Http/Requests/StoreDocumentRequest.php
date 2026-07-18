<?php

namespace App\Http\Requests;

use App\Rules\RealMimeType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $extensions = implode(',', config('document-extractor.allowed_extensions', []));
        $maxUploadSize = (int) config('document-extractor.max_upload_size_kb', 25000);

        return [
            'document' => ['required', 'file', "max:{$maxUploadSize}", "extensions:{$extensions}", new RealMimeType],
        ];
    }
}
