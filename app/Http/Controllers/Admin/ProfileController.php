<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index()
    {
        $profile = Auth::user();

        return view('admin.profile.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $user = User::findOrFail(auth()->id());
        $user->name = $request->name;
        $user->email = $request->email;

        if ($user->save()) {
            return redirect()->back()->with('message', 'Profile updated successfully!');
        }
        return redirect()->back()->with('error', 'Profile update fail!');
    }

    public function password()
    {
        return view('admin.profile.change-password');
    }

    public function updatePassword(ChangePasswordRequest $request)
    {
        #Match The Old Password
        if (!Hash::check($request->old_password, auth()->user()->password)) {
            return back()->with("error", "Old Password Doesn't match!");
        }

        #Update the new Password
        $updated = User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        if ($updated) {
            return redirect()->back()->with('message', 'Password changed successfully!');
        } else {
            return redirect()->back()->with('error', 'Profile change fail!');
        }
    }


    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password does not match.'],
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    /**
     * Edit the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function editProfile(Request $request)
    {
        $user = Auth::user();

        // Validate incoming data
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id), // Unique email, excluding current user's email
            ],
        ]);

        // Update user's profile data
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        return response()->json(['message' => 'Profile updated successfully.', 'user' => $user]);
    }

}
