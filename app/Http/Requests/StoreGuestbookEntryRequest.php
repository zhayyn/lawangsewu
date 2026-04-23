<?php

namespace App\Http\Requests;

use App\Rules\SafeHtml;
use App\Rules\ValidPhoneNumber;

/**
 * Store Guestbook Entry Request
 * 
 * Validates guestbook visitor data with XSS protection.
 */
class StoreGuestbookEntryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'visitor_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                new SafeHtml(),
            ],
            'visitor_email' => [
                'required',
                'email:rfc,dns',
                'max:255',
            ],
            'visitor_phone' => [
                'required',
                'string',
                new ValidPhoneNumber(),
            ],
            'visit_purpose' => [
                'required',
                'string',
                'min:5',
                'max:500',
                new SafeHtml(),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
                new SafeHtml(),
            ],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'visitor_name.required' => 'Nama pengunjung harus diisi.',
            'visitor_name.min' => 'Nama minimal 3 karakter.',
            'visitor_email.email' => 'Email tidak valid.',
            'visitor_phone.required' => 'Nomor telepon harus diisi.',
            'visit_purpose.required' => 'Tujuan kunjungan harus diisi.',
            'visit_purpose.min' => 'Tujuan kunjungan minimal 5 karakter.',
        ]);
    }
}
