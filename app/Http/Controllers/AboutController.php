<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class AboutController extends Controller
{

   /* Store or Update About */
   public function storeOrUpdate(Request $request)
{
    $user = auth()->user();
    $userId = $user->user_id;

    // Validate the incoming request data
    $validatedData = $request->validate([
        'location' => 'nullable|string|max:255',
        'relationshipStatus' => 'required|in:single,married,divorced',
        'work' => 'nullable|string|max:255',
        'education' =>'nullable|string|max:255',
    ]);

    // Check if the profile already exists
    $profile = About::where('user_id', $userId)->first();

    if ($profile) {
        // Update the existing record without modifying about_id
        $profile->update([
            'location' => $validatedData['location'],
            'relationship_status' => $validatedData['relationshipStatus'],
            'work' => $validatedData['work'],
            'education' => $validatedData['education']
        ]);
    } else {
        // Create a new record with a new about_id
        About::create([
            'about_id' => Str::uuid(),
            'user_id' => $userId,
            'location' => $validatedData['location'],
            'relationship_status' => $validatedData['relationshipStatus'],
            'work' => $validatedData['work'],
            'education' => $validatedData['education']
        ]);
    }

    return response()->json([
        
        'data'=>'About info updated sucessfully'
    ], 200);
}


 


public function getAbout(Request $request) {
    // Get Auth user
    $user = auth()->user();

    // If no authenticated user is found, return an error response
    if (!$user) {
        return response()->json([
            'error' => 'User not authenticated'
        ], 401);
    }

    // Fetch the user's "About" details from the About table
    $about = About::where('user_id', $user->user_id)->first();

    // If no "About" data is found, return default values for "About" fields and gender, birthdate from users table
    if (!$about) {
        return response()->json([
            'data' => [
                'location' => null,
                'relationship_status' => 'single',  // Default value
                'work' => null,
                'education' => null,
                'gender' => $user->gender,
                'birthdate' => $user->birthdate,
            ]
        ]);
    }

    // Combine "About" data with "User" data (gender and birthdate)
    return response()->json([
        'data' => [
            'location' => $about->location,
            'relationship_status' => $about->relationship_status,
            'work' => $about->work,
            'education' => $about->education,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
        ]
    ]);
}

/* Specific user About data */
public function getUserAbout(Request $request, $id) {
    // Fetch the user's "About" details from the About table
    $about = About::where('user_id', $id)->first();

    // Fetch gender and birthdate from the users table
    $user = User::where('user_id', $id)->select('gender', 'birthdate')->first();

    // If no "About" data is found, return default values for "About" fields and null for gender and birthdate
    if (!$about) {
        return response()->json([
            'data' => [
                'location' => null,
                'relationship_status' => 'single',  // Default value
                'work' => null,
                'education' => null,
                'gender' => $user ? $user->gender : null,
                'birthdate' => $user ? $user->birthdate : null,
            ]
        ]);
    }

    // Combine "About" data with "User" data (gender and birthdate)
    return response()->json([
        'data' => [
            'location' => $about->location,
            'relationship_status' => $about->relationship_status,
            'work' => $about->work,
            'education' => $about->education,
            'gender' => $user ? $user->gender : null,
            'birthdate' => $user ? $user->birthdate : null,
        ]
    ]);
}

    




}
