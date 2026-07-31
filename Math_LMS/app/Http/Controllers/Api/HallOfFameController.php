<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HallOfFame;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class HallOfFameController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $rankings = HallOfFame::with('user')
            ->orderBy('rank')
            ->paginate(50);

        $data = $rankings->getCollection()->map(function ($entry) {
            return [
                'rank' => $entry->rank,
                'rank_badge' => $entry->getRankBadgeAttribute(),
                'user' => [
                    'id' => $entry->user->id,
                    'name' => $entry->user->name,
                    'profile_picture' => $entry->user->getProfilePictureUrlAttribute(),
                ],
                'final_score' => $entry->final_score,
                'activity_accuracy' => $entry->activity_accuracy,
                'completion_rate' => $entry->completion_rate,
                'total_learning_time' => $entry->getTotalLearningTimeFormattedAttribute(),
                'improvement_percentage' => $entry->improvement_percentage,
                'updated_at' => $entry->updated_at,
            ];
        });

        return $this->successResponse([
            'rankings' => $data,
            'pagination' => [
                'current_page' => $rankings->currentPage(),
                'last_page' => $rankings->lastPage(),
                'per_page' => $rankings->perPage(),
                'total' => $rankings->total(),
            ],
        ]);
    }

    public function topRanked(Request $request)
    {
        $limit = $request->input('limit', 10);

        $rankings = HallOfFame::with('user')
            ->topRanked($limit)
            ->get()
            ->map(function ($entry) {
                return [
                    'rank' => $entry->rank,
                    'rank_badge' => $entry->getRankBadgeAttribute(),
                    'user' => [
                        'id' => $entry->user->id,
                        'name' => $entry->user->name,
                        'profile_picture' => $entry->user->getProfilePictureUrlAttribute(),
                    ],
                    'final_score' => $entry->final_score,
                    'activity_accuracy' => $entry->activity_accuracy,
                    'completion_rate' => $entry->completion_rate,
                    'improvement_percentage' => $entry->improvement_percentage,
                ];
            });

        return $this->successResponse($rankings);
    }

    public function myRank(Request $request)
    {
        $user = $request->user();

        $myEntry = HallOfFame::where('user_id', $user->id)->first();

        if (!$myEntry) {
            return $this->successResponse([
                'in_hall_of_fame' => false,
                'message' => 'Complete the final assessment to be ranked in the Hall of Fame',
            ]);
        }

        $totalEntries = HallOfFame::count();

        return $this->successResponse([
            'in_hall_of_fame' => true,
            'rank' => $myEntry->rank,
            'rank_badge' => $myEntry->getRankBadgeAttribute(),
            'total_entries' => $totalEntries,
            'final_score' => $myEntry->final_score,
            'activity_accuracy' => $myEntry->activity_accuracy,
            'completion_rate' => $myEntry->completion_rate,
            'total_learning_time' => $myEntry->getTotalLearningTimeFormattedAttribute(),
            'improvement_percentage' => $myEntry->improvement_percentage,
            'percentile' => $totalEntries > 0 ? round((1 - ($myEntry->rank / $totalEntries)) * 100, 2) : 0,
        ]);
    }
}
