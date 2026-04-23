<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base Form Request
 * 
 * Provides common validation and security measures for all form requests.
 * Ensures consistent input validation across the application.
 * 
 * Features:
 * - CSRF protection (automatic from Laravel)
 * - Input sanitization via custom rules
 * - Type checking and max length validation
 * - Authorization checking
 * 
 * Usage:
 * class StoreMessageRequest extends BaseFormRequest {
 *     public function rules(): array {
 *         return [
 *             'message' => ['required', 'string', 'max:1000', new SafeHtml()],
 *             'phone' => ['required', 'string', new ValidPhoneNumber()],
 *         ];
 *     }
 * }
 */
abstract class BaseFormRequest extends FormRequest
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
     */
    abstract public function rules(): array;

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'required' => 'Field :attribute harus diisi.',
            'email' => 'Format :attribute tidak valid.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'confirmed' => ':attribute tidak cocok dengan konfirmasi.',
            'unique' => ':attribute sudah terdaftar.',
        ];
    }

    /**
     * Prepare the data for validation.
     * Trim whitespace from all input.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(
            collect($this->all())->map(function ($value) {
                if (is_string($value)) {
                    return trim($value);
                }
                return $value;
            })->toArray()
        );
    }
}
