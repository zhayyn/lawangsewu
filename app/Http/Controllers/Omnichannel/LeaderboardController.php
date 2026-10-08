<?php
namespace App\Http\Controllers\Omnichannel;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Peringkat berdasarkan jumlah percakapan yang di-handle
        $mostConversations = DB::table('omni_conversations')
            ->join('users', 'omni_conversations.handled_by', '=', 'users.id')
            ->select('users.id', 'users.name', DB::raw('count(omni_conversations.id) as total_handled'))
            ->whereNotNull('omni_conversations.handled_by')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_handled')
            ->limit(10)
            ->get();

        // Rata-rata waktu respons pertama berdasarkan data omni_messages aktual
        // Dihitung dari selisih waktu pesan masuk dengan balasan pertama operator
        $responseMetrics = DB::table('omni_messages as first_reply')
            ->join('users', 'first_reply.user_id', '=', 'users.id')
            ->join('omni_messages as customer_msg', function ($join) {
                $join->on('customer_msg.conversation_id', '=', 'first_reply.conversation_id')
                     ->where('customer_msg.direction', 'inbound');
            })
            ->where('first_reply.direction', 'outbound')
            ->whereNotNull('first_reply.user_id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('AVG(TIMESTAMPDIFF(SECOND, customer_msg.created_at, first_reply.created_at)) as avg_response_seconds')
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('avg_response_seconds')
            ->limit(10)
            ->get()
            ->keyBy('id');

        // Gabungkan data respons dengan data percakapan
        $mostResponsive = $mostConversations->map(function ($item) use ($responseMetrics) {
            $metric = $responseMetrics->get($item->id);
            return [
                'name'                 => $item->name,
                'avg_response_time_ms' => $metric
                    ? (int) round($metric->avg_response_seconds * 1000)
                    : null, // null = data belum tersedia, bukan mock
            ];
        })->filter(fn ($item) => $item['avg_response_time_ms'] !== null)
          ->sortBy('avg_response_time_ms')
          ->values();

        return Inertia::render('Lawangsewu/LiveChat/Leaderboard', [
            'mostConversations' => $mostConversations,
            'mostResponsive'    => $mostResponsive,
        ]);
    }
}
