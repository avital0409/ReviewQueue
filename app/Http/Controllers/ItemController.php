<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Mail\ItemBannedMail;
use App\Mail\ItemRejectedMail;
use App\Models\Item;
use App\Services\HeuristicEngineService;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ItemController extends Controller
{
    /**
     * Display a listing of the items.
     */
    public function index(Request $request)
    {
        $query = Item::query();

        // 1. Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 2. Filter by search text
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%")
                    ->orWhere('heuristic_flags', 'like', "%{$search}%");
            });
        }

        // 3. Apply sort
        $sort = $request->input('sort', 'newest');
        if ($sort === 'risk_desc') {
            $query->orderBy('risk_score', 'desc');
        } elseif ($sort === 'risk_asc') {
            $query->orderBy('risk_score', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $items = $query->get();

        // Proactively fetch and append unique user rejections (strikes) and ban statuses
        // This is highly performant and avoids N+1 query locks.
        $emails = $items->pluck('author_email')->unique();

        $rejectionsCounts = Item::whereIn('author_email', $emails)
            ->where('status', 'rejected')
            ->select('author_email', DB::raw('count(*) as count'))
            ->groupBy('author_email')
            ->pluck('count', 'author_email');

        $bannedEmails = DB::table('banned_users')
            ->whereIn('email', $emails)
            ->pluck('email')
            ->toArray();

        foreach ($items as $item) {
            $item->author_rejections_count = $rejectionsCounts[$item->author_email] ?? 0;
            $item->author_is_banned = in_array($item->author_email, $bannedEmails) ? 1 : 0;
        }

        // Calculate dynamic state counters based on active search
        $countsQuery = Item::query();
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $countsQuery->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%")
                    ->orWhere('heuristic_flags', 'like', "%{$search}%");
            });
        }

        $allCounts = $countsQuery->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $counts = [
            'all' => array_sum($allCounts),
            'pending' => $allCounts['pending'] ?? 0,
            'approved' => $allCounts['approved'] ?? 0,
            'rejected' => $allCounts['rejected'] ?? 0,
            'blocked' => $allCounts['blocked'] ?? 0,
        ];

        return response()->json([
            'items' => $items,
            'counts' => $counts,
        ]);
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(StoreItemRequest $request, HeuristicEngineService $engine)
    {
        $validated = $request->validated();

        // 1. Gateway Ban Check: Intercept banned submitters at the entry point
        $isBanned = DB::table('banned_users')->where('email', $validated['author_email'])->exists();

        if ($isBanned) {
            $item = Item::create([
                'author_email' => $validated['author_email'],
                'content' => $validated['content'],
                'status' => 'blocked',
                'risk_score' => 100,
                'heuristic_flags' => ['banned_author'],
                'auto_suggestion' => 'reject',
                'reviewer_note' => 'Auto-rejected: Submitter is banned.',
                'reviewed_at' => now(),
            ]);

            return response()->json($item, 201);
        }

        // 2. Standard heuristic scan pipeline
        $analysis = $engine->analyze($validated['content']);

        $rejectionsCount = Item::where('author_email', $validated['author_email'])
            ->where('status', 'rejected')
            ->count();

        $autoSuggestion = $analysis['auto_suggestion'];
        if ($rejectionsCount >= 2 && $autoSuggestion !== 'approve') {
            $autoSuggestion = 'ban';
        }

        $item = Item::create([
            'author_email' => $validated['author_email'],
            'content' => $validated['content'],
            'status' => 'pending',
            'risk_score' => $analysis['risk_score'],
            'heuristic_flags' => $analysis['heuristic_flags'],
            'auto_suggestion' => $autoSuggestion,
        ]);

        return response()->json($item, 201);
    }

    /**
     * Update the specified item review state.
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reviewer_note' => 'nullable|string',
            'send_email' => 'nullable|boolean',
            'email_body' => 'nullable|string',
            'ban_user' => 'nullable|boolean',
            'ban_reason' => 'nullable|string',
        ]);

        $item = Item::findOrFail($id);

        $item->update([
            'status' => $request->status,
            'reviewer_note' => $request->reviewer_note,
            'reviewed_at' => now(),
        ]);

        $blockedCount = 0;

        // 1. Process Permanent Ban Escalation
        if ($request->status === 'rejected' && $request->input('ban_user')) {
            $banReason = $request->input('ban_reason') ?: $request->reviewer_note ?: 'Repeated policy violations (Strike 3 exceeded).';

            DB::table('banned_users')->updateOrInsert(
                ['email' => $item->author_email],
                [
                    'banned_at' => now(),
                    'ban_reason' => $banReason,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($request->input('send_email')) {
                Mail::to($item->author_email)->send(new ItemBannedMail(
                    $item->author_email,
                    $banReason,
                    $item->content
                ));
            }

            // Automatically block the rest of their pending items
            $blockedCount = Item::where('author_email', $item->author_email)
                ->where('status', 'pending')
                ->where('id', '!=', $item->id)
                ->update([
                    'status' => 'blocked',
                    'reviewer_note' => 'Automatically blocked: submitter banned.',
                    'reviewed_at' => now(),
                ]);
        }
        // 2. Process Standard Rejection Notice
        elseif ($request->status === 'rejected' && $request->input('send_email')) {
            $emailBody = $request->input('email_body') ?: $request->reviewer_note ?: 'Content does not meet community guidelines.';
            Mail::to($item->author_email)->send(new ItemRejectedMail($item->content, $emailBody));
        }

        $responseData = $item->toArray();
        $responseData['blocked_count'] = $blockedCount;

        return response()->json($responseData);
    }

    /**
     * Generate a dynamic rejection or ban email draft using local Ollama.
     */
    public function rejectionDraft(Request $request, $id, OllamaService $ollama)
    {
        $item = Item::findOrFail($id);
        $reason = $request->input('reviewer_note');
        $isBan = $request->input('is_ban', false);

        if ($isBan) {
            $draft = $ollama->generateAccountBanEmailDraft(
                $item->author_email,
                $item->content,
                $reason ?: 'Repeated policy violations (Strike 3 exceeded).'
            );
        } else {
            $draft = $ollama->generateRejectionEmailDraft($item->content, $item->author_email, $reason);
        }

        return response()->json([
            'draft' => $draft,
        ]);
    }

    /**
     * Generate a creative dynamic mock item using local Ollama.
     */
    public function generate(OllamaService $ollama)
    {
        @set_time_limit(120);

        try {
            return response()->json($ollama->generateMockItem());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'fallback',
                'message' => 'No active local AI providers detected. Falling back to local static personas.',
            ]);
        }
    }
}
