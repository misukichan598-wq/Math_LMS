<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'student_id' => ['nullable', 'string', 'max:50', 'unique:student_profiles'],
            'phone' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'grade_level' => ['nullable', 'string', 'max:50'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        // Create student profile
        StudentProfile::create([
            'user_id' => $user->id,
            'student_id' => $request->student_id,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'grade_level' => $request->grade_level,
        ]);

        event(new Registered($user));

        // Log the registration
        AuditLog::logAction('user_registration', $user);

        Auth::login($user);

        return redirect()->route('student.dashboard');
    }
}
