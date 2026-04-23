<?php

namespace App\Http\Requests;

use App\Rules\SafeHtml;

/**
 * Store Chat Message Request
 * 
 * Validates chat message data with XSS protection and message length limits.
 */
class StoreChatMessageRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'message' => [
                'required',
                'string',
                'min:1',
                'max:2000',
                new SafeHtml(),
            ],
            'conversation_id' => [
                'required',
                'integer',
                'exists:chat_messages,conversation_id',
            ],
            'attachment_id' => [
                'nullable',
                'integer',
                'exists:chat_attachments,id',
            ],
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'message.required' => 'Pesan tidak boleh kosong.',
            'message.max' => 'Pesan maksimal 2000 karakter.',
            'conversation_id.required' => 'Conversation ID harus diisi.',
            'conversation_id.exists' => 'Conversation tidak ditemukan.',
        ]);
    }
}
