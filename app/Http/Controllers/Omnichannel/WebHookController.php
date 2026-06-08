<?php
namespace App\Http\Controllers\Omnichannel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebHookController extends Controller
{
    public function receiveWebChat(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'customer_name' => 'nullable|string',
            'message' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $conversation = DB::table('omni_conversations')
                ->where('channel', 'web')
                ->where('channel_identity_id', $validated['session_id'])
                ->first();

            $convId = $conversation ? $conversation->id : (string) Str::uuid();

            if (!$conversation) {
                DB::table('omni_conversations')->insert([
                    'id' => $convId,
                    'channel' => 'web',
                    'channel_identity_id' => $validated['session_id'],
                    'customer_name' => $validated['customer_name'] ?? 'Guest',
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('omni_conversations')
                    ->where('id', $convId)
                    ->update(['updated_at' => now(), 'status' => 'open']);
            }

            DB::table('omni_messages')->insert([
                'id' => (string) Str::uuid(),
                'conversation_id' => $convId,
                'direction' => 'inbound',
                'type' => 'text',
                'content' => $validated['message'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WebChat Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
