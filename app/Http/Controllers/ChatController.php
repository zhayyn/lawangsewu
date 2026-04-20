<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\ChatAttachment;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class ChatController extends Controller
{
    public function index()
    {
        $messages = ChatMessage::with('user:id,name,alias,avatar')
            ->where('type', 'global')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function (ChatMessage $message) {
                return [
                    ...$message->toArray(),
                    'metadata' => [
                        ...($message->metadata ?? []),
                        'attachment' => ChatAttachment::present($message->metadata['attachment'] ?? null, $message),
                    ],
                ];
            })
            ->all();

        $activeUsers = User::where('is_active', true)
            ->select(['id', 'name', 'alias', 'avatar', 'role', 'is_active'])
            ->get();

        return Inertia::render('Lawangsewu/Chat', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'initialMessages' => $messages,
            'activeUsers' => $activeUsers,
        ]);
    }

    public function store(Request $request)
    {
        Log::info('Chat upload request received.', [
            'user_id' => $request->user()?->id,
            'expects_json' => $request->expectsJson(),
            'content_present' => filled($request->input('content')),
            'has_attachment_file' => $request->hasFile('attachment'),
            'attachment_name' => $request->file('attachment')?->getClientOriginalName(),
            'attachment_size' => $request->file('attachment')?->getSize(),
            'content_type' => $request->header('Content-Type'),
        ]);

        $payload = $request->validate([
            'content' => ['nullable', 'string', 'max:1000'],
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

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'content' => $content,
            'type' => 'global',
            'metadata' => $attachment ? [
                'attachment' => ChatAttachment::store($attachment),
            ] : null,
        ]);

        $message->load('user');

        try {
            ChatMessageSent::dispatch($message);
        } catch (Throwable $exception) {
            Log::warning('Chat message broadcast failed; falling back to polling sync.', [
                'message_id' => $message->id,
                'user_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Pesan berhasil dikirim.',
                'data' => [
                    ...LawangsewuPortal::transformMessage($message),
                    'user_id' => $message->user_id,
                    'realName' => $message->user?->name,
                    'time' => optional($message->created_at)->format('H:i'),
                ],
            ], 201);
        }

        return back()->with('status', 'message-sent');
    }

    public function media(Request $request, ChatMessage $message)
    {
        abort_unless($request->user(), 403);

        $attachment = $message->metadata['attachment'] ?? null;
        abort_unless(is_array($attachment) && ! empty($attachment['path']), 404);

        $disk = $attachment['disk'] ?? 'public';
        $path = $attachment['path'];
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response(
            $path,
            $attachment['original_name'] ?? basename($path),
            [
                'Content-Type' => $attachment['mime'] ?? 'application/octet-stream',
                'Cache-Control' => 'private, max-age=86400',
            ],
        );
    }

    public function destroy(Request $request, ChatMessage $message): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $attachment = $message->metadata['attachment'] ?? null;
        if (is_array($attachment) && !empty($attachment['path'])) {
            $disk = $attachment['disk'] ?? 'public';
            if (Storage::disk($disk)->exists($attachment['path'])) {
                Storage::disk($disk)->delete($attachment['path']);
            }
        }

        $message->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Pesan chat internal berhasil dihapus.',
        ]);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);

        $messages = ChatMessage::query()
            ->where(function ($query) {
                $query->where('type', 'global')
                    ->orWhereNull('type')
                    ->orWhere('type', '');
            })
            ->select(['id', 'metadata'])
            ->get();

        foreach ($messages as $message) {
            $attachment = $message->metadata['attachment'] ?? null;
            if (!is_array($attachment) || empty($attachment['path'])) {
                continue;
            }

            $disk = $attachment['disk'] ?? 'public';
            if (Storage::disk($disk)->exists($attachment['path'])) {
                Storage::disk($disk)->delete($attachment['path']);
            }
        }

        $deletedCount = ChatMessage::query()
            ->where(function ($query) {
                $query->where('type', 'global')
                    ->orWhereNull('type')
                    ->orWhere('type', '');
            })
            ->delete();

        return response()->json([
            'ok' => true,
            'deleted' => $deletedCount,
            'message' => 'Semua pesan chat internal berhasil dihapus.',
        ]);
    }
}
