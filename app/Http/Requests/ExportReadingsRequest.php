<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-param validation for the readings export endpoint.
 * Accepts format (json|csv), the same tag filters as the index,
 * and an optional anemometer UUID to scope the export.
 */
class ExportReadingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'format' => ['sometimes', 'string', 'in:json,csv'],
            'tags_any' => ['sometimes', 'string'],
            'tags_exact' => ['sometimes', 'string'],
            'anemometer' => ['sometimes', 'uuid', 'exists:anemometers,id'],
        ];
    }
}
