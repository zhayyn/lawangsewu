<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'content' => ['required', 'string', 'max:1000'],
            'type' => ['nullable', 'in:global,personal,system'],
            'recipient_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $message = ChatMessage::query()->create([
            'user_id' => $request->user()->id,
            'recipient_id' => $payload['recipient_id'] ?? null,
            'type' => $payload['type'] ?? 'global',
            'content' => $payload['content'],
        ]);

        $message->load('user');
        ChatMessageSent::dispatch($message);

        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'data' => LawangsewuPortal::transformMessage($message),
        ], 201);
    }
}
