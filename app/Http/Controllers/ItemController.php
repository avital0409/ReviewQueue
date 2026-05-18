<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Models\Item;
use App\Services\HeuristicEngineService;
use Illuminate\Http\Request;

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
        if ($request->has('search') && !empty($request->search)) {
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

        // Calculate dynamic state counters based on active search
        $countsQuery = Item::query();
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $countsQuery->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhere('author_email', 'like', "%{$search}%")
                  ->orWhere('heuristic_flags', 'like', "%{$search}%");
            });
        }

        $allCounts = $countsQuery->selectRaw("status, count(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $counts = [
            'all' => array_sum($allCounts),
            'pending' => $allCounts['pending'] ?? 0,
            'approved' => $allCounts['approved'] ?? 0,
            'rejected' => $allCounts['rejected'] ?? 0,
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

        $analysis = $engine->analyze($validated['content']);

        $item = Item::create([
            'author_email' => $validated['author_email'],
            'content' => $validated['content'],
            'status' => 'pending',
            'risk_score' => $analysis['risk_score'],
            'heuristic_flags' => $analysis['heuristic_flags'],
            'auto_suggestion' => $analysis['auto_suggestion'],
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
        ]);

        $item = Item::findOrFail($id);

        $item->update([
            'status' => $request->status,
            'reviewer_note' => $request->reviewer_note,
            'reviewed_at' => now(),
        ]);

        return response()->json($item);
    }
}
