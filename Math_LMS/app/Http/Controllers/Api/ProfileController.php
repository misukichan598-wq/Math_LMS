<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request)
    {
        $user = $request->user()->load(['studentProfile', 'hallOfFame']);

        $stats = [
            'overall_progress' => $user->getOverallProgress(),
            'completed_initial_assessment' => $user->hasCompletedInitialAssessment(),
            'completed_final_assessment' => $user->hasCompletedFinalAssessment(),
            'total_learning_time' => $user->progress()->sum('time_spent'),
            'completed_lessons' => $user->progress()->where('status', 'completed')->distinct('lesson_id')->count('lesson_id'),
            'total_activities_completed' => $user->activityAttempts()->distinct('activity_id')->count('activity_id'),
        ];

        return $this->successResponse([
            'user' => $user,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'grade_level' => ['nullable', 'string', 'max:50'],
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        $user->save();

        // Update student profile
        $profile = $user->studentProfile;
        $profile->update($request->only(['phone', 'address', 'date_of_birth', 'grade_level']));

        return $this->successResponse(
            $user->fresh()->load('studentProfile'),
            'Profile updated successfully'
        );
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

        return $this->successResponse([
            'profile_picture_url' => $user->getProfilePictureUrlAttribute(),
        ], 'Profile picture updated successfully');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all tokens except current
        $currentToken = $request->user()->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return $this->successResponse(null, 'Password changed successfully');
    }

    public function bookmarks(Request $request)
    {
        $bookmarks = $request->user()
            ->bookmarks()
            ->with(['lesson', 'lessonSection'])
            ->latest()
            ->get();

        return $this->successResponse($bookmarks);
    }

    public function addBookmark(Request $request)
    {
        $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'lesson_section_id' => ['nullable', 'exists:lesson_sections,id'],
            'note' => ['nullable', 'string'],
        ]);

        $bookmark = $request->user()->bookmarks()->create($request->all());

        return $this->createdResponse(
            $bookmark->load(['lesson', 'lessonSection']),
            'Bookmark added successfully'
        );
    }

    public function removeBookmark(Request $request, Bookmark $bookmark)
    {
        // Check ownership
        if ($bookmark->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $bookmark->delete();

        return $this->successResponse(null, 'Bookmark removed successfully');
    }
}
