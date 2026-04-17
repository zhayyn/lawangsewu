<?php

namespace App\Support;

use App\Models\ChatMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatAttachment
{
    public const MAX_BYTES = 2097152;

    public static function store(UploadedFile $file): array
    {
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $directory = 'chat-media/' . now()->format('Y/m');
        $filename = Str::uuid()->toString() . '.' . Str::lower($extension);
        $path = $file->storeAs($directory, $filename, 'public');

        return [
            'kind' => Str::startsWith($mime, 'video/') ? 'video' : 'image',
            'disk' => 'public',
            'path' => $path,
            'mime' => $mime,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $file->getSize(),
        ];
    }

    public static function present(?array $attachment, ChatMessage $message): ?array
    {
        if (! is_array($attachment) || empty($attachment['path'])) {
            return null;
        }

        return [
            ...$attachment,
            'url' => route('lawangsewu.chat.media', ['message' => $message]),
        ];
    }
}
