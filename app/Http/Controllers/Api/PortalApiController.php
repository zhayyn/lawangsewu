<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Support\ChatAttachment;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class PortalApiController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'data' => LawangsewuPortal::dashboardPayload(),
        ]);
    }

    public function cameras(): JsonResponse
    {
        return response()->json([
            'data' => LawangsewuPortal::cctvPayload(),
        ]);
    }

    public function chat(): JsonResponse
    {
        return response()->json([
            'data' => LawangsewuPortal::chatPayload(),
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'content' => ['nullable', 'string', 'max:1000'],
            'type' => ['nullable', 'in:global,personal,system'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
            'attachment' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'mov'])
                    ->max(2048),
            ],
        ]);

        $attachment = $request->file('attachment');
        $content = trim((string) ($payload['content'] ?? ''));

        if ($content === '' && ! $attachment) {
            throw ValidationException::withMessages([
                'content' => 'Silakan tulis pesan atau pilih gambar/video terlebih dahulu.',
            ]);
        }

        $message = ChatMessage::query()->create([
            'user_id' => $request->user()->id,
            'recipient_id' => $payload['recipient_id'] ?? null,
            'type' => $payload['type'] ?? 'global',
            'content' => $content,
            'metadata' => $attachment ? [
                'attachment' => ChatAttachment::store($attachment),
            ] : null,
        ]);

        $message->load('user');
        ChatMessageSent::dispatch($message);

        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'data' => LawangsewuPortal::transformMessage($message),
        ], 201);
    }
}
