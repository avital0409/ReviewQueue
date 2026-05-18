<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Get unique users reputation list.
     */
    public function index(Request $request)
    {
        $subquery = Item::select('author_email',
            DB::raw("COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count"),
            DB::raw("COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count"),
            DB::raw("COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked_count"),
            DB::raw("COUNT(*) as total_count")
        )
        ->groupBy('author_email');

        $users = DB::table(DB::raw("({$subquery->toSql()}) as stats"))
            ->mergeBindings($subquery->getQuery())
            ->leftJoin('banned_users', 'stats.author_email', '=', 'banned_users.email')
            ->select(
                'stats.author_email',
                'stats.approved_count',
                'stats.rejected_count',
                'stats.blocked_count',
                'stats.total_count',
                DB::raw("CASE WHEN banned_users.email IS NOT NULL THEN 1 ELSE 0 END as is_banned"),
                'banned_users.banned_at',
                'banned_users.ban_reason'
            );

        if ($search = $request->input('search')) {
            $users->where('stats.author_email', 'like', "%{$search}%");
        }

        $sort = $request->input('sort', 'violations');
        if ($sort === 'violations') {
            $users->orderBy('stats.rejected_count', 'desc');
        } elseif ($sort === 'total') {
            $users->orderBy('stats.total_count', 'desc');
        } else {
            $users->orderBy('stats.author_email', 'asc');
        }

        return response()->json([
            'users' => $users->get()
        ]);
    }

    /**
     * Get chronological review history for a specific user.
     */
    public function history(string $email)
    {
        $history = Item::where('author_email', $email)
            ->orderBy('created_at', 'desc')
            ->get();

        $banRecord = DB::table('banned_users')->where('email', $email)->first();

        return response()->json([
            'email' => $email,
            'is_banned' => !is_null($banRecord),
            'banned_at' => $banRecord ? $banRecord->banned_at : null,
            'ban_reason' => $banRecord ? $banRecord->ban_reason : null,
            'history' => $history
        ]);
    }

    /**
     * Toggle the ban state for a user.
     */
    public function toggleBan(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'action' => 'required|in:ban,unban',
            'reason' => 'nullable|string'
        ]);

        $email = $request->input('email');
        $action = $request->input('action');
        $reason = $request->input('reason', 'Repeated policy violations (exceeded Strike 3 threshold).');

        if ($action === 'ban') {
            DB::table('banned_users')->updateOrInsert(
                ['email' => $email],
                [
                    'banned_at' => now(),
                    'ban_reason' => $reason,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        } else {
            DB::table('banned_users')->where('email', $email)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $action === 'ban' ? 'User permanently banned successfully.' : 'Ban lifted successfully.'
        ]);
    }
}
