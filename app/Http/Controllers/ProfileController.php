<?php

namespace App\Http\Controllers;

use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
/*     View others Profile */

    /* get for specific user posts  */
    public function getSpecificUserPosts(Request $request)
    {
        $user = auth()->user();
        $specificUserId = cleanInput($request->query('id'));
        // Debug the value of $specificUserId
        $perPage = $request->query('per_page', 5);
        $page = $request->query('page', 1);

        $posts = Posts::where('author_id', $specificUserId)
            ->with(['author', 'textPost', 'imagePost'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($posts);
    }
    /* get for others profile all photo */
    public function getSpecificUserPhotos(Request $request)
    {
        // Clean the input and get the user ID from the request query
        $specificUserId = cleanInput($request->query('id'));

        // Set default pagination values, with the option to customize via query parameters
        $perPage = $request->query('per_page', 6); // default to 10 per page
        $page = $request->query('page', 1);

        // Query for the posts with associated image posts for the specific user, paginate the results
        $posts = Posts::where('author_id', $specificUserId)
            ->with('imagePost') // Eager load the image posts relationship
            ->whereHas('imagePost') // Ensure we only get posts with associated image posts
            ->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated result as JSON
        return response()->json($posts);
    }


  /*   Get all user follower for profile */
    public function getAllUserFollower(Request $request)
    {
        $specificUserId = cleanInput($request->query('id'));
    
        $user = User::where('user_id', $specificUserId)->first();
    
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        $perPage = $request->query('per_page', 7);
        $page = $request->query('page', 1);
    
        $followers = $user->followers()->with(['follower' => function ($query) {
            $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
        }])->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json($followers);
    }



  /*   Get all user following  for profile */
    public function getAllUserFollowing(Request $request)
    {
        $specificUserId = cleanInput($request->query('id'));
    
        $user = User::where('user_id', $specificUserId)->first();
    
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        $perPage = $request->query('per_page', 7);
        $page = $request->query('page', 1);
    
        $followings = $user->followings()->with(['following' => function ($query) {
            $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
        }])->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json($followings);
    }
    



/*     View Auth user Profile */
    /* get auth user profile posts  */
public function getAuthUserPosts(Request $request)
{
    $user = auth()->user();
    $userId=$user->user_id;
    // Debug the value of $specificUserId
    $perPage = $request->query('per_page', 5);
    $page = $request->query('page', 1);

    $posts = Posts::where('author_id', $userId)
        ->with(['author', 'textPost', 'imagePost'])
        ->paginate($perPage, ['*'], 'page', $page);

    return response()->json($posts);
}

 /* get for auth user profile all photo */
 public function getAuthUserPhotos(Request $request)
 {

    $user = auth()->user();
    $userId=$user->user_id;

     // Set default pagination values, with the option to customize via query parameters
     $perPage = $request->query('per_page', 6); // default to 10 per page
     $page = $request->query('page', 1);

     // Query for the posts with associated image posts for the specific user, paginate the results
     $posts = Posts::where('author_id', $userId)
         ->with('imagePost') // Eager load the image posts relationship
         ->whereHas('imagePost') // Ensure we only get posts with associated image posts
         ->paginate($perPage, ['*'], 'page', $page);

     // Return the paginated result as JSON
     return response()->json($posts);
 }

/*   Get all followers for the authenticated user on their profile */
public function getAllAuthUserFollower(Request $request)
{
    // Get the authenticated user
    $authUser = auth()->user();

    if (!$authUser) {
        return response()->json(['error' => 'User not authenticated.'], 401);
    }

    // Set pagination variables
    $perPage = $request->query('per_page', 7);
    $page = $request->query('page', 1);

    // Get the authenticated user's followers, including the follower details
    $followers = $authUser->followers()->with(['follower' => function ($query) {
        $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
    }])->paginate($perPage, ['*'], 'page', $page);

    // Return the paginated list of followers
    return response()->json($followers);
}


/*   Get all following for the authenticated user on their profile */
public function getAllAuthUserFollowing(Request $request)
{
    // Get the authenticated user
    $authUser = auth()->user();

    if (!$authUser) {
        return response()->json(['error' => 'User not authenticated.'], 401);
    }

    // Set pagination variables
    $perPage = $request->query('per_page', 7);
    $page = $request->query('page', 1);

    // Get the authenticated user's followings, including the following details
    $followings = $authUser->followings()->with(['following' => function ($query) {
        $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
    }])->paginate($perPage, ['*'], 'page', $page);

    // Return the paginated list of followings
    return response()->json($followings);
}






}
