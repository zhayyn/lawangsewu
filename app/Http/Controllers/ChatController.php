<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\User;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            ->values();

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
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = ChatMessage::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'type' => 'global',
        ]);

        ChatMessageSent::dispatch($message->load('user'));

        return back();
    }
}
