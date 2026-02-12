<?php

namespace App\Http\Controllers;

use App\Models\TeacherRegistrationToken;
use Illuminate\Http\Request;

class TeacherRegistrationController extends Controller
{
    /**
     * Show the teacher registration form.
     *
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $token)
    {
        // Validate the token
        $tokenRecord = TeacherRegistrationToken::validate($token);

        if (!$tokenRecord) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired registration link. Please contact the administrator.');
        }

        // Load the user
        $user = $tokenRecord->user;

        // Check if user has already completed registration
        if ($user->hasCompletedRegistration()) {
            return redirect()->route('login')
                ->with('info', 'You have already completed registration. Please log in.');
        }

        return view('teacher.register', [
            'token' => $token,
            'user' => $user,
        ]);
    }
}
