<?php

namespace App\Http\Controllers;

use App\Models\FriendList;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
class UserController extends Controller
{


    /*  Retrive user details */

    public function userDetails(Request $request)
    {
        // Get Auth user
        $user = auth()->user();
    
        if ($user) {
            // Extract the necessary fields
            $userDetails = [
                'profile_picture' => $user->profile_picture,
                'user_fname' => $user->user_fname,
                'user_lname' => $user->user_lname,
                'email' => $user->email,
                'identifier' => $user->identifier, // assuming 'identifier' is the user id
            ];
    
            return response()->json(['data' => $userDetails]);
        } else {
            return response()->json(['message' => 'Invalid token or user not authenticated.',401]);
        }
    }
    
    





    public function update_username(Request $request)
    {


        // Use DB transaction
        DB::transaction(function () use ($request) {
            // Validate the incoming request data
            $request->validate([
                'fname' => 'required|string|max:50',
                'fname' => 'required|string|max:50',
            ]);

            // Retrieve the authenticated user
            $user = auth()->user();
            $fname = cleanInput($request->fname);
            $lname = cleanInput($request->lname);
            // Update the 'fname' and 'lname' fields for the user
            $user->update([
                'user_fname' => $fname,
                'user_lname' => $lname,
            ]);
        });

        // Optionally, you can return a response indicating success
        return response()->json(['message' => 'User Name updated successfully']);
    }


    //Update gender
    public function update_gender(Request $request)
    {

        // Sanitize the incoming request data
        $gender = cleanInput($request->gender);

        // Use DB transaction
        DB::transaction(function () use ($request, $gender) {
            // Validate the incoming request data
            $request->validate([
                'gender' => 'required|in:male,female,others', // Validation rules for 'gender'
            ]);

            // Retrieve the authenticated user
            $user = auth()->user();
            // Set the default profile photo based on gender
            $photoPath = '';
            switch ($request->input('gender')) {
                case 'male':
                    $photoPath = 'storage/defaultProfile/male.png';
                    break;
                case 'female':
                    $photoPath = 'storage/defaultProfile/female.png';
                    break;
                default:
                    $photoPath = 'storage/defaultProfile/others.jpeg';
            }

            // Update the 'gender' field for the user
            $user->update([
                'gender' => $gender,
                'profile_picture' => $photoPath
            ]);

            // Delete profile update posts of male users
            if ($gender === 'female' || $gender === 'others') {
                $posts = Posts::where('timeline_ids', $user->user_id)->get();
                foreach ($posts as $post) {
                    $post->delete();
                }
            }
        });

        // Optionally, you can return a response indicating success
        return response()->json(['message' => 'User gender updated successfully']);
    }


    //Update user birthdate
    public function update_birthdate(Request $request)
    {
        $request->validate([
            'birthdate_day' => 'required|integer|between:1,31',
            'birthdate_month' => 'required|integer|between:1,12',
            'birthdate_year' => [
                'required',
                'integer',
                'date_format:Y',
                function ($attribute, $value, $fail) {
                    // Check if the birth year is within a reasonable range
                    $currentYear = now()->year;
                    $minValidYear = $currentYear - 150; // Adjust the range as needed
                    $sevenYearsAgo = $currentYear - 5; // Calculate the year 7 years ago
                    if ($value < $minValidYear || $value > $sevenYearsAgo) {
                        $fail('The ' . $attribute . ' must be a valid year within the past 150 years and at least 7 years ago.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($request, &$message) {
            // Sanitize the incoming request data
            $birthdate_day = cleanInput($request->birthdate_day);
            $birthdate_month = cleanInput($request->birthdate_month);
            $birthdate_year = cleanInput($request->birthdate_year);
            //get user
            $user = auth()->user();
            // Concatenate birthdate components into a single date format
            $birthdate = $birthdate_year . '-' . $birthdate_month . '-' . $birthdate_day;
            //Update user table
            $user->update(['birthdate' => $birthdate]);
        });
        // Return a response indicating success
        return response()->json(['message' => 'User birthdate updated successfully']);
    }
    //Update user birthdate
    public function updateprivacy_setting(Request $request)
    {
        $request->validate([

            'privacy_setting' => 'required|in:public,locked', // Validate day input
        ]);

        DB::transaction(function () use ($request, &$message) {
            // Sanitize the incoming request data
            $privacy_setting = cleanInput($request->privacy_setting);
            //get user
            $user = auth()->user();
            //Update user table
            $user->update(['privacy_setting' => $privacy_setting]);
        });
        // Return a response indicating success
        return response()->json(['message' => 'User privacy_setting updated successfully']);
    }


    //view_profile
    public function view_profile(Request $request, $userId)
    {
        $request->merge(['userId' => $userId]);

        // Validate input parameters
        $this->validate($request, [
            'userId' => 'required|string|max:50'
        ]);

        $authUser = auth()->user();
        $userId = cleanInput($userId);

        // Find the user by user_id
        $viewUser = User::where('user_id', $userId)->first();
        if (!$viewUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if the user's profile is public or locked
        if ($viewUser->privacy_setting === 'locked') {
            // Check if the authenticated user is friends with the view user
            $userFriendlist = FriendList::where('user_id', $viewUser->user_id)->first();
            if ($userFriendlist) {
                $friends = $userFriendlist->user_friends_ids;
                if (!$friends || !Str::contains($friends, $authUser->user_id)) {
                    return response()->json(['error' => 'Profile is locked and you are not friends with this user'], 403);
                }
            } else {
                return response()->json(['error' => 'Profile is locked and you are not friends with this user'], 403);
            }
        }

        // Construct the profile data to return in the response
        $profileData = [
            'id' => $viewUser->user_id,
            'userfname' => $viewUser->userfname,
            'userlname' => $viewUser->userlname,
            'profile_picture' => $viewUser->profile_picture,
            'birthdate' => $viewUser->birthdate,
            'gender' => $viewUser->gender,
            'total_quiz_point' => $viewUser->total_quiz_point,
            // Add more fields as needed
        ];

        return response()->json(['profile' => $profileData]);
    }


/*     Get other user information (name,identifire,profile,cover_photo) */
public function getUserInfo($id) {
  // Sanitize and validate the ID
  if (empty($id) || !Uuid::isValid($id)) {
    Log::warning("Invalid User ID format: {$id}");

    return response()->json([
        'error' => 'Invalid User ID format'
    ], 400); // Bad Request
}


     // Retrieve the user by ID
     $user = User::where('user_id', $id)->first();
// Check if user exists
if (!$user) {
    Log::warning("User not found for ID: {$id}");

    return response()->json([
        'error' => 'User not found'
    ], 404); // Not Found
}



  // Return the user data as JSON
  return response()->json([
    'user' => $user
], 200); // OK




}



}
