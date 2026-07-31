<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('studentProfile');

        $stats = [
            'overall_progress' => $user->getOverallProgress(),
            'total_learning_time' => $user->progress()->sum('time_spent'),
            'completed_lessons' => $user->progress()->where('status', 'completed')->distinct('lesson_id')->count('lesson_id'),
            'completed_activities' => $user->activityAttempts()->distinct('activity_id')->count('activity_id'),
        ];

        return view('student.profile.show', compact('user', 'stats'));
    }

    public function edit(Request $request)
    {
        $user = $request->user()->load('studentProfile');

        return view('student.profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'grade_level' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($request->only(['name', 'email']));

        $user->studentProfile->update($request->only([
            'phone',
            'address',
            'date_of_birth',
            'grade_level',
        ]));

        return redirect()->route('student.profile.show')
            ->with('success', 'Profile updated successfully');
    }

    public function updatePicture(Request $request)
    {
        $request->validate([
            'picture' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete old picture if exists
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }

        // Store new picture
        $path = $request->file('picture')->store('profile-pictures', 'public');
        
        $user->update(['profile_picture' => $path]);

        return back()->with('success', 'Profile picture updated successfully');
    }

    public function notifications(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        return view('student.notifications', compact('notifications'));
    }

    public function markNotificationRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read');
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->notifications()->unread()->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read');
    }

    public function addBookmark(Request $request)
    {
        $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'lesson_section_id' => ['nullable', 'exists:lesson_sections,id'],
            'note' => ['nullable', 'string'],
        ]);

        $request->user()->bookmarks()->create($request->all());

        return back()->with('success', 'Bookmark added successfully');
    }

    public function removeBookmark(Request $request, Bookmark $bookmark)
    {
        if ($bookmark->user_id !== $request->user()->id) {
            abort(403);
        }

        $bookmark->delete();

        return back()->with('success', 'Bookmark removed successfully');
    }
}
