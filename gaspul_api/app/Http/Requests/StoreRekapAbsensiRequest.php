<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRekapAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'bulan'      => ['required', 'date_format:Y-m'],
            'link_drive' => ['required', 'url', 'regex:/drive\.google\.com/'],
        ];
    }

    public function messages(): array
    {
        return [
            'bulan.required'      => 'Bulan wajib dipilih.',
            'bulan.date_format'   => 'Format bulan tidak valid.',
            'link_drive.required' => 'Link Google Drive wajib diisi.',
            'link_drive.url'      => 'Link harus berupa URL yang valid.',
            'link_drive.regex'    => 'Link harus berasal dari Google Drive (drive.google.com).',
        ];
    }
}
