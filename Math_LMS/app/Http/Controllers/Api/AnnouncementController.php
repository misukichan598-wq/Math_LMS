<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $announcements = Announcement::published()
            ->latest('published_at')
            ->paginate(20);

        $data = $announcements->getCollection()->map(function ($announcement) {
            return [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'content' => $announcement->content,
                'type' => $announcement->type,
                'icon' => $announcement->getTypeIconAttribute(),
                'published_at' => $announcement->published_at,
                'published_at_human' => $announcement->published_at->diffForHumans(),
            ];
        });

        return $this->successResponse([
            'announcements' => $data,
            'pagination' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    public function latest(Request $request)
    {
        $limit = $request->input('limit', 5);

        $announcements = Announcement::published()
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'type' => $announcement->type,
                    'icon' => $announcement->getTypeIconAttribute(),
                    'published_at' => $announcement->published_at,
                    'published_at_human' => $announcement->published_at->diffForHumans(),
                ];
            });

        return $this->successResponse($announcements);
    }

    public function show(Request $request, Announcement $announcement)
    {
        if (!$announcement->isPublished()) {
            return $this->notFoundResponse('Announcement not found');
        }

        return $this->successResponse([
            'id' => $announcement->id,
            'title' => $announcement->title,
            'content' => $announcement->content,
            'type' => $announcement->type,
            'icon' => $announcement->getTypeIconAttribute(),
            'published_at' => $announcement->published_at,
            'published_at_human' => $announcement->published_at->diffForHumans(),
        ]);
    }
}
