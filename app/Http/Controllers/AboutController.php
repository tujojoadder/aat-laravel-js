<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\UniqeUser;
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
    $userId = $user->user_id;
    $about = About::where('user_id', $userId)->first();


    /* This is for which is not created by valid gmail */
    if (!$about) {
        // If no data is found, return a response with null values for each key
        return response()->json(['data' => [
            'location' => null,
            'relationship_status' => 'single',
            'work' => null,
            'education' => null
        ]]);
    }

    return response()->json(['data' => $about]);
}
    




}
